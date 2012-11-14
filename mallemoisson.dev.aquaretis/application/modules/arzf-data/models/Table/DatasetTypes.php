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
class ArzfData_Model_Table_DatasetTypes extends Arzf_Crud_Db_Table
{
    protected $_name = 'dataset_types';
    protected $_nameColumn = 'dataset_type';
    
    protected $_columnsOptions = array(
        'id' => array(
            'name'    => 'id',
            'visible' => false,
        ),
        'name' => 'Nom',
        'ds' => 'Data Source',
        'rra' => 'Round Robin Archive',
        'description' => 'Description',
    );
    
}

?>
