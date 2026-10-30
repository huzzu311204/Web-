<?php
declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Response;
use App\Core\Validator;
use App\Models\Brand;

class BrandController
{
    public function index(): void
    {
        $brandModel = new Brand();
        $brands = $brandModel->getAllWithCounts();

        $editId = isset($_GET['edit']) ? (int) $_GET['edit'] : null;
        $editBrand = $editId ? $brandModel->findById($editId) : null;

        Response::view('admin/brands/index', [
            'pageTitle' => 'Quản lý Thương hiệu — ProGear Studio',
            'brands'    => $brands,
            'editBrand' => $editBrand,
            'errors'    => [],
            'old'       => $editBrand ?: [],
        ], 'admin');
    }

    public function save(): void
    {
        $brandModel = new Brand();
        $id = isset($_POST['id']) && $_POST['id'] !== '' ? (int) $_POST['id'] : null;
        $name = trim((string) ($_POST['name'] ?? ''));
        $logo = trim((string) ($_POST['logo'] ?? ''));

        $errors = [];

        if (!Validator::isValidLength($name, 100, 1)) {
            $errors['name'] = 'Tên thương hiệu không được để trống và tối đa 100 ký tự.';
        } elseif ($brandModel->nameExists($name, $id)) {
            $errors['name'] = 'Thương hiệu này đã tồn tại.';
        }

        if ($logo !== '' && !Validator::isValidLength($logo, 255)) {
            $errors['logo'] = 'Đường dẫn/tên file logo tối đa 255 ký tự.';
        }

        if (!empty($errors)) {
            Response::view('admin/brands/index', [
                'pageTitle' => 'Quản lý Thương hiệu — ProGear Studio',
                'brands'    => $brandModel->getAllWithCounts(),
                'editBrand' => $id ? $brandModel->findById($id) : null,
                'errors'    => $errors,
                'old'       => ['id' => $id, 'name' => $name, 'logo' => $logo],
            ], 'admin');
            return;
        }

        if ($id === null) {
            $brandModel->create(['name' => $name, 'logo' => $logo]);
            Response::setFlash('success', 'Đã thêm thương hiệu mới: ' . $name);
        } else {
            $brandModel->update($id, ['name' => $name, 'logo' => $logo]);
            Response::setFlash('success', 'Đã cập nhật thương hiệu: ' . $name);
        }

        Response::redirect('index.php?r=admin/brands');
    }

    public function delete(): void
    {
        $id = (int) ($_POST['id'] ?? 0);
        $brandModel = new Brand();
        $brand = $brandModel->findById($id);

        if (!$brand) {
            Response::setFlash('error', 'Không tìm thấy thương hiệu cần xóa.');
            Response::redirect('index.php?r=admin/brands');
        }

        // Chặn xóa thương hiệu khi vẫn còn sản phẩm tham chiếu (Mục 4.3 & TC04)
        if ($brandModel->hasProducts($id)) {
            Response::setFlash('error', 'Không thể xóa thương hiệu "' . $brand['name'] . '" vì vẫn còn sản phẩm thuộc thương hiệu này.');
            Response::redirect('index.php?r=admin/brands');
        }

        $brandModel->delete($id);
        Response::setFlash('success', 'Đã xóa thương hiệu: ' . $brand['name']);
        Response::redirect('index.php?r=admin/brands');
    }
}

