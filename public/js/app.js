/**
 * APP.JS — Helper dùng chung cho khu vực Khách và Quản trị
 * Tuân thủ MASTER.md: Toast tự đóng sau 4 giây, CSRF header, Button loading spinner, Format tiền VNĐ
 * Kèm xử lý Thanh so sánh cố định cuối màn hình (.compare-bar-fixed) cho khu vực Khách (Giai đoạn 4)
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

        const iconClass = isError ? 'bi-exclamation-octagon' : 'bi-check-circle';
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

    function updateCompareBadge(count) {
        const badge = document.getElementById('navbar-compare-count');
        if (badge) {
            badge.textContent = String(count);
        }
        const barCount = document.getElementById('compare-bar-count');
        if (barCount) {
            barCount.textContent = String(count);
        }
    }

    function renderCompareBar(items) {
        const bar = document.getElementById('compare-sticky-bar');
        const listWrap = document.getElementById('compare-bar-items');
        updateCompareBadge(Array.isArray(items) ? items.length : 0);

        if (!bar || !listWrap) return;

        if (!Array.isArray(items) || items.length === 0) {
            bar.classList.add('d-none');
            listWrap.textContent = '';
            return;
        }

        bar.classList.remove('d-none');
        listWrap.textContent = '';

        const activeIds = new Set(items.map(it => Number(it.id)));

        items.forEach(function (item) {
            const pill = document.createElement('div');
            pill.className = 'card px-2 py-1 d-flex flex-row align-items-center gap-2';
            pill.style.background = '#131416';

            const nameSpan = document.createElement('span');
            nameSpan.className = 'small fw-medium text-light text-truncate';
            nameSpan.style.maxWidth = '180px';
            nameSpan.textContent = item.name;

            const rmBtn = document.createElement('button');
            rmBtn.type = 'button';
            rmBtn.className = 'btn btn-sm p-0 text-muted btn-compare-bar-remove';
            rmBtn.setAttribute('data-id', String(item.id));
            rmBtn.setAttribute('aria-label', 'Bỏ so sánh ' + item.name);
            rmBtn.innerHTML = '<i class="bi bi-x-lg" aria-hidden="true"></i>';

            pill.appendChild(nameSpan);
            pill.appendChild(rmBtn);
            listWrap.appendChild(pill);
        });

        // Đồng bộ trạng thái nút .btn-toggle-compare trên trang
        document.querySelectorAll('.btn-toggle-compare').forEach(function (btn) {
            const pid = Number(btn.getAttribute('data-id'));
            const isSelected = activeIds.has(pid);
            btn.classList.toggle('btn-primary', isSelected);
            btn.classList.toggle('btn-outline-secondary', !isSelected);
            const span = btn.querySelector('span');
            if (span) {
                if (btn.closest('.product-card')) {
                    span.textContent = isSelected ? 'Đã chọn' : 'So sánh';
                } else {
                    span.textContent = isSelected ? 'Đã có trong Bảng so sánh' : 'Thêm vào Bảng so sánh (Tối đa 3)';
                }
            }
        });
    }

    return {
        getCsrfToken,
        formatMoney,
        showToast,
        setButtonLoading,
        fetchJson,
        updateCompareBadge,
        renderCompareBar
    };
})();

document.addEventListener('DOMContentLoaded', function () {
    'use strict';

    // Nếu đang ở khu vực khách (có #navbar-compare-count), tải danh sách compare hiện tại và lắng nghe nút So sánh
    if (!document.getElementById('navbar-compare-count')) return;

    async function callCompareApi(params) {
        const body = new URLSearchParams(params);
        return StudioUI.fetchJson('index.php?r=api/compare', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: body.toString()
        });
    }

    // Khởi tạo thanh so sánh nếu đã có sẵn sản phẩm trong session
    const initialCount = parseInt(document.getElementById('navbar-compare-count').textContent || '0', 10);
    if (initialCount > 0 && document.getElementById('compare-sticky-bar')) {
        callCompareApi({ action: 'list' })
            .then(data => {
                if (data && data.ok) StudioUI.renderCompareBar(data.items || []);
            })
            .catch(() => {});
    }

    // Lắng nghe click toàn cục cho các nút .btn-toggle-compare, xóa từng mục hoặc xóa hết
    document.addEventListener('click', async function (e) {
        const toggleBtn = e.target.closest('.btn-toggle-compare');
        if (toggleBtn) {
            e.preventDefault();
            const pid = toggleBtn.getAttribute('data-id');
            if (!pid) return;

            try {
                const data = await callCompareApi({ action: 'toggle', product_id: pid });
                if (data.items) {
                    StudioUI.renderCompareBar(data.items);
                }
                if (!data.ok) {
                    StudioUI.showToast(data.message || 'Chỉ so sánh tối đa 3 sản phẩm.', 'error');
                    return;
                }
                StudioUI.showToast(data.message, 'success');
            } catch (err) {
                StudioUI.showToast('Không thể cập nhật danh sách so sánh.', 'error');
            }
            return;
        }

        const barRemoveBtn = e.target.closest('.btn-compare-bar-remove');
        if (barRemoveBtn) {
            e.preventDefault();
            const pid = barRemoveBtn.getAttribute('data-id');
            try {
                const data = await callCompareApi({ action: 'remove', product_id: pid });
                if (data.ok) {
                    StudioUI.renderCompareBar(data.items || []);
                    StudioUI.showToast(data.message, 'success');
                }
            } catch (err) {
                StudioUI.showToast('Lỗi khi gỡ sản phẩm khỏi thanh so sánh.', 'error');
            }
            return;
        }

        const barClearBtn = e.target.closest('#btn-compare-bar-clear');
        if (barClearBtn) {
            e.preventDefault();
            try {
                const data = await callCompareApi({ action: 'clear' });
                if (data.ok) {
                    StudioUI.renderCompareBar([]);
                    document.querySelectorAll('.btn-toggle-compare').forEach(function (btn) {
                        btn.classList.remove('btn-primary');
                        btn.classList.add('btn-outline-secondary');
                        const span = btn.querySelector('span');
                        if (span) {
                            span.textContent = btn.closest('.product-card') ? 'So sánh' : 'Thêm vào Bảng so sánh (Tối đa 3)';
                        }
                    });
                    StudioUI.showToast(data.message, 'success');
                }
            } catch (err) {
                StudioUI.showToast('Lỗi khi xóa danh sách so sánh.', 'error');
            }
            return;
        }

        // Các nút trên trang index.php?r=compare
        const pageRemoveBtn = e.target.closest('.btn-compare-remove-item');
        if (pageRemoveBtn) {
            e.preventDefault();
            const pid = pageRemoveBtn.getAttribute('data-id');
            try {
                const data = await callCompareApi({ action: 'remove', product_id: pid });
                if (data.ok) {
                    window.location.reload();
                }
            } catch (err) {
                StudioUI.showToast('Không thể xóa sản phẩm khỏi bảng so sánh.', 'error');
            }
            return;
        }

        const pageClearBtn = e.target.closest('#btn-compare-page-clear');
        if (pageClearBtn) {
            e.preventDefault();
            try {
                const data = await callCompareApi({ action: 'clear' });
                if (data.ok) {
                    window.location.reload();
                }
            } catch (err) {
                StudioUI.showToast('Không thể xóa toàn bộ bảng so sánh.', 'error');
            }
        }
    });
});
