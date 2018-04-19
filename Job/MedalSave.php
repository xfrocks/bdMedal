<?php

namespace Xfrocks\Medal\Job;

use XF\Job\AbstractRebuildJob;
use Xfrocks\Medal\Entity\Awarded;

class MedalSave extends AbstractRebuildJob
{
    protected $defaultData = [
        'medal_id' => null,
        'count' => 0,
        'total' => null
    ];

    protected function getNextIds($start, $batch)
    {
        $db = $this->app->db();

        return $db->fetchAllColumn($db->limit("
            SELECT awarded_id
            FROM xf_bdmedal_awarded
            WHERE medal_id = ? AND awarded_id > ? 
            ORDER BY awarded_id
        ", $batch), [$this->data['medal_id'], $start]);
    }

    protected function rebuildById($id)
    {
        /** @var Awarded $awarded */
        $awarded = $this->app->em()->find('Xfrocks\Medal:Awarded', $id);
        if ($awarded) {
            $awarded->rebuildUser();
        }
    }

    protected function getStatusType()
    {
        return \XF::phrase('bdmedal_awarded_medals');
    }
}
