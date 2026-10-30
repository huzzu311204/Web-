<?php
declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Auth;
use App\Core\Response;
use App\Core\Validator;
use App\Models\User;

class UserController
{
    public function index(): void
    {
        $userModel = new User();
        $users = $userModel->getAll();

        $editId = isset($_GET['edit']) ? (int) $_GET['edit'] : null;
        $editUser = $editId ? $userModel->findById($editId) : null;

        Response::view('admin/users/index', [
            'pageTitle' => 'Quản lý Tài khoản Admin — ProGear Studio',
            'users'     => $users,
            'editUser'  => $editUser,
            'errors'    => [],
            'old'       => $editUser ?: [],
        ], 'admin');
    }

    public function save(): void
    {
        $userModel = new User();
        $id = isset($_POST['id']) && $_POST['id'] !== '' ? (int) $_POST['id'] : null;
        $username = trim((string) ($_POST['username'] ?? ''));
        $fullName = trim((string) ($_POST['full_name'] ?? ''));
        $email    = trim((string) ($_POST['email'] ?? ''));
        $password = (string) ($_POST['password'] ?? '');

        $errors = [];

        if ($id === null) {
            if ($username === '' || !preg_match('/^[a-zA-Z0-9_.-]{3,50}$/', $username)) {
                $errors['username'] = 'Username từ 3–50 ký tự, chỉ gồm chữ cái, số, dấu chấm hoặc gạch dưới.';
            } elseif ($userModel->usernameExists($username)) {
                $errors['username'] = 'Username này đã tồn tại.';
            }
            if (strlen($password) < 8) {
                $errors['password'] = 'Mật khẩu tạo mới phải có ít nhất 8 ký tự.';
            }
        } else {
            if ($password !== '' && strlen($password) < 8) {
                $errors['password'] = 'Mật khẩu mới phải có ít nhất 8 ký tự.';
            }
        }

        if (!Validator::isValidLength($fullName, 100, 1)) {
            $errors['full_name'] = 'Họ tên không được để trống và tối đa 100 ký tự.';
        }
        if ($email !== '' && !Validator::isValidEmail($email)) {
            $errors['email'] = 'Địa chỉ email không hợp lệ (tối đa 100 ký tự).';
        }

        if (!empty($errors)) {
            $users = $userModel->getAll();
            Response::view('admin/users/index', [
                'pageTitle' => 'Quản lý Tài khoản Admin — ProGear Studio',
                'users'     => $users,
                'editUser'  => $id ? $userModel->findById($id) : null,
                'errors'    => $errors,
                'old'       => [
                    'id'        => $id,
                    'username'  => $username,
                    'full_name' => $fullName,
                    'email'     => $email,
                ],
            ], 'admin');
            return;
        }

        if ($id === null) {
            $userModel->create([
                'username'  => $username,
                'password'  => $password,
                'full_name' => $fullName,
                'email'     => $email,
                'status'    => 'ACTIVE',
            ]);
            Response::setFlash('success', 'Đã thêm tài khoản quản trị mới: ' . $username);
        } else {
            $userModel->update($id, [
                'full_name' => $fullName,
                'email'     => $email,
                'password'  => $password,
            ]);
            Response::setFlash('success', 'Đã cập nhật thông tin tài khoản quản trị.');
        }

        Response::redirect('index.php?r=admin/users');
    }

    public function toggleStatus(): void
    {
        $id = (int) ($_POST['id'] ?? 0);
        if ($id === Auth::id()) {
            Response::setFlash('error', 'Bạn không thể tự khóa tài khoản đang đăng nhập của chính mình.');
            Response::redirect('index.php?r=admin/users');
        }

        $userModel = new User();
        if ($userModel->toggleStatus($id)) {
            Response::setFlash('success', 'Đã cập nhật trạng thái hoạt động của tài khoản.');
        } else {
            Response::setFlash('error', 'Không tìm thấy tài khoản cần đổi trạng thái.');
        }

        Response::redirect('index.php?r=admin/users');
    }
}
