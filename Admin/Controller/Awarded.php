<?php

namespace Xfrocks\Medal\Admin\Controller;

class Awarded extends Entity
{
    public function getEntityExplain($entity)
    {
        /** @var \Xfrocks\Medal\Entity\Awarded $awarded */
        $awarded = $entity;
        return $awarded->award_reason;
    }

    public function getEntityHint($entity)
    {
        /** @var \Xfrocks\Medal\Entity\Awarded $awarded */
        $awarded = $entity;
        return $awarded->username;
    }

    public function getEntityLabel($entity)
    {
        if (!$entity instanceof \Xfrocks\Medal\Entity\Awarded) {
            return parent::getEntityLabel($entity);
        }

        /** @var \Xfrocks\Medal\Entity\Awarded $awarded */
        $awarded = $entity;
        return $awarded->Medal->name;
    }

    protected function finderForList()
    {
        $finder = parent::finderForList();

        $medalId = $this->filter('medal_id', 'uint');
        if ($medalId > 0) {
            /** @var \Xfrocks\Medal\Entity\Medal $medal */
            $medal = $this->assertRecordExists('Xfrocks\Medal:Medal', $medalId);
            $finder->where('medal_id', $medal->medal_id);
        }

        return $finder;
    }

    protected function getPrefixForPhrases()
    {
        return 'bdmedal_awarded';
    }

    protected function getRoutePrefix()
    {
        return 'awarded-medals';
    }

    protected function getShortName()
    {
        return 'Xfrocks\Medal:Awarded';
    }
}
