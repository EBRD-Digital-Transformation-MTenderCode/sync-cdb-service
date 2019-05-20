<?php
namespace console\models\complaints;

/**
 * Class Complaint
 * @package console\models\complaints
 */
class Complaint
{
    const TABLE = 'complaints';

    public static function handleDb($id, $tenderId, $response)
    {
        $count = DB::rowCount('SELECT * FROM ' . self::TABLE . ' WHERE id = ?', [$id]);

        if ($count == 0) {
            DB::execute('INSERT INTO ' . self::TABLE . ' ("id", "tenderId", "response") VALUES (?, ?, ?)', [
                $id,
                $tenderId,
                $response,
            ]);
        }

        if ($count == 1) {
            DB::execute('UPDATE ' . self::TABLE . ' SET "tenderId" = ?, "response" = ? WHERE "id" = ?', [
                $tenderId,
                $response,
                $id,
            ]);
        }
    }
}