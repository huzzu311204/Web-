<?php
declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Auth;
use App\Core\Response;
use App\Core\Validator;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductSpec;
use App\Models\Supplier;
use App\Services\CsvExporter;
use App\Services\ImageUploader;
use App\Services\ProductQuery;
use RuntimeException;

class ProductController
{
    public function index(): void
    {
        $queryService = new ProductQuery();
        $result = $queryService->paginate($_GET, false, 10);

        $categoryModel = new Category();
        $brandModel = new Brand();

        Response::view('admin/products/index', [
            'pageTitle'   => 'Quản lý Sản phẩm & Tồn kho — ProGear Studio',
            'result'      => $result,
            'categories'  => $categoryModel->getTreeOptions(),
            'brands'      => $brandModel->getAllWithCounts(),
            'filters'     => $_GET,
            'pageScripts' => ['js/admin-products.js'],
        ], 'admin');
    }

    public function create(): void
    {
        $categoryModel = new Category();
        $brandModel    = new Brand();
        $supplierModel = new Supplier();

        $errors = [];
        $old = [
            'code'        => '',
            'name'        => '',
            'category_id' => '',
            'brand_id'    => '',
            'supplier_id' => '',
            'price'       => '0',
            'quantity'    => '0',
            'min_stock'   => '2',
            'status'      => 'ACTIVE',
            'description' => '',
            'image'       => null,
        ];
        $specs = [
            ['name' => 'Ngàm', 'value' => ''],
            ['name' => 'Cảm biến', 'value' => ''],
        ];

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            [$old, $specs, $errors] = $this->validateProductForm(null);

            $uploadedImage = null;
            if (empty($errors) && !empty($_FILES['image']['name'])) {
                try {
                    $uploadedImage = ImageUploader::uploadProductImage($_FILES['image']);
                    $old['image'] = $uploadedImage;
                } catch (RuntimeException $e) {
                    $errors['image'] = $e->getMessage();
                }
            }

            if (empty($errors)) {
                $productModel = new Product();
                $productModel->createWithSpecs($old, $specs, (int) Auth::id());
                Response::setFlash('success', 'Đã thêm sản phẩm mới thành công: ' . $old['name'] . ' (' . $old['code'] . ')');
                Response::redirect('index.php?r=admin/products');
            }
        }

        Response::view('admin/products/form', [
            'pageTitle'   => 'Thêm Sản phẩm mới — ProGear Studio',
            'isEdit'      => false,
            'old'         => $old,
            'specs'       => $specs,
            'errors'      => $errors,
            'categories'  => $categoryModel->getTreeOptions(),
            'brands'      => $brandModel->getAllWithCounts(),
            'suppliers'   => $supplierModel->getAllWithCounts(),
            'pageScripts' => ['js/admin-products.js'],
        ], 'admin');
    }

    public function edit(): void
    {
        $id = (int) ($_GET['id'] ?? $_POST['id'] ?? 0);
        $productModel = new Product();
        $product = $productModel->findById($id);

        if (!$product) {
            Response::setFlash('error', 'Không tìm thấy sản phẩm cần chỉnh sửa.');
            Response::redirect('index.php?r=admin/products');
        }

        $categoryModel = new Category();
        $brandModel    = new Brand();
        $supplierModel = new Supplier();
        $specModel     = new ProductSpec();

        $errors = [];
        $old = $product;
        $rawSpecs = $specModel->getByProductId($id);
        $specs = [];
        foreach ($rawSpecs as $s) {
            $specs[] = ['name' => $s['spec_name'], 'value' => $s['spec_value']];
        }
        if (empty($specs)) {
            $specs = [['name' => '', 'value' => '']];
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            [$old, $specs, $errors] = $this->validateProductForm($id);
            $old['id'] = $id;
            $old['quantity'] = $product['quantity'];
            $old['image'] = $product['image'];

            if (empty($errors) && !empty($_FILES['image']['name'])) {
                try {
                    $newImage = ImageUploader::uploadProductImage($_FILES['image']);
                    ImageUploader::deleteOldImage($product['image'] ?? null);
                    $old['image'] = $newImage;
                } catch (RuntimeException $e) {
                    $errors['image'] = $e->getMessage();
                }
            }

            if (empty($errors)) {
                $productModel->updateWithSpecs($id, $old, $specs);
                Response::setFlash('success', 'Đã cập nhật sản phẩm: ' . $old['name']);
                Response::redirect('index.php?r=admin/products');
            }
        }

        Response::view('admin/products/form', [
            'pageTitle'   => 'Chỉnh sửa Sản phẩm — ProGear Studio',
            'isEdit'      => true,
            'old'         => $old,
            'specs'       => $specs,
            'errors'      => $errors,
            'categories'  => $categoryModel->getTreeOptions(),
            'brands'      => $brandModel->getAllWithCounts(),
            'suppliers'   => $supplierModel->getAllWithCounts(),
            'pageScripts' => ['js/admin-products.js'],
        ], 'admin');
    }

    public function exportCsv(): void
    {
        $queryService = new ProductQuery();
        $products = $queryService->fetchAllFiltered($_GET, false);
        CsvExporter::exportProducts($products);
    }

    private function validateProductForm(?int $excludeId): array
    {
        $productModel  = new Product();
        $categoryModel = new Category();
        $brandModel    = new Brand();
        $supplierModel = new Supplier();

        $code        = strtoupper(trim((string) ($_POST['code'] ?? '')));
        $name        = trim((string) ($_POST['name'] ?? ''));
        $categoryId  = (int) ($_POST['category_id'] ?? 0);
        $brandId     = isset($_POST['brand_id']) && $_POST['brand_id'] !== '' ? (int) $_POST['brand_id'] : null;
        $supplierId  = (int) ($_POST['supplier_id'] ?? 0);
        $priceRaw    = trim((string) ($_POST['price'] ?? ''));
        $qtyRaw      = trim((string) ($_POST['quantity'] ?? '0'));
        $minStockRaw = trim((string) ($_POST['min_stock'] ?? '0'));
        $status      = ($_POST['status'] ?? 'ACTIVE') === 'INACTIVE' ? 'INACTIVE' : 'ACTIVE';
        $description = trim((string) ($_POST['description'] ?? ''));

        $specNames  = $_POST['spec_name'] ?? [];
        $specValues = $_POST['spec_value'] ?? [];
        $specs = [];
        if (is_array($specNames) && is_array($specValues)) {
            foreach ($specNames as $idx => $sName) {
                $n = trim((string) $sName);
                $v = trim((string) ($specValues[$idx] ?? ''));
                if ($n !== '' || $v !== '') {
                    $specs[] = ['name' => $n, 'value' => $v];
                }
            }
        }

        $errors = [];

        if (!Validator::isValidLength($code, 50, 2) || !preg_match('/^[A-Z0-9_-]+$/', $code)) {
            $errors['code'] = 'Mã SKU từ 2–50 ký tự, chỉ gồm chữ in hoa, số, gạch ngang hoặc gạch dưới.';
        } elseif ($productModel->codeExists($code, $excludeId)) {
            $errors['code'] = 'Mã SKU "' . $code . '" đã tồn tại trong hệ thống.';
        }

        if (!Validator::isValidLength($name, 200, 1)) {
            $errors['name'] = 'Tên sản phẩm không được để trống và tối đa 200 ký tự.';
        }

        if ($categoryId <= 0 || !$categoryModel->findById($categoryId)) {
            $errors['category_id'] = 'Vui lòng chọn danh mục hợp lệ.';
        }

        if ($supplierId <= 0 || !$supplierModel->findById($supplierId)) {
            $errors['supplier_id'] = 'Vui lòng chọn nhà cung cấp hợp lệ.';
        }

        if ($brandId !== null && !$brandModel->findById($brandId)) {
            $errors['brand_id'] = 'Thương hiệu đã chọn không tồn tại.';
        }

        if (!Validator::isNonNegativeInt($priceRaw) || (int) $priceRaw > 999999999999) {
            $errors['price'] = 'Giá bán phải là số nguyên không âm hợp lệ.';
        }

        if ($excludeId === null) {
            if (!Validator::isNonNegativeInt($qtyRaw) || (int) $qtyRaw > 100000) {
                $errors['quantity'] = 'Số lượng tồn đầu kỳ phải là số nguyên không âm.';
            }
        }

        if (!Validator::isNonNegativeInt($minStockRaw) || (int) $minStockRaw > 100000) {
            $errors['min_stock'] = 'Ngưỡng sắp hết phải là số nguyên không âm.';
        }

        if ($description !== '' && !Validator::isValidLength($description, 5000)) {
            $errors['description'] = 'Mô tả sản phẩm tối đa 5000 ký tự.';
        }

        foreach ($specs as $s) {
            if ($s['name'] === '' || $s['value'] === '') {
                $errors['specs'] = 'Mỗi dòng thông số kỹ thuật phải điền đầy đủ cả Tên thông số và Giá trị.';
                break;
            }
            if (!Validator::isValidLength($s['name'], 100) || !Validator::isValidLength($s['value'], 255)) {
                $errors['specs'] = 'Tên thông số tối đa 100 ký tự và giá trị tối đa 255 ký tự.';
                break;
            }
        }

        if (empty($specs)) {
            $specs = [['name' => '', 'value' => '']];
        }

        $old = [
            'code'        => $code,
            'name'        => $name,
            'category_id' => $categoryId,
            'brand_id'    => $brandId,
            'supplier_id' => $supplierId,
            'price'       => Validator::isNonNegativeInt($priceRaw) ? (int) $priceRaw : $priceRaw,
            'quantity'    => Validator::isNonNegativeInt($qtyRaw) ? (int) $qtyRaw : $qtyRaw,
            'min_stock'   => Validator::isNonNegativeInt($minStockRaw) ? (int) $minStockRaw : $minStockRaw,
            'status'      => $status,
            'description' => $description,
        ];

        return [$old, $specs, $errors];
    }
}
