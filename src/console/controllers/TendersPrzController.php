<?php
namespace console\controllers;

use Yii;
use yii\console\Controller;
use console\models\tenders_prz\TendersListHandler;
use console\models\tenders_prz\TendersHandler;
use console\models\tenders_prz\TendersUpdates;

/**
 * Class TendersController
 * @package console\controllers
 */
class TendersPrzController extends Controller
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

        if (!isset(Yii::$app->params['tenders_prz_url']) || empty(Yii::$app->params['tenders_prz_url'])) {
            Yii::error("tenders_prz_url parameter not set.", 'sync-info');
            exit(0);
        }

        TendersListHandler::run();
    }

    /**
     * Get tender updates
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

        if (!isset(Yii::$app->params['tenders_prz_url']) || empty(Yii::$app->params['tenders_prz_url'])) {
            Yii::error("tenders_prz_url parameter not set.", 'sync-info');
            exit(0);
        }

        TendersHandler::run();
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

        TendersUpdates::run();
    }

}