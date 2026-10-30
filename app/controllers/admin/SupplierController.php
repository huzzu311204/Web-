<?php
declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Response;
use App\Core\Validator;
use App\Models\Supplier;

class SupplierController
{
    public function index(): void
    {
        $supplierModel = new Supplier();
        $suppliers = $supplierModel->getAllWithCounts();

        $editId = isset($_GET['edit']) ? (int) $_GET['edit'] : null;
        $editSupplier = $editId ? $supplierModel->findById($editId) : null;

        Response::view('admin/suppliers/index', [
            'pageTitle'    => 'Quản lý Nhà cung cấp — ProGear Studio',
            'suppliers'    => $suppliers,
            'editSupplier' => $editSupplier,
            'errors'       => [],
            'old'          => $editSupplier ?: [],
        ], 'admin');
    }

    public function save(): void
    {
        $supplierModel = new Supplier();
        $id      = isset($_POST['id']) && $_POST['id'] !== '' ? (int) $_POST['id'] : null;
        $name    = trim((string) ($_POST['name'] ?? ''));
        $phone   = trim((string) ($_POST['phone'] ?? ''));
        $email   = trim((string) ($_POST['email'] ?? ''));
        $address = trim((string) ($_POST['address'] ?? ''));

        $errors = [];

        if (!Validator::isValidLength($name, 150, 1)) {
            $errors['name'] = 'Tên nhà cung cấp không được để trống và tối đa 150 ký tự.';
        } elseif ($supplierModel->nameExists($name, $id)) {
            $errors['name'] = 'Tên nhà cung cấp này đã tồn tại.';
        }

        if ($phone === '' || !Validator::isValidVietnamPhone($phone)) {
            $errors['phone'] = 'Vui lòng nhập số điện thoại hợp lệ (10–11 chữ số).';
        }

        if ($email !== '' && !Validator::isValidEmail($email)) {
            $errors['email'] = 'Địa chỉ email không hợp lệ (tối đa 100 ký tự).';
        }

        if ($address !== '' && !Validator::isValidLength($address, 255)) {
            $errors['address'] = 'Địa chỉ tối đa 255 ký tự.';
        }

        if (!empty($errors)) {
            Response::view('admin/suppliers/index', [
                'pageTitle'    => 'Quản lý Nhà cung cấp — ProGear Studio',
                'suppliers'    => $supplierModel->getAllWithCounts(),
                'editSupplier' => $id ? $supplierModel->findById($id) : null,
                'errors'       => $errors,
                'old'          => [
                    'id'      => $id,
                    'name'    => $name,
                    'phone'   => $phone,
                    'email'   => $email,
                    'address' => $address,
                ],
            ], 'admin');
            return;
        }

        if ($id === null) {
            $supplierModel->create([
                'name'    => $name,
                'phone'   => $phone,
                'email'   => $email,
                'address' => $address,
            ]);
            Response::setFlash('success', 'Đã thêm nhà cung cấp mới: ' . $name);
        } else {
            $supplierModel->update($id, [
                'name'    => $name,
                'phone'   => $phone,
                'email'   => $email,
                'address' => $address,
            ]);
            Response::setFlash('success', 'Đã cập nhật nhà cung cấp: ' . $name);
        }

        Response::redirect('index.php?r=admin/suppliers');
    }

    public function delete(): void
    {
        $id = (int) ($_POST['id'] ?? 0);
        $supplierModel = new Supplier();
        $supplier = $supplierModel->findById($id);

        if (!$supplier) {
            Response::setFlash('error', 'Không tìm thấy nhà cung cấp cần xóa.');
            Response::redirect('index.php?r=admin/suppliers');
        }

        if ($supplierModel->hasReferences($id)) {
            Response::setFlash('error', 'Không thể xóa nhà cung cấp "' . $supplier['name'] . '" vì đang có sản phẩm hoặc phiếu nhập tham chiếu.');
            Response::redirect('index.php?r=admin/suppliers');
        }

        $supplierModel->delete($id);
        Response::setFlash('success', 'Đã xóa nhà cung cấp: ' . $supplier['name']);
        Response::redirect('index.php?r=admin/suppliers');
    }
}
