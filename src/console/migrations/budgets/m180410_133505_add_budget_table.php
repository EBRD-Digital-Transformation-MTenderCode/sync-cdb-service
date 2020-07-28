<?php

use yii\db\Migration;

class m180410_133505_add_budget_table extends Migration
{
    CONST TABLE_NAME = "{{%budgets_updates}}";

    public function safeUp()
    {
        $this->execute('CREATE EXTENSION IF NOT EXISTS "uuid-ossp"');
        $this->createTable(
            self::TABLE_NAME,
            [
                'id' => 'uuid NOT NULL default uuid_generate_v4()',
                'ocid' => $this->string(32)->notNull()->unique(),
                'response' => 'json',
                'updated_at' => $this->integer(11)->null(),
                'date_modified' => $this->string(32)->null(),
            ]
        );
        $this->addPrimaryKey('pk_budgets_updates', self::TABLE_NAME, 'id');
    }

    public function safeDown()
    {
        echo "m180410_133505_budgets_table cannot be reverted.\n";

        return false;
    }

}
