<?php
namespace console\controllers;

use Yii;
use yii\console\Controller;
use console\models\budgets\BudgetsListHandler;

/**
 * Class TendersController
 * @package console\controllers
 */
class BudgetsController extends Controller
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

        if (!isset(Yii::$app->params['budgets_url']) || empty(Yii::$app->params['budgets_url'])) {
            Yii::error("budgets_url parameter not set.", 'sync-info');
            exit(0);
        }

        BudgetsListHandler::run();
    }
}