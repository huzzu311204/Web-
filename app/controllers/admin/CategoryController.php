<?php
declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Response;
use App\Core\Validator;
use App\Models\Category;

class CategoryController
{
    public function index(): void
    {
        $categoryModel = new Category();
        $categories = $categoryModel->getAllWithCounts();

        $editId = isset($_GET['edit']) ? (int) $_GET['edit'] : null;
        $editCat = $editId ? $categoryModel->findById($editId) : null;
        $parentOptions = $categoryModel->getTreeOptions($editId);

        Response::view('admin/categories/index', [
            'pageTitle'     => 'Quản lý Danh mục Sản phẩm — ProGear Studio',
            'categories'    => $categories,
            'parentOptions' => $parentOptions,
            'editCat'       => $editCat,
            'errors'        => [],
            'old'           => $editCat ?: [],
        ], 'admin');
    }

    public function save(): void
    {
        $categoryModel = new Category();
        $id = isset($_POST['id']) && $_POST['id'] !== '' ? (int) $_POST['id'] : null;
        $parentId = isset($_POST['parent_id']) && $_POST['parent_id'] !== '' ? (int) $_POST['parent_id'] : null;
        $name = trim((string) ($_POST['name'] ?? ''));
        $description = trim((string) ($_POST['description'] ?? ''));

        $errors = [];

        if (!Validator::isValidLength($name, 120, 1)) {
            $errors['name'] = 'Tên danh mục không được để trống và tối đa 120 ký tự.';
        } elseif ($categoryModel->nameExists($name, $id)) {
            $errors['name'] = 'Tên danh mục này đã tồn tại.';
        }

        if ($description !== '' && !Validator::isValidLength($description, 255)) {
            $errors['description'] = 'Mô tả tối đa 255 ký tự.';
        }

        if ($parentId !== null) {
            if ($id !== null && $parentId === $id) {
                $errors['parent_id'] = 'Danh mục không thể chọn chính nó làm danh mục cha.';
            } else {
                $parent = $categoryModel->findById($parentId);
                if (!$parent) {
                    $errors['parent_id'] = 'Danh mục cha đã chọn không tồn tại.';
                }
            }
        }

        if (!empty($errors)) {
            Response::view('admin/categories/index', [
                'pageTitle'     => 'Quản lý Danh mục Sản phẩm — ProGear Studio',
                'categories'    => $categoryModel->getAllWithCounts(),
                'parentOptions' => $categoryModel->getTreeOptions($id),
                'editCat'       => $id ? $categoryModel->findById($id) : null,
                'errors'        => $errors,
                'old'           => [
                    'id'          => $id,
                    'parent_id'   => $parentId,
                    'name'        => $name,
                    'description' => $description,
                ],
            ], 'admin');
            return;
        }

        if ($id === null) {
            $categoryModel->create([
                'parent_id'   => $parentId,
                'name'        => $name,
                'description' => $description,
            ]);
            Response::setFlash('success', 'Đã thêm danh mục mới: ' . $name);
        } else {
            $categoryModel->update($id, [
                'parent_id'   => $parentId,
                'name'        => $name,
                'description' => $description,
            ]);
            Response::setFlash('success', 'Đã cập nhật danh mục: ' . $name);
        }

        Response::redirect('index.php?r=admin/categories');
    }

    public function delete(): void
    {
        $id = (int) ($_POST['id'] ?? 0);
        $categoryModel = new Category();
        $cat = $categoryModel->findById($id);

        if (!$cat) {
            Response::setFlash('error', 'Không tìm thấy danh mục cần xóa.');
            Response::redirect('index.php?r=admin/categories');
        }

        // Không xóa danh mục khi còn sản phẩm hoặc danh mục con tham chiếu (Mục 4.3 & TC04)
        if ($categoryModel->hasProducts($id)) {
            Response::setFlash('error', 'Không thể xóa danh mục "' . $cat['name'] . '" vì đang có sản phẩm thuộc danh mục này.');
            Response::redirect('index.php?r=admin/categories');
        }

        if ($categoryModel->hasChildren($id)) {
            Response::setFlash('error', 'Không thể xóa danh mục "' . $cat['name'] . '" vì vẫn còn danh mục con trực thuộc.');
            Response::redirect('index.php?r=admin/categories');
        }

        $categoryModel->delete($id);
        Response::setFlash('success', 'Đã xóa danh mục: ' . $cat['name']);
        Response::redirect('index.php?r=admin/categories');
    }
}
