<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of Hosts
 *
 * @author RD
 */
class ArzfNetwork_Model_Table_Hosts extends Arzf_Crud_Db_Table 
{
    protected $_name = 'hosts';
    protected $_nameColumn = 'host';
    
    protected $_columnsOptions = array(
        'network_objects_id' => array(
            'name'    => 'id',
            'visible' => false,
        ),
        'sites_id' => 'Nom du site',
        'name' => 'Numéro de série',
        'active'    => array(
             'name'   => 'Actif',
             'values' => array(
                   0 => 'Non',
                   1 => 'Oui',
              ),
         ),
         'init_date' => 'Date d\'initialisation',
            
    );
    
    public function getColumnsOptions() {
        $colOpts = parent::getColumnsOptions();
        $colOpts['sites_id']['values'] = 
            Oft_Db_Table::getTableSelectValues('sites', 'id', 'name');
        return $colOpts;
    }

}

?>
