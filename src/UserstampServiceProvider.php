<?php

namespace Wildside\Userstamps;

use Illuminate\Support\Fluent;
use Illuminate\Support\ServiceProvider;
use Illuminate\Database\Schema\Blueprint;

class UserstampServiceProvider extends ServiceProvider
{
    /**
     * Register the userstamp shortcuts.
     *
     * @return void
     */
    public function boot()
    {
        /**
         * Add nullable created and updated by columns to the table.
         *
         * @return void
         */
        Blueprint::macro('userstamps', function(){
            // Create table colums from scratch
            $parameters = [
                'type' => 'integer',
                'autoIncrement' => false,
                'unsigned' => true,
                'nullable' => true,
                'default' => null
            ];
            // Make sure userstamp columns get inserted next to their timestamp counterparts
            for ($id = 0; $id < count($this->columns); $id++) {
                switch ($this->columns[$id]->name) {
                    case 'created_at':
                        array_splice($this->columns, $id+1, 0, []);
                        $this->columns[$id+1] = new Fluent(['name' => 'created_by'] + $parameters);
                        break;
                    case 'updated_at':
                        array_splice($this->columns, $id+1, 0, []);
                        $this->columns[$id+1] = new Fluent(['name' => 'updated_by'] + $parameters);
                        break;
                }
            }
        });
        
        /**
         * Indicate that the userstamp columns should be dropped.
         *
         * @return void
         */
        Blueprint::macro('dropUserstamps', function(){
            $this->dropColumn(['created_by', 'updated_by']);
        });
    
        /**
         * Add a "deleted by" column to the table.
         *
         * @return \Illuminate\Support\Fluent
         */
        Blueprint::macro('softDeletedBy', function(){
            $this->unsignedInteger('deleted_by')->nullable()->default(null);
        });
    
        /**
         * Indicate that the deleted by column should be dropped.
         *
         * @return void
         */
        Blueprint::macro('dropSoftDeletedBy', function(){
            $this->unsignedInteger('deleted_by')->nullable()->default(null);
        });
    }
}
