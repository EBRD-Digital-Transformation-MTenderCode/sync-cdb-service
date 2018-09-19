<?php
namespace console\models\Budgets;

use console\models\Curl;
use yii\web\HttpException;
use Yii;

class Elastic
{
    private $index;
    private $type;
    private $url;

    /**
     * Elastic constructor.
     */
    public function __construct()
    {
        $this->index = Yii::$app->params['elastic_index'] ?? "";
        $this->type = Yii::$app->params['elastic_type'] ?? "";
        $this->url = Yii::$app->params['elastic_url'] ?? "";
        if (!$this->index || !$this->type || !$this->url) {
            Yii::error("Elastic params not set.", 'sync-info');
            exit(0);
        }
    }

    /**
     * @return array
     * @throws HttpException
     */
    public function checkMapping()
    {
        $elastic_request_url = $this->url . DIRECTORY_SEPARATOR
            . $this->index . DIRECTORY_SEPARATOR
            . $this->type . DIRECTORY_SEPARATOR
            . "_mapping";

        $result = Curl::sendRequest($elastic_request_url, "GET");

        return $result;
    }

    /**
     * @param $docArr
     */
    public function indexDoc($docArr)
    {
        $delay = (int) Yii::$app->params['sleep_error_interval'];
        $elastic_request_url = $this->url . DIRECTORY_SEPARATOR
            . $this->index . DIRECTORY_SEPARATOR
            . $this->type . DIRECTORY_SEPARATOR;

        try {
            $data_string = json_encode($docArr);
            $curl_options = ['HTTPHEADER' => ['Content-Type:application/json']];
            $result = Curl::sendRequest($elastic_request_url . $docArr['ocid'], "POST", $data_string, $curl_options);

            if ($result['code'] != 200 && $result['code'] != 201 && $result['code'] != 100) {
                Yii::error("Elastic indexing error. Http-code: " . $result['code'], 'sync-info');
                sleep($delay);
            }

        } catch (HttpException $exception) {
            Yii::error('Elastic error. CURL ERROR[' . $exception->getCode() . ']. ' . $exception->getMessage(), 'sync-info');
            sleep($delay);
        }
    }

    /**
     * @param $budget
     */
    public function indexBudget($budget) {
        $response = $budget['response'];
        $jsonArr = json_decode($response, 1);
        $records = $jsonArr['records'];
        foreach ($records as $record) {
            if ($record['ocid'] == $budget['ocid']) {
                $ocid = $record['ocid'];
                $title = ($record['compiledRelease']['tender']['title']) ?? "";
                $description = ($record['compiledRelease']['tender']['description']) ?? "";
                $docArr = ['ocid' => $ocid, 'title' => $title, 'description' => $description];
                $this->indexDoc($docArr);
                break;
            }
        }
    }

}