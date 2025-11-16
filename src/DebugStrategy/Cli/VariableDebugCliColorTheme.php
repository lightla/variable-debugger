<?php

namespace lightla\VariableDebugger\DebugStrategy\Cli;

class VariableDebugCliColorTheme
{
    public string $reset          = "";

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
        $t->reset       = "\033[0m";
        $t->filePath    = "\033[38;5;117m";
        $t->lineNumber  = "\033[1;38;5;159m";
        $t->type        = "\033[38;5;80m";
        $t->string      = "\033[38;5;215m";
        $t->number      = "\033[38;5;150m";
        $t->boolNull    = "\033[38;5;75m";
        $t->key         = "\033[38;5;117m";
        $t->className   = "\033[38;5;182m";
        $t->visibility  = "\033[38;5;182m";
        $t->punctuation = "\033[38;5;244m";
        $t->comment     = "\033[38;5;241m";
        $t->error       = "\033[38;5;196m";
        return $t;
    }

    /** LIGHT THEME — VS Code Light / JetBrains Light */
    public static function light(): self
    {
        $t = new self();
        $t->reset       = "\033[0m";
        $t->filePath    = "\033[38;5;25m";   // xanh navy
        $t->lineNumber  = "\033[1;38;5;69m";
        $t->type        = "\033[38;5;27m";
        $t->string      = "\033[38;5;130m"; // nâu
        $t->number      = "\033[38;5;28m";  // xanh đậm
        $t->boolNull    = "\033[38;5;27m";
        $t->key         = "\033[38;5;25m";
        $t->className   = "\033[38;5;90m";  // tím nhạt
        $t->visibility  = "\033[38;5;90m";
        $t->punctuation = "\033[38;5;240m";
        $t->comment     = "\033[38;5;244m";
        $t->error       = "\033[38;5;196m";
        return $t;
    }

    public static function noColor(): self
    {
        $t = new self();

        // tất cả empty string
        $t->reset       = '';
        $t->filePath    = '';
        $t->lineNumber  = '';
        $t->type        = '';
        $t->string      = '';
        $t->number      = '';
        $t->boolNull    = '';
        $t->key         = '';
        $t->className   = '';
        $t->visibility  = '';
        $t->punctuation = '';
        $t->comment     = '';
        $t->error       = '';

        return $t;
    }
}