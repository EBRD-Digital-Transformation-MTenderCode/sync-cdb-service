<?php
namespace console\controllers;

use Yii;
use yii\web\HttpException;
use yii\console\Controller;
use console\models\elastic\Plans;
use console\models\elastic\Budgets;
use console\models\elastic\Tenders;
use console\models\elastic\Contracts;
use console\models\elastic\ElasticComponent;

/**
 * Class ReindexElasticController
 * @package console\controllers
 */
class ReindexElasticController extends Controller
{
    public $soft;
    public $hard;

    public function options($actionID)
    {
        return ['soft', 'hard'];
    }

    /**
     * reindex all indexes
     * @throws \yii\db\Exception
     */
    public function actionAll()
    {
        $this->reindexBudgets();

        $this->reindexPlans();

        $this->reindexTenders();

        $this->reindexContracts();

        $this->reindexComplaints();

        $this->reindexDecision();
    }

    /**
     * reindex budgets
     * @throws \yii\db\Exception
     */
    public function actionBudgets()
    {
        try {
            $this->reindexBudgets();
        } catch (HttpException $e) {
            Yii::error($e->getMessage(), 'console-msg');
            exit(0);
        }
    }

    /**
     * reindex plans
     * @throws \yii\db\Exception
     */
    public function actionPlans()
    {
        try {
            $this->reindexPlans();
        } catch (HttpException $e) {
            Yii::error($e->getMessage(), 'console-msg');
            exit(0);
        }
    }

    /**
     * reindex tenders
     * @throws \yii\db\Exception
     */
    public function actionTenders()
    {
        try {
            $this->reindexTenders();
        } catch (HttpException $e) {
            Yii::error($e->getMessage(), 'console-msg');
            exit(0);
        }
    }

    /**
     * reindex contracts
     * @throws \yii\db\Exception
     */
    public function actionContracts()
    {
        try {
            $this->reindexContracts();
        } catch (HttpException $e) {
            Yii::error($e->getMessage(), 'console-msg');
            exit(0);
        }
    }

    /**
     * reindex one budget
     * @param string $id
     * @throws \yii\db\Exception
     */
    public function actionAddBudget($id = '')
    {
        if (strlen($id) != 28) {
            Yii::error('Budget id must be 28 chars', 'sync-info');
            exit(0);
        }

        $budgets = new Budgets();
        $budgets->reindexOne($id);

        Yii::info("Budget {$id} added to queue for reindex", 'console-msg');
    }

    /**
     * reindex one tender
     * @param string $id
     * @throws \yii\db\Exception
     */
    public function actionAddTender($id = '')
    {
        if (strlen($id) != 28) {
            Yii::error('Tender id must be 28 chars', 'sync-info');
            exit(0);
        }

        $tenders = new Tenders();
        $tenders->reindexOne($id);

        Yii::info("Tender {$id} added to queue for reindex", 'console-msg');
    }

    /**
     * reindex one prozorro plan
     * @param string $id
     * @throws \yii\db\Exception
     */
    public function actionAddPlanPrz($id = '')
    {
        if (strlen($id) != 32) {
            Yii::error('Plan id must be 32 chars', 'sync-info');
            exit(0);
        }

        $plans = new Plans();
        $plans->reindexOne($id);

        Yii::info("Plan {$id} added to queue for reindex", 'console-msg');
    }

    /**
     * reindex one prozorro tender
     * @param string $id
     * @throws \yii\db\Exception
     */
    public function actionAddTenderPrz($id = '')
    {
        if (strlen($id) != 32) {
            Yii::error('Tender id must be 32 chars', 'sync-info');
            exit(0);
        }

        $tenders = new Tenders();
        $tenders->reindexOnePrz($id);

        Yii::info("Tender {$id} added to queue for reindex", 'console-msg');
    }

    /**
     * reindex one prozorro contract
     * @param string $id
     * @throws \yii\db\Exception
     */
    public function actionAddContractPrz($id = '')
    {
        if (strlen($id) != 32) {
            Yii::error('Contract id must be 32 chars', 'sync-info');
            exit(0);
        }

        $contracts = new Contracts();
        $contracts->reindexOne($id);

        Yii::info("Contract {$id} added to queue for reindex", 'console-msg');
    }

    /**
     * reindex budgets
     * @throws \yii\db\Exception
     */
    private function reindexBudgets()
    {
        $elastic_url = Yii::$app->params['elastic_url'];
        $elastic_index = Yii::$app->params['elastic_budgets_index'];
        $elastic_type = Yii::$app->params['elastic_budgets_type'];

        try {
            $elastic = new ElasticComponent($elastic_url, $elastic_index, $elastic_type);
            $result = $elastic->dropIndex();

            if ((int)$result['code'] != 200 && (int)$result['code'] != 404) {
                Yii::error("Elastic index " . $elastic_index . " error. Code: " . $result['code'], 'console-msg');
                exit(0);
            }

            $result = $elastic->setIndexSettings();

            if ((int)$result['code'] != 200) {
                Yii::error("Elastic set setting " . $elastic_index . " error", 'console-msg');
                exit(0);
            }

            $result = $elastic->budgetsMapping();

            if ((int)$result['code'] != 200 && (int)$result['code'] != 100) {
                Yii::error("Elastic mapping " . $elastic_index . " error", 'console-msg');
                exit(0);
            }

            $budgets = new Budgets();

            if ($this->hard) {
                $budgets->truncate();
                Yii::info('All budgets has been deleted from DB', 'console-msg');
            } else {
                $budgets->reindexItemsToElastic();
                Yii::info("Elastic indexing Budgets is complete", 'console-msg');
            }
        } catch (HttpException $e) {
            Yii::error($e->getMessage(), 'console-msg');
            exit(0);
        }
    }

    /**
     * reindex plans
     * @throws \yii\db\Exception
     */
    private function reindexPlans()
    {
        $elastic_url = Yii::$app->params['elastic_url'];
        $elastic_index = Yii::$app->params['elastic_plans_index'];
        $elastic_type = Yii::$app->params['elastic_plans_type'];

        try {
            $elastic = new ElasticComponent($elastic_url, $elastic_index, $elastic_type);
            $result = $elastic->dropIndex();

            if ((int)$result['code'] != 200 && (int)$result['code'] != 404) {
                Yii::error("Elastic index " . $elastic_index . " error. Code: " . $result['code'], 'console-msg');
                exit(0);
            }

            $result = $elastic->setIndexSettings();
            if ((int)$result['code'] != 200) {
                Yii::error("Elastic set setting " . $elastic_index . " error", 'console-msg');
                exit(0);
            }

            $result = $elastic->plansMapping();
            if ((int)$result['code'] != 200 && (int)$result['code'] != 100) {
                Yii::error("Elastic mapping " . $elastic_index . " error", 'console-msg');
                exit(0);
            }

            $plans = new Plans();

            if ($this->hard) {
                $plans->truncate();
                Yii::info('All plans has been deleted from DB', 'console-msg');
            } else {
                $plans->reindexItemsToElastic();
                Yii::info("Elastic indexing Plans is complete", 'console-msg');
            }
        } catch (HttpException $e) {
            Yii::error($e->getMessage(), 'console-msg');
            exit(0);
        }
    }

    /**
     * reindex tenders
     * @throws \yii\db\Exception
     */
    private function reindexTenders()
    {
        $elastic_url = Yii::$app->params['elastic_url'];
        $elastic_index = Yii::$app->params['elastic_tenders_index'];
        $elastic_type = Yii::$app->params['elastic_tenders_type'];

        try {
            $elastic = new ElasticComponent($elastic_url, $elastic_index, $elastic_type);
            $result = $elastic->dropIndex();

            if ((int)$result['code'] != 200 && (int)$result['code'] != 404) {
                Yii::error("Elastic index " . $elastic_index . " error. Code: " . $result['code'], 'console-msg');
                exit(0);
            }

            $result = $elastic->setIndexSettings();

            if ((int)$result['code'] != 200) {
                Yii::error("Elastic set setting " . $elastic_index . " error", 'console-msg');
                exit(0);
            }

            $result = $elastic->tendersMapping();

            if ((int)$result['code'] != 200 && (int)$result['code'] != 100) {
                Yii::error("Elastic mapping " . $elastic_index . " error", 'console-msg');
                exit(0);
            }

            $tenders = new Tenders();

            if ($this->hard) {
                $tenders->truncate();
                Yii::info('All tenders has been deleted from DB', 'console-msg');
            } else {
                $tenders->reindexItemsToElastic();
                Yii::info("Elastic indexing Tenders is complete", 'console-msg');
            }
        } catch (HttpException $e) {
            Yii::error($e->getMessage(), 'console-msg');
            exit(0);
        }
    }

    /**
     * reindex contracts
     * @throws \yii\db\Exception
     */
    private function reindexContracts()
    {
        $elastic_url = Yii::$app->params['elastic_url'];
        $elastic_index = Yii::$app->params['elastic_contracts_index'];
        $elastic_type = Yii::$app->params['elastic_contracts_type'];

        try {
            $elastic = new ElasticComponent($elastic_url, $elastic_index, $elastic_type);
            $result = $elastic->dropIndex();

            if ((int)$result['code'] != 200 && (int)$result['code'] != 404) {
                Yii::error("Elastic index " . $elastic_index . " error. Code: " . $result['code'], 'console-msg');
                exit(0);
            }

            $result = $elastic->setIndexSettings();
            if ((int)$result['code'] != 200) {
                Yii::error("Elastic set setting " . $elastic_index . " error", 'console-msg');
                exit(0);
            }

            $result = $elastic->contractsMapping();
            if ((int)$result['code'] != 200 && (int)$result['code'] != 100) {
                Yii::error("Elastic mapping " . $elastic_index . " error", 'console-msg');
                exit(0);
            }

            $contracts = new Contracts();

            if ($this->hard) {
                $contracts->truncate();
                Yii::info('All contracts has been deleted from DB', 'console-msg');
            } else {
                $contracts->reindexItemsToElastic();
                Yii::info("Elastic indexing Contracts is complete", 'console-msg');
            }
        } catch (HttpException $e) {
            Yii::error($e->getMessage(), 'console-msg');
            exit(0);
        }
    }
}