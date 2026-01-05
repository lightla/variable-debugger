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
    public bool $reflectsLiteralMatch = true;

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

    /**
     * Check if this node has actual content to display.
     * Returns false if the node only has hidden/excluded children with no visible values.
     */
    public function hasActualContent(): bool
    {
        // For containers (array/object), they have content if either:
        // 1. They explicitly matched (e.g. they are the target of a pattern)
        // 2. They have at least one child with actual content
        // 3. They are truncated/hidden/uninitialized (these are displayable states)
        if (in_array($this->valueType, ['array', 'object'])) {
            if ($this->isTruncated || $this->isHidden || $this->isUninitialized) {
                return true;
            }

            if ($this->reflectsLiteralMatch) {
                return true;
            }

            foreach ($this->children as $child) {
                if ($child->hasActualContent()) {
                    return true;
                }
            }

            return false;
        }

        // For non-containers, they have content ONLY if they reflected a literal match
        return $this->reflectsLiteralMatch;
    }
}
