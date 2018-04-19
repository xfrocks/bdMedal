<?php

namespace Xfrocks\Medal\Job;

use XF\Job\AbstractJob;
use Xfrocks\Medal\Entity\Category;
use Xfrocks\Medal\Entity\Medal;
use Xfrocks\Medal\Service\Medal\Image;

class DemoData extends AbstractJob
{
    protected $defaultData = [
        'first' => 1,
        'installer' => 1,
    ];

    public function run($maxRunTime)
    {
        $app = $this->app;
        $db = $app->db();
        if ($db->fetchOne('SELECT COUNT(*) FROM xf_bdmedal_category') ||
            $db->fetchOne('SELECT COUNT(*) FROM xf_bdmedal_medal')) {
            return $this->complete();
        }

        $data = [];
        /** @noinspection HtmlUnknownTarget */
        $data['category'] = [
            'name' => 'Example Category',
            'description' => 'This is an example resource medal category. ' .
                'Manage categories and medals in <a href="admin.php?medal-categories/">Admin Control Panel</a>. '
        ];
        $data['medals'] = [];
        $data['medals']['first'] = [
            'name' => 'The First Man/Woman',
            'description' => 'User ID number <strong>one</strong>.',
            // https://www.shareicon.net/sports-first-one-football-number-sport-medals-medal-658976
            'image' => 'first.svg'
        ];
        $data['medals']['installer'] = [
            'name' => '[bd] Medal Installer',
            // https://depositphotos.com/72038393/stock-illustration-technology-devices-futuro-line-icons.html
            'image' => 'installer.png'
        ];

        $em = $app->em();
        /** @var Category $category */
        $category = $em->create('Xfrocks\Medal:Category');
        $category->bulkSet($data['category']);
        $category->save();

        /** @var \Xfrocks\Medal\Repository\Medal $medalRepo */
        $medalRepo = $app->repository('Xfrocks\Medal:Medal');

        foreach ($data['medals'] as $codeName => $medalData) {
            /** @var Medal $medal */
            $medal = $em->create('Xfrocks\Medal:Medal');
            $medal->bulkSet($medalData, ['skipInvalid' => true]);
            $medal->category_id = $category->category_id;
            $medal->save();

            /** @var Image $imageService */
            $imageService = $app->service('Xfrocks\Medal:Medal\Image', $medal);
            $imagePath = dirname(__DIR__) . DIRECTORY_SEPARATOR .
                '_data' . DIRECTORY_SEPARATOR .
                'demo' . DIRECTORY_SEPARATOR .
                $medalData['image'];
            if ($imageService->setImage($imagePath)) {
                $imageService->updateImage();
            }

            $userId = $this->data[$codeName];
            /** @var \XF\Entity\User $user */
            $user = $em->find('XF:User', $userId);
            if (!empty($user)) {
                $medalRepo->award($medal, $user, ['award_reason' => 'For <em>demonstration</em> purpose.']);
            }
        }

        return $this->complete();
    }

    public function getStatusMessage()
    {
        return \XF::phrase('installing');
    }

    public function canCancel()
    {
        return false;
    }

    public function canTriggerByChoice()
    {
        return false;
    }
}
