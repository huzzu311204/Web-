/**
 * ADMIN-PRODUCTS.JS — Xử lý AJAX cho trang Quản lý Sản phẩm & Form Sản phẩm (Mục 5.2 & 7.1)
 * 1. Lọc, tìm kiếm, sắp xếp, phân trang danh sách Admin bằng AJAX + cập nhật link Xuất CSV
 * 2. Kiểm tra mã SKU trùng tức thì (api/admin/sku-check) với debounce 300ms
 * 3. Thêm / xóa dòng thông số kỹ thuật động trên form
 * 4. Sửa nhanh tại bảng (api/admin/product-inline): Giá bán, Ngưỡng sắp hết, Trạng thái (khôi phục nếu lỗi)
 * 5. Xóa sản phẩm qua Modal (api/admin/product-action)
 * 6. Tính toán trước tồn kho sau thao tác trong Modal Xuất kho / Điều chỉnh
 */

document.addEventListener('DOMContentLoaded', function () {
    'use strict';

    // -------------------------------------------------------------------------
    // 1. Bộ lọc & Phân trang AJAX trên danh sách sản phẩm Admin
    // -------------------------------------------------------------------------
    const filterForm = document.getElementById('admin-product-filter-form');
    const tbody = document.getElementById('admin-product-tbody');
    const paginationWrap = document.getElementById('admin-product-pagination');
    const region = document.getElementById('admin-products-region');
    const totalEl = document.getElementById('admin-product-total');
    const exportCsvBtn = document.getElementById('btn-export-csv');
    const pageInput = document.getElementById('admin-filter-page');

    let filterTimer = null;

    function syncExportCsvLink() {
        if (!filterForm || !exportCsvBtn) return;
        const fd = new FormData(filterForm);
        fd.delete('r');
        fd.delete('page');
        const qs = new URLSearchParams(fd).toString();
        exportCsvBtn.href = 'index.php?r=admin/products/export-csv' + (qs ? '&' + qs : '');
    }

    async function loadAdminProducts(pushHistory = true) {
        if (!filterForm || !tbody) return;
        syncExportCsvLink();

        const fd = new FormData(filterForm);
        fd.delete('r');
        const qs = new URLSearchParams(fd).toString();

        if (region) region.classList.add('is-loading');
        try {
            const data = await StudioUI.fetchJson(`index.php?r=api/admin/products&${qs}`);
            if (!data.ok) {
                StudioUI.showToast(data.message || 'Không thể tải danh sách sản phẩm.', 'error');
                return;
            }
            tbody.innerHTML = data.rows_html;
            if (paginationWrap) paginationWrap.innerHTML = data.pagination_html;
            if (totalEl) totalEl.textContent = String(data.total);

            if (pushHistory) {
                window.history.replaceState(null, '', `index.php?r=admin/products&${qs}`);
            }
        } catch (err) {
            StudioUI.showToast('Lỗi kết nối khi lọc danh sách sản phẩm.', 'error');
        } finally {
            if (region) region.classList.remove('is-loading');
        }
    }

    if (filterForm) {
        syncExportCsvLink();

        filterForm.addEventListener('submit', function (e) {
            e.preventDefault();
            if (pageInput) pageInput.value = '1';
            loadAdminProducts(true);
        });

        const searchInput = document.getElementById('filter-q');
        if (searchInput) {
            searchInput.addEventListener('input', function () {
                clearTimeout(filterTimer);
                filterTimer = setTimeout(function () {
                    if (pageInput) pageInput.value = '1';
                    loadAdminProducts(true);
                }, 300);
            });
        }

        filterForm.querySelectorAll('select').forEach(function (sel) {
            sel.addEventListener('change', function () {
                if (pageInput) pageInput.value = '1';
                loadAdminProducts(true);
            });
        });

        if (paginationWrap) {
            paginationWrap.addEventListener('click', function (e) {
                const link = e.target.closest('.admin-page-link');
                if (!link) return;
                e.preventDefault();
                const page = link.getAttribute('data-page') || '1';
                if (pageInput) pageInput.value = page;
                loadAdminProducts(true);
            });
        }
    }

    // -------------------------------------------------------------------------
    // 2. Sửa nhanh tại bảng (Inline Edit - Mục 5.2 - A7 & TC21)
    // -------------------------------------------------------------------------
    if (tbody) {
        tbody.addEventListener('click', async function (e) {
            const saveBtn = e.target.closest('.btn-save-inline');
            if (!saveBtn) return;

            const id = saveBtn.getAttribute('data-id');
            const row = document.getElementById(`product-row-${id}`);
            if (!row) return;

            const priceInput = row.querySelector('.inline-price-input');
            const minInput   = row.querySelector('.inline-min-stock-input');
            const statusSel  = row.querySelector('.inline-status-select');

            const body = new URLSearchParams();
            body.append('id', id);
            body.append('price', priceInput ? priceInput.value : '0');
            body.append('min_stock', minInput ? minInput.value : '0');
            body.append('status', statusSel ? statusSel.value : 'ACTIVE');

            StudioUI.setButtonLoading(saveBtn, true, '');

            try {
                const data = await StudioUI.fetchJson('index.php?r=api/admin/product-inline', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: body.toString()
                });

                if (!data.ok) {
                    // Khôi phục giá trị cũ khi lỗi (Mục 5.2 - A7 & TC21)
                    if (priceInput) priceInput.value = priceInput.getAttribute('data-original');
                    if (minInput) minInput.value = minInput.getAttribute('data-original');
                    if (statusSel) statusSel.value = statusSel.getAttribute('data-original');
                    StudioUI.showToast(data.message || 'Dữ liệu sửa nhanh không hợp lệ.', 'error');
                    return;
                }

                if (priceInput) {
                    priceInput.value = data.price;
                    priceInput.setAttribute('data-original', data.price);
                }
                if (minInput) {
                    minInput.value = data.min_stock;
                    minInput.setAttribute('data-original', data.min_stock);
                }
                if (statusSel) {
                    statusSel.value = data.status;
                    statusSel.setAttribute('data-original', data.status);
                }

                const badgeCell = document.getElementById(`product-stock-badge-${id}`);
                if (badgeCell && data.stock_badge_html) {
                    badgeCell.innerHTML = data.stock_badge_html;
                }

                row.className = data.row_class || '';
                StudioUI.showToast(data.message, 'success');
            } catch (err) {
                StudioUI.showToast('Không thể lưu thay đổi nhanh.', 'error');
            } finally {
                StudioUI.setButtonLoading(saveBtn, false);
            }
        });
    }

    // -------------------------------------------------------------------------
    // 3. Xóa sản phẩm qua Modal bằng AJAX (TC05, TC06)
    // -------------------------------------------------------------------------
    const deleteModalEl = document.getElementById('deleteProductModal');
    const confirmDeleteBtn = document.getElementById('btn-confirm-delete-product');

    if (deleteModalEl && confirmDeleteBtn) {
        deleteModalEl.addEventListener('show.bs.modal', function (event) {
            const btn = event.relatedTarget;
            if (!btn) return;
            confirmDeleteBtn.setAttribute('data-id', btn.getAttribute('data-id'));
            document.getElementById('del-prod-name').textContent = btn.getAttribute('data-name');
            document.getElementById('del-prod-code').textContent = btn.getAttribute('data-code');
        });

        confirmDeleteBtn.addEventListener('click', async function () {
            const id = confirmDeleteBtn.getAttribute('data-id');
            if (!id) return;

            StudioUI.setButtonLoading(confirmDeleteBtn, true, 'Đang xử lý...');
            try {
                const body = new URLSearchParams({ id: id, action: 'delete' });
                const data = await StudioUI.fetchJson('index.php?r=api/admin/product-action', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: body.toString()
                });

                const modalInstance = window.bootstrap.Modal.getInstance(deleteModalEl);
                if (modalInstance) modalInstance.hide();

                if (!data.ok) {
                    StudioUI.showToast(data.message || 'Không thể xóa sản phẩm.', 'error');
                    return;
                }

                const row = document.getElementById(`product-row-${id}`);
                if (data.action_done === 'hard_deleted') {
                    if (row) row.remove();
                } else if (data.action_done === 'soft_deleted' && row) {
                    const statusSel = row.querySelector('.inline-status-select');
                    if (statusSel) {
                        statusSel.value = 'INACTIVE';
                        statusSel.setAttribute('data-original', 'INACTIVE');
                    }
                    const badgeCell = document.getElementById(`product-stock-badge-${id}`);
                    if (badgeCell && data.stock_badge_html) {
                        badgeCell.innerHTML = data.stock_badge_html;
                    }
                    row.className = '';
                }

                StudioUI.showToast(data.message, 'success');
            } catch (err) {
                StudioUI.showToast('Lỗi kết nối khi xóa sản phẩm.', 'error');
            } finally {
                StudioUI.setButtonLoading(confirmDeleteBtn, false);
            }
        });
    }

    // -------------------------------------------------------------------------
    // 4. Modal Xuất kho / Điều chỉnh tồn kho (hiển thị tồn dự kiến sau thao tác)
    // -------------------------------------------------------------------------
    const stockModalEl = document.getElementById('stockAdjustModal');
    if (stockModalEl) {
        const adjIdInput   = document.getElementById('adj-product-id');
        const adjNameEl    = document.getElementById('adj-product-name');
        const adjCurrentEl = document.getElementById('adj-current-qty');
        const adjAfterEl   = document.getElementById('adj-after-qty');
        const adjTypeSel   = document.getElementById('adj-type');
        const adjQtyInput  = document.getElementById('adj-qty');
        const adjSubmitBtn = document.getElementById('btn-submit-stock-adjust');
        let currentStockVal = 0;

        function updateProjectedStock() {
            const qty = Math.max(0, parseInt(adjQtyInput.value || '0', 10));
            const type = adjTypeSel.value;
            const delta = (type === 'ADJUSTMENT_ADD') ? qty : -qty;
            const after = currentStockVal + delta;

            adjAfterEl.textContent = String(after);
            if (after < 0) {
                adjAfterEl.style.color = '#f87171';
                adjQtyInput.classList.add('is-invalid');
                if (adjSubmitBtn) adjSubmitBtn.disabled = true;
            } else {
                adjAfterEl.style.color = '#34d399';
                adjQtyInput.classList.remove('is-invalid');
                if (adjSubmitBtn) adjSubmitBtn.disabled = false;
            }
        }

        stockModalEl.addEventListener('show.bs.modal', function (event) {
            const btn = event.relatedTarget;
            if (!btn) return;
            currentStockVal = parseInt(btn.getAttribute('data-qty') || '0', 10);
            adjIdInput.value = btn.getAttribute('data-id');
            adjNameEl.textContent = btn.getAttribute('data-name') + ' (' + btn.getAttribute('data-code') + ')';
            adjCurrentEl.textContent = String(currentStockVal);
            adjTypeSel.value = currentStockVal > 0 ? 'EXPORT' : 'ADJUSTMENT_ADD';
            adjQtyInput.value = '1';
            document.getElementById('adj-reason').value = '';
            updateProjectedStock();
        });

        adjTypeSel.addEventListener('change', updateProjectedStock);
        adjQtyInput.addEventListener('input', updateProjectedStock);
    }

    // -------------------------------------------------------------------------
    // 5. Kiểm tra mã SKU trùng bằng AJAX trên Form Sản phẩm (debounce 300ms)
    // -------------------------------------------------------------------------
    const skuInput = document.getElementById('code');
    const skuFeedback = document.getElementById('sku-check-feedback');
    let skuTimer = null;

    if (skuInput && skuFeedback) {
        skuInput.addEventListener('input', function () {
            clearTimeout(skuTimer);
            const code = skuInput.value.trim().toUpperCase();
            const excludeId = skuInput.getAttribute('data-exclude-id') || '';

            if (code === '') {
                skuFeedback.textContent = '';
                skuInput.classList.remove('is-invalid');
                return;
            }

            skuTimer = setTimeout(async function () {
                try {
                    const qs = new URLSearchParams({ code: code, exclude_id: excludeId }).toString();
                    const data = await StudioUI.fetchJson(`index.php?r=api/admin/sku-check&${qs}`);
                    if (!data.ok) return;

                    skuFeedback.textContent = data.message || '';
                    if (data.exists || !data.valid) {
                        skuFeedback.style.color = '#f87171';
                        skuInput.classList.add('is-invalid');
                    } else {
                        skuFeedback.style.color = '#34d399';
                        skuInput.classList.remove('is-invalid');
                    }
                } catch (err) {
                    // Bỏ qua lỗi mạng tạm thời khi gõ
                }
            }, 300);
        });
    }

    // -------------------------------------------------------------------------
    // 6. Thêm / Xóa dòng thông số kỹ thuật động trên Form Sản phẩm
    // -------------------------------------------------------------------------
    const addSpecBtn = document.getElementById('btn-add-spec-row');
    const specContainer = document.getElementById('spec-rows-container');

    if (addSpecBtn && specContainer) {
        addSpecBtn.addEventListener('click', function () {
            const row = document.createElement('div');
            row.className = 'row g-2 align-items-center spec-row-item';
            row.innerHTML = `
                <div class="col-5">
                    <input type="text" name="spec_name[]" maxlength="100" class="form-control" placeholder="Tên thông số (VD: Ngàm)" aria-label="Tên thông số kỹ thuật">
                </div>
                <div class="col-6">
                    <input type="text" name="spec_value[]" maxlength="255" class="form-control" placeholder="Giá trị (VD: Sony E)" aria-label="Giá trị thông số kỹ thuật">
                </div>
                <div class="col-1 text-end">
                    <button type="button" class="btn btn-outline-danger btn-icon btn-remove-spec-row" aria-label="Xóa dòng thông số">
                        <i class="bi bi-x-lg" aria-hidden="true"></i>
                    </button>
                </div>
            `;
            specContainer.appendChild(row);
            row.querySelector('input').focus();
        });

        specContainer.addEventListener('click', function (e) {
            const removeBtn = e.target.closest('.btn-remove-spec-row');
            if (!removeBtn) return;
            const rows = specContainer.querySelectorAll('.spec-row-item');
            if (rows.length <= 1) {
                rows[0].querySelectorAll('input').forEach(inp => inp.value = '');
                return;
            }
            removeBtn.closest('.spec-row-item').remove();
        });
    }
});

