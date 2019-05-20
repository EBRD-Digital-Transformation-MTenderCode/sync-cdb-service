<?php
namespace console\models\complaints;


/**
 * Class UpdateTime
 * class is used to commit and read the synchronization time
 *
 * @package console\models\complaints;
 */
class UpdateTime
{
    const TABLE      = 'last_update_time';
    const COMPLAINTS = 'complaints';
    const DECISIONS  = 'decisions';

    /**
     * Get offset time
     * @return false|string
     */
    public static function getOffset($id)
    {
        $row = DB::fetch("SELECT * FROM " . self::TABLE . " WHERE id=?", [$id]);

        return $row['offset_time'] ?? '';
    }

    /**
     * Update offset time
     * @param $id
     * @param $offset
     */
    public static function updateOffset($id, $offset)
    {
        DB::execute("UPDATE " . self::TABLE . " SET offset_time=?, updated_at=? WHERE id=?", [
            $offset,
            time(),
            $id,
        ]);
    }
}