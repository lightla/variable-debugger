ok rồi đó, nhớ handle theo logic sau\
đầu tiên lọc ra danh sách cuối cùng\
function filterParentPaths_Optimized(array $paths): array
{
if (empty($paths)) {
return [];
}

    // 1. Sắp xếp mảng
    sort($paths);

    $result = [];
    $lastAdded = null;

    foreach ($paths as $path) {
        // 2. Nếu kết quả rỗng hoặc đường dẫn hiện tại không phải là con của đường dẫn cuối cùng đã thêm
        //    str_starts_with($path, $lastAdded . '.') kiểm tra xem 'app.config' có bắt đầu bằng 'app.' không
        if ($lastAdded === null || !str_starts_with($path, $lastAdded . '.')) {
            $result[] = $path;
            $lastAdded = $path; // Cập nhật đường dẫn gốc mới nhất
        }
    }

    return $result;
}

$includes = $config->resolveIncludedPropertiesOrDefault();
// $includes = ['app', 'app.config', 'code.t1.01', 'code.t2', 'code.t1', 'code.t2.g', 'tmp'];
$hasIncludeAll = empty($includes);
$includes = filterParentPaths_Optimized($includes); // result: app, code.t1, code.t2, tmp

$excludes = ['app.t1', 'code', 'tmp', 'code.t1'];
$excludes = filterParentPaths_Optimized($excludes); // result: app.t1, code, tmp

$flippedIncludes = array_flip($includes);
foreach($includes as $include) {
    foreach($excludes as $exclude) {
        // tmp = tmp
        // code.t1 start with code
        if ($include == $exclude || str_starts_with($include, $exclude)) {
            unset($flippedIncludes[$include]);
        }
    }
}

$finalIncludes = array_flip($flippedIncludes)
$isConflictShow = !$hasIncludeAll && empty($flippedIncludes);
finalExclude = $excludes;




