<?php

namespace lightla\VariableDebugger\Parsed;

class VariableDebugParsedInfo
{
    public ?string $name = null;
    public mixed $value = null;
    public ?string $accessModifier = null; // '+', '-', '#', 'public', 'private', 'protected'
    public string $valueType; // 'string', 'int', 'float', 'bool', 'null', 'array', 'object'
    public ?string $className = null; // For objects
    public ?int $count = null; // For arrays/strings
    public bool $isUninitialized = false;
    public bool $isTruncated = false;
    public ?string $truncatedMessage = null;
    public bool $isHidden = false; // For showKeyOnly mode
    public bool $isTypeOnly = false; // For SHOW_TYPE_ONLY mode
    public int $excludedCount = 0;
    
    /** @var self[] */
    public array $children = [];

    public static function parser(): VariableDebugInfoParser
    {
        return new VariableDebugInfoParser();
    }

    public function hasChildren(): bool
    {
        return !empty($this->children);
    }
}
