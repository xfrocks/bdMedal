<?php

namespace Xfrocks\Medal\Job;

use XF\Job\AbstractJob;
use Xfrocks\Medal\Entity\Awarded;

class MedalDelete extends AbstractJob
{
    protected $defaultData = [
        'medal_id' => null,
        'count' => 0,
        'total' => null
    ];

    public function run($maxRunTime)
    {
        $s = microtime(true);

        if (!$this->data['medal_id']) {
            throw new \InvalidArgumentException('Cannot run without a medal_id.');
        }

        $finder = $this->app->finder('Xfrocks\Medal:Awarded')
            ->where('medal_id', $this->data['medal_id']);

        if ($this->data['total'] === null) {
            $this->data['total'] = $finder->total();
            if (!$this->data['total']) {
                return $this->complete();
            }
        }

        $ids = $finder->pluckFrom('awarded_id')->fetch(100);
        if (!$ids) {
            return $this->complete();
        }

        $continue = count($ids) < 100 ? false : true;

        foreach ($ids as $id) {
            $this->data['count']++;

            /** @var Awarded $awarded */
            $awarded = $this->app->find('Xfrocks\Medal:Awarded', $id);
            if (!$awarded) {
                continue;
            }
            $awarded->setOption('rebuild_medal', false);
            $awarded->delete(false);

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
        $typePhrase = \XF::phrase('bdmedal_awarded_medals');
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
