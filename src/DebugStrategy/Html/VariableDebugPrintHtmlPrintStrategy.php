<?php

namespace lightla\VariableDebugger\DebugStrategy\Html;

use lightla\VariableDebugger\VariableDebugConfig;
use lightla\VariableDebugger\VariableDebugPrintStrategy;

class VariableDebugPrintHtmlPrintStrategy implements VariableDebugPrintStrategy
{
    public function printFromTrace(
        VariableDebugConfig $config,
        array $backtrace,
        ...$vars
    ): void
    {
        $caller = $backtrace[0];
        $caller['file'] = $this->calculateFilePathWithoutProjectRootPath($config, $caller['file']);

        $file = htmlspecialchars($caller['file']);
        $line = htmlspecialchars($caller['line']);

        echo '<div style="background:#2d2d2d;color:#d4d4d4;padding:15px;margin:15px 0;border:1px solid #444;border-radius:5px;font-family:Consolas,Monaco,monospace;font-size:12px;z-index:99999;">';
        echo '<div style="border-bottom:1px solid #555;padding-bottom:10px;margin-bottom:10px;color:#9cdcfe;">';
        echo "<strong>📁/</strong>{$file}:{$line}<br>";
        echo '</div>';

        $lineCount = 0;
        foreach ($vars as $var) {
            echo '<div style="margin:10px 0;padding:10px;border:1px solid #444;border-radius:3px;">';
            echo '<pre style="margin:0;font-family:inherit;white-space:pre-wrap;">';
            echo $this->formatVariable($config, $var, 0, '', $lineCount);
            echo '</pre>';
            echo '</div>';
        }
        echo '</div>';
    }

    private function formatVariable(
        VariableDebugConfig $config,
        $var,
        $depth = 0,
        $indent = '',
        int &$lineCount = 0,
        string $propertyPath = ''
    ): string
    {
        $output = '';
        $maxDepth = $config->resolveMaxDepthOrDefault();
        $maxLine = $config->resolveMaxLineOrDefault();

        if ($lineCount >= $maxLine) {
            return '<span style="color:#808080;">[Output Truncated]</span>';
        }

        if ($depth >= $maxDepth) {
            return '<span style="color:#808080;">[Max Depth Reached]</span>';
        }

        if (is_array($var)) {
            $count = count($var);
            if ($config->getShowValueType()) {
                $output .= '<span style="color:#4ec9b0;">array</span>(<span style="color:#b5cea8;">' . $count . '</span>) ';
            }

            if ($count === 0) {
                $output .= '<span style="color:#808080;">[]</span>';
            } else {
                $output .= '[<br>';
                $lineCount++;
                $i = 0;

                $showFirstArrayElement = $config->resolveShowArrayModeOrDefault()->isShowFirstElement();
                $showKeyOnly = $config->resolveShowKeyOnlyOrDefault();
                $ignoredShowKeyPaths = $config->resolveIgnoredShowKeyPropertiesOrDefault();

                // Build filter context for array keys
                $properties = $config->resolveIncludedPropertiesOrDefault();
                $withoutProperties = $config->resolveExcludedPropertiesOrDefault();
                $context = $this->buildPropertyContext($properties, $withoutProperties, $propertyPath);

                $excludedCount = 0;
                foreach ($var as $key => $value) {
                    // Filter array keys using same logic as object properties
                    if (!$this->shouldShowProperty((string)$key, $context)) {
                        $excludedCount++;
                        continue;
                    }

                    if ($lineCount >= $maxLine) {
                        $remaining = $count - $i;
                        $output .= $indent . '  <span style="color:#808080;">... (and ' . $remaining . ' hidden due to line limit)</span><br>';
                        break;
                    }

                    $newIndent = $indent . '  ';
                    $output .= $newIndent;

                    if (is_string($key)) {
                        $output .= '<span style="color:#ce9178;">"' . htmlspecialchars($key) . '"</span>';
                    } else {
                        $output .= '<span style="color:#b5cea8;">' . $key . '</span>';
                    }

                    $nextPath = $this->getNextPath($propertyPath, (string)$key);
                    if ($this->shouldShowValue($showKeyOnly, $ignoredShowKeyPaths, $nextPath)) {
                        $output .= ' <span style="color:#d4d4d4;">=></span> ';
                        $output .= $this->formatVariable($config, $value, $depth + 1, $newIndent, $lineCount, $nextPath);
                    } else {
                        $output .= ' <span style="color:#d4d4d4;">=></span> <span style="color:#808080;">[hidden]</span>';
                    }

                    if ($i < $count - 1) {
                        $output .= '<span style="color:#d4d4d4;">,</span>';
                    }
                    $output .= '<br>';
                    $lineCount++;
                    $i++;

                    if ($showFirstArrayElement) {
                        if ($count > 1) {
                            $othersCount = $count - $i;
                            $output .= $newIndent . "<span style='color:#808080;'>... (and {$othersCount} others)</span><br>";
                            $lineCount++;
                        }
                        break;
                    }
                }
                if ($config->resolveShowExcludedCount() && $excludedCount > 0) {
                    $output .= $newIndent . "<span style='color:#808080;'># [{$excludedCount} excluded]</span><br>";
                    $lineCount++;
                }
                $output .= $indent . ']';
            }
        } elseif (is_object($var)) {
            $reflection = new \ReflectionClass($var);
            $className = $reflection->getName();

            if ($config->getShowValueType()) {
                $output .= '<span style="color:#4ec9b0;">object</span>(<span style="color:#c586c0;">' . htmlspecialchars($className) . '</span>) {<br>';
            } else {
                $output .= '<span style="color:#c586c0;">' . htmlspecialchars($className) . '</span> {<br>';
            }
            $lineCount++;

            // Check buildLaterClassProperties
            $buildLaterProperties = $config->resolveBuildLaterClassPropertiesOrDefault();
            foreach ($buildLaterProperties as $buildClassName => $callback) {
                if ($var instanceof $buildClassName) {
                    // Gọi callback để lấy properties
                    $customProperties = $callback($var);
                    
                    // Render custom properties
                    foreach ($customProperties as $propName => $propValue) {
                        if ($lineCount >= $maxLine) {
                            $output .= $indent . '  <span style="color:#808080;">... (truncated)</span><br>';
                            return $output . $indent . '}';
                        }
                        
                        $output .= $indent . '  <span style="color:#9cdcfe;">' . htmlspecialchars($propName) . '</span>: ';
                        $output .= $this->formatVariable(
                            $config, $propValue, $depth + 1, $indent . '  ', $lineCount, $this->getNextPath($propertyPath, $propName)
                        ) . '<br>';
                        $lineCount++;
                    }
                    
                    return $output . $indent . '}';
                }
            }

            // Normal Reflection logic (existing code)
            $objectVars = get_object_vars($var);
            $hasAnyProperty = false;

            // Class filter luôn áp dụng cho object thuộc class đó (object = root của chính nó)
            $classIncludes = $this->getClassSpecificIncludes($var, $config);
            
            // Get global includes/excludes
            $globalIncludes = $config->resolveIncludedPropertiesOrDefault();
            $globalExcludes = $config->resolveExcludedPropertiesOrDefault();
            
            $showKeyOnly = $config->resolveShowKeyOnlyOrDefault();
            $ignoredShowKeyPaths = $config->resolveIgnoredShowKeyPropertiesOrDefault();

            // Conflict check
            $hasConflict = $classIncludes !== null && empty($classIncludes);

            // Loop qua class hierarchy và print trực tiếp
            $current = $reflection;
            $printedProps = [];
            $excludedCount = 0;

            while ($current) {
                if ($lineCount >= $maxLine) {
                    $output .= $indent . '  <span style="color:#808080;">... (truncated)</span><br>';
                    return $output . $indent . '}';
                }

                foreach ($current->getProperties() as $prop) {
                    $propName = $prop->getName();
                    if (isset($printedProps[$propName])) {
                        continue;
                    }

                    // Step 1: Class-specific filter
                    if ($classIncludes !== null && !in_array($propName, $classIncludes)) {
                        $printedProps[$propName] = true;
                        $excludedCount++;
                        continue;
                    }
                    
                    // Step 2: Global filter (check từ propertyPath của object)
                    $objectPropertyPath = $propName;
                    if (!$this->shouldShowPropertyGlobalFromObjectRoot($objectPropertyPath, $propertyPath, $globalIncludes, $globalExcludes)) {
                        $printedProps[$propName] = true;
                        $excludedCount++;
                        continue;
                    }

                    $printedProps[$propName] = true;
                    $hasAnyProperty = true;

                    if ($lineCount >= $maxLine) {
                        $output .= $indent . '  <span style="color:#808080;">... (truncated)</span><br>';
                        return $output . $indent . '}';
                    }

                    $prop->setAccessible(true);

                    $nextPath = $this->getNextPath($propertyPath, $propName);

                    if ($config->getShowDetailAccessModifiers()) {
                        $visibility = $prop->isPrivate() ? 'private' : ($prop->isProtected() ? 'protected' : 'public');
                        $output .= $indent . '  <span style="color:#c586c0;">' . $visibility . '</span> ';
                        $output .= '<span style="color:#9cdcfe;">' . $prop->getName() . '</span>';
                        
                        if ($this->shouldShowValue($showKeyOnly, $ignoredShowKeyPaths, $nextPath)) {
                            $output .= ': ';
                        }
                    } else {
                        $visibility = $prop->isPrivate() ? '-' : ($prop->isProtected() ? '#' : '+');
                        $output .= $indent . '  <span style="color:#c586c0;">' . $visibility . '</span>';
                        $output .= '<span style="color:#9cdcfe;">' . $prop->getName() . '</span>';
                        
                        if ($this->shouldShowValue($showKeyOnly, $ignoredShowKeyPaths, $nextPath)) {
                            $output .= ': ';
                        }
                    }

                    if (!$this->shouldShowValue($showKeyOnly, $ignoredShowKeyPaths, $nextPath)) {
                        $output .= '<br>';
                        $lineCount++;
                    } else {
                        try {
                            if (!$prop->isInitialized($var)) {
                                $output .= '<span style="color:#808080;">[uninitialized]</span>';
                            } else {
                                $propValue = $prop->getValue($var);
                                
                                // Check property show value mode từ classIncludes
                                $fullPath = $this->getNextPath($propertyPath, $propName);
                                $showValueMode = $this->getPropertyShowValueMode($var, $propName, $fullPath, $classIncludes, $config);
                                
                                if ($showValueMode->isShowTypeOnly()) {
                                    // Chỉ show type
                                    $output .= $this->formatTypeOnly($propValue);
                                } else {
                                    // Show full detail
                                    $output .= $this->formatVariable($config, $propValue, $depth + 1, $indent . '  ', $lineCount, $nextPath);
                                }
                            }
                        } catch (\Exception $e) {
                            $output .= '<span style="color:#ff6b6b;">Error: ' . htmlspecialchars($e->getMessage()) . '</span>';
                        }

                        $output .= '<br>';
                        $lineCount++;
                    }
                }

                $current = $current->getParentClass();
            }

            // Dynamic properties
            foreach ($objectVars as $propName => $propValue) {
                if (isset($printedProps[$propName])) {
                    continue;
                }

                // Step 1: Class-specific filter
                if ($classIncludes !== null && !in_array($propName, $classIncludes)) {
                    $excludedCount++;
                    continue;
                }
                
                // Step 2: Global filter
                $objectPropertyPath = $propName;
                if (!$this->shouldShowPropertyGlobalFromObjectRoot($objectPropertyPath, $propertyPath, $globalIncludes, $globalExcludes)) {
                    $excludedCount++;
                    continue;
                }

                $hasAnyProperty = true;

                if ($lineCount >= $maxLine) {
                    $output .= $indent . '  <span style="color:#808080;">... (truncated)</span><br>';
                    return $output . $indent . '}';
                }

                $nextPath = $this->getNextPath($propertyPath, $propName);

                if ($config->getShowDetailAccessModifiers()) {
                    $output .= $indent . '  <span style="color:#c586c0;">public</span> ';
                    $output .= '<span style="color:#9cdcfe;">"' . htmlspecialchars($propName) . '"</span>';
                    
                    if ($this->shouldShowValue($showKeyOnly, $ignoredShowKeyPaths, $nextPath)) {
                        $output .= ': ';
                    }
                } else {
                    $output .= $indent . '  <span style="color:#c586c0;">+</span>';
                    $output .= '<span style="color:#9cdcfe;">"' . htmlspecialchars($propName) . '"</span>';
                    
                    if ($this->shouldShowValue($showKeyOnly, $ignoredShowKeyPaths, $nextPath)) {
                        $output .= ': ';
                    }
                }

                if (!$this->shouldShowValue($showKeyOnly, $ignoredShowKeyPaths, $nextPath)) {
                    $output .= '<br>';
                } else {
                    // Check property show value mode từ classIncludes
                    $fullPath = $this->getNextPath($propertyPath, $propName);
                    $showValueMode = $this->getPropertyShowValueMode($var, $propName, $fullPath, $classIncludes, $config);
                    
                    if ($showValueMode->isShowTypeOnly()) {
                        // Chỉ show type
                        $output .= $this->formatTypeOnly($propValue);
                    } else {
                        // Show full detail
                        $output .= $this->formatVariable($config, $propValue, $depth + 1, $indent . '  ', $lineCount, $nextPath);
                    }
                    
                    $output .= '<br>';
                }
                $lineCount++;
            }

            if (!$hasAnyProperty) {
                if ($hasConflict) {
                    $output .= $indent . '  <span style="color:#808080;">[Empty] # excluded properties contain all included properties</span><br>';
                } else {
                    $output .= $indent . '  <span style="color:#808080;"># No properties</span><br>';
                }
                $lineCount++;
            } elseif ($config->resolveShowExcludedCount() && $excludedCount > 0) {
                $output .= $indent . '  <span style="color:#808080;"># [' . $excludedCount . ' excluded]</span><br>';
                $lineCount++;
            }

            $output .= $indent . '}';
        } elseif (is_string($var)) {
            $len = strlen($var);
            if ($config->getShowValueType()) {
                $output .= '<span style="color:#4ec9b0;">string</span>(<span style="color:#b5cea8;">' . $len . '</span>) ';
            }
            $output .= '<span style="color:#ce9178;">"' . htmlspecialchars($var) . '"</span>';
        } elseif (is_int($var)) {
            if ($config->getShowValueType()) {
                $output .= '<span style="color:#4ec9b0;">int</span>(<span style="color:#b5cea8;">' . $var . '</span>)';
            } else {
                $output .= '<span style="color:#b5cea8;">' . $var . '</span>';
            }
        } elseif (is_float($var)) {
            if ($config->getShowValueType()) {
                $output .= '<span style="color:#4ec9b0;">float</span>(<span style="color:#b5cea8;">' . $var . '</span>)';
            } else {
                $output .= '<span style="color:#b5cea8;">' . $var . '</span>';
            }
        } elseif (is_bool($var)) {
            if ($config->getShowValueType()) {
                $output .= '<span style="color:#4ec9b0;">bool</span>(<span style="color:#569cd6;">' . ($var ? 'true' : 'false') . '</span>)';
            } else {
                $output .= '<span style="color:#569cd6;">' . ($var ? 'true' : 'false') . '</span>';
            }
        } elseif (is_null($var)) {
            $output .= '<span style="color:#569cd6;">null</span>';
        } else {
            if ($config->getShowValueType()) {
                $output .= '<span style="color:#4ec9b0;">' . gettype($var) . '</span> ';
            }
            $output .= htmlspecialchars(print_r($var, true));
        }

        return $output;
    }

    private function calculateFilePathWithoutProjectRootPath(
        VariableDebugConfig $config,
        string $filePath
    ): string
    {
        if ($config->getProjectRootPath()) {
            return str_replace($config->getProjectRootPath() . '/', '', $filePath);
        }

        return ltrim($filePath, '/');
    }

    private function filterParentPaths(array $paths): array
    {
        if (empty($paths)) {
            return [];
        }

        sort($paths);
        $result = [];
        $lastAdded = null;

        foreach ($paths as $path) {
            if ($lastAdded === null || !str_starts_with($path, $lastAdded . '.')) {
                $result[] = $path;
                $lastAdded = $path;
            }
        }

        return $result;
    }

    /**
     * Extract property paths from properties array
     * Input: ['field1' => SHOW_DETAIL, 'field2' => SHOW_TYPE_ONLY] hoặc ['field1', 'field2']
     * Output: ['field1', 'field2']
     */
    private function extractPropertyPaths(array $properties): array
    {
        $paths = [];
        foreach ($properties as $key => $value) {
            if ($value instanceof \lightla\VariableDebugger\Config\VariableDebugClassPropertyShowValueMode) {
                // Key là property path
                $paths[] = $key;
            } else {
                // Value là property path (numeric key)
                $paths[] = $value;
            }
        }
        return $paths;
    }

    /**
     * Resolve includes/excludes cho object dựa trên class
     */
    private function resolveIncludesForClass(object $var, VariableDebugConfig $config): array
    {
        $classIncludes = $config->resolveIncludedClassPropertiesOrDefault();
        $classExcludes = $config->resolveExcludedClassPropertiesOrDefault();
        $globalIncludes = $config->resolveIncludedPropertiesOrDefault();
        $globalExcludes = $config->resolveExcludedPropertiesOrDefault();
        
        // Tìm class-specific rule (check instanceof)
        $specificIncludes = null;
        $specificExcludes = null;
        
        foreach ($classIncludes as $className => $paths) {
            if ($var instanceof $className) {
                $specificIncludes = $paths;
                break;
            }
        }
        
        foreach ($classExcludes as $className => $paths) {
            if ($var instanceof $className) {
                $specificExcludes = $paths;
                break;
            }
        }
        
        // Merge logic
        if ($specificIncludes !== null) {
            // Có class-specific includes
            if (empty($globalIncludes)) {
                // Global empty → dùng class-specific
                $finalIncludes = $specificIncludes;
            } else {
                // Global có → intersection (chỉ show những gì có trong cả 2)
                $finalIncludes = array_values(array_intersect($specificIncludes, $globalIncludes));
            }
        } else {
            // Không có class-specific → dùng global
            $finalIncludes = $globalIncludes;
        }
        
        // Excludes: merge cả 2 (union)
        $finalExcludes = array_unique(array_merge(
            $specificExcludes ?? [],
            $globalExcludes
        ));
        
        return [$finalIncludes, $finalExcludes];
    }

    private function buildPropertyContext(array $properties, array $withoutProperties, string $currentPath = ''): array
    {
        // Normalize properties: extract keys only (property paths)
        $properties = $this->extractPropertyPaths($properties);
        
        $hasIncludeAll = empty($properties);
        
        // Optimize paths
        $finalIncludes = $this->filterParentPaths($properties);
        $finalExcludes = $this->filterParentPaths($withoutProperties);
        
        // Calculate conflict: includes bị excludes khử hết
        $remainingIncludes = [];
        foreach ($finalIncludes as $include) {
            $isExcluded = false;
            foreach ($finalExcludes as $exclude) {
                if ($include === $exclude || str_starts_with($include, $exclude . '.')) {
                    $isExcluded = true;
                    break;
                }
            }
            if (!$isExcluded) {
                $remainingIncludes[] = $include;
            }
        }
        
        $isConflictShow = !$hasIncludeAll && empty($remainingIncludes);
        
        // Build context for current level
        $include = [];
        $exclude = [];
        $showAllNested = false; // Flag: nếu path match chính xác, show all nested
        
        $currentParts = $currentPath === '' ? [] : explode('.', $currentPath);
        $currentDepth = count($currentParts);

        foreach ($remainingIncludes as $path) {
            $parts = explode('.', $path);
            
            if ($this->pathStartsWith($parts, $currentParts)) {
                if (count($parts) === $currentDepth) {
                    // Path match CHÍNH XÁC current path → show ALL nested
                    $showAllNested = true;
                    break;
                } elseif (count($parts) > $currentDepth) {
                    // Path còn nested → include key để đi sâu
                    $include[$parts[$currentDepth]] = true;
                }
            } elseif ($this->pathStartsWith($currentParts, $parts)) {
                // Current path là con của included path → show all
                $showAllNested = true;
                break;
            }
        }

        // Nếu showAllNested, không cần check include list nữa
        if ($showAllNested) {
            $include = [];
        }

        foreach ($finalExcludes as $path) {
            $parts = explode('.', $path);
            
            if ($this->pathStartsWith($parts, $currentParts) && count($parts) === $currentDepth + 1) {
                $exclude[$parts[$currentDepth]] = true;
            }
        }

        return [
            'include' => $include,
            'exclude' => $exclude,
            'hasIncludeAll' => $hasIncludeAll || $showAllNested,
            'isConflictShow' => $isConflictShow
        ];
    }

    private function pathStartsWith(array $path, array $prefix): bool
    {
        if (count($prefix) > count($path)) {
            return false;
        }
        for ($i = 0; $i < count($prefix); $i++) {
            if ($path[$i] !== $prefix[$i]) {
                return false;
            }
        }
        return true;
    }

    private function shouldShowProperty(string $propName, array $context): bool
    {
        // Nếu trong exclude list, không show
        if (isset($context['exclude'][$propName])) {
            return false;
        }

        // Nếu hasIncludeAll (input empty), show tất cả (trừ excluded)
        if ($context['hasIncludeAll']) {
            return true;
        }

        // Nếu có include list, chỉ show nếu trong list
        return isset($context['include'][$propName]);
    }

    private function getNextPath(string $currentPath, string $key): string
    {
        return $currentPath === '' ? $key : $currentPath . '.' . $key;
    }

    private function shouldShowValue(bool $showKeyOnly, array $ignoredPaths, string $currentPath): bool
    {
        if (!$showKeyOnly) {
            return true; // showKeyOnly = false → luôn show value
        }

        // showKeyOnly = true
        // Nếu ignoredPaths empty → chỉ show key cho tất cả
        if (empty($ignoredPaths)) {
            return false;
        }

        // Nếu có ignoredPaths → CHỈ show value cho paths trong list (và children)
        $currentParts = $currentPath === '' ? [] : explode('.', $currentPath);
        
        foreach ($ignoredPaths as $ignoredPath) {
            $ignoredParts = explode('.', $ignoredPath);
            
            // Check exact match hoặc currentPath là con của ignoredPath
            if ($currentPath === $ignoredPath || $this->pathStartsWith($currentParts, $ignoredParts)) {
                return true; // Show value
            }
            
            // Check nếu ignoredPath là con của currentPath → cần đi sâu vào
            if ($this->pathStartsWith($ignoredParts, $currentParts) && count($ignoredParts) > count($currentParts)) {
                return true; // Show value để đi sâu vào
            }
        }

        return false; // Chỉ show key
    }

    /**
     * Get class-specific includes (chỉ root-level properties)
     */
    private function getClassSpecificIncludes(object $var, VariableDebugConfig $config): ?array
    {
        $classIncludes = $config->resolveIncludedClassPropertiesOrDefault();
        
        foreach ($classIncludes as $className => $properties) {
            if ($var instanceof $className) {
                // Properties đã normalized: ['field1' => SHOW_DETAIL, 'field2' => SHOW_TYPE_ONLY]
                // Chỉ lấy keys (property names)
                return array_keys($properties);
            }
        }
        
        return null;
    }

    /**
     * Check global filter từ object root
     */
    private function shouldShowPropertyGlobalFromObjectRoot(
        string $objectPropertyPath, 
        string $propertyPath, 
        array $globalIncludes, 
        array $globalExcludes
    ): bool {
        // Build full path từ root
        $fullPath = $this->getNextPath($propertyPath, $objectPropertyPath);
        
        // Check global filter với full path
        $context = $this->buildPropertyContext($globalIncludes, $globalExcludes, $propertyPath);
        
        return $this->shouldShowProperty($objectPropertyPath, $context);
    }
    
    private function getPropertyShowValueMode(
        object $var, 
        string $propName,
        string $fullPath,
        ?array $classIncludes, 
        VariableDebugConfig $config
    ): \lightla\VariableDebugger\Config\VariableDebugClassPropertyShowValueMode {
        // Step 1: Get mode from class-specific properties
        $mode = \lightla\VariableDebugger\Config\VariableDebugClassPropertyShowValueMode::SHOW_DETAIL;
        
        $allClassProperties = $config->resolveIncludedClassPropertiesOrDefault();
        foreach ($allClassProperties as $className => $properties) {
            if ($var instanceof $className && isset($properties[$propName])) {
                $mode = $properties[$propName];
                break;
            }
        }
        
        // Step 2: Global properties decorate/override
        $globalProperties = $config->resolveIncludedPropertiesOrDefault();
        
        // Check exact match với full path
        if (isset($globalProperties[$fullPath])) {
            $value = $globalProperties[$fullPath];
            if ($value instanceof \lightla\VariableDebugger\Config\VariableDebugClassPropertyShowValueMode) {
                $mode = $value; // Override
            }
        }
        // Check với property name đơn (nếu là root level)
        else if (isset($globalProperties[$propName])) {
            $value = $globalProperties[$propName];
            if ($value instanceof \lightla\VariableDebugger\Config\VariableDebugClassPropertyShowValueMode) {
                $mode = $value; // Override
            }
        }
        
        return $mode;
    }
    
    private function formatTypeOnly(mixed $value): string
    {
        if (is_object($value)) {
            $className = htmlspecialchars(get_class($value));
            return '<span style="color:#4ec9b0;">object</span>(<span style="color:#c586c0;">' . $className . '</span>)';
        }
        
        if (is_array($value)) {
            $count = count($value);
            return '<span style="color:#4ec9b0;">array</span>(<span style="color:#b5cea8;">' . $count . '</span>)';
        }
        
        if (is_string($value)) {
            $len = strlen($value);
            return '<span style="color:#4ec9b0;">string</span>(<span style="color:#b5cea8;">' . $len . '</span>)';
        }
        
        if (is_int($value)) {
            return '<span style="color:#4ec9b0;">int</span>';
        }
        
        if (is_float($value)) {
            return '<span style="color:#4ec9b0;">float</span>';
        }
        
        if (is_bool($value)) {
            return '<span style="color:#4ec9b0;">bool</span>';
        }
        
        if (is_null($value)) {
            return '<span style="color:#569cd6;">null</span>';
        }
        
        return '<span style="color:#4ec9b0;">' . htmlspecialchars(gettype($value)) . '</span>';
    }
}
