<?php
namespace console\controllers;

use console\models\cpv\Cpv;
use yii\console\Controller;
use Yii;

Class CpvController extends Controller
{
    public function actionImport()
    {
        $elastic_indexing = (bool) (Yii::$app->params['elastic_indexing'] ?? false);
        $elastic_url = Yii::$app->params['elastic_url'] ?? "";
        if ($elastic_indexing && !$elastic_url) {
            Yii::error("elastic_url parameter not set.", 'sync-info');
            exit(0);
        }

        Cpv::run();
    }
}