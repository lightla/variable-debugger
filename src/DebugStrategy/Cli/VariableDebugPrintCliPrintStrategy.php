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

        foreach ($vars as $i => $var) {
            if ($i > 0) {
                $outputLines[] = $colorTheme->punctuation . $this->strVarSeparatorLine();
            }

            $formattedVar = $this->formatVariable($config, $colorTheme, $var);
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
        string $indent = ''
    ): string {
        $maxDepth = $config->getMaxDepth();
        if ($depth > $maxDepth) {
            return $colorTheme->comment . '[Max Depth Reached]';
        }

        if (is_array($var)) {
            return $this->formatArray($config, $colorTheme, $var, $depth, $indent);
        }

        if (is_object($var)) {
            return $this->formatObject($config, $colorTheme, $var, $depth, $indent);
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
        string $indent): string
    {
        $count = count($var);
        $output = '';

        if ($config->getShowValueType()) {
            $output .= $colorTheme->type . "array" . $colorTheme->punctuation
                . "(" . $colorTheme->number . $count . $colorTheme->punctuation . ") ";
        }

        if ($count === 0) {
            return $output . $colorTheme->punctuation . "[]";
        }

        $output .= $colorTheme->punctuation . "[" . PHP_EOL;

        $newIndent = $indent . "  ";
        $i = 0;

        $showFirst = (
            $config->getShowArrayMode()?->isShowFirstElement()
            && $depth === 0
        );

        foreach ($var as $key => $value) {
            $output .= $newIndent;

            if (is_string($key)) {
                $output .= $colorTheme->string . '"' . $key . '"'
                    . $colorTheme->punctuation . " => ";
            } else {
                $output .= $colorTheme->number . $key
                    . $colorTheme->punctuation . " => ";
            }

            $output .= $this->formatVariable(
                $config, $colorTheme, $value, $depth + 1, $newIndent
            );

            if ($i < $count - 1) {
                $output .= $colorTheme->punctuation . ",";
            }

            $output .= PHP_EOL;
            $i++;

            if ($showFirst && $count > 1) {
                $output .= $newIndent . $colorTheme->comment
                    . "... (and " . ($count - 1) . " others)"
                    . PHP_EOL;
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
        string $indent): string
    {
        $ref = new \ReflectionClass($var);
        $className = $ref->getName();

        $output = '';

        // object(ClassName) {
        if ($config->getShowValueType()) {
            $output .= $colorTheme->type . "object" . $colorTheme->punctuation
                . "(" . $colorTheme->className . $className . $colorTheme->punctuation . ") "
                . $colorTheme->punctuation . "{" . PHP_EOL;
        } else {
            // className {
            $output .= $colorTheme->className . $className . " "
                . $colorTheme->punctuation . "{" . PHP_EOL;
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
                . $colorTheme->comment . "# No properties" . PHP_EOL
                . $indent . $colorTheme->punctuation . "}";
        }

        // ===== DECLARED PROPERTIES =====
        foreach ($allProperties as $prop) {
            $prop->setAccessible(true);

            if ($config->getShowDetailAccessModifiers()) {
                $visibility = $prop->isPrivate() ? "private"
                    : ($prop->isProtected() ? "protected" : "public");

                $output .= $indent . "  "
                    . $colorTheme->visibility . $visibility . " "
                    . $colorTheme->key . $prop->getName()
                    . $colorTheme->punctuation . ": ";
            } else {
                $visibility = $prop->isPrivate() ? "-"
                    : ($prop->isProtected() ? "#" : "+");

                $output .= $indent . "  "
                    . $colorTheme->visibility . $visibility . " "
                    . $colorTheme->key . $prop->getName()
                    . $colorTheme->punctuation . ": ";
            }

            // VALUE
            if (!$prop->isInitialized($var)) {
                $output .= $colorTheme->comment . "[uninitialized]" . PHP_EOL;
            } else {
                try {
                    $value = $prop->getValue($var);
                    $output .= $this->formatVariable(
                            $config, $colorTheme, $value, $depth + 1, $indent . "  "
                        )
                        . PHP_EOL;
                } catch (\Throwable $e) {
                    $output .= $colorTheme->error . "Error: " . $e->getMessage() . PHP_EOL;
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
                    . $colorTheme->visibility . "public "
                    . $colorTheme->key . '"' . $propName . '"'
                    . $colorTheme->punctuation . ": ";
            } else {
                $output .= $indent . "  "
                    . $colorTheme->visibility . "+"
                    . $colorTheme->key . '"' . $propName . '"'
                    . $colorTheme->punctuation . ": ";
            }

            $output .= $this->formatVariable(
                    $config, $colorTheme, $propValue, $depth + 1, $indent . "  "
                )
                . PHP_EOL;
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
