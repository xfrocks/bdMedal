<?php

namespace Xfrocks\Medal\Util;

use XF\App;
use Xfrocks\Medal\Entity\Awarded;

class Medal
{
    /**
     * @param App $app
     * @param int $medalId
     * @throws \XF\PrintableException
     */
    public static function rebuild($app, $medalId)
    {
        /** @var \Xfrocks\Medal\Entity\Medal $medal */
        $medal = $app->find('Xfrocks\Medal:Medal', $medalId);
        if (empty($medal)) {
            throw new \InvalidArgumentException('The requested medal could not be found.');
        }

        $finder = $app->finder('Xfrocks\Medal:Awarded')
            ->where('medal_id', $medal->medal_id)
            ->order('awarded_id', 'DESC');

        /** @var Awarded $lastAwarded */
        $lastAwarded = $finder->fetchOne();
        $count = $finder->total();

        $medal->user_count = $count;
        $medal->last_award_date = $lastAwarded ? $lastAwarded->award_date : 0;
        $medal->last_award_user_id = $lastAwarded ? $lastAwarded->user_id : 0;
        $medal->last_award_username = $lastAwarded ? $lastAwarded->username : '';
        $medal->setOption('rebuild_users', false);

        $medal->save(false);
    }
}
