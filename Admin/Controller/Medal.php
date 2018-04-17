<?php

namespace Xfrocks\Medal\Admin\Controller;

class Medal extends Entity
{
    protected function getPrefixForPhrases()
    {
        return 'bdmedal_medal';
    }

    protected function getRoutePrefix()
    {
        return 'medals';
    }

    protected function getShortName()
    {
        return 'Xfrocks\Medal:Medal';
    }
}
