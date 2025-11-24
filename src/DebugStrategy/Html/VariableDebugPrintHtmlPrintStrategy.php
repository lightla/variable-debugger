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

                foreach ($var as $key => $value) {
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

                    if (!$showKeyOnly) {
                        $output .= ' <span style="color:#d4d4d4;">=></span> ';
                        $nextPath = $this->getNextPath($propertyPath, (string)$key);
                        $output .= $this->formatVariable($config, $value, $depth + 1, $newIndent, $lineCount, $nextPath);
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

            $objectVars = get_object_vars($var);
            $hasAnyProperty = false;

            $properties = $config->resolveIncludedPropertiesOrDefault();
            $withoutProperties = $config->resolveExcludedPropertiesOrDefault();
            $showKeyOnly = $config->resolveShowKeyOnlyOrDefault();

            // Build context for current path
            $context = $this->buildPropertyContext($properties, $withoutProperties, $propertyPath);
            
            $hasConflict = $context['isConflictShow'] && $propertyPath === '';

            // Loop qua class hierarchy và print trực tiếp
            $current = $reflection;
            $printedProps = [];

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

                    // Filter using context
                    if (!$this->shouldShowProperty($propName, $context)) {
                        continue;
                    }

                    $printedProps[$propName] = true;
                    $hasAnyProperty = true;

                    if ($lineCount >= $maxLine) {
                        $output .= $indent . '  <span style="color:#808080;">... (truncated)</span><br>';
                        return $output . $indent . '}';
                    }

                    $prop->setAccessible(true);

                    if ($config->getShowDetailAccessModifiers()) {
                        $visibility = $prop->isPrivate() ? 'private' : ($prop->isProtected() ? 'protected' : 'public');
                        $output .= $indent . '  <span style="color:#c586c0;">' . $visibility . '</span> ';
                        $output .= '<span style="color:#9cdcfe;">' . $prop->getName() . '</span>';
                        
                        if (!$showKeyOnly) {
                            $output .= ': ';
                        }
                    } else {
                        $visibility = $prop->isPrivate() ? '-' : ($prop->isProtected() ? '#' : '+');
                        $output .= $indent . '  <span style="color:#c586c0;">' . $visibility . '</span>';
                        $output .= '<span style="color:#9cdcfe;">' . $prop->getName() . '</span>';
                        
                        if (!$showKeyOnly) {
                            $output .= ': ';
                        }
                    }

                    if ($showKeyOnly) {
                        $output .= '<br>';
                        $lineCount++;
                    } else {
                        try {
                            if (!$prop->isInitialized($var)) {
                                $output .= '<span style="color:#808080;">[uninitialized]</span>';
                            } else {
                                $propValue = $prop->getValue($var);
                                $nextPath = $this->getNextPath($propertyPath, $propName);
                                $output .= $this->formatVariable($config, $propValue, $depth + 1, $indent . '  ', $lineCount, $nextPath);
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

                // Filter using context
                if (!$this->shouldShowProperty($propName, $context)) {
                    continue;
                }

                $hasAnyProperty = true;

                if ($lineCount >= $maxLine) {
                    $output .= $indent . '  <span style="color:#808080;">... (truncated)</span><br>';
                    return $output . $indent . '}';
                }

                if ($config->getShowDetailAccessModifiers()) {
                    $output .= $indent . '  <span style="color:#c586c0;">public</span> ';
                    $output .= '<span style="color:#9cdcfe;">"' . htmlspecialchars($propName) . '"</span>';
                    
                    if (!$showKeyOnly) {
                        $output .= ': ';
                    }
                } else {
                    $output .= $indent . '  <span style="color:#c586c0;">+</span>';
                    $output .= '<span style="color:#9cdcfe;">"' . htmlspecialchars($propName) . '"</span>';
                    
                    if (!$showKeyOnly) {
                        $output .= ': ';
                    }
                }

                if ($showKeyOnly) {
                    $output .= '<br>';
                } else {
                    $nextPath = $this->getNextPath($propertyPath, $propName);
                    $output .= $this->formatVariable($config, $propValue, $depth + 1, $indent . '  ', $lineCount, $nextPath);
                    $output .= '<br>';
                }
                $lineCount++;
            }

            if (!$hasAnyProperty) {
                if ($hasConflict) {
                    $output .= $indent . '  <span style="color:#808080;">[Empty] # excluded properties contain all included properties</span><br>';
                } elseif ($context['hasIncludeAll'] && !empty($context['exclude'])) {
                    $output .= $indent . '  <span style="color:#808080;">[Empty] # all properties are excluded</span><br>';
                } else {
                    $output .= $indent . '  <span style="color:#808080;"># No properties</span><br>';
                }
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
        
        $currentParts = $currentPath === '' ? [] : explode('.', $currentPath);
        $currentDepth = count($currentParts);

        foreach ($remainingIncludes as $path) {
            $parts = explode('.', $path);
            
            if ($this->pathStartsWith($parts, $currentParts) && count($parts) > $currentDepth) {
                $include[$parts[$currentDepth]] = true;
            }
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
            'hasIncludeAll' => $hasIncludeAll,
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
}
