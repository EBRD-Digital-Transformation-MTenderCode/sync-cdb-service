<?php
namespace console\controllers;

use console\models\contracts_prz\ContractsUpdates;
use Yii;
use yii\console\Controller;
use console\models\contracts_prz\ContractsHandler;
use console\models\contracts_prz\ContractsListHandler;

/**
 * Class ContractsController
 * @package console\controllers
 */
class ContractsPrzController extends Controller
{
    public function actionGetChangedList() {
        if (!isset(Yii::$app->params['sleep_delay_interval']) || empty(Yii::$app->params['sleep_delay_interval'])) {
            Yii::error("sleep_delay_interval parameter not set.", 'sync-info');
            exit(0);
        }

        if (!isset(Yii::$app->params['sleep_error_interval']) || empty(Yii::$app->params['sleep_error_interval'])) {
            Yii::error("sleep_error_interval parameter not set.", 'sync-info');
            exit(0);
        }

        if (!isset(Yii::$app->params['contracts_prz_url']) || empty(Yii::$app->params['contracts_prz_url'])) {
            Yii::error("contracts_prz_url parameter not set.", 'sync-info');
            exit(0);
        }

        ContractsListHandler::run();
    }

    /**
     * Get contract updates
     */
    public function actionGetUpdates()
    {
        if (!isset(Yii::$app->params['sleep_delay_interval']) || empty(Yii::$app->params['sleep_delay_interval'])) {
            Yii::error("sleep_delay_interval parameter not set.", 'sync-info');
            exit(0);
        }

        if (!isset(Yii::$app->params['sleep_error_interval']) || empty(Yii::$app->params['sleep_error_interval'])) {
            Yii::error("sleep_error_interval parameter not set.", 'sync-info');
            exit(0);
        }

        if (!isset(Yii::$app->params['contracts_prz_url']) || empty(Yii::$app->params['contracts_prz_url'])) {
            Yii::error("contracts_prz_url parameter not set.", 'sync-info');
            exit(0);
        }

        ContractsHandler::run();
    }

    public function actionUpdates()
    {
        if (!isset(Yii::$app->params['sleep_delay_interval']) || empty(Yii::$app->params['sleep_delay_interval'])) {
            Yii::error("sleep_delay_interval parameter not set.", 'sync-info');
            exit(0);
        }

        if (!isset(Yii::$app->params['sleep_error_interval']) || empty(Yii::$app->params['sleep_error_interval'])) {
            Yii::error("sleep_error_interval parameter not set.", 'sync-info');
            exit(0);
        }

        $elastic_indexing = (bool) (Yii::$app->params['elastic_indexing'] ?? false);
        $elastic_url = Yii::$app->params['elastic_url'] ?? "";
        if ($elastic_indexing && !$elastic_url) {
            Yii::error("elastic_url parameter not set.", 'sync-info');
            exit(0);
        }

        ContractsUpdates::run();
    }

}