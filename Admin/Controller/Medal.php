<?php

namespace Xfrocks\Medal\Admin\Controller;

use XF\Mvc\ParameterBag;
use Xfrocks\Medal\Entity\Medal as EntityMedal;
use Xfrocks\Medal\Service\Medal\Image;

class Medal extends Entity
{
    public function actionIndex()
    {
        $categories = $this->finder('Xfrocks\Medal:Category')
            ->order('display_order')
            ->fetch();
        $medals = $this->finder('Xfrocks\Medal:Medal')
            ->order('display_order')
            ->fetch();

        $viewParams = [
            'categories' => $categories,
            'medals' => $medals,
        ];

        return $this->view('Xfrocks\Medal:Medal\List', 'bdmedal_medal_list', $viewParams);
    }

    public function actionFilterByUser(ParameterBag $params)
    {
        $entityId = $this->getEntityIdFromParams($params);
        /** @var EntityMedal $medal */
        $medal = $this->assertEntityExists($entityId);

        if ($this->isPost()) {
            $username = $this->filter('username', 'str');
            /** @var \XF\Repository\User $userRepo */
            $userRepo = $this->repository('XF:User');
            $user = $userRepo->getUserByNameOrEmail($username);
            if (empty($user)) {
                return $this->error(\XF::phrase('requested_user_not_found'), 400);
            }

            /** @var \Xfrocks\Medal\Repository\Medal $medalRepo */
            $medalRepo = $this->repository('Xfrocks\Medal:Medal');
            if (!$medalRepo->hasExistingAwarded($medal, $user)) {
                return $this->message(\XF::phrase('bdmedal_user_x_not_awarded_y', [
                    'username' => $user->username,
                    'medal' => $medal->name,
                ]));
            }

            return $this->redirect($this->buildLink('awarded-medals', null, [
                'medal_id' => $medal->medal_id,
                'user_id' => $user->user_id,
            ]));
        }

        $viewParams = ['medal' => $medal];

        return $this->view('Xfrocks\Medal:Medal\FilterByUser', 'bdmedal_medal_filter_by_user', $viewParams);
    }

    public function actionImage(ParameterBag $params)
    {
        $entityId = $this->getEntityIdFromParams($params);

        /** @var EntityMedal $medal */
        $medal = $this->assertEntityExists($entityId);
        if ($medal->is_svg || !$medal->image_date) {
            return $this->noPermission();
        }

        if ($this->isPost()) {
            $sizeMap = $medal->getImageSizeMap();
            foreach ($sizeMap as $code => $size) {
                $image = $this->request->getFile('image_' . $code, false, false);
                if (empty($image)) {
                    continue;
                }

                /** @var Image $imageService */
                $imageService = $this->service('Xfrocks\Medal:Medal\Image', $medal);

                if (!$imageService->setImageFromUpload($image)) {
                    continue;
                }

                if ($code === 'l') {
                    $imageService->updateImage();
                } else {
                    $imageService->replaceImage($code);
                }
            }

            return $this->redirect($this->buildLink('medals'));
        }

        $viewParams = ['medal' => $medal];

        return $this->view('Xfrocks\Medal:Medal\Image', 'bdmedal_medal_image', $viewParams);
    }

    public function actionRebuild()
    {
        return $this->view('Xfrocks\Medal:Rebuild', 'bdmedal_rebuild');
    }

    protected function entitySaveProcess($entity)
    {
        $form = parent::entitySaveProcess($entity);

        $deleteImage = $this->filter('delete_image', 'bool');
        if ($deleteImage) {
            $form->basicEntitySave($entity, ['image_date' => 0]);
            return $form;
        }

        /** @var Image $imageService */
        $imageService = $this->service('Xfrocks\Medal:Medal\Image', $entity);

        $form->validate(function () use ($form, $imageService) {
            $image = $this->request->getFile('image', false, false);
            if (empty($image)) {
                return;
            }

            if ($imageService->setImageFromUpload($image)) {
                return;
            }

            $form->logError($imageService->getError());
        });

        $form->complete(function () use ($imageService) {
            try {
                $imageService->updateImage();
            } catch (\LogicException $e) {
                // ignore logic exception (most likely no image path)
            }
        });

        return $form;
    }

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

    protected function getViewReply($action, array $viewParams)
    {
        $viewParams['macroTemplateEntityEdit'] = 'bdmedal_medal_image';

        return parent::getViewReply($action, $viewParams);
    }
}
