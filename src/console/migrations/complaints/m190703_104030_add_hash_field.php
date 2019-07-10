<?php

use yii\db\Migration;

class m190703_104030_add_hash_field extends Migration
{
    const TABLE_COMPLAINTS = '{{%complaints}}';
    const TABLE_DECISIONS = '{{%decisions}}';
    const COLUMN = 'hash';

    public function safeUp()
    {
        $this->addColumn(self::TABLE_COMPLAINTS, self::COLUMN, $this->string(32));
        $this->addColumn(self::TABLE_DECISIONS, self::COLUMN, $this->string(32));
    }

    public function safeDown()
    {
        $this->dropColumn(self::TABLE_COMPLAINTS, self::COLUMN);
        $this->dropColumn(self::TABLE_DECISIONS, self::COLUMN);
    }
}
