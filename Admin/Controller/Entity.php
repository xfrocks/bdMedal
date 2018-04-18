<?php

namespace Xfrocks\Medal\Admin\Controller;

use XF\Mvc\ParameterBag;

abstract class Entity extends \Xfrocks\Medal\DevHelper\Admin\Controller\Entity
{
    public function preDispatchType($action, ParameterBag $params)
    {
        parent::preDispatchType($action, $params);

        $this->assertAdminPermission('bdMedal');
    }

    protected function getPrefixForClasses()
    {
        return 'Xfrocks\Medal';
    }

    protected function getPrefixForTemplates()
    {
        return 'bdmedal';
    }
}
