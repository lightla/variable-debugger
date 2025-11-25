<?php

namespace lightla\VariableDebugger\Config;

use phpDocumentor\Reflection\Types\Static_;

enum VariableDebugClassPropertyShowValueMode: string
{
    case SHOW_DETAIL = 'SHOW_DETAIL';
    case SHOW_TYPE_ONLY = 'SHOW_TYPE_ONLY';

    public function isShowTypeOnly(): bool
    {
        return $this === static::SHOW_TYPE_ONLY;
    }
}
