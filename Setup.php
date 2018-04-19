<?php

namespace Xfrocks\Medal;

use XF\AddOn\AbstractSetup;
use XF\AddOn\StepRunnerInstallTrait;
use XF\AddOn\StepRunnerUninstallTrait;
use XF\AddOn\StepRunnerUpgradeTrait;
use XF\Db\Schema\Alter;
use XF\Db\Schema\Column;
use XF\Db\Schema\Create;

class Setup extends AbstractSetup
{
    use StepRunnerInstallTrait;
    use StepRunnerUpgradeTrait;
    use StepRunnerUninstallTrait;

    public function installStep1()
    {
        $sm = $this->schemaManager();

        foreach ($this->getTables() as $tableName => $closure) {
            $sm->createTable($tableName, $closure);
        }

        foreach ($this->getColumns() as $tableName => $tableColumns) {
            $sm->alterTable($tableName, function (Alter $table) use ($tableColumns) {
                foreach ($tableColumns as $columnName => $closure) {
                    $column = $table->addColumn($columnName);
                    $closure($column);
                }
            });
        }
    }

    public function installStep2()
    {
        $this->app->jobManager()->enqueue('Xfrocks\Medal:DemoData');
    }

    public function uninstallStep1()
    {
        $sm = $this->schemaManager();

        foreach (array_keys($this->getTables()) as $tableName) {
            $sm->dropTable($tableName);
        }

        foreach ($this->getColumns() as $tableName => $tableColumns) {
            $sm->alterTable($tableName, function (Alter $table) use ($tableColumns) {
                $table->dropColumns(array_keys($tableColumns));
            });
        }
    }

    private function getTables()
    {
        $tables = [];

        $tables['xf_bdmedal_category'] = function (Create $table) {
            $table->addColumn('category_id', 'int')->unsigned()->autoIncrement();
            $table->addColumn('name', 'varchar')->length(255);
            $table->addColumn('description', 'text')->nullable();
            $table->addColumn('display_order', 'int')->unsigned()->setDefault(0);
        };

        $tables['xf_bdmedal_medal'] = function (Create $table) {
            $table->addColumn('medal_id', 'int')->unsigned()->autoIncrement();
            $table->addColumn('name', 'varchar')->length(255);
            $table->addColumn('category_id', 'int')->unsigned();
            $table->addColumn('description', 'text')->nullable();
            $table->addColumn('display_order', 'int')->unsigned()->setDefault(0);
            $table->addColumn('user_count', 'int')->unsigned()->setDefault(0);
            $table->addColumn('last_award_date', 'int')->unsigned()->setDefault(0);
            $table->addColumn('last_award_user_id', 'int')->unsigned()->setDefault(0);
            $table->addColumn('last_award_username', 'varchar')->length(50)->setDefault('');
            $table->addColumn('image_date', 'int')->unsigned()->setDefault(0);
            $table->addColumn('is_svg', 'tinyint')->unsigned()->setDefault(0);

            $table->addKey('category_id');
        };

        $tables['xf_bdmedal_awarded'] = function (Create $table) {
            $table->addColumn('awarded_id', 'int')->unsigned()->autoIncrement();
            $table->addColumn('medal_id', 'int')->unsigned();
            $table->addColumn('user_id', 'int')->unsigned()->setDefault(0);
            $table->addColumn('username', 'varchar')->length(50)->setDefault('');
            $table->addColumn('award_date', 'int')->unsigned()->setDefault(0);
            $table->addColumn('award_reason', 'text');
            $table->addColumn('adjusted_display_order', 'int')->unsigned()->setDefault(0);

            $table->addKey('medal_id');
        };

        return $tables;
    }

    private function getColumns()
    {
        $columns = [];

        $columns['xf_user'] = [];
        $columns['xf_user']['xf_bdmedal_awarded_cached'] = function (Column $column) {
            $column->type('mediumblob')->nullable();
        };

        return $columns;
    }
}
