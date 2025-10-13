<?php

namespace Mattiverse\Userstamps;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\ServiceProvider;
use InvalidArgumentException;

class UserstampsServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->registerBlueprintMacros();
    }

    protected function registerBlueprintMacros(): void
    {
        Blueprint::macro(
            'userstamps',
            function ($dataType = 'unsignedBigInteger') {
                switch ($dataType) {
                    case 'unsignedBigInteger':
                        $this->foreignId('created_by')->nullable();
                        $this->foreignId('updated_by')->nullable();
                        break;

                    case 'uuid':
                        $this->uuid('created_by')->nullable();
                        $this->uuid('updated_by')->nullable();
                        break;

                    default:
                        throw new InvalidArgumentException(`Unsupported data type : $dataType`);
                }
            }
        );

        Blueprint::macro(
            'userstampSoftDeletes',
            function ($dataType = 'unsignedBigInteger') {
                switch ($dataType) {
                    case 'unsignedBigInteger':
                        $this->foreignId('deleted_by')->nullable();
                        break;

                    case 'uuid':
                        $this->uuid('deleted_by')->nullable();
                        break;

                    default:
                        throw new InvalidArgumentException(`Unsupported data type : $dataType`);
                }
            }
        );

        Blueprint::macro(
            'dropUserstamps',
            function () {
                $this->dropColumn('created_by', 'updated_by');
            }
        );

        Blueprint::macro(
            'dropUserstampSoftDeletes',
            function () {
                $this->dropColumn('deleted_by');
            }
        );
    }
}
