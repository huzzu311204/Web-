<?php
declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Auth;
use App\Core\Csrf;
use App\Core\Response;
use App\Models\User;

class AuthController
{
    public function login(): void
    {
        if (Auth::check()) {
            Response::redirect('index.php?r=admin/dashboard');
        }

        $errors = [];
        $old = ['username' => ''];

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            Csrf::requireValid();

            $username = trim((string) ($_POST['username'] ?? ''));
            $password = (string) ($_POST['password'] ?? '');
            $old['username'] = $username;

            if ($username === '') {
                $errors['username'] = 'Vui lòng nhập tên đăng nhập.';
            }
            if ($password === '') {
                $errors['password'] = 'Vui lòng nhập mật khẩu.';
            }

            if (empty($errors)) {
                $userModel = new User();
                $user = $userModel->findByUsername($username);

                if (!$user || !password_verify($password, (string) $user['password'])) {
                    $errors['general'] = 'Tên đăng nhập hoặc mật khẩu không chính xác.';
                } elseif ($user['status'] !== 'ACTIVE') {
                    $errors['general'] = 'Tài khoản này đang bị khóa. Vui lòng liên hệ quản trị viên.';
                } else {
                    Auth::login($user);
                    Response::setFlash('success', 'Đăng nhập thành công. Xin chào ' . $user['full_name'] . '!');
                    Response::redirect('index.php?r=admin/dashboard');
                }
            }
        }

        Response::view('admin/auth/login', [
            'pageTitle' => 'Đăng nhập Quản trị — ProGear Studio',
            'errors'    => $errors,
            'old'       => $old,
        ], null);
    }

    public function logout(): void
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            Csrf::requireValid();
        }
        Auth::logout();
        Response::setFlash('success', 'Đã đăng xuất khỏi khu vực quản trị.');
        Response::redirect('index.php?r=admin/login');
    }
}

