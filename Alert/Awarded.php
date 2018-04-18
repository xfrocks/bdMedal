<?php

namespace Xfrocks\Medal\Alert;

use XF\Alert\AbstractHandler;

class Awarded extends AbstractHandler
{
    public function getOptOutActions()
    {
        return ['insert'];
    }

    public function getOptOutDisplayOrder()
    {
        return 30005;
    }
}
