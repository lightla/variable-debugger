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
        $configurator->addClassProperties(
            \Illuminate\Database\Eloquent\Model::class,
            self::getPropertiesForLaravelEloquentModel()
        );

        $configurator->addClassProperties(
            \Illuminate\Support\Collection::class,
            self::getPropertiesForLaravelCollection()
        );

//        $configurator->addClassProperties(
//            \Illuminate\Database\Eloquent\Model::class,
//            self::getPropertiesForLaravelEloquentModel()
//        );

        $map = [
            #model: null
            #count: null
            #states: Illuminate\Support\Collection^ {#626
            #items: array:2 [
            #escapeWhenCastingToString:
            #has: Illuminate\Support\Collection^ {#592
            //    #items: []
            //    #escapeWhenCastingToString: false
            //  }
            // #for: like has
            // ##recycle:: like has
            // ###afterMaking::: like has
            // ####afterCreating:::: like has

            #expandRelationships: true
            #excludeRelationships: []
            #connection: null
            #faker: Faker\Generator^ {#59
            #providers: array:21 [
//                    0 => Faker\Provider\Uuid^ {#619
//                    #generator: Faker\Generator^ {#597}
//                    #unique: null
//                }
            // Faker\Provider\UserAgent like UUid
            //  Faker\Provider\en_US\Text
            #generator: Faker\Generator^ {#597}
            #unique: null
            #explodedText: null
            #consecutiveWords: []
            // Faker\Provider\en_US\PhoneNumber^ like UUid
            // Faker\Provider\en_US\Person^ like UUid
            // Faker\Provider\en_US\Payment^ like UUid
            //  Faker\Provider\Miscellaneous^ like UUid
            //  Faker\Provider\Medical^  like UUid
            //  Faker\Provider\Lorem^   like UUid
            //  Faker\Provider\Internet^   like UUid
            // Faker\Provider\Image^   like UUid
            // Faker\Provider\HtmlLorem^
            // Faker\Provider\Internet^
            // Faker\Provider\Lorem^
            // Faker\Provider\File
            // Faker\Provider\DateTime^
            //  Faker\Provider\en_US\Company^
            // Faker\Provider\Color^
            // Faker\Provider\Biased^
            //  Faker\Provider\Barcode^
            //   Faker\Provider\en_US\Address^
            #formatters: []
            // -container: Faker\Container\Container^ {#599
            // -definitions:
            // --services::
//        -uniqueGenerator: Faker\UniqueGenerator^ {#620
//              #generator: Faker\Generator^ {#597}
//              #maxRetries: 10000
//              #uniques: []
//    }

        ];
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
}