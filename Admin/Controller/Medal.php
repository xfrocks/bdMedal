<?php

namespace Xfrocks\Medal\Admin\Controller;

use XF\Mvc\ParameterBag;
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

    public function actionImage(ParameterBag $params)
    {
        $entityId = $this->getEntityIdFromParams($params);

        /** @var \Xfrocks\Medal\Entity\Medal $medal */
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

        $viewParams = [
            'medal' => $medal,
        ];

        return $this->view('Xfrocks\Medal:Medal\Image', 'bdmedal_medal_image', $viewParams);
    }

    protected function entityAddEdit($entity)
    {
        $view = parent::entityAddEdit($entity);

        $view->setParam('macroTemplate', 'bdmedal_medal_image');
        $view->setParam('macroName', 'entity_edit');

        return $view;
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
}
