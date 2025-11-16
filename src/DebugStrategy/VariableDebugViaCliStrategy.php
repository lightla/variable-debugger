<?php

namespace lightla\VariableDebugger\DebugStrategy;

use lightla\VariableDebugger\VariableDebugConfig;
use lightla\VariableDebugger\VariableDebugStrategy;

class VariableDebugViaCliStrategy implements VariableDebugStrategy
{
    // Bảng màu ANSI mô phỏng theme VS Code Default Dark+
    private const COLOR_RESET = "\033[0m";

    // Background panel giống #2d2d2d (không dùng 40 = black nữa)
    private const BG_BLACK = "\033[48;5;236m";
    // Foreground cho phần fill, trùng với background để "tàng hình"
    private const COLOR_FILL = "\033[38;5;236m";

    private const COLOR_FILE_PATH   = "\033[38;5;117m";  // #9cdcfe
    private const COLOR_LINE_NUMBER = "\033[1;38;5;159m"; // #9cdcfe (sáng)
    private const COLOR_TYPE        = "\033[38;5;80m";   // #4ec9b0
    private const COLOR_STRING      = "\033[38;5;215m";  // #ce9178
    private const COLOR_NUMBER      = "\033[38;5;150m";  // #b5cea8
    private const COLOR_BOOL_NULL   = "\033[38;5;75m";   // #569cd6
    private const COLOR_KEY         = "\033[38;5;117m";  // #9cdcfe
    private const COLOR_CLASS_NAME  = "\033[38;5;182m";   // #4ec9b0
    private const COLOR_VISIBILITY  = "\033[38;5;182m";  // #c586c0
    private const COLOR_PUNCTUATION = "\033[38;5;244m";  // #d4d4d4 (xám)
    private const COLOR_COMMENT     = "\033[38;5;241m";  // #808080
    private const COLOR_ERROR       = "\033[38;5;196m";  // Đỏ

    public function dumpFromTrace(
        VariableDebugConfig $config,
        array $backtrace,
                            ...$vars
    ): void {
        $caller = $backtrace[0];
        $file = $this->calculateFilePathWithoutProjectRootPath($config, $caller['file']);
        $line = $caller['line'];

        $outputLines = [];
        $outputLines[] =
            self::COLOR_PUNCTUATION . "📁" .
            self::COLOR_FILE_PATH . "/{$file}" .
            self::COLOR_PUNCTUATION . ":" .
            self::COLOR_LINE_NUMBER . $line;
        $outputLines[] = self::COLOR_PUNCTUATION . str_repeat('─', 10);

        foreach ($vars as $i => $var) {
            if ($i > 0) {
                // Thêm dòng phân cách giữa các biến
                $outputLines[] = self::COLOR_PUNCTUATION . str_repeat('-', 5);
            }
            $formattedVar = $this->formatVariable($config, $var);
            $lines = explode(PHP_EOL, $formattedVar);
            foreach ($lines as $lineContent) {
                $outputLines[] = $lineContent;
            }
        }

        $this->printFullWidth($outputLines);
    }

    private function printFullWidth(array $lines): void
    {
        $terminalWidth = $this->getTerminalWidth();

        // Màu panel xám đậm
        $bg = self::BG_BLACK;

        // Fill “tàng hình” (fg = bg)
        $fill = self::COLOR_FILL;

        // --- Padding top (1 dòng) ---
        echo $bg . $fill . str_repeat('█', $terminalWidth) . self::COLOR_RESET . PHP_EOL;

        $paddingLeft  = 1;
        $paddingRight = 1;

        foreach ($lines as $line) {
            // Bỏ mã màu để tính width thực
            $plain = preg_replace('/\033\[[0-9;]*m/', '', $line);

            $contentWidth = function_exists('mb_strwidth')
                ? mb_strwidth($plain, 'UTF-8')
                : strlen($plain);

            // Tổng chiều rộng text + padding
            $visibleWidth = $paddingLeft + $contentWidth + $paddingRight;

            // Phần còn lại để fill full width
            $remaining = max(0, $terminalWidth - $visibleWidth);

            echo self::BG_BLACK
                . str_repeat(' ', $paddingLeft)    // padding trái
                . $line                            // nội dung có màu riêng
                . str_repeat(' ', $paddingRight);  // padding phải

            // Filler chiếm hết phần còn lại, fg = bg nên "tàng hình"
            if ($remaining > 0) {
                echo self::COLOR_FILL . str_repeat('█', $remaining);
            }

            echo self::COLOR_RESET . PHP_EOL;
        }

        // --- Padding bottom (1 dòng) ---
        echo $bg . $fill . str_repeat('█', $terminalWidth) . self::COLOR_RESET . PHP_EOL;

        // 1 dòng trống dưới block để dễ đọc
        echo PHP_EOL;
    }


    /**
     * In block với background FULL chiều ngang terminal,
     * sao cho nhìn giống cái card HTML (hình chữ nhật kín).
     */
    private function printFullWidth1(array $lines): void
    {
        // Lấy chiều rộng thực của terminal
        $terminalWidth = $this->getTerminalWidth();

        // Padding đẹp giống margin nội dung
        $paddingLeft  = 1;
        $paddingRight = 1;

        /**
         * Dùng block char █ để fill phần còn thiếu của dòng.
         * Ưu điểm:
         *   - Không bị VSCode wrap
         *   - Không tạo ký tự trắng
         *   - Không bị resize làm lộ background
         *   - Luôn trông như block full-width
         */
        $fillChar = "█";

        foreach ($lines as $line) {

            // Tính độ dài thật (không tính mã màu)
            $plain = preg_replace('/\033\[[0-9;]*m/', '', $line);

            $contentWidth = function_exists('mb_strwidth')
                ? mb_strwidth($plain, 'UTF-8')
                : strlen($plain);

            // Độ dài text + padding
            $visibleWidth = $paddingLeft + $contentWidth + $paddingRight;

            // Phần còn lại để fill full-width
            $remaining = max(0, $terminalWidth - $visibleWidth);

            // In 1 dòng đầy màu background + filler không wrap
            echo self::BG_BLACK
                . str_repeat(' ', $paddingLeft)
                . $line
                . str_repeat(' ', $paddingRight)
                . str_repeat($fillChar, $remaining)
                . self::COLOR_RESET
                . PHP_EOL;
        }

        // Thêm dòng trống phía dưới cho đẹp
        echo PHP_EOL;
    }

    private function getTerminalWidth(): int
    {
        $defaultWidth = 80;
        if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
            $output = [];
            @exec('mode con', $output);
            if (isset($output[4]) && preg_match('/:\s*(\d+)/', $output[4], $matches)) {
                return (int)$matches[1];
            }
        } else {
            $width = @(int)shell_exec('tput cols');
            if ($width > 0) {
                return $width;
            }
        }
        return $defaultWidth;
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
            return self::COLOR_COMMENT . '[Max Depth Reached]';
        }

        if (is_array($var)) return $this->formatArray($config, $var, $depth, $indent);
        if (is_object($var)) return $this->formatObject($config, $var, $depth, $indent);

        $output = '';
        if (is_string($var)) {
            if ($config->getShowValueType()) {
                $output .= self::COLOR_TYPE . 'string' . self::COLOR_PUNCTUATION . '(' . self::COLOR_NUMBER . strlen($var) . self::COLOR_PUNCTUATION . ') ';
            }
            return $output . self::COLOR_STRING . '"' . addcslashes($var, '"\\') . '"';
        }

        if (is_int($var) || is_float($var)) {
            $type = is_int($var) ? 'int' : 'float';
            if ($config->getShowValueType()) {
                $output .= self::COLOR_TYPE . $type . self::COLOR_PUNCTUATION . '(';
            }
            $output .= self::COLOR_NUMBER . $var;
            if ($config->getShowValueType()) {
                $output .= self::COLOR_PUNCTUATION . ')';
            }
            return $output;
        }

        if (is_bool($var)) {
            if ($config->getShowValueType()) {
                $output .= self::COLOR_TYPE . 'bool' . self::COLOR_PUNCTUATION . '(';
            }
            $output .= self::COLOR_BOOL_NULL . ($var ? 'true' : 'false');
            if ($config->getShowValueType()) {
                $output .= self::COLOR_PUNCTUATION . ')';
            }
            return $output;
        }

        if (is_null($var)) {
            return self::COLOR_BOOL_NULL . 'null';
        }

        if ($config->getShowValueType()) {
            $output .= self::COLOR_TYPE . gettype($var) . ' ';
        }
        return $output . print_r($var, true);
    }

    private function formatArray(VariableDebugConfig $config, array $var, int $depth, string $indent): string
    {
        $count = count($var);
        $output = '';
        if ($config->getShowValueType()) {
            $output .= self::COLOR_TYPE . 'array' . self::COLOR_PUNCTUATION . '(' . self::COLOR_NUMBER . $count . self::COLOR_PUNCTUATION . ') ';
        }

        if ($count === 0) {
            return $output . self::COLOR_PUNCTUATION . '[]';
        }

        $output .= self::COLOR_PUNCTUATION . '[' . PHP_EOL;
        $newIndent = $indent . '  ';
        $i = 0;
        $showFirst = ($config->getShowArrayMode()?->isShowFirstElement() && $depth === 0);

        foreach ($var as $key => $value) {
            $output .= $newIndent;
            $keyColor = is_string($key) ? self::COLOR_STRING : self::COLOR_NUMBER;
            $keyStr = is_string($key) ? '"' . $key . '"' : $key;
            $output .= $keyColor . $keyStr . self::COLOR_PUNCTUATION . ' => ';
            $output .= $this->formatVariable($config, $value, $depth + 1, $newIndent);

            if ($i < $count - 1) $output .= self::COLOR_PUNCTUATION . ',';
            $output .= PHP_EOL;
            $i++;

            if ($showFirst && $count > 1) {
                $output .= $newIndent . self::COLOR_COMMENT . '... (and ' . ($count - 1) . ' others)' . PHP_EOL;
                break;
            }
        }
        return $output . $indent . self::COLOR_PUNCTUATION . ']';
    }

    private function formatObject(VariableDebugConfig $config, object $var, int $depth, string $indent): string
    {
        $reflection = new \ReflectionClass($var);
        $className = $reflection->getName();
        $newIndent = $indent . '  ';
        $output = '';

        if ($config->getShowValueType()) {
            $output .= self::COLOR_TYPE . 'object' . self::COLOR_PUNCTUATION . '(' . self::COLOR_CLASS_NAME . $className . self::COLOR_PUNCTUATION . ') ';
        } else {
            $output .= self::COLOR_CLASS_NAME . $className . ' ';
        }
        $output .= self::COLOR_PUNCTUATION . '{' . PHP_EOL;

        $properties = $reflection->getProperties();
        $objectVars = get_object_vars($var);

        if (empty($properties) && empty($objectVars)) {
            return $output . $newIndent . self::COLOR_COMMENT . '# No properties' . PHP_EOL . $indent . self::COLOR_PUNCTUATION . '}';
        }

        foreach ($properties as $prop) {
            $prop->setAccessible(true);
            $visibility = $prop->isPrivate() ? '-' : ($prop->isProtected() ? '#' : '+');
            $output .= $newIndent . self::COLOR_VISIBILITY . $visibility . ' ' . self::COLOR_KEY . $prop->getName() . self::COLOR_PUNCTUATION . ': ';
            $output .= $prop->isInitialized($var)
                ? $this->formatVariable($config, $prop->getValue($var), $depth + 1, $newIndent)
                : self::COLOR_COMMENT . '[uninitialized]';
            $output .= PHP_EOL;
        }

        foreach ($objectVars as $propName => $propValue) {
            if (!$reflection->hasProperty($propName)) {
                $output .= $newIndent . self::COLOR_VISIBILITY . '+ ' . self::COLOR_KEY . '"' . $propName . '"' . self::COLOR_PUNCTUATION . ': ';
                $output .= $this->formatVariable($config, $propValue, $depth + 1, $newIndent);
                $output .= PHP_EOL;
            }
        }

        return rtrim($output) . PHP_EOL . $indent . self::COLOR_PUNCTUATION . '}';
    }

    private function calculateFilePathWithoutProjectRootPath(VariableDebugConfig $config, string $filePath): string
    {
        if ($config->getProjectRootPath()) {
            return str_replace($config->getProjectRootPath() . '/', '', $filePath);
        }
        return ltrim($filePath, '/');
    }
}
