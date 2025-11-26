<?php

namespace lightla\VariableDebugger\Parsed;

class VariableDebugParsedInfo
{
    public string $name;

    public mixed $value;

    public string $accessModifier;

    /** @var string string, integer, float, object, array, closure, ... */
    public string $valueType;

    /** * @var static[] */
    public array $children;

    public function __construct()
    {
    }

    public static function parser(): VariableDebugInfoParser
    {
        return new VariableDebugInfoParser();
    }

    public function hasChildren(): bool
    {
        return ! empty($this->children);
    }
}