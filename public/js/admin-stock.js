/**
 * ADMIN-STOCK.JS — Xử lý giao diện Lập phiếu nhập kho nhiều dòng (Mục 6.3)
 * - Thêm / xóa dòng sản phẩm trong phiếu nhập
 * - Tự điền đơn giá nhập gợi ý khi chọn sản phẩm
 * - Tự tính thành tiền từng dòng và tổng tiền tạm tính phía client (server sẽ tính lại khi lưu)
 */

document.addEventListener('DOMContentLoaded', function () {
    'use strict';

    const tbody = document.getElementById('receipt-lines-tbody');
    const addBtn = document.getElementById('btn-add-receipt-line');
    const template = document.getElementById('receipt-line-template');
    const previewQtyEl = document.getElementById('receipt-preview-qty');
    const previewTotalEl = document.getElementById('receipt-preview-total');

    if (!tbody || !template) return;

    function recalcReceiptTotals() {
        let totalQty = 0;
        let totalAmount = 0;

        tbody.querySelectorAll('.receipt-line-row').forEach(function (row) {
            const qtyInput = row.querySelector('.receipt-qty-input');
            const priceInput = row.querySelector('.receipt-price-input');
            const subCell = row.querySelector('.receipt-line-subtotal');

            const qty = Math.max(0, parseInt(qtyInput ? qtyInput.value : '0', 10) || 0);
            const price = Math.max(0, parseInt(priceInput ? priceInput.value : '0', 10) || 0);
            const subtotal = qty * price;

            totalQty += qty;
            totalAmount += subtotal;

            if (subCell) {
                subCell.textContent = StudioUI.formatMoney(subtotal);
            }
        });

        if (previewQtyEl) previewQtyEl.textContent = String(totalQty);
        if (previewTotalEl) previewTotalEl.textContent = StudioUI.formatMoney(totalAmount);
    }

    if (addBtn) {
        addBtn.addEventListener('click', function () {
            const clone = template.content.cloneNode(true);
            tbody.appendChild(clone);
            recalcReceiptTotals();
        });
    }

    tbody.addEventListener('change', function (e) {
        const sel = e.target.closest('.receipt-product-select');
        if (sel) {
            const row = sel.closest('.receipt-line-row');
            const opt = sel.options[sel.selectedIndex];
            const defaultPrice = opt ? opt.getAttribute('data-default-price') : null;
            const priceInput = row ? row.querySelector('.receipt-price-input') : null;

            if (priceInput && defaultPrice && (priceInput.value === '' || priceInput.value === '0')) {
                priceInput.value = defaultPrice;
            }
            recalcReceiptTotals();
        }
    });

    tbody.addEventListener('input', function (e) {
        if (e.target.matches('.receipt-qty-input, .receipt-price-input')) {
            recalcReceiptTotals();
        }
    });

    tbody.addEventListener('click', function (e) {
        const rmBtn = e.target.closest('.btn-remove-receipt-line');
        if (!rmBtn) return;

        const rows = tbody.querySelectorAll('.receipt-line-row');
        if (rows.length <= 1) {
            StudioUI.showToast('Phiếu nhập phải có ít nhất 1 dòng sản phẩm.', 'warning');
            return;
        }
        rmBtn.closest('.receipt-line-row').remove();
        recalcReceiptTotals();
    });

    recalcReceiptTotals();
});
