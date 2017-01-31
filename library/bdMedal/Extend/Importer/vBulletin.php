<?php

define('bdMedal_Extend_Importer_vBulletin_LOADED', true);

class bdMedal_Extend_Importer_vBulletin extends XFCP_bdMedal_Extend_Importer_vBulletin
{
    public function getSteps()
    {
        $steps = parent::getSteps();

        $steps['medalCategories'] = array('title' => 'Medal Categories ([bd] Medal System)',);

        $steps['medals2'] = array(
            'title' => 'Medals ([bd] Medal System)',
            'depends' => array(
                'threads',
                'medalCategories'
            ),
        );

        $steps['userMedals'] = array(
            'title' => 'User Medals ([bd] Medal System)',
            'depends' => array('medals2'),
        );

        return $steps;
    }

    public function stepMedalCategories($start, array $options)
    {
        $sDb = $this->_sourceDb;
        $prefix = $this->_prefix;

        /** @var bdMedal_Extend_Model_Import $model */
        $model = $this->_importModel;

        $existed = $sDb->fetchOne("SHOW TABLES LIKE '{$prefix}award_cat'");
        if (empty($existed)) {
            return true;
        }

        $categories = $sDb->fetchAll('
            SELECT *
            FROM ' . $prefix . 'award_cat
            WHERE award_cat_id >= ' . $sDb->quote($start) . '
            ORDER BY award_cat_id
        ');

        if (!$categories) {
            return true;
        }

        $total = 0;

        XenForo_Db::beginTransaction();

        foreach ($categories AS $category) {
            $import = array(
                'name' => $category['award_cat_title'],
                'description' => $category['award_cat_desc'],
                'display_order' => $category['award_cat_displayorder'],
            );

            if ($model->bdMedal_importCategory($category['award_cat_id'], $import)) {
                $total++;
            }
        }

        XenForo_Db::commit();

        $this->_session->incrementStepImportTotal($total);

        return true;
    }

    public function stepMedals2($start, array $options)
    {
        $sDb = $this->_sourceDb;
        $prefix = $this->_prefix;

        /** @var bdMedal_Extend_Model_Import $model */
        $model = $this->_importModel;
        /** @var bdMedal_Model_Category $categoryModel */
        $categoryModel = $model->getModelFromCache('bdMedal_Model_Category');

        $existed = $sDb->fetchOne("SHOW TABLES LIKE '{$prefix}award'");
        if (empty($existed)) {
            return true;
        }

        $medals = $sDb->fetchAll('
            SELECT *
            FROM ' . $prefix . 'award
            WHERE award_id >= ' . $sDb->quote($start) . '
            ORDER BY award_id
        ');

        if (!$medals) {
            return true;
        }

        $categoryIds = array();

        foreach ($medals AS $medal) {
            $categoryIds[] = $medal['award_cat_id'];
        }

        $categoryIdMap = $model->getImportContentMap('medalCategories', $categoryIds);
        $firstCategory = $categoryModel->getCategoryById(1);

        $total = 0;

        XenForo_Db::beginTransaction();

        foreach ($medals AS $medal) {
            $categoryId = $this->_mapLookUp($categoryIdMap, $medal['award_cat_id']);
            if (empty($categoryId)) {
                if (empty($firstCategory)) {
                    continue;
                } else {
                    $categoryId = $firstCategory['category_id'];
                }
            }

            $import = array(
                'name' => $medal['award_name'],
                'category_id' => $categoryId,
                'description' => $medal['award_desc'],
                'display_order' => $medal['award_displayorder'],
            );

            $imageUrl = $medal['award_img_url'];

            if ($model->bdMedal_importMedal($medal['award_id'], $import, $imageUrl)) {
                $total++;
            }
        }

        XenForo_Db::commit();

        $this->_session->incrementStepImportTotal($total);

        return true;
    }

    public function stepUserMedals($start, array $options)
    {
        $options = array_merge(array(
            'max' => false,
            'limit' => 100,
            'processed' => 0,
        ), $options);

        $sDb = $this->_sourceDb;
        $prefix = $this->_prefix;

        /** @var bdMedal_Extend_Model_Import $model */
        $model = $this->_importModel;
        /** @var XenForo_Model_User $userModel */
        $userModel = $model->getModelFromCache('XenForo_Model_User');

        if ($options['max'] === false) {
            $existed = $sDb->fetchOne("SHOW TABLES LIKE '{$prefix}award_user'");
            if (!empty($existed)) {
                $data = $sDb->fetchRow('
                    SELECT MAX(issue_id) AS max, COUNT(issue_id) AS rows
                    FROM ' . $prefix . 'award_user
                    WHERE issue_id >= 0
                ');

                $options = array_merge($options, $data);
            } else {
                $options['max'] = 0;
                $options['rows'] = 0;
            }
        }

        $awardeds = $sDb->fetchAll($sDb->limit('
                SELECT *
                FROM ' . $prefix . 'award_user
                WHERE issue_id >= ' . $sDb->quote($start) . '
                ORDER BY issue_id
            ', $options['limit']));

        if (!$awardeds) {
            return true;
        }

        $next = 0;
        $total = 0;

        $medalIds = array();
        $userIds = array();

        foreach ($awardeds AS $awarded) {
            $medalIds[] = $awarded['award_id'];
            $userIds[] = $awarded['userid'];
        }

        $medalIdMap = $model->getImportContentMap('medals', $medalIds);
        $userIdMap = $model->getImportContentMap('user', $userIds);
        $users = $userModel->getUsersByIds($userIdMap);

        XenForo_Db::beginTransaction();

        foreach ($awardeds AS $awarded) {
            $medalId = $this->_mapLookUp($medalIdMap, $awarded['award_id']);
            $userId = $this->_mapLookUp($userIdMap, $awarded['userid']);

            if ($medalId > 0 && $userId > 0) {
                $model->bdMedal_importAwarded(
                    $medalId,
                    $userId,
                    $users[$userId]['username'],
                    $awarded['issue_time'],
                    $awarded['issue_reason']
                );
            }

            $total++;
            $next = $awarded['issue_id'] + 1;
        }

        XenForo_Db::commit();

        $options['processed'] += $total;
        $this->_session->incrementStepImportTotal($total);

        return array(
            $next,
            $options,
            $this->_getProgressOutput($options['processed'], $options['rows'])
        );
    }

}
