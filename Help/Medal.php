<?php

namespace Xfrocks\Medal\Help;

use XF\Mvc\Controller;
use XF\Mvc\Reply\View;

class Medal
{
    public static function renderMedals(Controller $controller, View &$response)
    {
        $categories = $controller->finder('Xfrocks\Medal:Category')
            ->order('display_order')
            ->fetch();
        $response->setParam('categories', $categories);

        $medals = $controller->finder('Xfrocks\Medal:Medal')
            ->order('display_order')
            ->fetch();
        $response->setParam('medals', $medals);
    }
}
