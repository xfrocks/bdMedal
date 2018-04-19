<?php

namespace Xfrocks\Medal\Admin\Controller;

class Category extends Entity
{
    public function getEntityExplain($entity)
    {
        /** @var \Xfrocks\Medal\Entity\Category $category */
        $category = $entity;
        return strip_tags($category->description);
    }

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
