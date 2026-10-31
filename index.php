<?php
declare(strict_types=1);

// Chuyển hướng sang public/index.php để các đường dẫn tương đối (css/, js/, images/, uploads/) hoạt động đúng
$query = isset($_SERVER['QUERY_STRING']) && $_SERVER['QUERY_STRING'] !== ''
    ? '?' . $_SERVER['QUERY_STRING']
    : '';

header('Location: public/index.php' . $query);
exit;


