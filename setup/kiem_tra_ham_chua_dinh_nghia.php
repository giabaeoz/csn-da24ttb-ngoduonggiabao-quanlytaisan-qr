<?php
/**
 * Script phân tích tĩnh tìm các hàm được gọi trong mã nguồn nhưng chưa được định nghĩa.
 */

declare(strict_types=1);

require_once dirname(__DIR__) . '/src/app/core/khoi-dong.php';
require_once dirname(__DIR__) . '/src/app/services/danh-muc-service.php';
require_once dirname(__DIR__) . '/src/app/services/tai-san-service.php';
require_once dirname(__DIR__) . '/src/app/services/nguoi-muon-service.php';
require_once dirname(__DIR__) . '/src/app/services/muon-tra-service.php';
require_once dirname(__DIR__) . '/src/app/services/dieu-chuyen-service.php';
require_once dirname(__DIR__) . '/src/app/services/kiem-ke-service.php';
require_once dirname(__DIR__) . '/src/app/services/su-co-service.php';
require_once dirname(__DIR__) . '/src/app/services/thong-ke-service.php';

$allDefined = get_defined_functions();
$definedMap = array_flip(array_map('strtolower', array_merge($allDefined['internal'], $allDefined['user'])));

// Các hàm/cấu trúc ngôn ngữ đặc biệt
$builtinKeywords = [
    'echo', 'print', 'empty', 'isset', 'unset', 'die', 'exit', 'eval', 'list',
    'include', 'include_once', 'require', 'require_once', 'array',
];
foreach ($builtinKeywords as $k) {
    $definedMap[$k] = true;
}

$iterator = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator(dirname(__DIR__) . '/src', FilesystemIterator::SKIP_DOTS)
);

$chuaDinhNghia = [];

foreach ($iterator as $tep) {
    if (strtolower($tep->getExtension()) !== 'php') {
        continue;
    }

    $duongDan = $tep->getPathname();
    $code = file_get_contents($duongDan);
    $tokens = token_get_all($code);

    $count = count($tokens);
    for ($i = 0; $i < $count; $i++) {
        $token = $tokens[$i];

        if (is_array($token) && $token[0] === T_STRING) {
            $name = $token[1];
            $line = $token[2];

            // Tìm token kế tiếp bỏ qua khoảng trắng
            $next = null;
            for ($j = $i + 1; $j < $count; $j++) {
                if (is_array($tokens[$j]) && $tokens[$j][0] === T_WHITESPACE) {
                    continue;
                }
                $next = $tokens[$j];
                break;
            }

            // Tìm token phía trước bỏ qua khoảng trắng
            $prev = null;
            for ($j = $i - 1; $j >= 0; $j--) {
                if (is_array($tokens[$j]) && $tokens[$j][0] === T_WHITESPACE) {
                    continue;
                }
                $prev = $tokens[$j];
                break;
            }

            // Nếu tiếp theo là dấu '(' và không phải là function def, method call ($obj->), static call (Class::)
            if ($next === '(') {
                if (is_array($prev)) {
                    if (in_array($prev[0], [T_FUNCTION, T_OBJECT_OPERATOR, T_NULLSAFE_OBJECT_OPERATOR, T_DOUBLE_COLON, T_NEW], true)) {
                        continue;
                    }
                }

                $lowerName = strtolower($name);
                if (!isset($definedMap[$lowerName])) {
                    $chuaDinhNghia[] = [
                        'file' => $duongDan,
                        'line' => $line,
                        'function' => $name,
                    ];
                }
            }
        }
    }
}

echo "=== KẾT QUẢ QUÉT HÀM CHƯA ĐỊNH NGHĨA ===\n";
if (empty($chuaDinhNghia)) {
    echo "TẤT CẢ CÁC HÀM ĐƯỢC GỌI ĐỀU ĐÃ ĐƯỢC ĐỊNH NGHĨA CHÍNH XÁC!\n";
} else {
    echo "Phát hiện " . count($chuaDinhNghia) . " hàm chưa được định nghĩa:\n";
    foreach ($chuaDinhNghia as $item) {
        echo "  [LỖI] {$item['file']}:{$item['line']} -> {$item['function']}()\n";
    }
}

