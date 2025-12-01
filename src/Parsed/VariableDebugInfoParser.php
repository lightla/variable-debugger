<?php

namespace lightla\VariableDebugger\Parsed;

use lightla\VariableDebugger\VariableDebugConfig;

class VariableDebugInfoParser
{
    private int $lineCount = 0;

    public function parseFrom(
        mixed $var,
        VariableDebugConfig $config,
        int $depth = 0,
        string $propertyPath = ''
    ): VariableDebugParsedInfo {
        $info = new VariableDebugParsedInfo();
        $maxDepth = $config->resolveMaxDepthOrDefault();
        $maxLine = $config->resolveMaxLineOrDefault();

        if ($this->lineCount >= $maxLine) {
            $info->isTruncated = true;
            $info->truncatedMessage = '[Output Truncated]';
            $info->valueType = 'truncated';
            return $info;
        }

        if ($depth > $maxDepth) {
            $info->isTruncated = true;
            $info->truncatedMessage = '[Max Depth Reached]';
            if (is_object($var)) {
                $info->valueType = 'object';
                $info->className = get_class($var);
            } elseif (is_array($var)) {
                $info->valueType = 'array';
                $info->count = count($var);
            } else {
                $info->valueType = 'truncated';
            }
            return $info;
        }

        if (is_array($var)) {
            return $this->parseArray($var, $config, $depth, $propertyPath);
        }

        if (is_object($var)) {
            return $this->parseObject($var, $config, $depth, $propertyPath);
        }

        return $this->parseScalar($var, $config);
    }

    private function parseScalar(mixed $var, VariableDebugConfig $config): VariableDebugParsedInfo
    {
        $info = new VariableDebugParsedInfo();

        if (is_string($var)) {
            $info->valueType = 'string';
            $info->value = $var;
            $info->count = strlen($var);
        } elseif (is_int($var)) {
            $info->valueType = 'int';
            $info->value = $var;
        } elseif (is_float($var)) {
            $info->valueType = 'float';
            $info->value = $var;
        } elseif (is_bool($var)) {
            $info->valueType = 'bool';
            $info->value = $var;
        } elseif (is_null($var)) {
            $info->valueType = 'null';
            $info->value = null;
        } else {
            $info->valueType = gettype($var);
            $info->value = $var;
        }

        return $info;
    }

    private function parseArray(
        array $var,
        VariableDebugConfig $config,
        int $depth,
        string $propertyPath
    ): VariableDebugParsedInfo {
        $info = new VariableDebugParsedInfo();
        $info->valueType = 'array';
        $info->count = count($var);

        if ($info->count === 0) {
            return $info;
        }

        $this->lineCount++;
        $maxLine = $config->resolveMaxLineOrDefault();
        $showFirst = $config->resolveShowArrayModeOrDefault()->isShowFirstElement();
        $showKeyOnly = $config->resolveShowKeyOnlyOrDefault();
        $ignoredShowKeyPaths = $config->resolveIgnoredShowKeyPropertiesOrDefault();

        $properties = $config->resolveIncludedPropertiesOrDefault();
        $withoutProperties = $config->resolveExcludedPropertiesOrDefault();
        $context = $this->buildPropertyContext($properties, $withoutProperties, $propertyPath);

        $excludedCount = 0;
        $i = 0;

        foreach ($var as $key => $value) {
            if (!$this->shouldShowProperty((string)$key, $context)) {
                $excludedCount++;
                continue;
            }

            if ($this->lineCount >= $maxLine) {
                $remaining = $info->count - $i;
                $truncInfo = new VariableDebugParsedInfo();
                $truncInfo->isTruncated = true;
                $truncInfo->truncatedMessage = "... (and {$remaining} hidden due to line limit)";
                $truncInfo->valueType = 'truncated';
                $info->children[] = $truncInfo;
                break;
            }

            $childInfo = new VariableDebugParsedInfo();
            $childInfo->name = (string)$key;

            $nextPath = $this->getNextPath($propertyPath, (string)$key);
            if (!$this->shouldShowValue($showKeyOnly, $ignoredShowKeyPaths, $nextPath)) {
                $childInfo->isHidden = true;
                $childInfo->valueType = 'hidden';
            } else {
                $parsed = $this->parseFrom($value, $config, $depth + 1, $nextPath);
                $childInfo->value = $parsed->value;
                $childInfo->valueType = $parsed->valueType;
                $childInfo->className = $parsed->className;
                $childInfo->count = $parsed->count;
                $childInfo->children = $parsed->children;
                $childInfo->isTruncated = $parsed->isTruncated;
                $childInfo->truncatedMessage = $parsed->truncatedMessage;
                $childInfo->isTypeOnly = $parsed->isTypeOnly;
            }

            $info->children[] = $childInfo;
            $this->lineCount++;
            $i++;

            if ($showFirst && $info->count > 1) {
                $othersCount = $info->count - $i;
                $truncInfo = new VariableDebugParsedInfo();
                $truncInfo->isTruncated = true;
                $truncInfo->truncatedMessage = "... (and {$othersCount} others)";
                $truncInfo->valueType = 'truncated';
                $info->children[] = $truncInfo;
                $this->lineCount++;
                break;
            }
        }

        $info->excludedCount = $excludedCount;
        return $info;
    }

    private function parseObject(
        object $var,
        VariableDebugConfig $config,
        int $depth,
        string $propertyPath
    ): VariableDebugParsedInfo {
        $info = new VariableDebugParsedInfo();
        $info->valueType = 'object';
        
        $ref = new \ReflectionClass($var);
        $info->className = $ref->getName();
        
        $this->lineCount++;
        $maxLine = $config->resolveMaxLineOrDefault();

        // Check buildLaterClassProperties
        $buildLaterProperties = $config->resolveBuildLaterClassPropertiesOrDefault();
        foreach ($buildLaterProperties as $buildClassName => $callback) {
            if ($var instanceof $buildClassName) {
                $customProperties = $callback($var);
                
                foreach ($customProperties as $propName => $propValue) {
                    if ($this->lineCount >= $maxLine) {
                        $truncInfo = new VariableDebugParsedInfo();
                        $truncInfo->isTruncated = true;
                        $truncInfo->truncatedMessage = '... (truncated)';
                        $truncInfo->valueType = 'truncated';
                        $info->children[] = $truncInfo;
                        return $info;
                    }
                    
                    $childInfo = new VariableDebugParsedInfo();
                    $childInfo->name = $propName;
                    
                    $parsed = $this->parseFrom($propValue, $config, $depth + 1, $this->getNextPath($propertyPath, $propName));
                    $childInfo->value = $parsed->value;
                    $childInfo->valueType = $parsed->valueType;
                    $childInfo->className = $parsed->className;
                    $childInfo->count = $parsed->count;
                    $childInfo->children = $parsed->children;
                    
                    $info->children[] = $childInfo;
                    $this->lineCount++;
                }
                
                return $info;
            }
        }

        // Normal reflection logic
        $classIncludes = $this->getClassSpecificIncludes($var, $config);
        $globalIncludes = $config->resolveIncludedPropertiesOrDefault();
        $globalExcludes = $config->resolveExcludedPropertiesOrDefault();
        $showKeyOnly = $config->resolveShowKeyOnlyOrDefault();
        $ignoredShowKeyPaths = $config->resolveIgnoredShowKeyPropertiesOrDefault();

        $hasConflict = $classIncludes !== null && empty($classIncludes);
        $current = $ref;
        $printedProps = [];
        $excludedCount = 0;

        while ($current) {
            if ($this->lineCount >= $maxLine) {
                $truncInfo = new VariableDebugParsedInfo();
                $truncInfo->isTruncated = true;
                $truncInfo->truncatedMessage = '... (truncated)';
                $truncInfo->valueType = 'truncated';
                $info->children[] = $truncInfo;
                return $info;
            }

            foreach ($current->getProperties() as $prop) {
                $propName = $prop->getName();
                if (isset($printedProps[$propName])) {
                    continue;
                }

                if ($classIncludes !== null && !in_array($propName, $classIncludes)) {
                    $printedProps[$propName] = true;
                    $excludedCount++;
                    continue;
                }

                $objectPropertyPath = $propName;
                if (!$this->shouldShowPropertyGlobalFromObjectRoot($objectPropertyPath, $propertyPath, $globalIncludes, $globalExcludes)) {
                    $printedProps[$propName] = true;
                    $excludedCount++;
                    continue;
                }

                $printedProps[$propName] = true;

                if ($this->lineCount >= $maxLine) {
                    $truncInfo = new VariableDebugParsedInfo();
                    $truncInfo->isTruncated = true;
                    $truncInfo->truncatedMessage = '... (truncated)';
                    $truncInfo->valueType = 'truncated';
                    $info->children[] = $truncInfo;
                    return $info;
                }

                $prop->setAccessible(true);
                $childInfo = new VariableDebugParsedInfo();
                $childInfo->name = $propName;

                if ($config->getShowDetailAccessModifiers()) {
                    $childInfo->accessModifier = $prop->isPrivate() ? 'private' : ($prop->isProtected() ? 'protected' : 'public');
                } else {
                    $childInfo->accessModifier = $prop->isPrivate() ? '-' : ($prop->isProtected() ? '#' : '+');
                }

                $nextPath = $propName;
                if (!$this->shouldShowValue($showKeyOnly, $ignoredShowKeyPaths, $nextPath)) {
                    $childInfo->isHidden = true;
                    $childInfo->valueType = 'hidden';
                } elseif (!$prop->isInitialized($var)) {
                    $childInfo->isUninitialized = true;
                    $childInfo->valueType = 'uninitialized';
                } else {
                    try {
                        $propValue = $prop->getValue($var);
                        $fullPath = $this->getNextPath($propertyPath, $propName);
                        $showValueMode = $this->getPropertyShowValueMode($var, $propName, $fullPath, $classIncludes, $config);

                        if ($showValueMode->isShowTypeOnly()) {
                            $childInfo->isTypeOnly = true;
                            $typeInfo = $this->getTypeInfo($propValue);
                            $childInfo->valueType = $typeInfo['type'];
                            $childInfo->className = $typeInfo['className'];
                            $childInfo->count = $typeInfo['count'];
                        } else {
                            $parsed = $this->parseFrom($propValue, $config, $depth + 1, $fullPath);
                            $childInfo->value = $parsed->value;
                            $childInfo->valueType = $parsed->valueType;
                            $childInfo->className = $parsed->className;
                            $childInfo->count = $parsed->count;
                            $childInfo->children = $parsed->children;
                            $childInfo->isTruncated = $parsed->isTruncated;
                            $childInfo->truncatedMessage = $parsed->truncatedMessage;
                        }
                    } catch (\Throwable $e) {
                        $childInfo->valueType = 'error';
                        $childInfo->value = $e->getMessage();
                    }
                }

                $info->children[] = $childInfo;
                $this->lineCount++;
            }

            $current = $current->getParentClass();
        }

        // Dynamic properties
        $objectVars = get_object_vars($var);
        foreach ($objectVars as $propName => $propValue) {
            if (isset($printedProps[$propName])) {
                continue;
            }

            if ($classIncludes !== null && !in_array($propName, $classIncludes)) {
                $excludedCount++;
                continue;
            }

            $objectPropertyPath = $propName;
            if (!$this->shouldShowPropertyGlobalFromObjectRoot($objectPropertyPath, $propertyPath, $globalIncludes, $globalExcludes)) {
                $excludedCount++;
                continue;
            }

            if ($this->lineCount >= $maxLine) {
                $truncInfo = new VariableDebugParsedInfo();
                $truncInfo->isTruncated = true;
                $truncInfo->truncatedMessage = '... (truncated)';
                $truncInfo->valueType = 'truncated';
                $info->children[] = $truncInfo;
                return $info;
            }

            $childInfo = new VariableDebugParsedInfo();
            $childInfo->name = '"' . $propName . '"';
            $childInfo->accessModifier = $config->getShowDetailAccessModifiers() ? 'public' : '+';

            $nextPath = $propName;
            if (!$this->shouldShowValue($showKeyOnly, $ignoredShowKeyPaths, $nextPath)) {
                $childInfo->isHidden = true;
                $childInfo->valueType = 'hidden';
            } else {
                $fullPath = $this->getNextPath($propertyPath, $propName);
                $showValueMode = $this->getPropertyShowValueMode($var, $propName, $fullPath, $classIncludes, $config);

                if ($showValueMode->isShowTypeOnly()) {
                    $childInfo->isTypeOnly = true;
                    $typeInfo = $this->getTypeInfo($propValue);
                    $childInfo->valueType = $typeInfo['type'];
                    $childInfo->className = $typeInfo['className'];
                    $childInfo->count = $typeInfo['count'];
                } else {
                    $parsed = $this->parseFrom($propValue, $config, $depth + 1, $fullPath);
                    $childInfo->value = $parsed->value;
                    $childInfo->valueType = $parsed->valueType;
                    $childInfo->className = $parsed->className;
                    $childInfo->count = $parsed->count;
                    $childInfo->children = $parsed->children;
                    $childInfo->isTruncated = $parsed->isTruncated;
                    $childInfo->truncatedMessage = $parsed->truncatedMessage;
                }
            }

            $info->children[] = $childInfo;
            $this->lineCount++;
        }

        if (empty($info->children)) {
            if ($hasConflict) {
                $emptyInfo = new VariableDebugParsedInfo();
                $emptyInfo->valueType = 'comment';
                $emptyInfo->value = '[Empty] # excluded properties contain all included properties';
                $info->children[] = $emptyInfo;
            } else {
                $emptyInfo = new VariableDebugParsedInfo();
                $emptyInfo->valueType = 'comment';
                $emptyInfo->value = '# [No properties]';
                $info->children[] = $emptyInfo;
            }
            $this->lineCount++;
        }

        $info->excludedCount = $excludedCount;
        return $info;
    }

    private function getTypeInfo(mixed $value): array
    {
        if (is_object($value)) {
            return ['type' => 'object', 'className' => get_class($value), 'count' => null];
        }
        if (is_array($value)) {
            return ['type' => 'array', 'className' => null, 'count' => count($value)];
        }
        if (is_string($value)) {
            return ['type' => 'string', 'className' => null, 'count' => strlen($value)];
        }
        if (is_int($value)) {
            return ['type' => 'int', 'className' => null, 'count' => null];
        }
        if (is_float($value)) {
            return ['type' => 'float', 'className' => null, 'count' => null];
        }
        if (is_bool($value)) {
            return ['type' => 'bool', 'className' => null, 'count' => null];
        }
        if (is_null($value)) {
            return ['type' => 'null', 'className' => null, 'count' => null];
        }
        return ['type' => gettype($value), 'className' => null, 'count' => null];
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

    private function extractPropertyPaths(array $properties): array
    {
        $paths = [];
        foreach ($properties as $key => $value) {
            if ($value instanceof \lightla\VariableDebugger\Config\VariableDebugClassPropertyShowValueMode) {
                $paths[] = $key;
            } else {
                $paths[] = $value;
            }
        }
        return $paths;
    }

    private function shouldShowPropertyGlobalFromObjectRoot(
        string $objectPropertyPath,
        string $propertyPath,
        array $globalIncludes,
        array $globalExcludes
    ): bool {
        $context = $this->buildPropertyContext($globalIncludes, $globalExcludes, $propertyPath);
        return $this->shouldShowProperty($objectPropertyPath, $context);
    }

    private function getClassSpecificIncludes(object $var, VariableDebugConfig $config): ?array
    {
        $classIncludes = $config->resolveIncludedClassPropertiesOrDefault();
        foreach ($classIncludes as $className => $properties) {
            if ($var instanceof $className) {
                return array_keys($properties);
            }
        }
        return null;
    }

    private function buildPropertyContext(array $properties, array $withoutProperties, string $currentPath = ''): array
    {
        $properties = $this->extractPropertyPaths($properties);
        $hasIncludeAll = empty($properties);
        $finalIncludes = $this->filterParentPaths($properties);
        $finalExcludes = $this->filterParentPaths($withoutProperties);

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
        $include = [];
        $exclude = [];
        $showAllNested = false;

        $currentParts = $currentPath === '' ? [] : explode('.', $currentPath);
        $currentDepth = count($currentParts);

        foreach ($remainingIncludes as $path) {
            $parts = explode('.', $path);
            if ($this->pathStartsWith($parts, $currentParts)) {
                if (count($parts) === $currentDepth) {
                    $showAllNested = true;
                    break;
                } elseif (count($parts) > $currentDepth) {
                    $include[$parts[$currentDepth]] = true;
                }
            } elseif ($this->pathStartsWith($currentParts, $parts)) {
                $showAllNested = true;
                break;
            }
        }

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
        if (isset($context['exclude'][$propName])) {
            return false;
        }
        if ($context['hasIncludeAll']) {
            return true;
        }
        return isset($context['include'][$propName]);
    }

    private function getNextPath(string $currentPath, string $key): string
    {
        return $currentPath === '' ? $key : $currentPath . '.' . $key;
    }

    private function shouldShowValue(bool $showKeyOnly, array $ignoredPaths, string $currentPath): bool
    {
        if (!$showKeyOnly) {
            return true;
        }
        if (empty($ignoredPaths)) {
            return false;
        }
        $currentParts = $currentPath === '' ? [] : explode('.', $currentPath);
        foreach ($ignoredPaths as $ignoredPath) {
            $ignoredParts = explode('.', $ignoredPath);
            if ($currentPath === $ignoredPath || $this->pathStartsWith($currentParts, $ignoredParts)) {
                return true;
            }
            if ($this->pathStartsWith($ignoredParts, $currentParts) && count($ignoredParts) > count($currentParts)) {
                return true;
            }
        }
        return false;
    }

    private function getPropertyShowValueMode(
        object $var,
        string $propName,
        string $fullPath,
        ?array $classIncludes,
        VariableDebugConfig $config
    ): \lightla\VariableDebugger\Config\VariableDebugClassPropertyShowValueMode {
        $mode = \lightla\VariableDebugger\Config\VariableDebugClassPropertyShowValueMode::SHOW_DETAIL;

        $allClassProperties = $config->resolveIncludedClassPropertiesOrDefault();
        foreach ($allClassProperties as $className => $properties) {
            if ($var instanceof $className && isset($properties[$propName])) {
                $mode = $properties[$propName];
                break;
            }
        }

        $globalProperties = $config->resolveIncludedPropertiesOrDefault();
        if (isset($globalProperties[$fullPath])) {
            $value = $globalProperties[$fullPath];
            if ($value instanceof \lightla\VariableDebugger\Config\VariableDebugClassPropertyShowValueMode) {
                $mode = $value;
            }
        } elseif (isset($globalProperties[$propName])) {
            $value = $globalProperties[$propName];
            if ($value instanceof \lightla\VariableDebugger\Config\VariableDebugClassPropertyShowValueMode) {
                $mode = $value;
            }
        }

        return $mode;
    }
}
