<?php
declare(strict_types=1);

namespace App\Controllers\Api;

use App\Controllers\Storefront\CatalogController;
use App\Core\Response;
use App\Models\Brand;
use App\Models\Category;
use App\Services\ProductQuery;

class ProductApi
{
    /**
     * GET index.php?r=api/products — Lọc, tìm kiếm, sắp xếp, phân trang sản phẩm cho khách bằng AJAX (Mục 7 - GĐ3 & TC07, TC08)
     * Trả về: { ok, html, total, page, pages }
     */
    public function index(): void
    {
        $queryService  = new ProductQuery();
        $categoryModel = new Category();
        $brandModel    = new Brand();
        $catalogCtrl   = new CatalogController();

        $result         = $queryService->paginate($_GET, true, 9);
        $categories     = $categoryModel->getTreeOptions();
        $brands         = $brandModel->getAllWithCounts();
        $selectedBrands = $catalogCtrl->parseSelectedBrands($_GET);
        $activeChips    = $catalogCtrl->buildActiveFilterChips($_GET, $categories, $brands, $selectedBrands);

        ob_start();
        $items = $result['items'];
        $total = $result['total'];
        $page  = $result['page'];
        $pages = $result['pages'];
        $filters = $_GET;
        require dirname(__DIR__, 2) . '/views/storefront/catalog/_grid_wrapper.php';
        $html = ob_get_clean();

        Response::json([
            'ok'    => true,
            'html'  => $html,
            'total' => $result['total'],
            'page'  => $result['page'],
            'pages' => $result['pages'],
        ]);
    }
}
