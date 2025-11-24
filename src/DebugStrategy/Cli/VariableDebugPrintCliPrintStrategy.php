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
        int &$lineCount = 0
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
            return $this->formatArray($config, $colorTheme, $var, $depth, $indent, $lineCount);
        }

        if (is_object($var)) {
            return $this->formatObject($config, $colorTheme, $var, $depth, $indent, $lineCount);
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
        int &$lineCount
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
        $showKeyOnly = $config->getShowKeyOnlyOrDefault();

        foreach ($var as $key => $value) {
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

            if (!$showKeyOnly) {
                $output .= $colorTheme->punctuation . " => ";
                $output .= $this->formatVariable(
                    $config, $colorTheme, $value, $depth + 1, $newIndent, $lineCount
                );
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

        return $output . $indent . $colorTheme->punctuation . "]";
    }

    private function formatObject(
        VariableDebugConfig $config,
        VariableDebugCliColorTheme $colorTheme,
        object $var,
        int $depth,
        string $indent,
        int &$lineCount
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

        $properties = $config->resolvePropertiesOrDefault();
        $withoutProperties = $config->resolveWithoutPropertiesOrDefault();
        $showKeyOnly = $config->getShowKeyOnlyOrDefault();

        // Loop qua class hierarchy và print trực tiếp
        $current = $ref;
        $printedProps = [];

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

                // Filter properties
                if (!empty($properties) && !in_array($propName, $properties)) {
                    continue;
                }
                if (!empty($withoutProperties) && in_array($propName, $withoutProperties)) {
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
                    
                    if (!$showKeyOnly) {
                        $output .= $colorTheme->punctuation . ": ";
                    }
                } else {
                    $visibility = $prop->isPrivate() ? "-"
                        : ($prop->isProtected() ? "#" : "+");

                    $output .= $indent . "  "
                        . $colorTheme->visibility . $visibility . " "
                        . $colorTheme->key . $prop->getName();
                    
                    if (!$showKeyOnly) {
                        $output .= $colorTheme->punctuation . ": ";
                    }
                }

                if ($showKeyOnly) {
                    $output .= PHP_EOL;
                    $lineCount++;
                } elseif (!$prop->isInitialized($var)) {
                    $output .= $colorTheme->comment . "[uninitialized]" . PHP_EOL;
                    $lineCount++;
                } else {
                    try {
                        $value = $prop->getValue($var);
                        $output .= $this->formatVariable(
                                $config, $colorTheme, $value, $depth + 1, $indent . "  ", $lineCount
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

            // Filter properties
            if (!empty($properties) && !in_array($propName, $properties)) {
                continue;
            }
            if (!empty($withoutProperties) && in_array($propName, $withoutProperties)) {
                continue;
            }

            $hasAnyProperty = true;

            if ($lineCount >= $maxLine) {
                $output .= $indent . "  " . $colorTheme->comment . "... (truncated)" . PHP_EOL;
                return $output . $indent . $colorTheme->punctuation . "}";
            }

            if ($config->getShowDetailAccessModifiers()) {
                $output .= $indent . "  "
                    . $colorTheme->visibility . "public "
                    . $colorTheme->key . '"' . $propName . '"';
                
                if (!$showKeyOnly) {
                    $output .= $colorTheme->punctuation . ": ";
                }
            } else {
                $output .= $indent . "  "
                    . $colorTheme->visibility . "+"
                    . $colorTheme->key . '"' . $propName . '"';
                
                if (!$showKeyOnly) {
                    $output .= $colorTheme->punctuation . ": ";
                }
            }

            if ($showKeyOnly) {
                $output .= PHP_EOL;
            } else {
                $output .= $this->formatVariable(
                        $config, $colorTheme, $propValue, $depth + 1, $indent . "  ", $lineCount
                    )
                    . PHP_EOL;
            }
            $lineCount++;
        }

        if (!$hasAnyProperty) {
            $output .= $indent . "  " . $colorTheme->comment . "# No properties" . PHP_EOL;
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
}
