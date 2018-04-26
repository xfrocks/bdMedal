<?php

namespace Xfrocks\Medal\Job;

use XF\Job\AbstractRebuildJob;

class User extends AbstractRebuildJob
{
    protected function getNextIds($start, $batch)
    {
        $db = $this->app->db();

        return $db->fetchAllColumn($db->limit('
            SELECT user_id
            FROM xf_user
            WHERE user_id > ?
            ORDER BY user_id
        ', $batch), $start);
    }

    protected function rebuildById($id)
    {
        \Xfrocks\Medal\Util\User::rebuild($this->app, $id);
    }

    protected function getStatusType()
    {
        return \XF::phrase('users');
    }
}
