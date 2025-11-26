<?php

namespace lightla\VariableDebugger\Parsed;

class VariableDebugInfoParser
{
    public function parseFrom(mixed $param): VariableDebugParsedInfo
    {
        $parsedInfo = new VariableDebugParsedInfo();

//        $parsedInfo->name = $param;

        return $parsedInfo;
    }
}