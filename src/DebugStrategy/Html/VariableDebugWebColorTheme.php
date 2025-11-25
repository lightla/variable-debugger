<?php

namespace lightla\VariableDebugger\DebugStrategy\Html;

class VariableDebugWebColorTheme
{
    public string $background     = "";
    public string $text           = "";
    public string $border         = "";

    public string $filePath       = "";
    public string $lineNumber     = "";
    public string $type           = "";
    public string $string         = "";
    public string $number         = "";
    public string $boolNull       = "";
    public string $key            = "";
    public string $className      = "";
    public string $visibility     = "";
    public string $punctuation    = "";
    public string $comment        = "";
    public string $error          = "";

    private function __construct() {}

    /** DARK THEME — VS Code Dark+ */
    public static function dark(): self
    {
        $t = new self();
        $t->background  = "#2d2d2d";
        $t->text        = "#a5a5a5";
        $t->border      = "#555";
        $t->filePath    = "#72c3e3";
        $t->lineNumber  = "#72c3e3";
        $t->type        = "#4ec9b0";
        $t->string      = "#ce9178";
        $t->number      = "#b5cea8";
        $t->boolNull    = "#569cd6";
        $t->key         = "#72c3e3";
        $t->className   = "#c586c0";
        $t->visibility  = "#c586c0";
        $t->punctuation = "#d4d4d4";
        $t->comment     = "#808080";
        $t->error       = "#ff6b6b";
        return $t;
    }

    /** LIGHT THEME — VS Code Light */
    public static function light(): self
    {
        $t = new self();
        $t->background  = "#F5F5F5";
        $t->text        = "#333333";
        $t->border      = "#a9a9a9";
        $t->filePath    = "#0000ff";
        $t->lineNumber  = "#0000ff";
        $t->type        = "#267f99";
        $t->string      = "#a31515";
        $t->number      = "#098658";
        $t->boolNull    = "#0000ff";
        $t->key         = "#001080";
        $t->className   = "#267f99";
        $t->visibility  = "#af00db";
        $t->punctuation = "#000000";
        $t->comment     = "#808080";
        $t->error       = "#cd3131";
        return $t;
    }

    public static function noColor(): self
    {
        $t = new self();
        $t->background  = "#ffffff";
        $t->text        = "#000000";
        $t->border      = "#cccccc";
        $t->filePath    = "";
        $t->lineNumber  = "";
        $t->type        = "";
        $t->string      = "";
        $t->number      = "";
        $t->boolNull    = "";
        $t->key         = "";
        $t->className   = "";
        $t->visibility  = "";
        $t->punctuation = "";
        $t->comment     = "";
        $t->error       = "";
        return $t;
    }
}
