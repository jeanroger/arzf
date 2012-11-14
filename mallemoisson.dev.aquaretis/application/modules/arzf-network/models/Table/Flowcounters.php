<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of Flowcounters
 *
 * @author RD
 */
class ArzfNetwork_Model_Table_Flowcounters extends Arzf_Crud_Db_Table
{
    protected $_name = 'flowcounters';
    protected $_nameColumn = 'flowcounter';
    
    protected $_columnsOptions = array(
        'network_objects_id' => array(
            'name'    => 'id',
            'visible' => false,
        ),
        'sites_id' => 'Nom du site',
        'name' => 'Nom du compteur',
        'kfactor' => 'Facteur k',
        'description' => 'Description',
    );
    

    public function getColumnsOptions() {
        $colOpts = parent::getColumnsOptions();
        $colOpts['sites_id']['values'] = 
            Oft_Db_Table::getTableSelectValues('sites', 'id', 'name');
        return $colOpts;
    }

}

?>
