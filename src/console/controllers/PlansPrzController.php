<?php
namespace console\controllers;

use Yii;
use yii\console\Controller;
use console\models\plans_prz\PlansListHandler;

/**
 * Class PlansController
 * @package console\controllers
 */
class PlansPrzController extends Controller
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

        if (!isset(Yii::$app->params['plans_prz_url']) || empty(Yii::$app->params['plans_prz_url'])) {
            Yii::error("plans_prz_url parameter not set.", 'sync-info');
            exit(0);
        }

        PlansListHandler::run();
    }
}