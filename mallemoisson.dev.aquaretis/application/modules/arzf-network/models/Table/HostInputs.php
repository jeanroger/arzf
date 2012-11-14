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
class ArzfNetwork_Model_Table_HostInputs extends Arzf_Crud_Db_Table
{
    protected $_name = 'host_inputs';
    protected $_nameColumn = 'host_input';
    
    protected $_columnsOptions = array(
        'id' => array(
            'name'    => 'id',
            'visible' => false,
        ),
        'name' => 'Code de l\'entrée',
        'description' => 'Description',
    );
    
}

?>
