<?php

namespace lightla\VariableDebugger\Adapters\Laravel;

use lightla\VariableDebugger\Adapters\VariableDebugClassPropertyPluginAdapter;
use lightla\VariableDebugger\Config\VariableDebugClassPropertyShowValueMode;
use lightla\VariableDebugger\Config\VariableDebugConfigurator;

class VariableDebugClassPropertyPluginAdapterLaravel implements VariableDebugClassPropertyPluginAdapter
{
    public function applyTo(
        VariableDebugConfigurator $configurator
    ): void
    {
        $configurator->addClassProperties(
            \Illuminate\Database\Eloquent\Model::class,
            self::getPropertiesForLaravelEloquentModel()
        );

        $configurator->addClassProperties(
            \Illuminate\Support\Collection::class,
            self::getPropertiesForLaravelCollection()
        );

        $configurator->addClassProperties(
            \Illuminate\Database\Eloquent\Factories\Factory::class,
            self::getPropertiesForLaravelEloquentFactory()
        );

        $configurator->addClassProperties(
            \Illuminate\Database\Query\Builder::class,
            self::getPropertiesForLaravelDatabaseQueryBuilder()
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

    public static function getPropertiesForLaravelCollection(): array
    {
        return [
            'items',
            'escapeWhenCastingToString',
        ];
    }

    public static function getPropertiesForLaravelEloquentFactory(): array
    {
        return [
            'model',
            'count',
            'states',
            'has',
            'for',
            'recycle',
            'afterMaking',
            'afterCreating',
            'expandRelationships',
            'excludeRelationships',
            'connection',
        ];
    }

    private static function getPropertiesForLaravelDatabaseQueryBuilder()
    {
        return [
            'connection' => VariableDebugClassPropertyShowValueMode::SHOW_TYPE_ONLY,
            'grammar' => VariableDebugClassPropertyShowValueMode::SHOW_TYPE_ONLY,
            'processor' => VariableDebugClassPropertyShowValueMode::SHOW_TYPE_ONLY,
            'bindings',
            'aggregate',
            'columns',
            'distinct',
            'from',
            'indexHint',
            'joins',
            'wheres',
            'groups',
            'havings',
            'orders',
            'limit',
            'groupLimit',
            'offset',
            'unions',
            'unionLimit',
            'unionOffset',
            'unionOrders',
            'lock',
            'beforeQueryCallbacks',
            'afterQueryCallbacks',
            'operators',
            'bitwiseOperators',
            'useWritePdo',
        ];
    }
}