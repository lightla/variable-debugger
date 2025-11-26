<?php

namespace lightla\VariableDebugger\DebugStrategy\Cli;

use lightla\VariableDebugger\Parsed\VariableDebugParsedInfo;
use lightla\VariableDebugger\VariableDebugConfig;
use lightla\VariableDebugger\VariableDebugPrintStrategy;

class VariableDebugCliPrintStrategy implements VariableDebugPrintStrategy
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

            $parser = VariableDebugParsedInfo::parser();
            $parsedInfo = $parser->parseFrom($var, $config);
            $formattedVar = $this->renderParsedInfo($config, $colorTheme, $parsedInfo, '');
            $lines = explode(PHP_EOL, $formattedVar);
            foreach ($lines as $lineContent) {
                $outputLines[] = $lineContent;
            }
        }

        $this->printFullWidth($colorTheme, $outputLines);
    }

    private function renderParsedInfo(
        VariableDebugConfig $config,
        VariableDebugCliColorTheme $colorTheme,
        VariableDebugParsedInfo $info,
        string $indent
    ): string {
        if ($info->isTruncated) {
            return $colorTheme->comment . $info->truncatedMessage;
        }

        if ($info->isHidden) {
            return $colorTheme->comment . '[hidden]';
        }

        if ($info->isUninitialized) {
            return $colorTheme->comment . '[uninitialized]';
        }

        if ($info->valueType === 'error') {
            return $colorTheme->error . 'Error: ' . $info->value;
        }

        if ($info->valueType === 'comment') {
            return $colorTheme->comment . $info->value;
        }

        if ($info->valueType === 'array') {
            return $this->renderArray($config, $colorTheme, $info, $indent);
        }

        if ($info->valueType === 'object') {
            return $this->renderObject($config, $colorTheme, $info, $indent);
        }

        return $this->renderScalar($config, $colorTheme, $info);
    }

    private function renderScalar(
        VariableDebugConfig $config,
        VariableDebugCliColorTheme $colorTheme,
        VariableDebugParsedInfo $info
    ): string {
        $output = '';

        if ($info->valueType === 'string') {
            if ($config->getShowValueType()) {
                $output .= $colorTheme->type . 'string'
                    . $colorTheme->punctuation . '('
                    . $colorTheme->number . $info->count
                    . $colorTheme->punctuation . ') ';
            }
            return $output . $colorTheme->string . '"' . addcslashes($info->value, '"\\') . '"';
        }

        if ($info->valueType === 'int' || $info->valueType === 'float') {
            if ($config->getShowValueType()) {
                $output .= $colorTheme->type . $info->valueType . $colorTheme->punctuation . '(';
            }
            $output .= $colorTheme->number . $info->value;
            if ($config->getShowValueType()) {
                $output .= $colorTheme->punctuation . ')';
            }
            return $output;
        }

        if ($info->valueType === 'bool') {
            if ($config->getShowValueType()) {
                $output .= $colorTheme->type . 'bool' . $colorTheme->punctuation . '(';
            }
            $output .= $colorTheme->boolNull . ($info->value ? 'true' : 'false');
            if ($config->getShowValueType()) {
                $output .= $colorTheme->punctuation . ')';
            }
            return $output;
        }

        if ($info->valueType === 'null') {
            return $colorTheme->boolNull . 'null';
        }

        if ($config->getShowValueType()) {
            $output .= $colorTheme->type . $info->valueType . ' ';
        }
        return $output . print_r($info->value, true);
    }

    private function renderArray(
        VariableDebugConfig $config,
        VariableDebugCliColorTheme $colorTheme,
        VariableDebugParsedInfo $info,
        string $indent
    ): string {
        $output = '';

        if ($config->getShowValueType()) {
            $output .= $colorTheme->type . "array" . $colorTheme->punctuation
                . "(" . $colorTheme->number . $info->count . $colorTheme->punctuation . ") ";
        }

        if ($info->count === 0) {
            return $output . $colorTheme->punctuation . "[]";
        }

        $output .= $colorTheme->punctuation . "[" . PHP_EOL;
        $newIndent = $indent . "  ";

        foreach ($info->children as $i => $child) {
            $output .= $newIndent;

            if (is_numeric($child->name)) {
                $output .= $colorTheme->number . $child->name;
            } else {
                $output .= $colorTheme->string . '"' . $child->name . '"';
            }

            $output .= $colorTheme->punctuation . " => ";
            $output .= $this->renderParsedInfo($config, $colorTheme, $child, $newIndent);

            if ($i < count($info->children) - 1 && !$child->isTruncated) {
                $output .= $colorTheme->punctuation . ",";
            }
            $output .= PHP_EOL;
        }

        if ($config->resolveShowExcludedCount() && $info->excludedCount > 0) {
            $output .= $newIndent . $colorTheme->comment . "[{$info->excludedCount} excluded]" . PHP_EOL;
        }

        return $output . $indent . $colorTheme->punctuation . "]";
    }

    private function renderObject(
        VariableDebugConfig $config,
        VariableDebugCliColorTheme $colorTheme,
        VariableDebugParsedInfo $info,
        string $indent
    ): string {
        $output = '';

        if ($config->getShowValueType()) {
            $output .= $colorTheme->type . "object" . $colorTheme->punctuation
                . "(" . $colorTheme->className . $info->className . $colorTheme->punctuation . ") "
                . $colorTheme->punctuation . "{" . PHP_EOL;
        } else {
            $output .= $colorTheme->className . $info->className . " "
                . $colorTheme->punctuation . "{" . PHP_EOL;
        }

        $newIndent = $indent . "  ";

        foreach ($info->children as $child) {
            $output .= $newIndent;

            if ($child->accessModifier) {
                $output .= $colorTheme->visibility . $child->accessModifier . " ";
            }

            $output .= $colorTheme->key . $child->name;

            if (!$child->isHidden) {
                $output .= $colorTheme->punctuation . ": ";
                
                if ($child->isTypeOnly) {
                    $output .= $this->renderTypeOnly($colorTheme, $child);
                } else {
                    $output .= $this->renderParsedInfo($config, $colorTheme, $child, $newIndent);
                }
            }

            $output .= PHP_EOL;
        }

        if ($config->resolveShowExcludedCount() && $info->excludedCount > 0) {
            $output .= $newIndent . $colorTheme->comment . "[{$info->excludedCount} excluded]" . PHP_EOL;
        }

        return $output . $indent . $colorTheme->punctuation . "}";
    }

    private function renderTypeOnly(VariableDebugCliColorTheme $colorTheme, VariableDebugParsedInfo $info): string
    {
        if ($info->valueType === 'object') {
            return $colorTheme->type . "object" . $colorTheme->punctuation
                . "(" . $colorTheme->className . $info->className . $colorTheme->punctuation . ")";
        }

        if ($info->valueType === 'array') {
            return $colorTheme->type . "array" . $colorTheme->punctuation
                . "(" . $colorTheme->number . $info->count . $colorTheme->punctuation . ")";
        }

        if ($info->valueType === 'string') {
            return $colorTheme->type . "string" . $colorTheme->punctuation
                . "(" . $colorTheme->number . $info->count . $colorTheme->punctuation . ")";
        }

        if ($info->valueType === 'int' || $info->valueType === 'float') {
            return $colorTheme->type . $info->valueType;
        }

        if ($info->valueType === 'bool') {
            return $colorTheme->type . "bool";
        }

        if ($info->valueType === 'null') {
            return $colorTheme->boolNull . "null";
        }

        return $colorTheme->type . $info->valueType;
    }

    private function printFullWidth(VariableDebugCliColorTheme $colorTheme, array $lines): void
    {
        foreach ($lines as $line) {
            echo $line . $colorTheme->reset . PHP_EOL;
        }
        echo $colorTheme->reset;
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
