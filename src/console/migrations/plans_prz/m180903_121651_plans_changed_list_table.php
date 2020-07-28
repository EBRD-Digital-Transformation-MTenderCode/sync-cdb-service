<?php

use yii\db\Migration;

class m180903_121651_plans_changed_list_table extends Migration
{
    CONST TABLE_NAME = "{{%plans_prz_changed_list}}";

    public function safeUp()
    {
        $this->createTable(
            self::TABLE_NAME,
            [
                'plan_id' => $this->string(32)->unique(),
                'date_modified' => $this->string(32),
            ]
        );
        $this->addPrimaryKey('pk_plans_prz_changed_list', self::TABLE_NAME, 'plan_id');
    }

    public function safeDown()
    {
        $this->dropTable(self::TABLE_NAME);

        return false;
    }
}