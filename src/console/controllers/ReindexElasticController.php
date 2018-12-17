<?php
namespace console\controllers;

use Yii;
use yii\web\HttpException;
use yii\console\Controller;
use console\models\elastic\Budgets;
use console\models\elastic\Contracts;
use console\models\elastic\ElasticComponent;
use console\models\elastic\Plans;
use console\models\elastic\Tenders;

/**
 * Class ReindexElasticController
 * @package console\controllers
 */
class ReindexElasticController extends Controller
{
    /**
     * Reindex all indexes
     */
    public function actionAll()
    {
        $this->reindexBudgets();

        $this->reindexPlans();

        $this->reindexTenders();

        $this->reindexContracts();

        Yii::info("Elastic indexing is complete", 'console-msg');
    }

    /**
     * Reindex budgets
     */
    public function actionBudgets()
    {
        try {
            $this->reindexBudgets();
        } catch (HttpException $e) {
            Yii::error($e->getMessage(), 'console-msg');
            exit(0);
        }

        Yii::info("Elastic indexing Budgets is complete", 'console-msg');
    }

    /**
     * Reindex plans
     */
    public function actionPlans()
    {
        try {
            $this->reindexPlans();
        } catch (HttpException $e) {
            Yii::error($e->getMessage(), 'console-msg');
            exit(0);
        }

        Yii::info("Elastic indexing Plans is complete", 'console-msg');
    }

    /**
     * Reindex tenders
     */
    public function actionTenders()
    {
        try {
            $this->reindexTenders();
        } catch (HttpException $e) {
            Yii::error($e->getMessage(), 'console-msg');
            exit(0);
        }

        Yii::info("Elastic indexing Tenders is complete", 'console-msg');
    }

    /**
     * Reindex contracts
     */
    public function actionContracts()
    {
        try {
            $this->reindexContracts();
        } catch (HttpException $e) {
            Yii::error($e->getMessage(), 'console-msg');
            exit(0);
        }

        Yii::info("Elastic indexing Contracts is complete", 'console-msg');
    }

    /**
     * Reindex budgets
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
            $budgets->reindexItemsToElastic();
        } catch (HttpException $e) {
            Yii::error($e->getMessage(), 'console-msg');
            exit(0);
        }
    }

    /**
     * Reindex plans
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
            $plans->reindexItemsToElastic();

        } catch (HttpException $e) {
            Yii::error($e->getMessage(), 'console-msg');
            exit(0);
        }
    }

    /**
     * Reindex tenders
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
            $tenders->reindexItemsToElastic();

        } catch (HttpException $e) {
            Yii::error($e->getMessage(), 'console-msg');
            exit(0);
        }
    }

    /**
     * Reindex contracts
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
            $contracts->reindexItemsToElastic();

        } catch (HttpException $e) {
            Yii::error($e->getMessage(), 'console-msg');
            exit(0);
        }
    }
}