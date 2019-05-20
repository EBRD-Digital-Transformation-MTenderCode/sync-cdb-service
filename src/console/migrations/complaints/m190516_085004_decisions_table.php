<?php

use yii\db\Migration;

class m190516_085004_decisions_table extends Migration
{
    const TABLE = '{{%decisions}}';

    public function safeUp()
    {
        $this->createTable(self::TABLE, [
            'id' => $this->integer()->unsigned()->notNull(),
            'tenderId' => $this->string(100)->notNull(),
            'response' => 'json',
        ]);
        $this->addPrimaryKey('pk_decision_id', self::TABLE, 'id');
    }

    public function safeDown()
    {
        $this->dropTable(self::TABLE);
    }
}
