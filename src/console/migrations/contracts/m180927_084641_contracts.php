<?php

use yii\db\Migration;

class m180927_084641_contracts extends Migration
{
    const TABLE_CONTRACTS = '{{%contracts}}';
    const TABLE_CDU = '{{%cdu}}';

    public function safeUp()
    {
        $this->execute('CREATE EXTENSION IF NOT EXISTS "uuid-ossp"');
        $this->createTable(self::TABLE_CONTRACTS, [
            'id' => 'uuid NOT NULL default uuid_generate_v4()',
            'contract_id' => $this->string(32)->notNull()->unique(),
            'response' => 'json',
            'cdu_id' => $this->integer()->unsigned(),
            'release_package' => 'json'
        ]);
        $this->addPrimaryKey('pk_contract_id', self::TABLE_CONTRACTS, 'id');
        $this->createIndex('i_contracts_plan_id', self::TABLE_CONTRACTS, ['contract_id'], true);

        $this->createTable(self::TABLE_CDU, [
            'id' => $this->integer()->unsigned(),
            'name' => $this->string(32),
            'alias' => $this->string(32),
        ]);
        $this->addPrimaryKey('pk_cdu', self::TABLE_CDU, 'id');
        $this->addForeignKey('fk_cdu_id', self::TABLE_CONTRACTS, 'cdu_id', self::TABLE_CDU, 'id');

        $this->batchInsert(self::TABLE_CDU, ['id', 'name', 'alias'], [
            [1, 'mtender1', 'mtender1'],
            [2, 'mtender2', 'mtender2'],
        ]);
    }

    public function safeDown()
    {
        $this->dropTable(self::TABLE_CONTRACTS);
        $this->dropTable(self::TABLE_CDU);
    }

}
