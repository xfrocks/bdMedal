<?php

namespace Xfrocks\Medal\Finder;

use Xfrocks\Medal\Entity\Medal;
use Xfrocks\Medal\XF\Entity\User;

class Category extends \XF\Mvc\Entity\Finder
{
    protected $defaultOrder = [['lft', 'ASC']];
}
