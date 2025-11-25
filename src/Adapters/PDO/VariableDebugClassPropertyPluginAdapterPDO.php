<?php

namespace lightla\VariableDebugger\Adapters\PDO;

use lightla\VariableDebugger\Adapters\VariableDebugClassPropertyPluginAdapter;
use lightla\VariableDebugger\Config\VariableDebugConfigurator;
use PDO;

class VariableDebugClassPropertyPluginAdapterPDO implements VariableDebugClassPropertyPluginAdapter
{
    public function applyTo(VariableDebugConfigurator $configurator): void
    {
        $configurator->addBuildLaterClassProperties(PDO::class, [$this, 'callbackBuildPropertiesForPDO']);
    }

    public function callbackBuildPropertiesForPDO(PDO $pdo): array
    {
        $attributes = [];
        
        $attrMap = [
            'CASE' => PDO::ATTR_CASE,
            'ERRMODE' => PDO::ATTR_ERRMODE,
            'PERSISTENT' => PDO::ATTR_PERSISTENT,
            'DRIVER_NAME' => PDO::ATTR_DRIVER_NAME,
            'ORACLE_NULLS' => PDO::ATTR_ORACLE_NULLS,
            'CLIENT_VERSION' => PDO::ATTR_CLIENT_VERSION,
            'SERVER_VERSION' => PDO::ATTR_SERVER_VERSION,
            'STATEMENT_CLASS' => PDO::ATTR_STATEMENT_CLASS,
            'STRINGIFY_FETCHES' => PDO::ATTR_STRINGIFY_FETCHES,
            'DEFAULT_FETCH_MODE' => PDO::ATTR_DEFAULT_FETCH_MODE,
        ];
        
        foreach ($attrMap as $name => $constant) {
            try {
                $value = $pdo->getAttribute($constant);
                
                // Convert constant values to readable names
                if ($name === 'CASE') {
                    $value = match($value) {
                        PDO::CASE_NATURAL => 'NATURAL',
                        PDO::CASE_LOWER => 'LOWER',
                        PDO::CASE_UPPER => 'UPPER',
                        default => $value
                    };
                } elseif ($name === 'ERRMODE') {
                    $value = match($value) {
                        PDO::ERRMODE_SILENT => 'SILENT',
                        PDO::ERRMODE_WARNING => 'WARNING',
                        PDO::ERRMODE_EXCEPTION => 'EXCEPTION',
                        default => $value
                    };
                } elseif ($name === 'ORACLE_NULLS') {
                    $value = match($value) {
                        PDO::NULL_NATURAL => 'NATURAL',
                        PDO::NULL_EMPTY_STRING => 'EMPTY_STRING',
                        PDO::NULL_TO_STRING => 'TO_STRING',
                        default => $value
                    };
                } elseif ($name === 'DEFAULT_FETCH_MODE') {
                    $value = match($value) {
                        PDO::FETCH_BOTH => 'BOTH',
                        PDO::FETCH_ASSOC => 'ASSOC',
                        PDO::FETCH_NUM => 'NUM',
                        PDO::FETCH_OBJ => 'OBJ',
                        default => $value
                    };
                }
                
                $attributes[$name] = $value;
            } catch (\PDOException $e) {
                // Skip unsupported attributes
            }
        }
        
        return [
            'inTransaction' => $pdo->inTransaction(),
            'attributes' => $attributes,
        ];
    }
}
