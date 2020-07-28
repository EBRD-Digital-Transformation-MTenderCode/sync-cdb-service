<?php
namespace console\controllers;

use Yii;
use yii\console\Controller;
use yii\web\HttpException;
use console\models\elastic\ElasticComponent;

/**
 * Class MappingElasticController
 * @package console\controllers
 */
class MappingElasticController extends Controller
{
    /**
     * @throws HttpException
     * @throws \yii\web\ForbiddenHttpException
     */
    public function actionAll() {

        $this->actionBudgets();
        $this->actionTenders();
        $this->actionPlans();
        $this->actionContracts();
        $this->actionComplaints();

        Yii::info("Elastic mapping is complete", 'console-msg');
    }

    /**
     * @throws HttpException
     * @throws \yii\web\ForbiddenHttpException
     */
    public function actionBudgets()
    {
        $elastic_url = Yii::$app->params['elastic_url'];
        $elastic_index = Yii::$app->params['elastic_budgets_index'];
        $elastic_type = Yii::$app->params['elastic_budgets_type'];
        $elastic = new ElasticComponent($elastic_url, $elastic_index, $elastic_type);
        $elastic->dropIndex();

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

        Yii::info("Budgets mapping is complete", 'console-msg');
    }

    /**
     * @throws HttpException
     * @throws \yii\web\ForbiddenHttpException
     */
    public function actionTenders()
    {
        $elastic_url = Yii::$app->params['elastic_url'];
        $elastic_index = Yii::$app->params['elastic_tenders_index'];
        $elastic_type = Yii::$app->params['elastic_tenders_type'];
        $elastic = new ElasticComponent($elastic_url, $elastic_index, $elastic_type);
        $elastic->dropIndex();

        $result = $elastic->setIndexSettings();

        if ((int)$result['code'] != 200) {
            Yii::error("Elastic set setting " . $elastic_index . " error", 'console-msg');
            exit(0);
        }

        $result = $elastic->tendersMapping();

        if ((int)$result['code'] != 200 && (int)$result['code'] != 100) {
            Yii::error("Elastic mapping " . $elastic_index . " error", 'c   onsole-msg');
            exit(0);
        }

        Yii::info("Tenders mapping is complete", 'console-msg');
    }

    /**
     * @throws HttpException
     * @throws \yii\web\ForbiddenHttpException
     */
    public function actionPlans()
    {
        $elastic_url = Yii::$app->params['elastic_url'];
        $elastic_index = Yii::$app->params['elastic_plans_index'];
        $elastic_type = Yii::$app->params['elastic_plans_type'];
        $elastic = new ElasticComponent($elastic_url, $elastic_index, $elastic_type);
        $elastic->dropIndex();

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

        Yii::info("Plans mapping is complete", 'console-msg');
    }

    /**
     * @throws HttpException
     * @throws \yii\web\ForbiddenHttpException
     */
    public function actionContracts()
    {
        $elastic_url = Yii::$app->params['elastic_url'];
        $elastic_index = Yii::$app->params['elastic_contracts_index'];
        $elastic_type = Yii::$app->params['elastic_contracts_type'];
        $elastic = new ElasticComponent($elastic_url, $elastic_index, $elastic_type);
        $elastic->dropIndex();

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

        Yii::info("Contracts mapping is complete", 'console-msg');
    }

    /**
     * @throws HttpException
     * @throws \yii\web\ForbiddenHttpException
     */
    public function actionComplaints()
    {
        $elastic_url = Yii::$app->params['elastic_url'];

        //Mapping proceedings
        $elastic_index = Yii::$app->params['elastic_proceedings_index'];
        $elastic_type = Yii::$app->params['elastic_proceedings_type'];
        $elastic = new ElasticComponent($elastic_url, $elastic_index, $elastic_type);
        $elastic->dropIndex();

        $result = $elastic->setIndexSettings();

        if ((int)$result['code'] != 200) {
            Yii::error("Elastic set setting " . $elastic_index . " error", 'console-msg');
            exit(0);
        }

        $result = $elastic->proceedingsMapping();

        if ((int)$result['code'] != 200 && (int)$result['code'] != 100) {
            Yii::error("Elastic mapping " . $elastic_index . " error", 'console-msg');
            exit(0);
        }

        Yii::info("Proceedings mapping is complete", 'console-msg');

        //Mapping complaints
        $elastic_index = Yii::$app->params['elastic_complaints_index'];
        $elastic_type = Yii::$app->params['elastic_complaints_type'];
        $elastic = new ElasticComponent($elastic_url, $elastic_index, $elastic_type);
        $elastic->dropIndex();

        $result = $elastic->setIndexSettings();

        if ((int)$result['code'] != 200) {
            Yii::error("Elastic set setting " . $elastic_index . " error", 'console-msg');
            exit(0);
        }

        $result = $elastic->complaintsMapping();

        if ((int)$result['code'] != 200 && (int)$result['code'] != 100) {
            Yii::error("Elastic mapping " . $elastic_index . " error", 'console-msg');
            exit(0);
        }

        Yii::info("Complaints mapping is complete", 'console-msg');

        //Mapping decisions
        $elastic_index = Yii::$app->params['elastic_decisions_index'];
        $elastic_type = Yii::$app->params['elastic_decisions_type'];
        $elastic = new ElasticComponent($elastic_url, $elastic_index, $elastic_type);
        $elastic->dropIndex();

        $result = $elastic->setIndexSettings();

        if ((int)$result['code'] != 200) {
            Yii::error("Elastic set setting " . $elastic_index . " error", 'console-msg');
            exit(0);
        }

        $result = $elastic->decisionsMapping();

        if ((int)$result['code'] != 200 && (int)$result['code'] != 100) {
            Yii::error("Elastic mapping " . $elastic_index . " error", 'console-msg');
            exit(0);
        }

        Yii::info("Decisions mapping is complete", 'console-msg');
    }
}