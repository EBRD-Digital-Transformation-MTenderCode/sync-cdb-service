<?php

use yii\db\Migration;

class m180903_142626_add_tender_table extends Migration
{
    CONST TABLE_NAME = "{{%tenders_prz_updates}}";

    public function safeUp()
    {
        $this->execute('CREATE EXTENSION IF NOT EXISTS "uuid-ossp"');
        $this->createTable(
            self::TABLE_NAME,
            [
                'id' => 'uuid NOT NULL default uuid_generate_v4()',
                'tender_id' => $this->string(32)->notNull()->unique(),
                'response' => 'json',
                'updated_at' => $this->integer(11)->null(),
                'date_modified' => $this->string(32)->null(),
            ]
        );
        $this->addPrimaryKey('pk_tenders_prz_updates', self::TABLE_NAME, 'id');
    }

    public function safeDown()
    {
        echo "m180903_142626_add_tender_table cannot be reverted.\n";

        return false;
    }
}