<?php
/*
 * 파일명: test_category_depth.php
 * 위치: /test_category_depth.php
 * 기능: 카테고리 구조와 제품 분포 확인
 * 작성일: 2025-08-01
 */

require_once(__DIR__ . '/smart_parser.php');

echo "=== 카테고리 깊이별 제품 분포 테스트 ===\n\n";

$parser = new SmartNanoParser();

// CPU 카테고리만 테스트
$mainCategory = 'CPU';
$mainCode = '10000000';

echo "메인 카테고리: {$mainCategory}\n";
echo str_repeat("=", 60) . "\n\n";

// 카테고리 구조 가져오기
$categoryData = $parser->getSingleCategory($mainCategory, $mainCode);

if (!$categoryData['success']) {
    die("카테고리 로드 실패: " . $categoryData['error']);
}

// 깊이별 카테고리 분류
$categoriesByDepth = [];
foreach ($categoryData['categories'] as $cat) {
    $depth = $cat['depth'];
    if (!isset($categoriesByDepth[$depth])) {
        $categoriesByDepth[$depth] = [];
    }
    $categoriesByDepth[$depth][] = $cat;
}

// 각 깊이별로 카테고리 표시
foreach ($categoriesByDepth as $depth => $cats) {
    echo "깊이 {$depth} (총 " . count($cats) . "개):\n";
    echo str_repeat("-", 40) . "\n";
    
    foreach (array_slice($cats, 0, 3) as $cat) {
        echo "  • " . $cat['path'];
        if ($cat['is_final']) echo " [최종]";
        echo "\n";
    }
    
    if (count($cats) > 3) {
        echo "  ... 외 " . (count($cats) - 3) . "개\n";
    }
    echo "\n";
}

// 각 깊이에서 샘플 제품 확인
echo "\n각 깊이별 제품 샘플:\n";
echo str_repeat("=", 60) . "\n\n";

foreach ($categoriesByDepth as $depth => $cats) {
    if (empty($cats)) continue;
    
    // 첫 번째 카테고리에서만 테스트
    $testCat = $cats[0];
    
    echo "깊이 {$depth} - {$testCat['path']}:\n";
    echo str_repeat("-", 40) . "\n";
    
    // 1페이지만 파싱
    $result = $parser->parseProductsWithDedup(
        $testCat['catcode'],
        $mainCategory,
        $testCat['path'],
        1
    );
    
    if ($result['success'] && count($result['products']) > 0) {
        $product = $result['products'][0]; // 첫 번째 제품만
        echo "  제품수: " . count($result['products']) . "개\n";
        echo "  샘플:\n";
        echo "    대분류: " . $product['대분류'] . "\n";
        echo "    소분류: " . $product['소분류'] . "\n";
        echo "    분류: " . $product['분류'] . "\n";
        echo "    제품명: " . substr($product['제품명'], 0, 50) . "...\n";
    } else {
        echo "  제품 없음\n";
    }
    echo "\n";
}

// 권장 사항
echo "\n권장 사항:\n";
echo str_repeat("=", 60) . "\n";
echo "• 깊이 0 (전체)는 건너뛰어야 함\n";
echo "• 깊이 1은 중간 카테고리일 가능성이 높음\n";
echo "• 깊이 2 이상 또는 is_final=true인 카테고리에서 파싱 권장\n";
echo "• 각 사이트의 구조에 따라 조정 필요\n";

echo "\n=== 테스트 완료 ===\n";
?>