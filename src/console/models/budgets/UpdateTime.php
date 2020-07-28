<?php
namespace console\models\budgets;


/**
 * Class UpdateTime
 * class is used to commit and read the synchronization time
 *
 * @package console\models\budgets;
 */
class UpdateTime
{
    CONST TABLE_NAME = "last_update_time";

    /**
     * Get offset time
     * @return false|string
     */
    public static function getOffset()
    {
        $row = DB::fetch("SELECT * FROM " . self::TABLE_NAME . " WHERE id='budgets-changed-list'", []);

        return $row['offset_time'] ?? '';
    }

    /**
     * Update offset time
     * @param string $offset
     */
    public static function updateOffset($offset) {
        DB::execute("UPDATE " . self::TABLE_NAME . " SET offset_time=?, updated_at=? WHERE id=?", [$offset, time(), 'budgets-changed-list']);
    }
}