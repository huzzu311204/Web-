/**
 * APP.JS — Helper dùng chung cho khu vực Khách và Quản trị
 * Tuân thủ MASTER.md: Toast tự đóng sau 4 giây, CSRF header, Button loading spinner, Format tiền VNĐ
 */

window.StudioUI = (function () {
    'use strict';

    function getCsrfToken() {
        const meta = document.querySelector('meta[name="csrf-token"]');
        return meta ? meta.getAttribute('content') : '';
    }

    function formatMoney(amount) {
        const num = Number(amount) || 0;
        return new Intl.NumberFormat('vi-VN').format(Math.round(num)) + ' đ';
    }

    function showToast(message, type = 'success') {
        const container = document.getElementById('studio-toast-container');
        if (!container) return;

        const isError = (type === 'error' || type === 'danger');
        const toastEl = document.createElement('div');
        toastEl.className = `toast toast-studio ${isError ? 'toast-error' : 'toast-success'} align-items-center border-0 mb-2`;
        toastEl.setAttribute('role', 'status');
        toastEl.setAttribute('aria-live', 'polite');
        toastEl.setAttribute('aria-atomic', 'true');

        const iconClass = isError ? 'bi-exclamation-octagon' : 'bi-check2-circle';
        const iconColor = isError ? '#f87171' : '#34d399';

        toastEl.innerHTML = `
            <div class="d-flex align-items-center justify-content-between p-3">
                <div class="d-flex align-items-center gap-2">
                    <i class="bi ${iconClass}" style="color: ${iconColor};" aria-hidden="true"></i>
                    <span class="small fw-medium"></span>
                </div>
                <button type="button" class="btn-close btn-close-white ms-3" data-bs-dismiss="toast" aria-label="Đóng thông báo"></button>
            </div>
        `;
        toastEl.querySelector('span').textContent = message;
        container.appendChild(toastEl);

        if (window.bootstrap && window.bootstrap.Toast) {
            const bsToast = new window.bootstrap.Toast(toastEl, { delay: 4000 });
            bsToast.show();
            toastEl.addEventListener('hidden.bs.toast', () => toastEl.remove());
        } else {
            toastEl.style.display = 'block';
            setTimeout(() => toastEl.remove(), 4000);
        }
    }

    function setButtonLoading(btn, isLoading, loadingText = null) {
        if (!btn) return;
        if (isLoading) {
            if (!btn.dataset.originalHtml) {
                btn.dataset.originalHtml = btn.innerHTML;
            }
            btn.disabled = true;
            const label = loadingText || btn.textContent.trim();
            btn.innerHTML = `<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span><span>${label}</span>`;
        } else {
            btn.disabled = false;
            if (btn.dataset.originalHtml) {
                btn.innerHTML = btn.dataset.originalHtml;
                delete btn.dataset.originalHtml;
            }
        }
    }

    async function fetchJson(url, options = {}) {
        const headers = Object.assign({
            'X-Requested-With': 'XMLHttpRequest',
            'X-CSRF-Token': getCsrfToken()
        }, options.headers || {});

        const response = await fetch(url, Object.assign({}, options, { headers }));
        const data = await response.json();
        return data;
    }

    function updateCartBadge(count) {
        const badge = document.getElementById('navbar-cart-count');
        if (badge) {
            badge.textContent = String(count);
        }
    }

    return {
        getCsrfToken,
        formatMoney,
        showToast,
        setButtonLoading,
        fetchJson,
        updateCartBadge
    };
})();

