<?php

namespace Xfrocks\Medal\Finder;

use Xfrocks\Medal\Entity\Medal;
use Xfrocks\Medal\XF\Entity\User;

class Awarded extends \XF\Mvc\Entity\Finder
{
    /**
     * @param \Xfrocks\Medal\Admin\Controller\Awarded $controller
     * @param array $filters
     * @throws \XF\Mvc\Reply\Exception
     */
    public function entityDoListData($controller, array $filters)
    {
        $this->setDefaultOrder('award_date', 'DESC');

        $medalId = $controller->filter('medal_id', 'uint');
        if ($medalId > 0) {
            /** @var Medal $medal */
            $medal = $controller->assertRecordExists('Xfrocks\Medal:Medal', $medalId);
            $this->where('medal_id', $medal->medal_id);
            $filters['medal'] = $medal;
            $filters['pageNavParams']['medal_id'] = $medal->medal_id;
        }

        $userId = $controller->filter('user_id', 'uint');
        if ($userId > 0) {
            /** @var User $user */
            $user = $controller->assertRecordExists('XF:User', $userId);
            $this->where('user_id', $user->user_id);
            $filters['user'] = $user;
            $filters['pageNavParams']['user_id'] = $user->user_id;
        }
    }
}
