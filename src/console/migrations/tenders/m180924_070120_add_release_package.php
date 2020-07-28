<?php

use yii\db\Migration;

class m180924_070120_add_release_package extends Migration
{
    const TABLE_TENDERS         = '{{%tenders}}';
    const TABLE_TENDERS_UPDATES = '{{%tenders_updates}}';
    const COLUMN                = 'release_package';
    const TYPE                  = 'json';

    public function safeUp()
    {
        $this->addColumn(self::TABLE_TENDERS_UPDATES, self::COLUMN, self::TYPE);
        $this->addColumn(self::TABLE_TENDERS, self::COLUMN, self::TYPE);
    }

    public function safeDown()
    {
        $this->dropColumn(self::TABLE_TENDERS_UPDATES, self::COLUMN);
        $this->dropColumn(self::TABLE_TENDERS, self::COLUMN);
    }
}