<?php
namespace console\controllers;

use console\models\tenders\TendersHandler;
use console\models\tenders\TendersListHandler;
use console\models\tenders\TendersUpdates;
use Yii;
use yii\console\Controller;

/**
 * Class TendersController
 * @package console\controllers
 */
class TendersController extends Controller
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

        if (!isset(Yii::$app->params['tenders_url']) || empty(Yii::$app->params['tenders_url'])) {
            Yii::error("tenders_url parameter not set.", 'sync-info');
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

        if (!isset(Yii::$app->params['tenders_url']) || empty(Yii::$app->params['tenders_url'])) {
            Yii::error("tenders_url parameter not set.", 'sync-info');
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