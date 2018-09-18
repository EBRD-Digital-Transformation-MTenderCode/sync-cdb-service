<?php
namespace console\models\tenders;

use Yii;
use PDO;
use PDOStatement;

/**
 * Class DB
 * class for connecting to PDO
 *
 * @package console\models\tenders
 *
 * @property PDO $connect
 */
class DB {

    public $connect = NULL;
    static private $_ins = NULL;

    /**
     * DB constructor.
     */
    private function __construct() {
        $db = Yii::$app->db_tenders;
        $this->connect = new PDO($db->dsn, $db->username, $db->password);
        $this->connect->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        unset($db);
    }

    private function __clone() { }

    /**
     * @return DB|null
     */
    public static function getInstance() {
        if (self::$_ins instanceof self) {
            return self::$_ins;
        }
        return self::$_ins = new self;
    }

    /**
     * Drop DB instance
     */
    public static function dropInstance() {
        self::$_ins = null;
    }

    /**
     * Provide PDO::fetch()
     * @param $sql
     * @param $params
     * @return array
     */
    public static function fetch($sql, $params) {
        $query = self::execute($sql, $params);

        return $query->fetch();
    }

    /**
     * Provide PDO::fetchAll()
     * @param $sql
     * @param $params
     * @return array
     */
    public static function fetchAll($sql, $params) {
        $query = self::execute($sql, $params);

        return $query->fetchAll();
    }

    /**
     * Provide PDO::rowCount()
     * @param $sql
     * @param $params
     * @return int
     */
    public static function rowCount($sql, $params) {
        $query = self::execute($sql, $params);

        return $query->rowCount();
    }

    /**
     * @param $sql
     * @param $params
     * @return PDOStatement
     */
    public static function execute($sql, $params) {
        $connect = self::getInstance()->getConnect();
        $query = $connect->prepare($sql);
        $query->execute($params);

        return $query;
    }

    /**
     * @return null|PDO
     */
    public function getConnect() {
        return $this->connect;
    }
}