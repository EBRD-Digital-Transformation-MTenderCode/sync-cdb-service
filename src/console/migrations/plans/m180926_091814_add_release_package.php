<?php

use yii\db\Migration;

class m180926_091814_add_release_package extends Migration
{
    const TABLE_PLANS         = '{{%plans}}';
    const TABLE_TENDERS_UPDATES = '{{%tenders_updates}}';
    const COLUMN                = 'release_package';
    const TYPE                  = 'json';

    public function safeUp()
    {
        $this->addColumn(self::TABLE_PLANS, self::COLUMN, self::TYPE);
    }

    public function safeDown()
    {
        $this->dropColumn(self::TABLE_PLANS, self::COLUMN);
    }
}
