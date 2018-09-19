<?php

use yii\db\Migration;

class m180517_100456_budgets extends Migration
{
    CONST TABLE_NAME = "{{%budgets}}";

    public function safeUp()
    {
        $this->execute('CREATE EXTENSION IF NOT EXISTS "uuid-ossp"');

        $this->createTable(self::TABLE_NAME, [
            'id' => 'uuid NOT NULL default uuid_generate_v4()',
            'ocid' => $this->string(32)->notNull()->unique(),
            'response' => 'json'
        ]);
        $this->addPrimaryKey('pk_budgets', self::TABLE_NAME, 'id');
        $this->createIndex('i_budgets_cbd_id', self::TABLE_NAME, ['ocid'], true);

    }

    public function safeDown()
    {

    }
}
