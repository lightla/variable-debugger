<?php

namespace lightla\VariableDebugger\DebugStrategy\Web;

use lightla\VariableDebugger\Parsed\VariableDebugParsedInfo;
use lightla\VariableDebugger\VariableDebugConfig;
use lightla\VariableDebugger\VariableDebugPrintStrategy;

class VariableDebugWebPrintStrategy implements VariableDebugPrintStrategy
{
    public function printFromTrace(
        VariableDebugConfig $config,
        array $backtrace,
        ...$vars
    ): void {
        if (! $config->resolveAllowPrintOrDefault()) {
            return;
        }

        $theme = $config->resolveWebThemeOrDefault();

        $caller = $backtrace[0];
        $caller['file'] = $this->calculateFilePathWithoutProjectRootPath($config, $caller['file']);

        $file = htmlspecialchars($caller['file']);
        $line = htmlspecialchars($caller['line']);

        $bg = $theme->background;
        $txt = $theme->text;
        $bdr = $theme->border;
        $fp = $theme->filePath;

        echo $this->minimize('<script>
            if (typeof vdToggle === "undefined") {
                function vdToggle(e){
                    const c=e.nextSibling,d=e.querySelector(".vd-dots");
                    
                    if(c.style.display==="none"){
                        c.style.display="";d.innerHTML="&lt;&lt;&lt;"
                    }else{
                        c.style.display="none";d.textContent="..."
                    }
                }
            }
        </script>');

        echo "<div style=\"background:{$bg};color:{$txt};padding:15px;margin:15px 0;border:1px solid {$bdr};font-family:Consolas,Monaco,monospace;font-size:12px;z-index:99999;\">";
        echo "<div style=\"border-bottom:1px solid {$bdr};padding-bottom:10px;margin-bottom:10px;color:{$fp};\">";
        echo "<strong>📁/</strong>{$file}:{$line}<br>";
        echo '</div>';

        foreach ($vars as $var) {
            echo "<div style=\"margin:10px 0;padding:10px;border:1px solid {$bdr};\">";
            echo '<pre style="margin:0;font-family:inherit;white-space:pre-wrap;">';
            
            $parser = VariableDebugParsedInfo::parser();
            $parsedInfo = $parser->parseFrom($var, $config);
            echo $this->renderParsedInfo($config, $theme, $parsedInfo, '');
            
            echo '</pre>';
            echo '</div>';
        }
        echo '</div>';
    }

    private function renderParsedInfo(
        VariableDebugConfig $config,
        VariableDebugWebColorTheme $theme,
        VariableDebugParsedInfo $info,
        string $indent
    ): string {
        if ($info->isTruncated) {
            return $this->c($theme, $theme->comment, $info->truncatedMessage);
        }

        if ($info->isHidden) {
            return $this->c($theme, $theme->comment, '[hidden]');
        }

        if ($info->isUninitialized) {
            return $this->c($theme, $theme->comment, '[uninitialized]');
        }

        if ($info->valueType === 'error') {
            return $this->c($theme, $theme->error, 'Error: ' . htmlspecialchars($info->value));
        }

        if ($info->valueType === 'comment') {
            return $this->c($theme, $theme->comment, htmlspecialchars($info->value));
        }

        if ($info->valueType === 'array') {
            return $this->renderArray($config, $theme, $info, $indent);
        }

        if ($info->valueType === 'object') {
            return $this->renderObject($config, $theme, $info, $indent);
        }

        return $this->renderScalar($config, $theme, $info);
    }

    private function renderScalar(
        VariableDebugConfig $config,
        VariableDebugWebColorTheme $theme,
        VariableDebugParsedInfo $info
    ): string {
        $output = '';

        if ($info->valueType === 'string') {
            if ($config->getShowValueType()) {
                $output .= $this->c($theme, $theme->type, 'string') . '(' 
                    . $this->c($theme, $theme->number, (string)$info->count) . ') ';
            }
            return $output . $this->c($theme, $theme->string, '"' . htmlspecialchars($info->value) . '"');
        }

        if ($info->valueType === 'int' || $info->valueType === 'float') {
            if ($config->getShowValueType()) {
                $output .= $this->c($theme, $theme->type, $info->valueType) . '(' 
                    . $this->c($theme, $theme->number, (string)$info->value) . ')';
            } else {
                $output .= $this->c($theme, $theme->number, (string)$info->value);
            }
            return $output;
        }

        if ($info->valueType === 'bool') {
            if ($config->getShowValueType()) {
                $output .= $this->c($theme, $theme->type, 'bool') . '(' 
                    . $this->c($theme, $theme->boolNull, $info->value ? 'true' : 'false') . ')';
            } else {
                $output .= $this->c($theme, $theme->boolNull, $info->value ? 'true' : 'false');
            }
            return $output;
        }

        if ($info->valueType === 'null') {
            return $this->c($theme, $theme->boolNull, 'null');
        }

        if ($config->getShowValueType()) {
            $output .= '<span style="color:#4ec9b0;">' . htmlspecialchars($info->valueType) . '</span> ';
        }
        return $output . htmlspecialchars(print_r($info->value, true));
    }

    private function renderArray(
        VariableDebugConfig $config,
        VariableDebugWebColorTheme $theme,
        VariableDebugParsedInfo $info,
        string $indent
    ): string {
        $output = '';

        if ($config->getShowValueType()) {
            $output .= $this->c($theme, $theme->type, 'array') . '(' 
                . $this->c($theme, $theme->number, (string)$info->count) . ') ';
        }

        if ($info->count === 0) {
            return $output . $this->c($theme, $theme->comment, '[]');
        }

        $output .= '<span class="vd-toggle" onclick="vdToggle(this)" style="cursor:pointer;user-select:none;">[<span class="vd-dots">&lt;&lt;&lt;</span></span><span class="vd-content"><br>';
        $newIndent = $indent . '  ';

        foreach ($info->children as $i => $child) {
            $output .= $newIndent;

            if (is_numeric($child->name)) {
                $output .= $this->c($theme, $theme->number, htmlspecialchars($child->name));
            } else {
                $output .= $this->c($theme, $theme->string, '"' . htmlspecialchars($child->name) . '"');
            }

            $output .= ' ' . $this->c($theme, $theme->punctuation, '=>') . ' ';
            $output .= $this->renderParsedInfo($config, $theme, $child, $newIndent);

            if ($i < count($info->children) - 1 && !$child->isTruncated) {
                $output .= $this->c($theme, $theme->punctuation, ',');
            }
            $output .= '<br>';
        }

        if ($config->resolveShowExcludedCount() && $info->excludedCount > 0) {
            $output .= $newIndent . $this->c($theme, $theme->comment, "# [{$info->excludedCount} excluded]") . '<br>';
        }

        $output .= $indent . '</span>]';
        return $output;
    }

    private function renderObject(
        VariableDebugConfig $config,
        VariableDebugWebColorTheme $theme,
        VariableDebugParsedInfo $info,
        string $indent
    ): string {
        $output = '';

        if ($config->getShowValueType()) {
            $output .= $this->c($theme, $theme->type, 'object') . '(' 
                . $this->c($theme, $theme->className, htmlspecialchars($info->className)) . ') ';
        } else {
            $output .= $this->c($theme, $theme->className, htmlspecialchars($info->className)) . ' ';
        }

        $output .= '<span class="vd-toggle" onclick="vdToggle(this)" style="cursor:pointer;user-select:none;">{<span class="vd-dots">&lt;&lt;&lt;</span></span><span class="vd-content"><br>';
        $newIndent = $indent . '  ';

        foreach ($info->children as $child) {
            $output .= $newIndent;

            if ($child->accessModifier) {
                $output .= $this->c($theme, $theme->visibility, $child->accessModifier) . ' ';
            }

            $output .= $this->c($theme, $theme->key, htmlspecialchars($child->name));

            if (!$child->isHidden) {
                $output .= ': ';
                
                if ($child->isTypeOnly) {
                    $output .= $this->renderTypeOnly($config, $theme, $child);
                } else {
                    $output .= $this->renderParsedInfo($config, $theme, $child, $newIndent);
                }
            }

            $output .= '<br>';
        }

        if ($config->resolveShowExcludedCount() && $info->excludedCount > 0) {
            $output .= $newIndent . $this->c($theme, $theme->comment, "# [{$info->excludedCount} excluded]") . '<br>';
        }

        $output .= $indent . '</span>}';
        return $output;
    }

    private function renderTypeOnly(
        VariableDebugConfig $config,
        VariableDebugWebColorTheme $theme,
        VariableDebugParsedInfo $info
    ): string {
        if ($info->valueType === 'object') {
            return $this->c($theme, $theme->type, 'object') . '(' 
                . $this->c($theme, $theme->className, htmlspecialchars($info->className)) . ')';
        }

        if ($info->valueType === 'array') {
            return $this->c($theme, $theme->type, 'array') . '(' 
                . $this->c($theme, $theme->number, (string)$info->count) . ')';
        }

        if ($info->valueType === 'string') {
            return $this->c($theme, $theme->type, 'string') . '(' 
                . $this->c($theme, $theme->number, (string)$info->count) . ')';
        }

        if ($info->valueType === 'int' || $info->valueType === 'float') {
            return $this->c($theme, $theme->type, $info->valueType);
        }

        if ($info->valueType === 'bool') {
            return $this->c($theme, $theme->type, 'bool');
        }

        if ($info->valueType === 'null') {
            return $this->c($theme, $theme->boolNull, 'null');
        }

        return $this->c($theme, $theme->type, htmlspecialchars($info->valueType));
    }

    private function calculateFilePathWithoutProjectRootPath(
        VariableDebugConfig $config,
        string $filePath
    ): string {
        if ($config->getProjectRootPath()) {
            return str_replace($config->getProjectRootPath() . '/', '', $filePath);
        }
        return ltrim($filePath, '/');
    }

    private function c(VariableDebugWebColorTheme $theme, string $color, string $text): string
    {
        return $color ? "<span style=\"color:{$color};\">{$text}</span>" : $text;
    }

    private function minimize(string $raw): string
    {
        return preg_replace('/\s+/', '', $raw);
    }
}
