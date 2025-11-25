<?php

namespace lightla\VariableDebugger\Adapters\Laravel;

use lightla\VariableDebugger\Adapters\VariableDebugClassPropertyPluginAdapter;
use lightla\VariableDebugger\Config\VariableDebugConfigurator;

class VariableDebugClassPropertyPluginAdapterLaravel implements VariableDebugClassPropertyPluginAdapter
{
    public function applyTo(
        VariableDebugConfigurator $configurator
    ): void
    {
        $configurator->withClassProperties(
            \Illuminate\Database\Eloquent\Model::class,
            self::getPropertiesForLaravelEloquentModel()
        );
    }

    public static function getPropertiesForLaravelEloquentModel(): array
    {
        return [
            'connection',
            'table',
            'primaryKey',
            'keyType',
            'incrementing',
            'with',
            'withCount',
            'preventsLazyLoading',
            'perPage',
            'exists',
            'wasRecentlyCreated',
            'escapeWhenCastingToString',
            'attributes',
            'original',
            'changes',
            'previous',
            'casts',
            'classCastCache',
            'attributeCastCache',
            'dateFormat',
            'appends',
            'dispatchesEvents',
            'observables',
            'relations',
            'touches',
            'relationAutoloadCallback',
            'relationAutoloadContext',
            'timestamps',
            'usesUniqueIds',
            'hidden',
            'visible',
            'fillable',
            'guarded',
            'authPasswordName',
            'rememberTokenName',
        ];
    }
}