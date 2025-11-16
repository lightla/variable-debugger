<?php

namespace lightla\VariableDebugger\Config;

enum VariableDebugConfigArrayShowMode: string
{
    case SHOW_ALL_ELEMENT = 'show_all_element';
    case SHOW_FIRST_ELEMENT = 'show_first_element';

    public function isShowFirstElement(): bool
    {
        return $this === VariableDebugConfigArrayShowMode::SHOW_FIRST_ELEMENT;
    }

    public function isShowAllElement(): bool
    {
        return $this === VariableDebugConfigArrayShowMode::SHOW_ALL_ELEMENT;
    }
}
