<?php

namespace console\models\cpv;

use console\models\elastic\ElasticComponent;
use Yii;
use yii\web\HttpException;

/**
 * Class Cpv
 * @package console\models\cpv
 */
Class Cpv
{
    /**
     * Import CPV
     */
    public static function run()
    {
        try {
            self::mapping();
            self::handle();
        } catch (HttpException $exception) {
            Yii::error('CURL ERROR[' . $exception->getCode() . ']. ' . $exception->getMessage(), 'sync-info');
            Yii::info("Memory usage: " . memory_get_usage(), 'sync-info');
        }
    }

    /**
     * @throws HttpException
     * @throws \yii\web\ForbiddenHttpException
     */
    private static function mapping()
    {
        $elastic_url = Yii::$app->params['elastic_url'];
        $elastic_index = Yii::$app->params['elastic_cpv_index'];
        $elastic_type = Yii::$app->params['elastic_cpv_type'];
        $elastic = new ElasticComponent($elastic_url, $elastic_index, $elastic_type);
        $elastic->dropIndex();

        $result = $elastic->setIndexSettings();

        if ((int)$result['code'] != 200) {
            Yii::error("Elastic set setting " . $elastic_index . " error", 'console-msg');
            exit(0);
        }

        $result = $elastic->cpvMapping();

        if ((int)$result['code'] != 200 && (int)$result['code'] != 100) {
            Yii::error("Elastic mapping " . $elastic_index . " error", 'console-msg');
            exit(0);
        }

        Yii::info("Cpv mapping is complete", 'console-msg');
    }

    /**
     * @throws HttpException
     * @throws \yii\web\ForbiddenHttpException
     * @throws \yii\web\NotFoundHttpException
     */
    public static function handle()
    {
        $elastic = new ElasticComponent(
            Yii::$app->params['elastic_url'],
            Yii::$app->params['elastic_cpv_index'],
            Yii::$app->params['elastic_cpv_type']
        );


        $result = $elastic->checkMapping();
        if ($result['code'] != 200) {
            throw new HttpException(400, "Elastic mapping error. Http-code: " . $result['code']);
        }

        $cpvArray = self::getCpv();
        $i = 0;
        foreach ($cpvArray as $keyId => $itemArr) {
            $data['id'] = $keyId;
            foreach ($itemArr as $keyLanguage => $item) {
                $data['name'][$keyLanguage] = $item['name'];
            }

            $elastic->indexCpv($data);

            if(($i%1000) == 0) {
                Yii::info("1000 CPV-rows imported into Elastic", 'console-msg');
            }
            $i++;
        }
        Yii::info("CPV dictionary import completed ($i)", 'console-msg');
    }

    /**
     * @return array
     * @throws \yii\web\NotFoundHttpException
     */
    private static function getCpv()
    {
        $result = [];

        $file = __DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'source_data' . DIRECTORY_SEPARATOR . 'cpv' . DIRECTORY_SEPARATOR . 'cpv.csv';
        $csv = array_map('str_getcsv', file($file));

        foreach ($csv as $item) {
            $id = trim($item[0]);
            $name = trim($item[3]);
            $language = trim($item[5]);

            $result[$id][$language] = [
                'id' => $id,
                'name' => $name,
            ];
        }

        return $result;
    }
}