<?php

namespace lightla\VariableDebugger\DebugStrategy;

use lightla\VariableDebugger\VariableDebugConfig;
use lightla\VariableDebugger\VariableDebugStrategy;

class VariableDebugViaCliStrategy implements VariableDebugStrategy
{
    private VariableDebugCliColorTheme $colorTheme;

    public function __construct(
        ?VariableDebugCliColorTheme $colorTheme = null,
    )
    {
        if ($colorTheme) {
            $this->colorTheme = $colorTheme;
        } else {
            $this->colorTheme = VariableDebugCliColorTheme::noColor();
        }
    }

    public function dumpFromTrace(
        VariableDebugConfig $config,
        array $backtrace,
                            ...$vars
    ): void {
        $this->colorTheme = $config->getCliTheme() ?? $this->colorTheme;

        $caller = $backtrace[0];
        $file = $this->calculateFilePathWithoutProjectRootPath($config, $caller['file']);
        $line = $caller['line'];

        $outputLines = [];
        $outputLines[] = $this->colorTheme->punctuation . $this->strHeaderFooterSeparatorLine();
        $outputLines[] =
            $this->colorTheme->punctuation . "📁" .
            $this->colorTheme->filePath . "/{$file}" .
            $this->colorTheme->punctuation . ":" .
            $this->colorTheme->lineNumber . $line;
        $outputLines[] = $this->colorTheme->punctuation . str_repeat('·', 5);

        foreach ($vars as $i => $var) {
            if ($i > 0) {
                $outputLines[] = $this->colorTheme->punctuation . $this->strVarSeparatorLine();
            }

            $formattedVar = $this->formatVariable($config, $var);
            $lines = explode(PHP_EOL, $formattedVar);
            foreach ($lines as $lineContent) {
                $outputLines[] = $lineContent;
            }
        }

//        $outputLines[] = $this->colorTheme->punctuation . $this->strHeaderFooterSeparatorLine();

        $this->printFullWidth($outputLines);
    }

    private function printFullWidth(array $lines): void
    {
        foreach ($lines as $line) {
            echo $line . $this->colorTheme->reset . PHP_EOL;
        }

        // Reset thêm 1 lần nữa cho chắc chắn
        echo $this->colorTheme->reset;
    }

    // --- PHẦN FORMAT GIỮ NGUYÊN NHƯ ÔNG ĐANG DÙNG ---

    private function formatVariable(
        VariableDebugConfig $config,
                            $var,
        int $depth = 0,
        string $indent = ''
    ): string {
        $maxDepth = $config->getMaxDepth();
        if ($depth > $maxDepth) {
            return $this->colorTheme->comment . '[Max Depth Reached]';
        }

        if (is_array($var)) {
            return $this->formatArray($config, $var, $depth, $indent);
        }

        if (is_object($var)) {
            return $this->formatObject($config, $var, $depth, $indent);
        }

        $output = '';

        // STRING
        if (is_string($var)) {
            if ($config->getShowValueType()) {
                $output .= $this->colorTheme->type . 'string'
                    . $this->colorTheme->punctuation . '('
                    . $this->colorTheme->number . strlen($var)
                    . $this->colorTheme->punctuation . ') ';
            }

            return $output
                . $this->colorTheme->string
                . '"' . addcslashes($var, '"\\') . '"';
        }

        // INT / FLOAT
        if (is_int($var) || is_float($var)) {
            $type = is_int($var) ? 'int' : 'float';

            if ($config->getShowValueType()) {
                $output .= $this->colorTheme->type . $type . $this->colorTheme->punctuation . '(';
            }

            $output .= $this->colorTheme->number . $var;

            if ($config->getShowValueType()) {
                $output .= $this->colorTheme->punctuation . ')';
            }

            return $output;
        }

        // BOOL
        if (is_bool($var)) {
            if ($config->getShowValueType()) {
                $output .= $this->colorTheme->type . 'bool' . $this->colorTheme->punctuation . '(';
            }

            $output .= $this->colorTheme->boolNull . ($var ? 'true' : 'false');

            if ($config->getShowValueType()) {
                $output .= $this->colorTheme->punctuation . ')';
            }

            return $output;
        }

        // NULL
        if (is_null($var)) {
            return $this->colorTheme->boolNull . 'null';
        }

        // Other scalar types
        if ($config->getShowValueType()) {
            $output .= $this->colorTheme->type . gettype($var) . ' ';
        }

        return $output . print_r($var, true);
    }

    private function formatArray(VariableDebugConfig $config, array $var, int $depth, string $indent): string
    {
        $count = count($var);
        $output = '';

        if ($config->getShowValueType()) {
            $output .= $this->colorTheme->type . "array" . $this->colorTheme->punctuation
                . "(" . $this->colorTheme->number . $count . $this->colorTheme->punctuation . ") ";
        }

        if ($count === 0) {
            return $output . $this->colorTheme->punctuation . "[]";
        }

        $output .= $this->colorTheme->punctuation . "[" . PHP_EOL;

        $newIndent = $indent . "  ";
        $i = 0;

        $showFirst = (
            $config->getShowArrayMode()?->isShowFirstElement()
            && $depth === 0
        );

        foreach ($var as $key => $value) {
            $output .= $newIndent;

            if (is_string($key)) {
                $output .= $this->colorTheme->string . '"' . $key . '"'
                    . $this->colorTheme->punctuation . " => ";
            } else {
                $output .= $this->colorTheme->number . $key
                    . $this->colorTheme->punctuation . " => ";
            }

            $output .= $this->formatVariable($config, $value, $depth + 1, $newIndent);

            if ($i < $count - 1) {
                $output .= $this->colorTheme->punctuation . ",";
            }

            $output .= PHP_EOL;
            $i++;

            if ($showFirst && $count > 1) {
                $output .= $newIndent . $this->colorTheme->comment
                    . "... (and " . ($count - 1) . " others)"
                    . PHP_EOL;
                break;
            }
        }

        return $output . $indent . $this->colorTheme->punctuation . "]";
    }

    private function formatObject(VariableDebugConfig $config, object $var, int $depth, string $indent): string
    {
        $ref = new \ReflectionClass($var);
        $className = $ref->getName();

        $output = '';

        // object(ClassName) {
        if ($config->getShowValueType()) {
            $output .= $this->colorTheme->type . "object" . $this->colorTheme->punctuation
                . "(" . $this->colorTheme->className . $className . $this->colorTheme->punctuation . ") "
                . $this->colorTheme->punctuation . "{" . PHP_EOL;
        } else {
            // className {
            $output .= $this->colorTheme->className . $className . " "
                . $this->colorTheme->punctuation . "{" . PHP_EOL;
        }

        // Gather ALL declared properties (includes inherited)
        $allProperties = [];
        $current = $ref;

        while ($current) {
            foreach ($current->getProperties() as $p) {
                $name = $p->getName();
                if (!isset($allProperties[$name])) {
                    $allProperties[$name] = $p;
                }
            }
            $current = $current->getParentClass();
        }

        // Dynamic (stdClass or magic)
        $objectVars = get_object_vars($var);

        if (empty($allProperties) && empty($objectVars)) {
            return $output . $indent . "  "
                . $this->colorTheme->comment . "# No properties" . PHP_EOL
                . $indent . $this->colorTheme->punctuation . "}";
        }

        // ===== DECLARED PROPERTIES =====
        foreach ($allProperties as $prop) {
            $prop->setAccessible(true);

            if ($config->getShowDetailAccessModifiers()) {
                $visibility = $prop->isPrivate() ? "private"
                    : ($prop->isProtected() ? "protected" : "public");

                $output .= $indent . "  "
                    . $this->colorTheme->visibility . $visibility . " "
                    . $this->colorTheme->key . $prop->getName()
                    . $this->colorTheme->punctuation . ": ";
            } else {
                $visibility = $prop->isPrivate() ? "-"
                    : ($prop->isProtected() ? "#" : "+");

                $output .= $indent . "  "
                    . $this->colorTheme->visibility . $visibility . " "
                    . $this->colorTheme->key . $prop->getName()
                    . $this->colorTheme->punctuation . ": ";
            }

            // VALUE
            if (!$prop->isInitialized($var)) {
                $output .= $this->colorTheme->comment . "[uninitialized]" . PHP_EOL;
            } else {
                try {
                    $value = $prop->getValue($var);
                    $output .= $this->formatVariable($config, $value, $depth + 1, $indent . "  ")
                        . PHP_EOL;
                } catch (\Throwable $e) {
                    $output .= $this->colorTheme->error . "Error: " . $e->getMessage() . PHP_EOL;
                }
            }
        }

        // ===== DYNAMIC PROPERTIES =====
        foreach ($objectVars as $propName => $propValue) {
            // Skip if already declared
            if (isset($allProperties[$propName])) {
                continue;
            }

            if ($config->getShowDetailAccessModifiers()) {
                $output .= $indent . "  "
                    . $this->colorTheme->visibility . "public "
                    . $this->colorTheme->key . '"' . $propName . '"'
                    . $this->colorTheme->punctuation . ": ";
            } else {
                $output .= $indent . "  "
                    . $this->colorTheme->visibility . "+"
                    . $this->colorTheme->key . '"' . $propName . '"'
                    . $this->colorTheme->punctuation . ": ";
            }

            $output .= $this->formatVariable($config, $propValue, $depth + 1, $indent . "  ")
                . PHP_EOL;
        }

        return $output . $indent . $this->colorTheme->punctuation . "}";
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
