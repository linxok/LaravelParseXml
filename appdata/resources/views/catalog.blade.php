<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Каталог товарів</title>
    <style>
        /* мінімальний стиль для колонки фільтрів і каталогу */
        .filters { float: left; width: 20%; }
        .products { float: right; width: 75%; }
        .product { border: 1px solid #ccc; padding: 10px; margin-bottom: 10px; }
    </style>
</head>
<body>
<div class="filters">
    <h3>Фільтри</h3>
    <div id="filters-container"></div>
</div>
<div class="products">
    <h3>Список товарів</h3>
    <div>
        Сортування:
        <select id="sort-select">
            <option value="">за замовчуванням</option>
            <option value="price_asc">ціна ↑</option>
            <option value="price_desc">ціна ↓</option>
        </select>
    </div>
    <div id="products-container"></div>
    <div id="pagination"></div>
</div>

<script>
    const state = {
        filters: {},
        sort_by: null,
        page: 1,
        limit: 10
    };

    document.addEventListener('DOMContentLoaded', () => {
        loadFilters();
        loadProducts();

        document.getElementById('sort-select')
            .addEventListener('change', e => {
                state.sort_by = e.target.value || null;
                state.page = 1;
                loadProducts();
                loadFilters();
            });
    });

    function buildQuery(params) {
        return Object.entries(params)
            .flatMap(([k, v]) => {
                if (typeof v === 'object' && v !== null) {
                    return Object.entries(v).flatMap(([slug, arr]) =>
                        Array.isArray(arr) ? arr.map(val => `filter[${slug}][]=${encodeURIComponent(val)}`) : []
                    );
                }
                return v !== undefined ? [`${encodeURIComponent(k)}=${encodeURIComponent(v)}`] : [];
            })
            .join('&');
    }

    function loadFilters() {
        const qs = buildQuery({
            ...state,
            page: undefined,
            limit: undefined
        });
        fetch(`/api/catalog/filters?` + qs)
            .then(res => res.json())
            .then(data => renderFilters(data));
    }

    function renderFilters(data) {
        const container = document.getElementById('filters-container');
        container.innerHTML = '';
        data.forEach(filter => {
            const block = document.createElement('div');
            const title = document.createElement('strong');
            title.textContent = filter.name;
            block.appendChild(title);
            filter.values.forEach(v => {
                const id = `f_${filter.slug}_${v.value}`;
                const cb = document.createElement('input');
                cb.type = 'checkbox';
                cb.id = id;
                cb.checked = v.active;
                cb.addEventListener('change', () => {
                    if (!state.filters[filter.slug]) state.filters[filter.slug] = [];
                    if (cb.checked) state.filters[filter.slug].push(v.value);
                    else state.filters[filter.slug] = state.filters[filter.slug].filter(x => x !== v.value);
                    state.page = 1;
                    loadFilters();
                    loadProducts();
                });
                const lbl = document.createElement('label');
                lbl.htmlFor = id;
                lbl.textContent = `${v.value} (${v.count})`;
                block.appendChild(cb);
                block.appendChild(lbl);
                block.appendChild(document.createElement('br'));
            });
            container.appendChild(block);
        });
    }

    function loadProducts() {
        const qs = buildQuery(state);
        fetch(`/api/catalog/products?` + qs)
            .then(res => res.json())
            .then(resp => {
                renderProducts(resp.data);
                renderPagination(resp.meta);
            });
    }

    function renderProducts(products) {
        const cont = document.getElementById('products-container');
        cont.innerHTML = '';
        products.forEach(p => {
            const d = document.createElement('div');
            d.className = 'product';
            d.innerHTML = `<h4>${p.name}</h4>
                     <p>Ціна: ${p.price}</p>
                     <p>${p.description || ''}</p>`;
            cont.appendChild(d);
        });
    }

    function renderPagination(meta) {
        const pg = document.getElementById('pagination');
        pg.innerHTML = '';
        for (let i=1; i<=meta.last_page; i++) {
            const btn = document.createElement('button');
            btn.textContent = i;
            if (i === meta.current_page) btn.disabled = true;
            btn.addEventListener('click', () => {
                state.page = i;
                loadProducts();
            });
            pg.appendChild(btn);
        }
    }
</script>
</body>
</html>
