<?php

use yii\db\Migration;

class m190516_073620_last_update_time extends Migration
{
    const TABLE_NAME = "{{%last_update_time}}";

    public function safeUp()
    {
        $this->createTable(self::TABLE_NAME, [
                'id'          => $this->string(20)->notNull(),
                'updated_at'  => $this->integer(11)->null(),
                'offset_time' => $this->string(32)->null(),
        ]);
        $this->addPrimaryKey('pk_last_update_time', self::TABLE_NAME, 'id');
        $this->insert(self::TABLE_NAME, ['id' => 'complaints', 'updated_at' => null, 'offset_time' => null]);
        $this->insert(self::TABLE_NAME, ['id' => 'decisions', 'updated_at' => null, 'offset_time' => null]);
    }

    public function safeDown()
    {
        $this->dropTable(self::TABLE_NAME);
    }
}
