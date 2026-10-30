<?php
declare(strict_types=1);

namespace App\Services;

use finfo;
use RuntimeException;

class ImageUploader
{
    private const MAX_BYTES = 2 * 1024 * 1024; // 2 MB
    private const ALLOWED_MIMES = [
        'image/jpeg' => 'jpg',
        'image/png'  => 'png',
        'image/webp' => 'webp',
    ];

    /**
     * Upload ảnh sản phẩm an toàn (Mục 6.7 & TC25):
     * - Chỉ nhận jpg, png, webp (tuyệt đối không nhận SVG hoặc PHP ngụy trang)
     * - Kiểm tra MIME bằng finfo và xác nhận ảnh thực bằng getimagesize()
     * - Đổi tên bằng bin2hex(random_bytes(16))
     * - Lưu vào public/uploads/products/
     */
    public static function uploadProductImage(array $file): string
    {
        $error = (int) ($file['error'] ?? UPLOAD_ERR_NO_FILE);
        if ($error !== UPLOAD_ERR_OK) {
            throw new RuntimeException('Tải tệp lên không thành công (mã lỗi: ' . $error . ').');
        }

        $size = (int) ($file['size'] ?? 0);
        if ($size <= 0 || $size > self::MAX_BYTES) {
            throw new RuntimeException('Dung lượng ảnh phải lớn hơn 0 và không vượt quá 2 MB.');
        }

        $tmpName = (string) ($file['tmp_name'] ?? '');
        if ($tmpName === '' || !is_file($tmpName)) {
            throw new RuntimeException('Không tìm thấy tệp tạm thời để kiểm tra.');
        }

        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mime = (string) $finfo->file($tmpName);

        if (!isset(self::ALLOWED_MIMES[$mime])) {
            throw new RuntimeException('Định dạng tệp không hợp lệ. Hệ thống chỉ chấp nhận ảnh JPG, PNG hoặc WebP (không nhận SVG).');
        }

        $imgInfo = @getimagesize($tmpName);
        if ($imgInfo === false || empty($imgInfo[0]) || empty($imgInfo[1])) {
            throw new RuntimeException('Tệp tải lên không phải là hình ảnh hợp lệ.');
        }

        $ext = self::ALLOWED_MIMES[$mime];
        $filename = bin2hex(random_bytes(16)) . '.' . $ext;

        $targetDir = dirname(__DIR__, 2) . '/public/uploads/products';
        if (!is_dir($targetDir)) {
            mkdir($targetDir, 0775, true);
        }

        $destination = $targetDir . '/' . $filename;
        if (!move_uploaded_file($tmpName, $destination)) {
            throw new RuntimeException('Không thể lưu ảnh vào thư mục đích.');
        }

        return 'uploads/products/' . $filename;
    }

    /**
     * Xóa ảnh cũ đã upload trong uploads/products/ (không xóa ảnh mẫu trong images/products/)
     */
    public static function deleteOldImage(?string $imagePath): void
    {
        if (!$imagePath || !str_starts_with($imagePath, 'uploads/products/')) {
            return;
        }

        $fullPath = dirname(__DIR__, 2) . '/public/' . ltrim($imagePath, '/');
        if (is_file($fullPath)) {
            @unlink($fullPath);
        }
    }
}
