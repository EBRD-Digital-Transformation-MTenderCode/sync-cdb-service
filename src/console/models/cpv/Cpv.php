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
            self::handle();
        } catch (HttpException $exception) {
            Yii::error('CURL ERROR[' . $exception->getCode() . ']. ' . $exception->getMessage(), 'sync-info');
            Yii::info("Memory usage: " . memory_get_usage(), 'sync-info');
        }
    }

    /**
     * @throws HttpException
     * @throws \yii\web\ForbiddenHttpException
     * @throws \yii\web\NotFoundHttpException
     */
    public static function handle()
    {
        $elastic_indexing = (bool)Yii::$app->params['elastic_indexing'];
        $elastic = new ElasticComponent(
            Yii::$app->params['elastic_url'],
            Yii::$app->params['elastic_cpv_index'],
            Yii::$app->params['elastic_cpv_type']
        );

        if ($elastic_indexing) {
            $result = $elastic->checkMapping();
            if ($result['code'] != 200) {
                throw new HttpException(400, "Elastic mapping error. Http-code: " . $result['code']);
            }
        }

        $cpvArray = self::getCpv();

        foreach ($cpvArray as $key => $item) {
            $data['id'] = $item['id'];
            $data['name'] = [
                'en' => $item['name_en'],
                'uk' => $item['name_uk'],
                'ru' => $item['name_ru']
            ];

            if ($elastic_indexing) {
                $elastic->indexCpv($data);
                Yii::info("Cpv import to elastic add id #" . $data['id'], 'console-msg');
            }
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