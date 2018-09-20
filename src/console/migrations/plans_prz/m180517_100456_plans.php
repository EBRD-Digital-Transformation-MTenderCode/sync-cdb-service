<?php

use yii\db\Migration;

class m180517_100456_plans extends Migration
{
    const TABLE_PLANS = '{{%plans}}';
    const TABLE_CDU = '{{%cdu}}';

    public function safeUp()
    {
        $this->execute('CREATE EXTENSION IF NOT EXISTS "uuid-ossp"');
        $this->createTable(self::TABLE_PLANS, [
            'id' => 'uuid NOT NULL default uuid_generate_v4()',
            'plan_id' => $this->string(32)->notNull()->unique(),
            'response' => 'json',
            'cdu_id' => $this->integer()->unsigned(),
        ]);
        $this->addPrimaryKey('pk_plans', self::TABLE_PLANS, 'id');
        $this->createIndex('i_plans_plan_id', self::TABLE_PLANS, ['plan_id'], true);

        $this->createTable(self::TABLE_CDU, [
            'id' => $this->integer()->unsigned(),
            'name' => $this->string(32),
            'alias' => $this->string(32),
        ]);
        $this->addPrimaryKey('pk_cdu', self::TABLE_CDU, 'id');
        $this->addForeignKey('fk_cdu_id', self::TABLE_PLANS, 'cdu_id', self::TABLE_CDU, 'id');

        $this->batchInsert(self::TABLE_CDU, ['id', 'name', 'alias'], [
            [1, 'mtender1', 'mtender1'],
            [2, 'mtender2', 'mtender2'],
        ]);
    }

    public function safeDown()
    {
        $this->dropTable(self::TABLE_PLANS);
        $this->dropTable(self::TABLE_CDU);
    }
}
