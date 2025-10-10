<?php

namespace Mattiverse\Userstamps;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\ServiceProvider;

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
            function ($dataType) {
                $this->$dataType('created_by')->nullable();
                $this->$dataType('updated_by')->nullable();
            }
        );


        Blueprint::macro(
            'userstampSoftDeletes',
            function ($dataType) {
                $this->$dataType('deleted_by')->nullable();
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
