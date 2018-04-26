<?php

namespace Xfrocks\Medal\Job;

use XF\Job\AbstractRebuildJob;
use Xfrocks\Medal\Entity\Medal;
use Xfrocks\Medal\Service\Medal\Image;

class MedalImage extends AbstractRebuildJob
{
    protected function getNextIds($start, $batch)
    {
        $db = $this->app->db();

        return $db->fetchAllColumn($db->limit('
            SELECT medal_id
            FROM xf_bdmedal_medal
            WHERE medal_id > ?
            ORDER BY medal_id
        ', $batch), $start);
    }

    protected function rebuildById($id)
    {
        /** @var Medal $medal */
        $medal = $this->app->find('Xfrocks\Medal:Medal', $id);
        if (empty($medal)) {
            throw new \InvalidArgumentException('The requested medal could not be found.');
        }

        /** @var Image $imageService */
        $imageService = $this->app->service('Xfrocks\Medal:Medal\Image', $medal);
        $imageService->generateMissingImages();
    }

    protected function getStatusType()
    {
        return \XF::phrase('bdmedal_medal_entities');
    }

    public static function enqueueSelf()
    {
        return !!\XF::app()->jobManager()->enqueue(__CLASS__);
    }
}
