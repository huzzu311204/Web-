<?php
declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Auth;
use App\Core\Response;
use App\Core\Validator;
use App\Models\User;

class AuthController
{
    /**
     * Dummy bcrypt hash sinh ngẫu nhiên riêng biệt để luôn gọi password_verify khi không tìm thấy user (chống dò tên tài khoản - Mục 11)
     */
    private const DUMMY_HASH = '$2y$10$8y/jRv.vu.q.jE6zc03E2.UvSagj3xbN8DJc3hSRNDLcKBHqqZEN.';
    private const MAX_ATTEMPTS = 5;
    private const LOCKOUT_SECONDS = 120;

    public function login(): void
    {
        if (Auth::check()) {
            Response::redirect('index.php?r=admin/products');
        }

        $errors = [];
        $old = ['username' => ''];

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $attemptsData = $_SESSION['_login_attempts'] ?? ['count' => 0, 'locked_until' => 0];
            $now = time();

            if ($attemptsData['locked_until'] > $now) {
                $remaining = $attemptsData['locked_until'] - $now;
                $errors['general'] = 'Bạn đã nhập sai quá nhiều lần. Vui lòng thử lại sau ' . $remaining . ' giây.';
            } else {
                $username = trim((string) ($_POST['username'] ?? ''));
                $password = (string) ($_POST['password'] ?? '');
                $old['username'] = $username;

                if ($username === '' || !Validator::isValidLength($username, 50, 1)) {
                    $errors['username'] = 'Vui lòng nhập tên đăng nhập hợp lệ.';
                }
                if ($password === '') {
                    $errors['password'] = 'Vui lòng nhập mật khẩu.';
                }

                if (empty($errors)) {
                    $userModel = new User();
                    $user = $userModel->findByUsername($username);

                    // Luôn gọi password_verify để thời gian phản hồi đồng nhất (Mục 11)
                    $hashToVerify = $user ? (string) $user['password'] : self::DUMMY_HASH;
                    $isPasswordValid = password_verify($password, $hashToVerify);

                    if (!$user || !$isPasswordValid || ($user['status'] ?? '') !== 'ACTIVE' || ($user['role'] ?? '') !== 'ADMIN') {
                        $count = (int) ($attemptsData['count'] ?? 0) + 1;
                        $lockedUntil = $count >= self::MAX_ATTEMPTS ? ($now + self::LOCKOUT_SECONDS) : 0;
                        $_SESSION['_login_attempts'] = [
                            'count'        => $count >= self::MAX_ATTEMPTS ? 0 : $count,
                            'locked_until' => $lockedUntil,
                        ];

                        // Thông báo lỗi chung, không tiết lộ username có tồn tại hay không (Mục 11)
                        $errors['general'] = 'Thông tin đăng nhập không hợp lệ hoặc tài khoản không có quyền truy cập.';
                    } else {
                        Auth::login($user);
                        Response::setFlash('success', 'Đăng nhập thành công. Xin chào ' . $user['full_name'] . '!');
                        Response::redirect('index.php?r=admin/products');
                    }
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
        Auth::logout();
        Response::setFlash('success', 'Đã đăng xuất khỏi khu vực quản trị.');
        Response::redirect('index.php?r=admin/login');
    }
}
