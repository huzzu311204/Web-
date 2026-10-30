<?php
declare(strict_types=1);

// Chuyển tiếp về public/index.php nếu người dùng truy cập http://localhost/camera-rental/
chdir(__DIR__ . '/public');
require __DIR__ . '/public/index.php';

