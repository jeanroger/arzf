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
class ArzfNetwork_Model_Table_Sites extends Arzf_Crud_Db_Table
{
    protected $_name = 'sites';
    protected $_nameColumn = 'site';
    
    protected $_columnsOptions = array(
        'id' => array(
            'name'    => 'id',
            'visible' => false,
        ),
        'name' => 'Nom du site',
        'description' => 'Description',
        'address' => 'Adresse',
    );
    
}

?>
