<?php
/**
 * 1C-Bitrix YML Feed Generator for Yandex.Tovary (https://tdpv.ru/ — Промышленная вентиляция)
 *
 * Modes: CLI, Cron, or CAgent (\Tdpv\YandexTovaryExporter::runAgent()).
 * PHP 8.x, UTF-8 without BOM.
 */

declare(strict_types=1);

namespace Tdpv;

use Bitrix\Main\Loader;
use Bitrix\Catalog\ProductTable;
use CCatalogSKU;
use CCatalogProduct;
use CPrice;
use CIBlockElement;
use CIBlockSection;
use CFile;
use DOMDocument;
use Throwable;

if (PHP_SAPI === 'cli' && empty($_SERVER['DOCUMENT_ROOT'])) {
    $_SERVER['DOCUMENT_ROOT'] = dirname(__DIR__);
}

define('NO_KEEP_STATISTIC', true);
define('NOT_CHECK_PERMISSIONS', true);
define('STOP_STATISTICS', true);
define('BX_NO_ACCELERATOR_RESET', true);

$prologPath = rtrim((string)$_SERVER['DOCUMENT_ROOT'], '/\\') . '/bitrix/modules/main/include/prolog_before.php';
if (is_file($prologPath)) {
    require_once $prologPath;
}

class YandexTovaryExporter
{
    private const SITE_URL = 'https://tdpv.ru';
    private const SHOP_NAME = 'ТДПВ — Промышленная вентиляция';
    private const COMPANY_NAME = 'ООО «ТДПВ»';
    private const IBLOCK_ID = 10; // Catalog IBlock ID (adjust per environment)
    private const PRICE_TYPE_ID = 1; // Base price type ID

    private const FEED_REL_PATH = '/feeds/yandex-tovary.xml';
    private const TMP_REL_PATH = '/feeds/yandex-tovary.xml.tmp';
    private const EXCLUDED_CSV_REL_PATH = '/feeds/yandex-tovary-excluded.csv';
    private const REPORT_JSON_REL_PATH = '/feeds/yandex-tovary-report.json';

    private const EXCLUDED_SECTION_KEYWORDS = [
        'монтаж',
        'проектирование',
        'услуги',
        'сервис',
        'пусконаладка',
    ];

    private const SERVICE_QUERY_PARAMS = [
        'clear_cache',
        'bxrand',
        'PHPSESSID',
        'utm_source',
        'utm_medium',
        'utm_campaign',
        'utm_content',
        'utm_term',
        'yclid',
        'gclid',
        'fbclid',
    ];

    private string $docRoot;
    private int $startTs;
    private array $excludedLog = [];
    private array $warnings = [];
    private array $vendorCodeRegistry = [];
    private array $seenOfferIds = [];
    private array $validCategories = [];

    public function __construct(?string $docRoot = null)
    {
        $this->docRoot = rtrim($docRoot ?? (string)$_SERVER['DOCUMENT_ROOT'], '/\\');
        $this->startTs = time();
    }

    public static function runAgent(): string
    {
        $exporter = new self();
        $exporter->export();
        return '\\' . self::class . '::runAgent();';
    }

    public function export(): bool
    {
        if (!Loader::includeModule('iblock') || !Loader::includeModule('catalog')) {
            $this->warnings[] = 'Required Bitrix modules (iblock, catalog) could not be loaded.';
            $this->writeReport(0, 0);
            return false;
        }

        $feedPath = $this->docRoot . self::FEED_REL_PATH;
        $tmpPath = $this->docRoot . self::TMP_REL_PATH;
        $dir = dirname($feedPath);
        if (!is_dir($dir) && !mkdir($dir, 0775, true) && !is_dir($dir)) {
            $this->warnings[] = "Failed to create output directory: {$dir}";
            $this->writeReport(0, 0);
            return false;
        }

        $categoriesXml = $this->buildCategoriesXml();
        $offersXml = $this->buildOffersXml();

        $xml = "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n";
        $xml .= '<yml_catalog date="' . $this->escapeXml(date('c', $this->startTs)) . "\">\n";
        $xml .= "  <shop>\n";
        $xml .= '    <name>' . $this->escapeXml(self::SHOP_NAME) . "</name>\n";
        $xml .= '    <company>' . $this->escapeXml(self::COMPANY_NAME) . "</company>\n";
        $xml .= '    <url>' . $this->escapeXml(self::SITE_URL . '/') . "</url>\n";
        $xml .= "    <currencies>\n";
        $xml .= "      <currency id=\"RUR\" rate=\"1\"/>\n";
        $xml .= "    </currencies>\n";
        $xml .= "    <categories>\n{$categoriesXml}    </categories>\n";
        $xml .= "    <offers>\n{$offersXml}    </offers>\n";
        $xml .= "  </shop>\n";
        $xml .= "</yml_catalog>\n";

        if (file_put_contents($tmpPath, $xml, LOCK_EX) === false) {
            $this->warnings[] = "Failed to write temporary feed file: {$tmpPath}";
            $this->writeReport(count($this->seenOfferIds), count($this->excludedLog));
            return false;
        }

        if (!$this->validateXmlFile($tmpPath)) {
            @unlink($tmpPath);
            $this->warnings[] = 'XML validation failed. Previous valid feed preserved.';
            $this->writeReport(count($this->seenOfferIds), count($this->excludedLog));
            return false;
        }

        if (!@rename($tmpPath, $feedPath)) {
            @unlink($tmpPath);
            $this->warnings[] = 'Atomic rename failed. Previous valid feed preserved.';
            $this->writeReport(count($this->seenOfferIds), count($this->excludedLog));
            return false;
        }

        $this->writeExcludedCsv();
        $this->writeReport(count($this->seenOfferIds), count($this->excludedLog));

        return true;
    }

    private function buildCategoriesXml(): string
    {
        $xml = '';
        $res = CIBlockSection::GetList(
            ['LEFT_MARGIN' => 'ASC'],
            ['IBLOCK_ID' => self::IBLOCK_ID, 'ACTIVE' => 'Y', 'GLOBAL_ACTIVE' => 'Y'],
            false,
            ['ID', 'IBLOCK_SECTION_ID', 'NAME']
        );

        while ($section = $res->Fetch()) {
            $name = $this->cleanText((string)$section['NAME']);
            if ($this->isExcludedServiceText($name)) {
                continue;
            }

            $id = (int)$section['ID'];
            $parentId = (int)$section['IBLOCK_SECTION_ID'];
            $this->validCategories[$id] = true;

            $parentAttr = ($parentId > 0 && isset($this->validCategories[$parentId]))
                ? ' parentId="' . $parentId . '"'
                : '';

            $xml .= '      <category id="' . $id . '"' . $parentAttr . '>'
                . $this->escapeXml($name) . "</category>\n";
        }

        return $xml;
    }

    private function buildOffersXml(): string
    {
        $xml = '';
        $rsElements = CIBlockElement::GetList(
            ['ID' => 'ASC'],
            ['IBLOCK_ID' => self::IBLOCK_ID, 'ACTIVE' => 'Y', 'ACTIVE_DATE' => 'Y'],
            false,
            false,
            [
                'ID', 'IBLOCK_ID', 'NAME', 'CODE', 'DETAIL_PAGE_URL',
                'IBLOCK_SECTION_ID', 'PREVIEW_PICTURE', 'DETAIL_PICTURE',
                'PREVIEW_TEXT', 'DETAIL_TEXT',
            ]
        );

        while ($ob = $rsElements->GetNextElement()) {
            $fields = $ob->GetFields();
            $props = $ob->GetProperties();
            $elementId = (int)$fields['ID'];

            $canonicalSectionId = $this->resolveCanonicalSectionId($elementId, (int)$fields['IBLOCK_SECTION_ID']);
            if ($canonicalSectionId <= 0 || !isset($this->validCategories[$canonicalSectionId])) {
                $this->logExcluded($elementId, $this->extractVendorCode($props), (string)$fields['NAME'], (string)$fields['DETAIL_PAGE_URL'], 'Раздел исключён (услуги/монтаж/неактивен)');
                continue;
            }

            // Check for SKU offers
            $skuList = CCatalogSKU::getOffersList(
                [$elementId],
                self::IBLOCK_ID,
                ['ACTIVE' => 'Y', 'ACTIVE_DATE' => 'Y'],
                ['ID', 'NAME', 'DETAIL_PAGE_URL', 'PREVIEW_PICTURE', 'DETAIL_PICTURE', 'PREVIEW_TEXT', 'DETAIL_TEXT'],
                ['CODE' => ['ARTNUMBER', 'CML2_ARTICLE', 'VENDOR_CODE', 'BRAND', 'MANUFACTURER', 'VOLTAGE', 'POWER', 'AIRFLOW', 'WEIGHT']]
            );

            if (!empty($skuList[$elementId])) {
                // Export each SKU as standalone <offer>, never duplicating empty parent shell
                foreach ($skuList[$elementId] as $skuId => $sku) {
                    $mergedFields = $this->mergeSkuFields($fields, $sku);
                    $mergedProps = array_merge($props, $sku['PROPERTIES'] ?? []);
                    $xml .= $this->buildSingleOfferXml((int)$skuId, $mergedFields, $mergedProps, $canonicalSectionId);
                }
            } else {
                $xml .= $this->buildSingleOfferXml($elementId, $fields, $props, $canonicalSectionId);
            }
        }

        return $xml;
    }

    private function buildSingleOfferXml(int $offerId, array $fields, array $props, int $categoryId): string
    {
        $name = $this->cleanText((string)$fields['NAME']);
        $vendorCode = $this->extractVendorCode($props);
        $rawUrl = (string)$fields['DETAIL_PAGE_URL'];
        $cleanUrl = $this->sanitizeUrl(self::SITE_URL . $rawUrl);

        if (isset($this->seenOfferIds[$offerId])) {
            return '';
        }

        // 1. Price check
        $priceData = $this->resolvePrice($offerId);
        if ($priceData === null || $priceData['price'] <= 0.0) {
            $this->logExcluded($offerId, $vendorCode, $name, $cleanUrl, 'Цена по запросу или нулевая цена');
            return '';
        }

        // 2. Stock / availability check ("Под заказ" / unconfirmed stock excluded)
        if (!$this->isConfirmedInStock($offerId, $props)) {
            $this->logExcluded($offerId, $vendorCode, $name, $cleanUrl, 'Под заказ / не подтверждено наличие');
            return '';
        }

        // 3. Main photo check
        $pictureUrl = $this->resolvePictureUrl($fields);
        if ($pictureUrl === '') {
            $this->logExcluded($offerId, $vendorCode, $name, $cleanUrl, 'Отсутствует основное фото');
            return '';
        }

        // 4. Description check (70..3000 chars after stripping phones/URLs/prices/promos)
        $rawDesc = (string)($fields['DETAIL_TEXT'] ?: $fields['PREVIEW_TEXT']);
        $description = $this->sanitizeDescription($rawDesc);
        $descLen = mb_strlen($description, 'UTF-8');
        if ($descLen < 70 || $descLen > 3000) {
            $this->logExcluded($offerId, $vendorCode, $name, $cleanUrl, "Неподходящее описание ({$descLen} симв., требуется 70–3000)");
            return '';
        }

        // 5. VendorCode handling: omit if empty (never ART_ + ID!), warn on duplicates
        if ($vendorCode === '') {
            $this->warnings[] = "Offer ID {$offerId} ({$name}): empty vendorCode, <vendorCode> omitted.";
        } else {
            if (isset($this->vendorCodeRegistry[$vendorCode])) {
                $this->warnings[] = "Duplicate vendorCode '{$vendorCode}' on offer ID {$offerId} (already seen on ID {$this->vendorCodeRegistry[$vendorCode]}).";
            } else {
                $this->vendorCodeRegistry[$vendorCode] = $offerId;
            }
        }

        $this->seenOfferIds[$offerId] = true;

        $vendor = $this->extractVendor($props);
        $paramsXml = $this->buildParamsXml($props);

        $out = '      <offer id="' . $offerId . "\" available=\"true\">\n";
        $out .= '        <url>' . $this->escapeXml($cleanUrl) . "</url>\n";
        $out .= '        <price>' . number_format($priceData['price'], 2, '.', '') . "</price>\n";
        if ($priceData['oldprice'] !== null) {
            $out .= '        <oldprice>' . number_format($priceData['oldprice'], 2, '.', '') . "</oldprice>\n";
        }
        $out .= "        <currencyId>RUR</currencyId>\n";
        $out .= '        <categoryId>' . $categoryId . "</categoryId>\n";
        $out .= '        <picture>' . $this->escapeXml($pictureUrl) . "</picture>\n";
        $out .= '        <name>' . $this->escapeXml($name) . "</name>\n";
        if ($vendor !== '') {
            $out .= '        <vendor>' . $this->escapeXml($vendor) . "</vendor>\n";
        }
        if ($vendorCode !== '') {
            $out .= '        <vendorCode>' . $this->escapeXml($vendorCode) . "</vendorCode>\n";
        }
        $out .= '        <description>' . $this->escapeXml($description) . "</description>\n";
        $out .= $paramsXml;
        $out .= "      </offer>\n";

        return $out;
    }

    private function resolveCanonicalSectionId(int $elementId, int $primarySectionId): int
    {
        if ($primarySectionId > 0 && isset($this->validCategories[$primarySectionId])) {
            return $primarySectionId;
        }

        $res = CIBlockElement::GetElementGroups($elementId, true, ['ID']);
        while ($group = $res->Fetch()) {
            $gid = (int)$group['ID'];
            if (isset($this->validCategories[$gid])) {
                return $gid;
            }
        }

        return 0;
    }

    private function resolvePrice(int $productId): ?array
    {
        $optimal = CCatalogProduct::GetOptimalPrice($productId, 1, [2], 'N', [], 's1');
        if (!is_array($optimal) || empty($optimal['RESULT_PRICE'])) {
            return null;
        }

        $basePrice = (float)($optimal['RESULT_PRICE']['BASE_PRICE'] ?? 0);
        $discountPrice = (float)($optimal['RESULT_PRICE']['DISCOUNT_PRICE'] ?? $basePrice);

        if ($discountPrice <= 0.0) {
            return null;
        }

        $oldPrice = null;
        if ($basePrice > $discountPrice && $basePrice > 0.0) {
            $discountPct = (($basePrice - $discountPrice) / $basePrice) * 100.0;
            if ($discountPct >= 5.0 && $discountPct <= 75.0) {
                $oldPrice = $basePrice;
            }
        }

        return [
            'price' => $discountPrice,
            'oldprice' => $oldPrice,
        ];
    }

    private function isConfirmedInStock(int $productId, array $props): bool
    {
        $availabilityProp = mb_strtolower(trim((string)($props['AVAILABILITY']['VALUE'] ?? $props['STATUS']['VALUE'] ?? '')), 'UTF-8');
        if ($availabilityProp !== '' && str_contains($availabilityProp, 'под заказ')) {
            return false;
        }

        $product = ProductTable::getRowById($productId);
        if (!$product) {
            return false;
        }

        return ($product['AVAILABLE'] ?? 'N') === 'Y' && (float)($product['QUANTITY'] ?? 0) > 0;
    }

    private function resolvePictureUrl(array $fields): string
    {
        $picId = (int)($fields['DETAIL_PICTURE'] ?: $fields['PREVIEW_PICTURE']);
        if ($picId <= 0) {
            return '';
        }

        $path = CFile::GetPath($picId);
        if (!$path) {
            return '';
        }

        return $this->sanitizeUrl(self::SITE_URL . $path);
    }

    private function buildParamsXml(array $props): string
    {
        $paramMap = [
            'VOLTAGE' => ['name' => 'Напряжение', 'unit' => 'В'],
            'POWER' => ['name' => 'Мощность', 'unit' => 'кВт'],
            'AIRFLOW' => ['name' => 'Производительность', 'unit' => 'м³/ч'],
            'WEIGHT' => ['name' => 'Вес', 'unit' => 'кг'],
        ];

        $xml = '';
        foreach ($paramMap as $code => $meta) {
            $rawVal = $this->cleanText((string)($props[$code]['VALUE'] ?? ''));
            if ($rawVal === '') {
                continue;
            }

            // Strip duplicate unit suffix from value
            $cleanVal = preg_replace('/\s*' . preg_quote($meta['unit'], '/') . '$/iu', '', $rawVal) ?? $rawVal;
            $cleanVal = trim($cleanVal);
            if ($cleanVal === '') {
                continue;
            }

            $xml .= '        <param name="' . $this->escapeXml($meta['name'])
                . '" unit="' . $this->escapeXml($meta['unit']) . '">'
                . $this->escapeXml($cleanVal) . "</param>\n";
        }

        return $xml;
    }

    private function sanitizeDescription(string $raw): string
    {
        $text = html_entity_decode(strip_tags($raw), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        // Strip URLs, phones, prices, promo phrases
        $text = preg_replace('#https?://\S+#iu', '', $text) ?? $text;
        $text = preg_replace('/(\+7|8)[\s\-\(]*\d{3}[\s\-\)]*\d{3}[\s\-]*\d{2}[\s\-]*\d{2}/u', '', $text) ?? $text;
        $text = preg_replace('/\d[\d\s.,]*\s*(руб\.?|₽|р\.)/iu', '', $text) ?? $text;
        $text = preg_replace('/\b(акция|скидка|спецпредложение|звоните|уточняйте)\b[^.!?\n]*[.!?]?/iu', '', $text) ?? $text;

        return $this->cleanText($text);
    }

    private function sanitizeUrl(string $url): string
    {
        $parts = parse_url($url);
        if ($parts === false) {
            return $this->cleanText($url);
        }

        if (!empty($parts['query'])) {
            parse_str($parts['query'], $query);
            foreach (array_keys($query) as $key) {
                if (in_array($key, self::SERVICE_QUERY_PARAMS, true) || str_starts_with((string)$key, 'PAGEN_')) {
                    unset($query[$key]);
                }
            }
            $parts['query'] = http_build_query($query);
        }

        $scheme = $parts['scheme'] ?? 'https';
        $host = $parts['host'] ?? 'tdpv.ru';
        $path = $parts['path'] ?? '/';
        $queryStr = !empty($parts['query']) ? '?' . $parts['query'] : '';

        return $this->cleanText("{$scheme}://{$host}{$path}{$queryStr}");
    }

    private function cleanText(string $text): string
    {
        // Strip ASCII control chars 0..31 except \t (9), \n (10), \r (13)
        $text = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F]/u', '', $text) ?? $text;
        $text = preg_replace('/\s+/u', ' ', $text) ?? $text;
        return trim($text);
    }

    private function escapeXml(string $text): string
    {
        return htmlspecialchars($this->cleanText($text), ENT_XML1 | ENT_QUOTES, 'UTF-8');
    }

    private function isExcludedServiceText(string $text): bool
    {
        $lower = mb_strtolower($text, 'UTF-8');
        foreach (self::EXCLUDED_SECTION_KEYWORDS as $kw) {
            if (str_contains($lower, $kw)) {
                return true;
            }
        }
        return false;
    }

    private function extractVendorCode(array $props): string
    {
        foreach (['ARTNUMBER', 'CML2_ARTICLE', 'VENDOR_CODE'] as $code) {
            $val = $this->cleanText((string)($props[$code]['VALUE'] ?? ''));
            if ($val !== '') {
                return $val;
            }
        }
        return '';
    }

    private function extractVendor(array $props): string
    {
        foreach (['BRAND', 'MANUFACTURER', 'VENDOR'] as $code) {
            $val = $this->cleanText((string)($props[$code]['VALUE'] ?? ''));
            if ($val !== '') {
                return $val;
            }
        }
        return '';
    }

    private function mergeSkuFields(array $parent, array $sku): array
    {
        return [
            'ID' => $sku['ID'],
            'NAME' => !empty($sku['NAME']) ? $sku['NAME'] : $parent['NAME'],
            'DETAIL_PAGE_URL' => !empty($sku['DETAIL_PAGE_URL']) ? $sku['DETAIL_PAGE_URL'] : $parent['DETAIL_PAGE_URL'],
            'PREVIEW_PICTURE' => !empty($sku['PREVIEW_PICTURE']) ? $sku['PREVIEW_PICTURE'] : $parent['PREVIEW_PICTURE'],
            'DETAIL_PICTURE' => !empty($sku['DETAIL_PICTURE']) ? $sku['DETAIL_PICTURE'] : $parent['DETAIL_PICTURE'],
            'PREVIEW_TEXT' => !empty($sku['PREVIEW_TEXT']) ? $sku['PREVIEW_TEXT'] : $parent['PREVIEW_TEXT'],
            'DETAIL_TEXT' => !empty($sku['DETAIL_TEXT']) ? $sku['DETAIL_TEXT'] : $parent['DETAIL_TEXT'],
        ];
    }

    private function validateXmlFile(string $filePath): bool
    {
        $dom = new DOMDocument('1.0', 'UTF-8');
        $prevUseErrors = libxml_use_internal_errors(true);
        $loaded = $dom->load($filePath, LIBXML_NONET);
        libxml_clear_errors();
        libxml_use_internal_errors($prevUseErrors);

        if (!$loaded) {
            return false;
        }

        $categoryIds = [];
        foreach ($dom->getElementsByTagName('category') as $cat) {
            $cid = $cat->getAttribute('id');
            if ($cid === '' || isset($categoryIds[$cid])) {
                return false;
            }
            $categoryIds[$cid] = true;
        }

        $offerIds = [];
        foreach ($dom->getElementsByTagName('offer') as $offer) {
            $oid = $offer->getAttribute('id');
            if ($oid === '' || isset($offerIds[$oid])) {
                return false;
            }
            $offerIds[$oid] = true;

            $catNode = $offer->getElementsByTagName('categoryId')->item(0);
            if (!$catNode || !isset($categoryIds[trim($catNode->textContent)])) {
                return false;
            }
        }

        return true;
    }

    private function logExcluded(int $id, string $vendorCode, string $name, string $url, string $reason): void
    {
        $this->excludedLog[] = [
            'id' => $id,
            'vendorCode' => $vendorCode,
            'name' => $name,
            'url' => $url,
            'reason' => $reason,
        ];
    }

    private function writeExcludedCsv(): void
    {
        $csvPath = $this->docRoot . self::EXCLUDED_CSV_REL_PATH;
        $fp = fopen($csvPath, 'wb');
        if ($fp === false) {
            return;
        }

        fputcsv($fp, ['ID', 'Артикул', 'Название', 'URL', 'Причина исключения'], ';');
        foreach ($this->excludedLog as $row) {
            fputcsv($fp, [$row['id'], $row['vendorCode'], $row['name'], $row['url'], $row['reason']], ';');
        }
        fclose($fp);
    }

    private function writeReport(int $exportedCount, int $excludedCount): void
    {
        $reportPath = $this->docRoot . self::REPORT_JSON_REL_PATH;
        $payload = [
            'generated_at' => date('c', $this->startTs),
            'exported_offers' => $exportedCount,
            'excluded_offers' => $excludedCount,
            'warnings' => $this->warnings,
            'excluded' => $this->excludedLog,
        ];

        file_put_contents(
            $reportPath,
            json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT),
            LOCK_EX
        );
    }
}

if (PHP_SAPI === 'cli' && realpath((string)($_SERVER['SCRIPT_FILENAME'] ?? '')) === __FILE__) {
    try {
        $exporter = new YandexTovaryExporter();
        exit($exporter->export() ? 0 : 1);
    } catch (Throwable $e) {
        fwrite(STDERR, 'Export failed: ' . $e->getMessage() . PHP_EOL);
        exit(1);
    }
}
