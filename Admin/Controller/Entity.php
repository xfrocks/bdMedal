<?php

namespace Xfrocks\Medal\Admin\Controller;

abstract class Entity extends \Xfrocks\Medal\DevHelper\Admin\Controller\Entity
{
    protected function getPrefixForClasses()
    {
        return 'Xfrocks\Medal';
    }

    protected function getPrefixForTemplates()
    {
        return 'bdmedal';
    }
}
