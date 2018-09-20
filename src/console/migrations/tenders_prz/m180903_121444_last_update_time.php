<?php

use yii\db\Migration;

class m180903_121444_last_update_time extends Migration
{
    CONST TABLE_NAME = "{{%last_update_time}}";
    CONST TABLE_NAME_SHORT = "last_update_time";

    /**
     * @return bool|void
     * @throws \yii\base\NotSupportedException
     */
    public function safeUp()
    {
        if (!in_array(self::TABLE_NAME_SHORT, $this->getDb()->getSchema()->getTableNames())) {
            $this->createTable(
                self::TABLE_NAME,
                [
                    'id'=> $this->string(24)->notNull(),
                    'updated_at' => $this->integer(11)->null(),
                    'offset_time' => $this->string(32)->null(),
                ]
            );
            $this->addPrimaryKey('pk_last_update_time', self::TABLE_NAME, 'id');
        }

        $this->insert(self::TABLE_NAME, ['id' => 'tenders-prz-changed-list', 'updated_at' => null, 'offset_time' => null]);
    }

    public function safeDown()
    {
        $this->dropTable(self::TABLE_NAME);

        return false;
    }
}