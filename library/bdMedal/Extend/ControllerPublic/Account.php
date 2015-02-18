<?php

class bdMedal_Extend_ControllerPublic_Account extends XFCP_bdMedal_Extend_ControllerPublic_Account
{
    public function actionMedals()
    {
        if (!XenForo_Visitor::getInstance()->hasPermission('general', 'bdMedal_organize')) {
            return $this->responseNoPermission();
        }

        $changed = false;
        $input = $this->_input->filter(array(
            'top' => XenForo_Input::UINT,
            'up' => XenForo_Input::UINT,
            'down' => XenForo_Input::UINT,
            'bottom' => XenForo_Input::UINT,
            'show' => XenForo_Input::UINT,
            'hide' => XenForo_Input::UINT,
        ));

        /** @var bdMedal_Model_Awarded $awardedModel */
        $awardedModel = $this->getModelFromCache('bdMedal_Model_Awarded');
        $medals = $awardedModel->getAwardedMedals(XenForo_Visitor::getUserId());
        $awardedModel->applyOrganizedOrder($medals);

        if (!empty($input['top'])) {
            if (empty($medals[$input['top']])) {
                return $this->responseNoPermission();
            }

            $organized = $this->_bdMedal_filterOrganizedMedals($medals);

            if (!empty($organized[$input['top']])) {
                unset($organized[$input['top']]);
            }
            $organized = array_merge(array($input['top'] => $medals[$input['top']]), $organized);

            $i = 0;
            foreach ($organized as $medal) {
                $i += 10;

                $dw = XenForo_DataWriter::create('bdMedal_DataWriter_Awarded');
                $dw->setExistingData($medal, true);
                $dw->set('adjusted_display_order', $i);
                $dw->save();

                $changed = true;
            }
        } elseif (!empty($input['up'])) {
            $awardedIds = array_keys($medals);
            $upPos = array_search($input['up'], $awardedIds);
            if ($upPos === false) {
                return $this->responseNoPermission();
            }

            $i = 0;
            foreach ($awardedIds as $pos => $awardedId) {
                $i += 10;
                $medal = $medals[$awardedId];

                $dw = XenForo_DataWriter::create('bdMedal_DataWriter_Awarded');
                $dw->setExistingData($medal, true);

                if ($pos == $upPos) {
                    if ($i > 10) {
                        $dw->set('adjusted_display_order', $i - 10);
                    } else {
                        $dw->set('adjusted_display_order', 10);
                    }
                } elseif ($pos == $upPos - 1) {
                    $dw->set('adjusted_display_order', $i + 10);
                } else {
                    $dw->set('adjusted_display_order', $i);
                }

                $dw->save();

                if ($pos == $upPos) {
                    break;
                }
            }

            $changed = true;
        } elseif (!empty($input['down'])) {
            $awardedIds = array_keys($medals);
            $downPos = array_search($input['down'], $awardedIds);
            if ($downPos === false) {
                return $this->responseNoPermission();
            }

            $i = 0;
            foreach ($awardedIds as $pos => $awardedId) {
                $i += 10;
                $medal = $medals[$awardedId];

                $dw = XenForo_DataWriter::create('bdMedal_DataWriter_Awarded');
                $dw->setExistingData($medal, true);

                if ($pos == $downPos) {
                    $dw->set('adjusted_display_order', $i + 10);
                } elseif ($pos == $downPos + 1) {
                    $dw->set('adjusted_display_order', $i - 10);
                } else {
                    $dw->set('adjusted_display_order', $i);
                }

                $dw->save();

                if ($pos == $downPos + 1) {
                    break;
                }
            }

            $changed = true;
        } elseif (!empty($input['bottom'])) {
            if (empty($medals[$input['bottom']])) {
                return $this->responseNoPermission();
            }

            $i = 0;
            foreach ($medals as $awardedId => $medal) {
                $i += 10;

                $dw = XenForo_DataWriter::create('bdMedal_DataWriter_Awarded');
                $dw->setExistingData($medal, true);

                if ($awardedId == $input['bottom']) {
                    $i -= 10;
                    $dw->set('adjusted_display_order', 10 * count($medals));
                } else {
                    $dw->set('adjusted_display_order', $i);
                }

                $dw->save();
            }

            $changed = true;
        }

        if ($changed) {
            $awardedModel->rebuildUser(XenForo_Visitor::getUserId());

            return $this->responseRedirect(XenForo_ControllerResponse_Redirect::RESOURCE_UPDATED, XenForo_Link::buildPublicLink('account/medals'));
        }

        $viewParams = array('medals' => $medals);

        return $this->_getWrapper('account', 'medals', $this->responseView('bdMedal_ViewPublic_Member_Medals', 'bdmedal_account_medals', $viewParams));
    }

    protected function _bdMedal_filterOrganizedMedals($medals)
    {
        $organized = array();

        foreach ($medals as $key => $medal) {
            if ($medal['adjusted_display_order'] > 0) {
                $organized[$key] = $medal;
            }
        }

        return $organized;
    }

}
