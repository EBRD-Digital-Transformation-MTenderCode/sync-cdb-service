<?php

use yii\db\Migration;


class m180409_114427_budgets_changed_list_table extends Migration
{
    CONST TABLE_NAME = "{{%budgets_changed_list}}";

    public function safeUp()
    {
        $this->createTable(
            self::TABLE_NAME,
            [
                'ocid' => $this->string(32)->unique(),
                'date_modified' => $this->string(32),
            ]
        );
        $this->addPrimaryKey('pk_budgets_changed_list', self::TABLE_NAME, 'ocid');
    }

    public function safeDown()
    {
        $this->dropTable(self::TABLE_NAME);

        return false;
    }
}
