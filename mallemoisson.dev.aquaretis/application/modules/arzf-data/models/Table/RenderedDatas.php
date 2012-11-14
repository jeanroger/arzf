<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of Sites
 *
 * @author yrda7553
 */
class ArzfData_Model_Table_RenderedDatas extends Arzf_Crud_Db_Table
{
    protected $_name = 'rendered_datas';
    protected $_nameColumn = 'rendered_data';
    
    protected $_columnsOptions = array(
        'id' => array(
            'name'    => 'id',
            'visible' => false,
        ),
        'network_objects_id' => 'Element de réseau',
    );
    
    public function getColumnsOptions() {
        $colOpts = parent::getColumnsOptions();
        $colOpts['network_objects_id']['values'] = 
            $this->findNoname();
        return $colOpts;
    }
}

?>
