<?php

namespace lightla\VariableDebugger\DebugStrategy\Cli;

use lightla\VariableDebugger\VariableDebugConfig;
use lightla\VariableDebugger\VariableDebugPrintStrategy;

class VariableDebugPrintCliPrintStrategy implements VariableDebugPrintStrategy
{
    public function printFromTrace(
        VariableDebugConfig $config,
        array $backtrace,
        ...$vars
    ): void {
        $colorTheme = $config->resolveCliThemeOrDefault();

        $caller = $backtrace[0];
        $file = $this->calculateFilePathWithoutProjectRootPath($config, $caller['file']);
        $line = $caller['line'];

        $outputLines = [];
        $outputLines[] = $colorTheme->punctuation . $this->strHeaderFooterSeparatorLine();
        $outputLines[] =
            $colorTheme->punctuation . "📁" .
            $colorTheme->filePath . "/{$file}" .
            $colorTheme->punctuation . ":" .
            $colorTheme->lineNumber . $line;
        $outputLines[] = $colorTheme->punctuation . str_repeat('·', 5);

        $lineCount = 0;
        foreach ($vars as $i => $var) {
            if ($i > 0) {
                $outputLines[] = $colorTheme->punctuation . $this->strVarSeparatorLine();
            }

            $formattedVar = $this->formatVariable($config, $colorTheme, $var, 0, '', $lineCount);
            $lines = explode(PHP_EOL, $formattedVar);
            foreach ($lines as $lineContent) {
                $outputLines[] = $lineContent;
            }
        }

        $this->printFullWidth($colorTheme, $outputLines);
    }

    private function printFullWidth(
        VariableDebugCliColorTheme $colorTheme,
        array $lines
    ): void
    {
        foreach ($lines as $line) {
            echo $line . $colorTheme->reset . PHP_EOL;
        }

        echo $colorTheme->reset;
    }

    private function formatVariable(
        VariableDebugConfig $config,
        VariableDebugCliColorTheme $colorTheme,
        mixed $var,
        int $depth = 0,
        string $indent = '',
        int &$lineCount = 0,
        string $propertyPath = ''
    ): string {
        $maxDepth = $config->resolveMaxDepthOrDefault();
        $maxLine = $config->resolveMaxLineOrDefault();

        if ($lineCount >= $maxLine) {
            return $colorTheme->comment . '[Output Truncated]';
        }

        if ($depth > $maxDepth) {
            return $colorTheme->comment . '[Max Depth Reached]';
        }

        if (is_array($var)) {
            return $this->formatArray($config, $colorTheme, $var, $depth, $indent, $lineCount, $propertyPath);
        }

        if (is_object($var)) {
            return $this->formatObject($config, $colorTheme, $var, $depth, $indent, $lineCount, $propertyPath);
        }

        $output = '';

        // STRING
        if (is_string($var)) {
            if ($config->getShowValueType()) {
                $output .= $colorTheme->type . 'string'
                    . $colorTheme->punctuation . '('
                    . $colorTheme->number . strlen($var)
                    . $colorTheme->punctuation . ') ';
            }

            return $output
                . $colorTheme->string
                . '"' . addcslashes($var, '"\\') . '"';
        }

        // INT / FLOAT
        if (is_int($var) || is_float($var)) {
            $type = is_int($var) ? 'int' : 'float';

            if ($config->getShowValueType()) {
                $output .= $colorTheme->type . $type . $colorTheme->punctuation . '(';
            }

            $output .= $colorTheme->number . $var;

            if ($config->getShowValueType()) {
                $output .= $colorTheme->punctuation . ')';
            }

            return $output;
        }

        // BOOL
        if (is_bool($var)) {
            if ($config->getShowValueType()) {
                $output .= $colorTheme->type . 'bool' . $colorTheme->punctuation . '(';
            }

            $output .= $colorTheme->boolNull . ($var ? 'true' : 'false');

            if ($config->getShowValueType()) {
                $output .= $colorTheme->punctuation . ')';
            }

            return $output;
        }

        // NULL
        if (is_null($var)) {
            return $colorTheme->boolNull . 'null';
        }

        // Other scalar types
        if ($config->getShowValueType()) {
            $output .= $colorTheme->type . gettype($var) . ' ';
        }

        return $output . print_r($var, true);
    }

    private function formatArray(
        VariableDebugConfig $config,
        VariableDebugCliColorTheme $colorTheme,
        array $var,
        int $depth,
        string $indent,
        int &$lineCount,
        string $propertyPath = ''
    ): string {
        $count = count($var);
        $output = '';
        $maxLine = $config->resolveMaxLineOrDefault();

        if ($config->getShowValueType()) {
            $output .= $colorTheme->type . "array" . $colorTheme->punctuation
                . "(" . $colorTheme->number . $count . $colorTheme->punctuation . ") ";
        }

        if ($count === 0) {
            return $output . $colorTheme->punctuation . "[]";
        }

        $output .= $colorTheme->punctuation . "[" . PHP_EOL;
        $lineCount++;

        $newIndent = $indent . "  ";
        $i = 0;

        $showFirst = $config->resolveShowArrayModeOrDefault()->isShowFirstElement();
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
                $output .= $newIndent . $colorTheme->comment . "... (and {$remaining} hidden due to line limit)" . PHP_EOL;
                break;
            }

            $output .= $newIndent;

            if (is_string($key)) {
                $output .= $colorTheme->string . '"' . $key . '"';
            } else {
                $output .= $colorTheme->number . $key;
            }

            $nextPath = $this->getNextPath($propertyPath, (string)$key);
            if ($this->shouldShowValue($showKeyOnly, $ignoredShowKeyPaths, $nextPath)) {
                $output .= $colorTheme->punctuation . " => ";
                $output .= $this->formatVariable(
                    $config, $colorTheme, $value, $depth + 1, $newIndent, $lineCount, $nextPath
                );
            } else {
                $output .= $colorTheme->punctuation . " => " . $colorTheme->comment . "[hidden]";
            }

            if ($i < $count - 1) {
                $output .= $colorTheme->punctuation . ",";
            }

            $output .= PHP_EOL;
            $lineCount++;
            $i++;

            if ($showFirst && $count > 1) {
                $remaining = $count - $i;
                $output .= $newIndent . $colorTheme->comment
                    . "... (and {$remaining} others)"
                    . PHP_EOL;
                $lineCount++;
                break;
            }
        }

        if ($excludedCount > 0) {
            $output .= $newIndent . $colorTheme->comment . "[{$excludedCount} excluded]" . PHP_EOL;
            $lineCount++;
        }

        return $output . $indent . $colorTheme->punctuation . "]";
    }

    private function formatObject(
        VariableDebugConfig $config,
        VariableDebugCliColorTheme $colorTheme,
        object $var,
        int $depth,
        string $indent,
        int &$lineCount,
        string $propertyPath = ''
    ): string {
        $ref = new \ReflectionClass($var);
        $className = $ref->getName();
        $maxLine = $config->resolveMaxLineOrDefault();

        $output = '';

        if ($config->getShowValueType()) {
            $output .= $colorTheme->type . "object" . $colorTheme->punctuation
                . "(" . $colorTheme->className . $className . $colorTheme->punctuation . ") "
                . $colorTheme->punctuation . "{" . PHP_EOL;
        } else {
            $output .= $colorTheme->className . $className . " "
                . $colorTheme->punctuation . "{" . PHP_EOL;
        }
        $lineCount++;

        $objectVars = get_object_vars($var);
        $hasAnyProperty = false;

        // Nếu đang ở trong path (nested), dùng global properties
        // Nếu ở root, dùng class-specific rules
        if ($propertyPath === '') {
            list($properties, $withoutProperties) = $this->resolveIncludesForClass($var, $config);
        } else {
            $properties = $config->resolveIncludedPropertiesOrDefault();
            $withoutProperties = $config->resolveExcludedPropertiesOrDefault();
        }
        
        $showKeyOnly = $config->resolveShowKeyOnlyOrDefault();
        $ignoredShowKeyPaths = $config->resolveIgnoredShowKeyPropertiesOrDefault();

        // Build context for current path
        $context = $this->buildPropertyContext($properties, $withoutProperties, $propertyPath);
        
        $hasConflict = $context['isConflictShow'] && $propertyPath === '';

        // Loop qua class hierarchy và print trực tiếp
        $current = $ref;
        $printedProps = [];
        $excludedCount = 0;

        while ($current) {
            if ($lineCount >= $maxLine) {
                $output .= $indent . "  " . $colorTheme->comment . "... (truncated)" . PHP_EOL;
                return $output . $indent . $colorTheme->punctuation . "}";
            }

            foreach ($current->getProperties() as $prop) {
                $propName = $prop->getName();
                if (isset($printedProps[$propName])) {
                    continue;
                }

                // Filter using context
                if (!$this->shouldShowProperty($propName, $context)) {
                    $printedProps[$propName] = true;
                    $excludedCount++;
                    continue;
                }

                $printedProps[$propName] = true;
                $hasAnyProperty = true;

                if ($lineCount >= $maxLine) {
                    $output .= $indent . "  " . $colorTheme->comment . "... (truncated)" . PHP_EOL;
                    return $output . $indent . $colorTheme->punctuation . "}";
                }

                $prop->setAccessible(true);

                if ($config->getShowDetailAccessModifiers()) {
                    $visibility = $prop->isPrivate() ? "private"
                        : ($prop->isProtected() ? "protected" : "public");

                    $output .= $indent . "  "
                        . $colorTheme->visibility . $visibility . " "
                        . $colorTheme->key . $prop->getName();
                    
                    $nextPath = $this->getNextPath($propertyPath, $propName);
                    if ($this->shouldShowValue($showKeyOnly, $ignoredShowKeyPaths, $nextPath)) {
                        $output .= $colorTheme->punctuation . ": ";
                    }
                } else {
                    $visibility = $prop->isPrivate() ? "-"
                        : ($prop->isProtected() ? "#" : "+");

                    $output .= $indent . "  "
                        . $colorTheme->visibility . $visibility . " "
                        . $colorTheme->key . $prop->getName();
                    
                    $nextPath = $this->getNextPath($propertyPath, $propName);
                    if ($this->shouldShowValue($showKeyOnly, $ignoredShowKeyPaths, $nextPath)) {
                        $output .= $colorTheme->punctuation . ": ";
                    }
                }

                $nextPath = $this->getNextPath($propertyPath, $propName);
                if (!$this->shouldShowValue($showKeyOnly, $ignoredShowKeyPaths, $nextPath)) {
                    $output .= PHP_EOL;
                    $lineCount++;
                } elseif (!$prop->isInitialized($var)) {
                    $output .= $colorTheme->comment . "[uninitialized]" . PHP_EOL;
                    $lineCount++;
                } else {
                    try {
                        $value = $prop->getValue($var);
                        $output .= $this->formatVariable(
                                $config, $colorTheme, $value, $depth + 1, $indent . "  ", $lineCount, $nextPath
                            )
                            . PHP_EOL;
                    } catch (\Throwable $e) {
                        $output .= $colorTheme->error . "Error: " . $e->getMessage() . PHP_EOL;
                    }
                }
                $lineCount++;
            }

            $current = $current->getParentClass();
        }

        // Dynamic properties
        foreach ($objectVars as $propName => $propValue) {
            if (isset($printedProps[$propName])) {
                continue;
            }

            // Filter using context
            if (!$this->shouldShowProperty($propName, $context)) {
                $excludedCount++;
                continue;
            }

            $hasAnyProperty = true;

            if ($lineCount >= $maxLine) {
                $output .= $indent . "  " . $colorTheme->comment . "... (truncated)" . PHP_EOL;
                return $output . $indent . $colorTheme->punctuation . "}";
            }

            $nextPath = $this->getNextPath($propertyPath, $propName);
            
            if ($config->getShowDetailAccessModifiers()) {
                $output .= $indent . "  "
                    . $colorTheme->visibility . "public "
                    . $colorTheme->key . '"' . $propName . '"';
                
                if ($this->shouldShowValue($showKeyOnly, $ignoredShowKeyPaths, $nextPath)) {
                    $output .= $colorTheme->punctuation . ": ";
                }
            } else {
                $output .= $indent . "  "
                    . $colorTheme->visibility . "+"
                    . $colorTheme->key . '"' . $propName . '"';
                
                if ($this->shouldShowValue($showKeyOnly, $ignoredShowKeyPaths, $nextPath)) {
                    $output .= $colorTheme->punctuation . ": ";
                }
            }

            if (!$this->shouldShowValue($showKeyOnly, $ignoredShowKeyPaths, $nextPath)) {
                $output .= PHP_EOL;
            } else {
                $output .= $this->formatVariable(
                        $config, $colorTheme, $propValue, $depth + 1, $indent . "  ", $lineCount, $nextPath
                    )
                    . PHP_EOL;
            }
            $lineCount++;
        }

        if (!$hasAnyProperty) {
            if ($hasConflict) {
                $output .= $indent . "  " . $colorTheme->comment . "[Empty] # excluded properties contain all included properties" . PHP_EOL;
            } elseif ($context['hasIncludeAll'] && !empty($context['exclude'])) {
                $output .= $indent . "  " . $colorTheme->comment . "[Empty] # all properties are excluded" . PHP_EOL;
            } else {
                $output .= $indent . "  " . $colorTheme->comment . "# [No properties]" . PHP_EOL;
            }
            $lineCount++;
        } elseif ($excludedCount > 0) {
            $output .= $indent . "  " . $colorTheme->comment . "[{$excludedCount} excluded]" . PHP_EOL;
            $lineCount++;
        }

        return $output . $indent . $colorTheme->punctuation . "}";
    }

    private function calculateFilePathWithoutProjectRootPath(VariableDebugConfig $config, string $filePath): string
    {
        if ($config->getProjectRootPath()) {
            return str_replace($config->getProjectRootPath() . '/', '', $filePath);
        }
        return ltrim($filePath, '/');
    }

    private function strHeaderFooterSeparatorLine(): string
    {
        return str_repeat('─', 20);
    }

    private function strVarSeparatorLine(): string
    {
        return str_repeat('-', 5);
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

    /**
     * Build property filter context for current path
     */
    private function buildPropertyContext(array $properties, array $withoutProperties, string $currentPath = ''): array
    {
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
}
