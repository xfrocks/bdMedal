<?php

namespace Xfrocks\Medal\Admin\Controller;

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

    protected function entityAddEdit($entity)
    {
        $view = parent::entityAddEdit($entity);

        $view->setParam('macroTemplate', 'bdmedal_medal');
        $view->setParam('macroName', 'entity_edit');

        return $view;
    }

    protected function entitySaveProcess($entity)
    {
        $form = parent::entitySaveProcess($entity);
        /** @var Image $imageService */
        $imageService = $this->service('Xfrocks\Medal:Medal\Image', $entity);

        $form->validate(function () use ($form, $imageService) {
            $upload = $this->request->getFile('image', false, false);
            if (!$upload) {
                return;
            }

            if ($imageService->setImageFromUpload($upload)) {
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
