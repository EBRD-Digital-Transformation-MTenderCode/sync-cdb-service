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
            //Yii::info("Memory usage: " . memory_get_usage(), 'sync-info');
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

        foreach ($cpvArray as $key => $item) {
            $data['id'] = $item['id'];
            $data['name'] = [
                'en' => $item['name_en'],
                'uk' => $item['name_uk'],
                'ru' => $item['name_ru']
            ];

            $elastic->indexCpv($data);
            Yii::info("Cpv import to elastic add id #" . $data['id'], 'console-msg');
        }
    }

    /**
     * @return array
     * @throws \yii\web\NotFoundHttpException
     */
    private static function getCpv()
    {
        $arrLanguages = ['en', 'uk', 'ru'];
        $defaultLanguage = 'en';
        $inputData = [];

        // input data
        foreach ($arrLanguages as $language) {
            $file = __DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'source_data' . DIRECTORY_SEPARATOR . 'cpv' . DIRECTORY_SEPARATOR . 'cpv_' . $language . '.json';

            if (file_exists($file)) {
                $inputData[$language] = json_decode(file_get_contents($file), true);
            }
        }

        $result = [];


        if (!isset($inputData[$defaultLanguage])) {
            throw new \yii\web\NotFoundHttpException("Json file not found!");
        }

        // Defining a parent id for a nodes
        foreach ($inputData[$defaultLanguage] as $key => $val) {

            // splitting the cpv code into categories/digits
            preg_match('/^([0-9]{2})([0-9])([0-9])([0-9])([0-9]{3})\-[0-9]$/', $key, $digits);

            $cpvId = $digits[0];

            $result[$cpvId] = [
                'id' => $cpvId,
                'name_en' => trim($inputData['en'][$key]),
                'name_uk' => trim($inputData['uk'][$key]) ?? null,
                'name_ru' => trim($inputData['ru'][$key]) ?? null,
            ];
        }

        $result["99999999-9"] = [
            'id' => "99999999-9",
            'name_en' => "Not categorized",
            'name_uk' => "Не визначено",
            'name_ru' => "Не определен",
        ];

        return $result;
    }
}