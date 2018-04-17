<?php

namespace Xfrocks\Medal\Admin\Controller;

class Category extends Entity
{
    protected function getPrefixForPhrases()
    {
        return 'bdmedal_category';
    }

    protected function getRoutePrefix()
    {
        return 'medal-categories';
    }

    protected function getShortName()
    {
        return 'Xfrocks\Medal:Category';
    }
}
