/**
 * CATALOG.JS — Xử lý AJAX cho trang Danh mục & Chi tiết sản phẩm (Mục 7 - GĐ3 & TC07, TC08, TC09)
 * 1. Lọc kết hợp danh mục, nhiều thương hiệu (checkbox), khoảng giá, tình trạng tồn kho và sắp xếp
 * 2. Debounce 300ms cho ô tìm kiếm và ô khoảng giá
 * 3. Hiển thị và xử lý gỡ từng Chip bộ lọc hoặc "Xóa tất cả"
 * 4. Đồng bộ URL bằng history.pushState và khôi phục trạng thái form + lưới khi bấm Back/Forward (window.onpopstate)
 */

document.addEventListener('DOMContentLoaded', function () {
    'use strict';

    const filterForm = document.getElementById('catalog-filter-form');
    const gridRegion = document.getElementById('catalog-grid-region');
    const totalCountEl = document.getElementById('catalog-total-count');
    const sortSelect = document.getElementById('catalog-sort-select');
    const sortHidden = document.getElementById('filter-sort-hidden');
    const pageHidden = document.getElementById('filter-page-hidden');

    let debounceTimer = null;

    /**
     * Thu thập tham số lọc sạch từ form (bỏ các trường rỗng để URL gọn đẹp)
     */
    function buildCleanQueryParams() {
        if (!filterForm) return new URLSearchParams();
        const fd = new FormData(filterForm);
        const params = new URLSearchParams();

        const q = (fd.get('q') || '').toString().trim();
        if (q !== '') params.set('q', q);

        const categoryId = (fd.get('category_id') || '').toString().trim();
        if (categoryId !== '') params.set('category_id', categoryId);

        const brands = fd.getAll('brands[]').map(v => v.toString().trim()).filter(Boolean);
        brands.forEach(b => params.append('brands[]', b));

        const minPrice = (fd.get('min_price') || '').toString().trim();
        if (minPrice !== '') params.set('min_price', minPrice);

        const maxPrice = (fd.get('max_price') || '').toString().trim();
        if (maxPrice !== '') params.set('max_price', maxPrice);

        const stock = (fd.get('stock') || '').toString().trim();
        if (stock !== '') params.set('stock', stock);

        const sort = (fd.get('sort') || 'newest').toString().trim();
        if (sort !== '' && sort !== 'newest') params.set('sort', sort);

        const page = parseInt((fd.get('page') || '1').toString(), 10);
        if (page > 1) params.set('page', String(page));

        return params;
    }

    /**
     * Đồng bộ ngược từ URL query string vào các ô input trên form (phục vụ sự kiện popstate Back/Forward - TC08)
     */
    function syncFormFromUrl(searchString) {
        if (!filterForm) return;
        const params = new URLSearchParams(searchString);

        const qInput = document.getElementById('catalog-q');
        if (qInput) qInput.value = params.get('q') || '';

        const catSel = document.getElementById('catalog-category');
        if (catSel) catSel.value = params.get('category_id') || params.get('category') || '';

        const brandValues = params.getAll('brands[]');
        const singleBrand = params.get('brand_id') || params.get('brands');
        const activeBrands = new Set(brandValues);
        if (singleBrand && !singleBrand.includes(',')) {
            activeBrands.add(singleBrand);
        } else if (singleBrand) {
            singleBrand.split(',').forEach(b => activeBrands.add(b.trim()));
        }

        filterForm.querySelectorAll('.catalog-brand-checkbox').forEach(function (chk) {
            chk.checked = activeBrands.has(chk.value);
        });

        const minPriceInp = document.getElementById('catalog-min-price');
        if (minPriceInp) minPriceInp.value = params.get('min_price') || '';

        const maxPriceInp = document.getElementById('catalog-max-price');
        if (maxPriceInp) maxPriceInp.value = params.get('max_price') || '';

        const stockSel = document.getElementById('catalog-stock');
        if (stockSel) stockSel.value = params.get('stock') || '';

        const sortVal = params.get('sort') || 'newest';
        if (sortHidden) sortHidden.value = sortVal;
        if (sortSelect) sortSelect.value = sortVal;

        const pageVal = params.get('page') || '1';
        if (pageHidden) pageHidden.value = pageVal;
    }

    /**
     * Gọi API api/products để tải lưới sản phẩm + chip bộ lọc + phân trang bằng AJAX
     */
    async function fetchCatalogResults(pushHistory = true) {
        if (!filterForm || !gridRegion) return;

        const cleanParams = buildCleanQueryParams();
        const qs = cleanParams.toString();
        const apiUrl = 'index.php?r=api/products' + (qs ? '&' + qs : '');
        const browserUrl = 'index.php?r=catalog' + (qs ? '&' + qs : '');

        gridRegion.classList.add('is-loading');
        try {
            const data = await StudioUI.fetchJson(apiUrl);
            if (!data.ok) {
                StudioUI.showToast(data.message || 'Không thể tải danh sách sản phẩm.', 'error');
                return;
            }

            gridRegion.innerHTML = data.html;
            if (totalCountEl) {
                totalCountEl.textContent = String(data.total);
            }

            if (pushHistory) {
                window.history.pushState({ catalogQuery: qs }, '', browserUrl);
            }
        } catch (err) {
            StudioUI.showToast('Lỗi kết nối khi lọc danh mục thiết bị.', 'error');
        } finally {
            gridRegion.classList.remove('is-loading');
        }
    }

    function resetAllFilters() {
        if (!filterForm) return;
        const qInput = document.getElementById('catalog-q');
        if (qInput) qInput.value = '';

        const catSel = document.getElementById('catalog-category');
        if (catSel) catSel.value = '';

        filterForm.querySelectorAll('.catalog-brand-checkbox').forEach(chk => chk.checked = false);

        const minPriceInp = document.getElementById('catalog-min-price');
        if (minPriceInp) minPriceInp.value = '';

        const maxPriceInp = document.getElementById('catalog-max-price');
        if (maxPriceInp) maxPriceInp.value = '';

        const stockSel = document.getElementById('catalog-stock');
        if (stockSel) stockSel.value = '';

        if (pageHidden) pageHidden.value = '1';
        fetchCatalogResults(true);
    }

    if (filterForm && gridRegion) {
        // Submit form bộ lọc
        filterForm.addEventListener('submit', function (e) {
            e.preventDefault();
            if (pageHidden) pageHidden.value = '1';
            fetchCatalogResults(true);
        });

        // Debounce 300ms cho ô tìm kiếm và khoảng giá
        filterForm.querySelectorAll('#catalog-q, #catalog-min-price, #catalog-max-price').forEach(function (inp) {
            inp.addEventListener('input', function () {
                clearTimeout(debounceTimer);
                debounceTimer = setTimeout(function () {
                    if (pageHidden) pageHidden.value = '1';
                    fetchCatalogResults(true);
                }, 300);
            });
        });

        // Thay đổi danh mục, tình trạng tồn kho hoặc tích chọn thương hiệu
        filterForm.querySelectorAll('select, .catalog-brand-checkbox').forEach(function (el) {
            el.addEventListener('change', function () {
                if (pageHidden) pageHidden.value = '1';
                fetchCatalogResults(true);
            });
        });

        // Thay đổi sắp xếp
        if (sortSelect) {
            sortSelect.addEventListener('change', function () {
                if (sortHidden) sortHidden.value = sortSelect.value;
                if (pageHidden) pageHidden.value = '1';
                fetchCatalogResults(true);
            });
        }

        // Nút đặt lại mặc định ở Sidebar
        const sidebarResetBtn = document.getElementById('btn-sidebar-reset-filters');
        if (sidebarResetBtn) {
            sidebarResetBtn.addEventListener('click', resetAllFilters);
        }

        // Lắng nghe sự kiện trên vùng lưới: gỡ từng chip bộ lọc, xóa tất cả, hoặc chuyển trang AJAX
        gridRegion.addEventListener('click', function (e) {
            const removeChipBtn = e.target.closest('.btn-remove-filter-chip');
            if (removeChipBtn) {
                const type = removeChipBtn.getAttribute('data-filter-type');
                const val = removeChipBtn.getAttribute('data-filter-value');

                if (type === 'q') {
                    const qInput = document.getElementById('catalog-q');
                    if (qInput) qInput.value = '';
                } else if (type === 'category_id') {
                    const catSel = document.getElementById('catalog-category');
                    if (catSel) catSel.value = '';
                } else if (type === 'brand') {
                    const chk = document.getElementById(`brand-chk-${val}`);
                    if (chk) chk.checked = false;
                } else if (type === 'min_price') {
                    const minInp = document.getElementById('catalog-min-price');
                    if (minInp) minInp.value = '';
                } else if (type === 'max_price') {
                    const maxInp = document.getElementById('catalog-max-price');
                    if (maxInp) maxInp.value = '';
                } else if (type === 'stock') {
                    const stockSel = document.getElementById('catalog-stock');
                    if (stockSel) stockSel.value = '';
                }

                if (pageHidden) pageHidden.value = '1';
                fetchCatalogResults(true);
                return;
            }

            const clearAllBtn = e.target.closest('#btn-clear-all-filters, #btn-empty-reset-filters');
            if (clearAllBtn) {
                e.preventDefault();
                resetAllFilters();
                return;
            }

            const pageLink = e.target.closest('.catalog-page-link');
            if (pageLink) {
                const pageItem = pageLink.closest('.page-item');
                if (pageItem && pageItem.classList.contains('disabled')) {
                    e.preventDefault();
                    return;
                }
                e.preventDefault();
                const targetPage = pageLink.getAttribute('data-page') || '1';
                if (pageHidden) pageHidden.value = targetPage;
                fetchCatalogResults(true);
                window.scrollTo({ top: 0, behavior: 'smooth' });
            }
        });

        // Khôi phục đúng kết quả khi người dùng bấm nút Back / Forward trên trình duyệt (TC08)
        window.addEventListener('popstate', function () {
            syncFormFromUrl(window.location.search);
            fetchCatalogResults(false);
        });
    }
});
