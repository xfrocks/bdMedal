<?php

class bdMedal_DataWriter_Category extends XenForo_DataWriter
{
    protected function _postDelete()
    {
        $medalModel = $this->_getMedalModel();

        $medals = $medalModel->getAllMedal(array('category_id' => $this->get('category_id')));

        foreach ($medals as $medal) {
            $dw = XenForo_DataWriter::create('bdMedal_DataWriter_Medal');
            $dw->setExistingData($medal, true);
            $dw->delete();
        }
    }

    protected function _getFields()
    {
        return array(
            'xf_bdmedal_category' => array(
                'category_id' => array(
                    'type' => 'uint',
                    'autoIncrement' => true
                ),
                'name' => array(
                    'type' => 'string',
                    'length' => 255,
                    'required' => true
                ),
                'description' => array('type' => 'string'),
                'display_order' => array(
                    'type' => 'uint',
                    'default' => 0
                )
            )
        );
    }

    protected function _getExistingData($data)
    {
        if (!$id = $this->_getExistingPrimaryKey($data, 'category_id')) {
            return false;
        }

        return array('xf_bdmedal_category' => $this->_getCategoryModel()->getCategoryById($id));
    }

    protected function _getUpdateCondition($tableName)
    {
        $conditions = array();

        foreach (array('0' => 'category_id') as $field) {
            $conditions[] = $field . ' = ' . $this->_db->quote($this->getExisting($field));
        }

        return implode(' AND ', $conditions);
    }

    /**
     * @return bdMedal_Model_Category
     */
    protected function _getCategoryModel()
    {
        /** @noinspection PhpIncompatibleReturnTypeInspection */
        return $this->getModelFromCache('bdMedal_Model_Category');
    }

    /**
     * @return bdMedal_Model_Medal
     */
    protected function _getMedalModel()
    {
        /** @noinspection PhpIncompatibleReturnTypeInspection */
        return $this->getModelFromCache('bdMedal_Model_Medal');
    }
}
