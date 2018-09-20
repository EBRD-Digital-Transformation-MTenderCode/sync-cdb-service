<?php

use yii\db\Migration;

class m180409_130127_last_update_time extends Migration
{
    CONST TABLE_NAME = "{{%last_update_time}}";

    public function safeUp()
    {
        $this->createTable(
            self::TABLE_NAME,
            [
                'id'=> $this->string(20)->notNull(),
                'updated_at' => $this->integer(11)->null(),
                'offset_time' => $this->string(32)->null(),
            ]
        );
        $this->addPrimaryKey('pk_last_update_time', self::TABLE_NAME, 'id');
        $this->insert(self::TABLE_NAME, ['id' => 'budgets-changed-list', 'updated_at' => null, 'offset_time' => null]);
    }

    public function safeDown()
    {

        $this->dropTable(self::TABLE_NAME);

        return false;
    }

}
