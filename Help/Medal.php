<?php

namespace Xfrocks\Medal\Help;

use XF\Mvc\Controller;
use XF\Mvc\Reply\View;
use Xfrocks\Medal\Entity\Medal as EntityMedal;

class Medal
{
    public static function renderMedals(Controller $controller, View &$response)
    {
        $categories = $controller->finder('Xfrocks\Medal:Category')
            ->order('display_order')
            ->fetch();
        $response->setParam('categories', $categories);

        $medalId = $controller->filter('medal_id', 'uint');
        if ($medalId > 0) {
            /** @var EntityMedal $medal */
            $medal = $controller->assertRecordExists('Xfrocks\Medal:Medal', $medalId);
            $finder = $controller->finder('Xfrocks\Medal:Awarded')
                ->with('User', true)
                ->where('medal_id', $medal->medal_id)
                ->order('award_date', 'DESC');
            $total = $finder->total();

            $page = $controller->filterPage();
            $perPage = 20;
            $awardeds = $finder->limitByPage($page, $perPage)->fetch();

            $response->setParams([
                'medal' => $medal,
                'total' => $total,
                'awardeds' => $awardeds,

                'page' => $page,
                'perPage' => $perPage,
                'pageNavParams' => ['medal_id' => $medal->medal_id],
            ]);
        } else {
            $medals = $controller->finder('Xfrocks\Medal:Medal')
                ->order('display_order')
                ->fetch();
            $response->setParam('medals', $medals);
        }
    }
}
