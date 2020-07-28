<?php
namespace console\models\complaints;

/**
 * Class Complaint
 * @package console\models\complaints
 */
class Complaint
{
    const TABLE = 'complaints';

    /**
     * @param $id
     * @param $tenderId
     * @param $hash
     * @param $response
     * @return bool
     */
    public static function handleDb($id, $tenderId, $hash, $response)
    {
        $result = false;

        $row = DB::fetch('SELECT hash FROM ' . self::TABLE . ' WHERE id = ?', [$id]);

        if (empty($row)) {
            DB::execute('INSERT INTO ' . self::TABLE . ' ("id", "tenderId", "hash", "response") VALUES (?, ?, ?, ?)', [
                $id,
                $tenderId,
                $hash,
                $response,
            ]);
            $result = true;
        }

        if (isset($row['hash']) && $row['hash'] != $hash) {
            DB::execute('UPDATE ' . self::TABLE . ' SET "tenderId" = ?, "hash" = ?, "response" = ? WHERE "id" = ?', [
                $tenderId,
                $hash,
                $response,
                $id,
            ]);
            $result = true;
        }

        return $result;
    }
}