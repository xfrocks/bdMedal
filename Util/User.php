<?php

namespace Xfrocks\Medal\Util;

use XF\App;
use XF\Util\Php;
use Xfrocks\Medal\Entity\Awarded;

class User
{
    /**
     * @param App $app
     * @param int $userId
     */
    public static function rebuild($app, $userId)
    {
        /** @var \Xfrocks\Medal\Repository\Medal $medalRepo */
        $medalRepo = $app->repository('Xfrocks\Medal:Medal');
        $awardeds = $medalRepo->findAwardedsForUser($userId)
            ->with(['Medal', 'Medal.Category'], true)
            ->fetch();

        $data = [];
        /** @var Awarded $awarded */
        foreach ($awardeds as $awarded) {
            $data[] = $awarded->toArray(false) +
                $awarded->Medal->toArray(false) +
                [
                    'category_name' => $awarded->Medal->Category->name,
                    'category_description' => $awarded->Medal->Category->description,
                ];
        }

        /** @noinspection PhpParamsInspection */
        $serialized = Php::safeSerialize($data);

        $app->db()->update(
            'xf_user',
            ['xf_bdmedal_awarded_cached' => $serialized],
            'user_id = ' . intval($userId)
        );
    }
}
