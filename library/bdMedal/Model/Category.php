<?php

class bdMedal_Model_Category extends XenForo_Model
{
    public function getList(array $conditions = array(), array $fetchOptions = array())
    {
        $data = $this->getAllCategory($conditions, $fetchOptions);
        $list = array();

        foreach ($data as $id => $row) {
            $list[$id] = $row['name'];
        }

        return $list;
    }

    public function getCategoryById($id, array $fetchOptions = array())
    {
        $data = $this->getAllCategory(array('category_id' => $id), $fetchOptions);

        return reset($data);
    }

    public function getAllCategory(array $conditions = array(), array $fetchOptions = array())
    {
        $whereConditions = $this->prepareCategoryConditions($conditions, $fetchOptions);

        $orderClause = $this->prepareCategoryOrderOptions($fetchOptions);
        $joinOptions = $this->prepareCategoryFetchOptions($fetchOptions);
        $limitOptions = $this->prepareLimitFetchOptions($fetchOptions);

        return $this->fetchAllKeyed($this->limitQueryResults("
				SELECT category.*
					$joinOptions[selectFields]
				FROM `xf_bdmedal_category` AS category
					$joinOptions[joinTables]
				WHERE $whereConditions
					$orderClause
			", $limitOptions['limit'], $limitOptions['offset']), 'category_id');
    }

    public function countAllCategory(array $conditions = array(), array $fetchOptions = array())
    {
        $whereConditions = $this->prepareCategoryConditions($conditions, $fetchOptions);
        $joinOptions = $this->prepareCategoryFetchOptions($fetchOptions);

        return $this->_getDb()->fetchOne("
			SELECT COUNT(*)
			FROM `xf_bdmedal_category` AS category
				$joinOptions[joinTables]
			WHERE $whereConditions
		");
    }

    public function prepareCategoryConditions(array $conditions, array &$fetchOptions)
    {
        $sqlConditions = array();
        $db = $this->_getDb();

        foreach (array('0' => 'category_id', '1' => 'display_order') as $intField) {
            if (!isset($conditions[$intField])) {
                continue;
            }

            if (is_array($conditions[$intField])) {
                $sqlConditions[] = "category.$intField IN (" . $db->quote($conditions[$intField]) . ")";
            } else {
                $sqlConditions[] = "category.$intField = " . $db->quote($conditions[$intField]);
            }
        }

        return $this->getConditionsForClause($sqlConditions);
    }

    public function prepareCategoryFetchOptions(array $fetchOptions)
    {
        $selectFields = '';
        $joinTables = '';

        return array(
            'selectFields' => $selectFields,
            'joinTables' => $joinTables
        );
    }

    public function prepareCategoryOrderOptions(array &$fetchOptions)
    {
        $choices = array();
        return $this->getOrderByClause($choices, $fetchOptions, 'category.display_order');
    }

}
