(() => {
  'use strict';

  const CATEGORIES = [
    { id: 100, name: 'Вентиляция и климат' },
    { id: 101, parentId: 100, name: 'Канальные вентиляторы' },
    { id: 102, parentId: 100, name: 'Воздушно-тепловые завесы' },
    { id: 204, parentId: 100, name: 'Автоматика и регуляторы скорости' },
    { id: 205, parentId: 100, name: 'Частотные преобразователи' },
    { id: 103, parentId: 100, name: 'Осевые промышленные вентиляторы' }
  ];

  const CATALOG_ITEMS = [
    {
      id: '14820',
      sku: 'MTY-1.5',
      name: 'Регулятор скорости вращения вентилятора MTY (СРМ) – 1,5',
      vendor: 'Вентс',
      price: 4850,
      priceOnRequest: true,
      inStock: true,
      hasPhoto: true,
      descOk: true,
      catId: 204,
      url: 'https://tdpv.ru/catalog/avtomatika/regulyator-mty-srm-1-5/',
      picture: 'https://tdpv.ru/upload/iblock/mty-1-5.jpg',
      description: 'Симисторный регулятор скорости вращения однофазных вентиляторов до 1.5 А.',
      params: [
        { name: 'Ток', unit: 'А', val: '1.5' },
        { name: 'Напряжение', unit: 'В', val: '220' }
      ]
    },
    {
      id: '19304',
      sku: 'ESQ-A500-021-0.75K',
      name: 'Однофазный преобразователь частоты ESQ-A500-021-0.75K, 220 В',
      vendor: 'ESQ',
      price: 11900,
      priceOnRequest: true,
      inStock: true,
      hasPhoto: true,
      descOk: true,
      catId: 205,
      url: 'https://tdpv.ru/catalog/chastotnye-preobrazovateli/esq-a500-021-0-75k/',
      picture: 'https://tdpv.ru/upload/iblock/esq-a500-021-0-75k.jpg',
      description: 'Частотный преобразователь ESQ-A500 для управления асинхронными электродвигателями 0.75 кВт.',
      params: [
        { name: 'Напряжение', unit: 'В', val: '220' },
        { name: 'Мощность двигателя', unit: 'кВт', val: '0.75' }
      ]
    },
    {
      id: '22110',
      sku: 'BHC-D20-T18-MS',
      name: 'Воздушно-тепловая завеса Ballu BHC-D20-T18-MS (с пультом BRC-E)',
      vendor: 'Ballu',
      price: 54900,
      oldPrice: 61000,
      priceOnRequest: false,
      inStock: true,
      hasPhoto: true,
      descOk: true,
      catId: 102,
      url: 'https://tdpv.ru/catalog/teplovye-zavesy/ballu-bhc-d20-t18-ms/',
      picture: 'https://tdpv.ru/upload/iblock/ballu-bhc-d20-t18-ms.jpg',
      description: 'Промышленная электрическая воздушно-тепловая завеса Ballu с выносным пультом BRC-E.',
      params: [
        { name: 'Метод нагрева', unit: '', val: 'Электрический' },
        { name: 'Длина корпуса', unit: 'мм', val: '1500' }
      ]
    },
    {
      id: '22111',
      sku: 'BHC-D20-T18-MS',
      name: 'Воздушно-тепловая завеса Ballu BHC-D20-T18-MS (базовая комплектация)',
      vendor: 'Ballu',
      price: 51200,
      priceOnRequest: false,
      inStock: true,
      hasPhoto: true,
      descOk: true,
      catId: 102,
      url: 'https://tdpv.ru/catalog/teplovye-zavesy/ballu-bhc-d20-t18-ms-base/',
      picture: 'https://tdpv.ru/upload/iblock/ballu-bhc-d20-t18-ms-base.jpg',
      description: 'Промышленная электрическая воздушно-тепловая завеса Ballu в базовой комплектации.',
      params: [
        { name: 'Метод нагрева', unit: '', val: 'Электрический' },
        { name: 'Длина корпуса', unit: 'мм', val: '1500' }
      ]
    },
    {
      id: '30501',
      sku: '',
      name: 'Канальный круглый вентилятор ВКК-160 М',
      vendor: 'Тепломаш',
      price: 8400,
      priceOnRequest: false,
      inStock: true,
      hasPhoto: true,
      descOk: true,
      catId: 101,
      url: 'https://tdpv.ru/catalog/ventilyatory-kanalnye/vkk-160-m/',
      picture: 'https://tdpv.ru/upload/iblock/vkk-160-m.jpg',
      description: 'Круглый канальный вентилятор ВКК-160 М для приточно-вытяжных систем вентиляции.',
      params: [
        { name: 'Диаметр подключения', unit: 'мм', val: '160' }
      ]
    },
    {
      id: '31090-v1',
      sku: 'KEV-6P1221E-SS',
      name: 'Тепловая завеса Тепломаш КЭВ-6П1221Е (нерж. корпус)',
      vendor: 'Тепломаш',
      price: 38900,
      priceOnRequest: false,
      inStock: true,
      hasPhoto: true,
      descOk: true,
      catId: 102,
      url: 'https://tdpv.ru/catalog/teplovye-zavesy/kev-6p1221e-nerzh/',
      picture: 'https://tdpv.ru/upload/iblock/kev-6p1221e-ss.jpg',
      description: 'Тепловая завеса КЭВ-6П1221Е в корпусе из нержавеющей стали (самостоятельный SKU).',
      params: [
        { name: 'Материал корпуса', unit: '', val: 'Нержавеющая сталь' },
        { name: 'Мощность', unit: 'кВт', val: '6' }
      ]
    },
    {
      id: '31090-v2',
      sku: 'KEV-6P1221E-RAL9016',
      name: 'Тепловая завеса Тепломаш КЭВ-6П1221Е (белый RAL 9016)',
      vendor: 'Тепломаш',
      price: 34500,
      priceOnRequest: false,
      inStock: true,
      hasPhoto: true,
      descOk: true,
      catId: 102,
      url: 'https://tdpv.ru/catalog/teplovye-zavesy/kev-6p1221e-ral9016/',
      picture: 'https://tdpv.ru/upload/iblock/kev-6p1221e-white.jpg',
      description: 'Тепловая завеса КЭВ-6П1221Е в белом корпусе RAL 9016 (самостоятельный SKU).',
      params: [
        { name: 'Цвет корпуса', unit: '', val: 'Белый RAL 9016' },
        { name: 'Мощность', unit: 'кВт', val: '6' }
      ]
    },
    {
      id: '40112',
      sku: 'SRV-MONTAZH',
      name: 'Услуга шеф-монтажа и пусконаладки вентиляции',
      vendor: 'ТД ПВ',
      price: 25000,
      priceOnRequest: false,
      inStock: true,
      hasPhoto: true,
      descOk: true,
      isService: true,
      catId: 100,
      url: 'https://tdpv.ru/services/shef-montazh-ventilyacii/',
      picture: 'https://tdpv.ru/upload/iblock/service-montazh.jpg',
      description: 'Шеф-монтаж и пусконаладочные работы систем промышленной вентиляции.',
      params: []
    },
    {
      id: '40899',
      sku: 'VO-06-300-5',
      name: 'Промышленный осевой вентилятор ВО-06-300 №5',
      vendor: 'Вентс',
      price: 29400,
      priceOnRequest: false,
      inStock: false,
      hasPhoto: true,
      descOk: true,
      catId: 103,
      url: 'https://tdpv.ru/catalog/ventilyatory-osevye/vo-06-300-5/',
      picture: 'https://tdpv.ru/upload/iblock/vo-06-300-5.jpg',
      description: 'Промышленный осевой вентилятор низкого давления ВО-06-300 №5.',
      params: [
        { name: 'Типоразмер', unit: '', val: '№5' }
      ]
    }
  ];

  function escapeXml(str) {
    return String(str ?? '')
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;')
      .replace(/'/g, '&apos;');
  }

  function escapeHtml(str) {
    return String(str ?? '')
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;');
  }

  function formatPrice(val) {
    return Number(val || 0).toLocaleString('ru-RU') + ' ₽';
  }

  function getFormattedYmlDate() {
    const now = new Date();
    const pad = (n) => String(n).padStart(2, '0');
    return `${now.getFullYear()}-${pad(now.getMonth() + 1)}-${pad(now.getDate())}T${pad(now.getHours())}:${pad(now.getMinutes())}:${pad(now.getSeconds())}+03:00`;
  }

  function evaluateCatalog() {
    const included = [];
    const excluded = [];
    const warnings = [];

    for (const item of CATALOG_ITEMS) {
      if (item.isService) {
        excluded.push({ item, reason: 'Страница услуги монтажа/проектирования' });
        continue;
      }
      if (item.priceOnRequest) {
        excluded.push({ item, reason: 'Цена по запросу в публичной карточке' });
        continue;
      }
      if (!item.inStock) {
        excluded.push({ item, reason: 'Нет подтверждённого наличия / Под заказ' });
        continue;
      }
      if (!item.price || item.price <= 0) {
        excluded.push({ item, reason: 'Некорректная или нулевая цена' });
        continue;
      }
      if (!item.hasPhoto) {
        excluded.push({ item, reason: 'Отсутствует изображение товара' });
        continue;
      }
      if (!item.descOk) {
        excluded.push({ item, reason: 'Не заполнено описание товара' });
        continue;
      }

      included.push(item);
    }

    // Check duplicate SKUs and missing vendorCode among included offers
    const skuMap = new Map();
    for (const item of included) {
      const sku = (item.sku || '').trim();
      if (!sku) {
        warnings.push({
          id: item.id,
          name: item.name,
          code: 'MISSING_VENDOR_CODE',
          message: 'Не заполнен артикул производителя (выгружен без <vendorCode>)'
        });
      } else {
        if (!skuMap.has(sku)) {
          skuMap.set(sku, []);
        }
        skuMap.get(sku).push(item);
      }
    }

    for (const [sku, group] of skuMap.entries()) {
      if (group.length > 1) {
        for (const item of group) {
          const otherIds = group
            .filter((g) => g.id !== item.id)
            .map((g) => g.id)
            .join(', ');
          warnings.push({
            id: item.id,
            name: item.name,
            code: 'DUPLICATE_SKU',
            message: `Совпадение артикула ${sku} с ID ${otherIds} — требуется проверка исполнения перед объединением`
          });
        }
      }
    }

    return { included, excluded, warnings };
  }

  function buildYmlXml(included) {
    const lines = [];
    lines.push('<?xml version="1.0" encoding="UTF-8"?>');
    lines.push(`<yml_catalog date="${escapeXml(getFormattedYmlDate())}">`);
    lines.push('  <shop>');
    lines.push('    <name>ТД ПВ — Промышленная вентиляция</name>');
    lines.push('    <company>ООО «ТД ПВ»</company>');
    lines.push('    <url>https://tdpv.ru/</url>');
    lines.push('    <currencies>');
    lines.push('      <currency id="RUR" rate="1"/>');
    lines.push('    </currencies>');
    lines.push('    <categories>');
    for (const cat of CATEGORIES) {
      const parentAttr = cat.parentId ? ` parentId="${escapeXml(cat.parentId)}"` : '';
      lines.push(`      <category id="${escapeXml(cat.id)}"${parentAttr}>${escapeXml(cat.name)}</category>`);
    }
    lines.push('    </categories>');
    lines.push('    <offers>');

    for (const item of included) {
      lines.push(`      <offer id="${escapeXml(item.id)}" available="true">`);
      lines.push(`        <url>${escapeXml(item.url)}</url>`);
      lines.push(`        <price>${escapeXml(item.price)}</price>`);
      if (item.oldPrice && item.oldPrice > item.price) {
        lines.push(`        <oldprice>${escapeXml(item.oldPrice)}</oldprice>`);
      }
      lines.push('        <currencyId>RUR</currencyId>');
      lines.push(`        <categoryId>${escapeXml(item.catId)}</categoryId>`);
      if (item.picture) {
        lines.push(`        <picture>${escapeXml(item.picture)}</picture>`);
      }
      lines.push(`        <name>${escapeXml(item.name)}</name>`);
      if (item.vendor) {
        lines.push(`        <vendor>${escapeXml(item.vendor)}</vendor>`);
      }
      const sku = (item.sku || '').trim();
      if (sku) {
        lines.push(`        <vendorCode>${escapeXml(sku)}</vendorCode>`);
      }
      if (item.description) {
        lines.push(`        <description>${escapeXml(item.description)}</description>`);
      }
      if (Array.isArray(item.params)) {
        for (const p of item.params) {
          const unitAttr = p.unit ? ` unit="${escapeXml(p.unit)}"` : '';
          lines.push(`        <param name="${escapeXml(p.name)}"${unitAttr}>${escapeXml(p.val)}</param>`);
        }
      }
      lines.push('      </offer>');
    }

    lines.push('    </offers>');
    lines.push('  </shop>');
    lines.push('</yml_catalog>');
    return lines.join('\n');
  }

  function renderCatalogSim(includedIds) {
    const tbody = document.getElementById('catalogSimBody');
    if (!tbody) return;

    tbody.innerHTML = CATALOG_ITEMS.map((item) => {
      const isIncluded = includedIds.has(item.id);
      const statusBadge = isIncluded
        ? '<span class="badge badge-ok">В фиде</span>'
        : '<span class="badge badge-excl">Исключён</span>';

      const priceBtnText = item.priceOnRequest ? 'Цена по запросу' : 'Открыта цена';
      const priceBtnClass = item.priceOnRequest ? 'btn-sm btn-warn toggle-price-req' : 'btn-sm btn-ok toggle-price-req';

      const stockBtnText = item.inStock ? 'В наличии' : 'Под заказ';
      const stockBtnClass = item.inStock ? 'btn-sm btn-ok toggle-stock' : 'btn-sm btn-excl toggle-stock';

      const skuDisplay = item.sku ? escapeHtml(item.sku) : '<span class="muted">— (нет)</span>';

      return `
        <tr data-id="${escapeHtml(item.id)}">
          <td><code>${escapeHtml(item.id)}</code></td>
          <td>${skuDisplay}</td>
          <td>
            <div class="item-name">${escapeHtml(item.name)}</div>
            <div class="item-meta">${escapeHtml(item.vendor)}${item.isService ? ' · Услуга' : ''}</div>
          </td>
          <td>${formatPrice(item.price)}</td>
          <td>
            <button type="button" class="${priceBtnClass}" data-id="${escapeHtml(item.id)}"${item.isService ? ' disabled' : ''}>
              ${priceBtnText}
            </button>
          </td>
          <td>
            <button type="button" class="${stockBtnClass}" data-id="${escapeHtml(item.id)}"${item.isService ? ' disabled' : ''}>
              ${stockBtnText}
            </button>
          </td>
          <td>${statusBadge}</td>
        </tr>
      `;
    }).join('');
  }

  function renderExcludedTable(excluded) {
    const tbody = document.getElementById('excludedTableBody');
    if (!tbody) return;

    if (excluded.length === 0) {
      tbody.innerHTML = '<tr><td colspan="4" class="empty-cell">Исключённых позиций нет</td></tr>';
      return;
    }

    tbody.innerHTML = excluded.map(({ item, reason }) => `
      <tr>
        <td><code>${escapeHtml(item.id)}</code></td>
        <td>${item.sku ? escapeHtml(item.sku) : '<span class="muted">—</span>'}</td>
        <td>${escapeHtml(item.name)}</td>
        <td><span class="badge badge-excl">${escapeHtml(reason)}</span></td>
      </tr>
    `).join('');
  }

  function renderWarningsTable(warnings) {
    const tbody = document.getElementById('warningsTableBody');
    if (!tbody) return;

    if (warnings.length === 0) {
      tbody.innerHTML = '<tr><td colspan="4" class="empty-cell">Предупреждений нет</td></tr>';
      return;
    }

    tbody.innerHTML = warnings.map((w) => `
      <tr>
        <td><code>${escapeHtml(w.id)}</code></td>
        <td>${escapeHtml(w.name)}</td>
        <td><code>${escapeHtml(w.code)}</code></td>
        <td><span class="badge badge-warn">${escapeHtml(w.message)}</span></td>
      </tr>
    `).join('');
  }

  function showToast(message) {
    const toastBox = document.getElementById('toastBox');
    if (!toastBox) return;
    toastBox.textContent = message;
    toastBox.classList.add('visible');
    window.clearTimeout(showToast._timer);
    showToast._timer = window.setTimeout(() => {
      toastBox.classList.remove('visible');
    }, 2400);
  }

  function recomputeAll() {
    const { included, excluded, warnings } = evaluateCatalog();
    const includedIds = new Set(included.map((i) => i.id));

    const kpiChecked = document.getElementById('kpiChecked');
    const kpiIncluded = document.getElementById('kpiIncluded');
    const kpiExcluded = document.getElementById('kpiExcluded');
    const kpiWarnings = document.getElementById('kpiWarnings');

    if (kpiChecked) kpiChecked.textContent = String(CATALOG_ITEMS.length);
    if (kpiIncluded) kpiIncluded.textContent = String(included.length);
    if (kpiExcluded) kpiExcluded.textContent = String(excluded.length);
    if (kpiWarnings) kpiWarnings.textContent = String(warnings.length);

    renderCatalogSim(includedIds);
    renderExcludedTable(excluded);
    renderWarningsTable(warnings);

    const xmlCode = document.getElementById('xmlPreviewCode');
    if (xmlCode) {
      xmlCode.textContent = buildYmlXml(included);
    }
  }

  function initEvents() {
    const simBody = document.getElementById('catalogSimBody');
    if (simBody) {
      simBody.addEventListener('click', (e) => {
        const priceBtn = e.target.closest('.toggle-price-req');
        if (priceBtn) {
          const item = CATALOG_ITEMS.find((i) => i.id === priceBtn.dataset.id);
          if (item && !item.isService) {
            item.priceOnRequest = !item.priceOnRequest;
            recomputeAll();
          }
          return;
        }

        const stockBtn = e.target.closest('.toggle-stock');
        if (stockBtn) {
          const item = CATALOG_ITEMS.find((i) => i.id === stockBtn.dataset.id);
          if (item && !item.isService) {
            item.inStock = !item.inStock;
            recomputeAll();
          }
        }
      });
    }

    const tabButtons = document.querySelectorAll('.tab-btn[data-tab]');
    tabButtons.forEach((btn) => {
      btn.addEventListener('click', () => {
        const targetTab = btn.dataset.tab;
        tabButtons.forEach((b) => b.classList.toggle('active', b === btn));
        document.querySelectorAll('[id^="panel-"]').forEach((panel) => {
          panel.classList.toggle('active', panel.id === `panel-${targetTab}`);
        });
      });
    });

    const btnCopyXml = document.getElementById('btnCopyXml');
    if (btnCopyXml) {
      btnCopyXml.addEventListener('click', async () => {
        const xmlCode = document.getElementById('xmlPreviewCode');
        const text = xmlCode ? xmlCode.textContent : '';
        if (!text) return;

        try {
          if (navigator.clipboard && navigator.clipboard.writeText) {
            await navigator.clipboard.writeText(text);
          } else {
            const ta = document.createElement('textarea');
            ta.value = text;
            ta.style.position = 'fixed';
            ta.style.opacity = '0';
            document.body.appendChild(ta);
            ta.select();
            document.execCommand('copy');
            document.body.removeChild(ta);
          }
          showToast('YML-фид скопирован в буфер обмена');
        } catch {
          showToast('Не удалось скопировать XML');
        }
      });
    }
  }

  document.addEventListener('DOMContentLoaded', () => {
    initEvents();
    recomputeAll();
  });
})();
