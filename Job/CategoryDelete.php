<?php

namespace Xfrocks\Medal\Job;

use XF\Job\AbstractJob;
use Xfrocks\Medal\Entity\Medal;

class CategoryDelete extends AbstractJob
{
    protected $defaultData = [
        'category_id' => null,
        'count' => 0,
        'total' => null
    ];

    public function run($maxRunTime)
    {
        $s = microtime(true);

        if (!$this->data['category_id']) {
            throw new \InvalidArgumentException('Cannot run without a category_id.');
        }

        $finder = $this->app->finder('Xfrocks\Medal:Medal')
            ->where('category_id', $this->data['category_id']);

        if ($this->data['total'] === null) {
            $this->data['total'] = $finder->total();
            if (!$this->data['total']) {
                return $this->complete();
            }
        }

        $ids = $finder->pluckFrom('medal_id')->fetch(100);
        if (!$ids) {
            return $this->complete();
        }

        $continue = count($ids) < 100 ? false : true;

        foreach ($ids as $id) {
            $this->data['count']++;

            /** @var Medal $medal */
            $medal = $this->app->find('Xfrocks\Medal:Medal', $id);
            if (!$medal) {
                continue;
            }
            $medal->delete(false);

            if ($maxRunTime && microtime(true) - $s > $maxRunTime) {
                $continue = true;
                break;
            }
        }

        if ($continue) {
            return $this->resume();
        } else {
            return $this->complete();
        }
    }

    public function getStatusMessage()
    {
        $actionPhrase = \XF::phrase('running');
        $typePhrase = \XF::phrase('bdmedal_medals');
        return sprintf(
            '%s... %s (%s/%s)',
            $actionPhrase,
            $typePhrase,
            \XF::language()->numberFormat($this->data['count']),
            \XF::language()->numberFormat($this->data['total'])
        );
    }

    public function canCancel()
    {
        return true;
    }

    public function canTriggerByChoice()
    {
        return false;
    }
}
