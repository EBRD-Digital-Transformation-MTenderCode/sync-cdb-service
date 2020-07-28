<?php

use yii\db\Migration;

class m180903_142627_tenders extends Migration
{
    const TABLE_TENDERS = '{{%tenders}}';
    const TABLE_CDU = '{{%cdu}}';
    CONST TABLE_NAME_SHORT = "tenders";

    /**
     * @return bool|void
     * @throws \yii\base\NotSupportedException
     */
    public function safeUp()
    {
        $this->execute('CREATE EXTENSION IF NOT EXISTS "uuid-ossp"');

        if (!in_array(self::TABLE_NAME_SHORT, $this->getDb()->getSchema()->getTableNames())) {

            $this->createTable(self::TABLE_TENDERS, [
                'id' => 'uuid NOT NULL default uuid_generate_v4()',
                'tender_id' => $this->string(32)->notNull()->unique(),
                'response' => 'json',
                'cdu_id' => $this->integer()->unsigned(),
            ]);
            $this->addPrimaryKey('pk_tenders', self::TABLE_TENDERS, 'id');
            $this->createIndex('i_tenders_tender_id', self::TABLE_TENDERS, ['tender_id'], true);

            $this->createTable(self::TABLE_CDU, [
                'id' => $this->integer()->unsigned(),
                'name' => $this->string(32),
                'alias' => $this->string(32),
            ]);
            $this->addPrimaryKey('pk_cdu', self::TABLE_CDU, 'id');
            $this->addForeignKey('fk_cdu_id', self::TABLE_TENDERS, 'cdu_id', self::TABLE_CDU, 'id');

            $this->batchInsert(self::TABLE_CDU, ['id', 'name', 'alias'], [
                [1, 'mtender1', 'mtender1'],
                [2, 'mtender2', 'mtender2'],
            ]);

        }
    }

    public function safeDown()
    {
        $this->dropTable(self::TABLE_TENDERS);
        $this->dropTable(self::TABLE_CDU);
    }
}
