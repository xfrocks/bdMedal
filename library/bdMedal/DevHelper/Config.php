<?php

class bdMedal_DevHelper_Config extends DevHelper_Config_Base
{
    protected $_dataClasses = array(
        'category' => array(
            'name' => 'category',
            'camelCase' => 'Category',
            'camelCasePlural' => false,
            'camelCaseWSpace' => 'Category',
            'camelCasePluralWSpace' => false,
            'fields' => array(
                'category_id' => array('name' => 'category_id', 'type' => 'uint', 'autoIncrement' => true),
                'name' => array('name' => 'name', 'type' => 'string', 'length' => 255, 'required' => true),
                'description' => array('name' => 'description', 'type' => 'string'),
                'display_order' => array('name' => 'display_order', 'type' => 'uint', 'required' => true, 'default' => 0),
            ),
            'phrases' => array(),
            'id_field' => 'category_id',
            'title_field' => 'name',
            'primaryKey' => array('category_id'),
            'indeces' => array(),
            'files' => array('data_writer' => false, 'model' => false, 'route_prefix_admin' => false, 'controller_admin' => false),
        ),
        'medal' => array(
            'name' => 'medal',
            'camelCase' => 'Medal',
            'camelCasePlural' => false,
            'camelCaseWSpace' => 'Medal',
            'camelCasePluralWSpace' => false,
            'fields' => array(
                'medal_id' => array('name' => 'medal_id', 'type' => 'uint', 'autoIncrement' => true),
                'name' => array('name' => 'name', 'type' => 'string', 'length' => 255, 'required' => true),
                'category_id' => array('name' => 'category_id', 'type' => 'uint', 'required' => true),
                'description' => array('name' => 'description', 'type' => 'string'),
                'display_order' => array('name' => 'display_order', 'type' => 'uint', 'required' => true, 'default' => 0),
                'user_count' => array('name' => 'user_count', 'type' => 'uint', 'required' => true, 'default' => 0),
                'last_award_date' => array('name' => 'last_award_date', 'type' => 'uint', 'required' => true, 'default' => 0),
                'last_award_user_id' => array('name' => 'last_award_user_id', 'type' => 'uint', 'required' => true, 'default' => 0),
                'last_award_username' => array('name' => 'last_award_username', 'type' => 'string', 'length' => 50, 'required' => true, 'default' => ''),
                'image_date' => array('name' => 'image_date', 'type' => 'uint', 'required' => true, 'default' => 0),
                'is_svg' => array('name' => 'is_svg', 'type' => 'boolean', 'required' => true, 'default' => 0),
            ),
            'phrases' => array(),
            'id_field' => 'medal_id',
            'title_field' => 'name',
            'primaryKey' => array('medal_id'),
            'indeces' => array(
                'category_id' => array('name' => 'category_id', 'fields' => array('category_id'), 'type' => 'NORMAL'),
            ),
            'files' => array('data_writer' => false, 'model' => false, 'route_prefix_admin' => false, 'controller_admin' => false),
        ),
        'awarded' => array(
            'name' => 'awarded',
            'camelCase' => 'Awarded',
            'camelCasePlural' => false,
            'camelCaseWSpace' => 'Awarded',
            'camelCasePluralWSpace' => false,
            'fields' => array(
                'awarded_id' => array('name' => 'awarded_id', 'type' => 'uint', 'autoIncrement' => true),
                'medal_id' => array('name' => 'medal_id', 'type' => 'uint', 'required' => true),
                'user_id' => array('name' => 'user_id', 'type' => 'uint', 'required' => true, 'default' => 0),
                'username' => array('name' => 'username', 'type' => 'string', 'length' => 50, 'required' => true, 'default' => 0),
                'award_date' => array('name' => 'award_date', 'type' => 'uint', 'required' => true, 'default' => 0),
                'award_reason' => array('name' => 'award_reason', 'type' => 'string'),
                'adjusted_display_order' => array('name' => 'adjusted_display_order', 'type' => 'int', 'required' => true, 'default' => 0),
            ),
            'phrases' => array(),
            'id_field' => 'awarded_id',
            'title_field' => false,
            'primaryKey' => array('awarded_id'),
            'indeces' => array(
                'medal_id' => array('name' => 'medal_id', 'fields' => array('medal_id'), 'type' => 'NORMAL'),
            ),
            'files' => array('data_writer' => false, 'model' => false, 'route_prefix_admin' => false, 'controller_admin' => false),
        ),
    );
    protected $_dataPatches = array(
        'xf_user' => array(
            'xf_bdmedal_awarded_cached' => array('name' => 'xf_bdmedal_awarded_cached', 'type' => 'serialized'),
        ),
        'xf_bdmedal_awarded' => array(
            'award_reason' => array('name' => 'award_reason', 'type' => 'string'),
            'adjusted_display_order' => array('name' => 'adjusted_display_order', 'type' => 'int', 'required' => true, 'default' => 0),
        ),
        'xf_bdmedal_medal' => array(
            'is_svg' => array('name' => 'is_svg', 'type' => 'boolean', 'required' => true, 'default' => 0),
        ),
    );
    protected $_exportPath = '/Users/sondh/XenForo/bdMedal';
    protected $_exportIncludes = array();

    /**
     * Return false to trigger the upgrade!
     * common use methods:
     *    public function addDataClass($name, $fields = array(), $primaryKey = false, $indeces = array())
     *    public function addDataPatch($table, array $field)
     *    public function setExportPath($path)
     **/
    protected function _upgrade()
    {
        return true; // remove this line to trigger update

        /*
        $this->addDataClass(
                'name_here',
                array( // fields
                        'field_here' => array(
                                'type' => 'type_here',
                                // 'length' => 'length_here',
                                // 'required' => true,
                                // 'allowedValues' => array('value_1', 'value_2'),
                                // 'default' => 0,
                                // 'autoIncrement' => true,
                        ),
                        // other fields go here
                ),
                'primary_key_field_here',
                array( // indeces
                        array(
                                'fields' => array('field_1', 'field_2'),
                                'type' => 'NORMAL', // UNIQUE or FULLTEXT
                        ),
                ),
        );
        */
    }
}