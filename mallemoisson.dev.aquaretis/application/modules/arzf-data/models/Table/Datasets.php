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
class ArzfData_Model_Table_Datasets extends Arzf_Crud_Db_Table
{
    protected $_name = 'datasets';
    protected $_nameColumn = 'dataset';
    
    protected $_columnsOptions = array(
        'id' => array(
            'name'    => 'id',
            'visible' => false,
        ),
        'network_objects_id' => 'Element de réseau',
        'name' => 'Nom',
        'dataset_types_id' => 'Type',
        'host_inputs_id' => 'Entrée de l\'enregistreur',
        'hosts_network_objects_id' => 'Enregistreur',
    );
    
    public function getColumnsOptions() {
        $colOpts = parent::getColumnsOptions();
        $colOpts['dataset_types_id']['values'] = 
            Oft_Db_Table::getTableSelectValues('dataset_types', 'id', 'name');
        $colOpts['host_inputs_id']['values'] = 
            Oft_Db_Table::getTableSelectValues('host_inputs', 'id', 'name');
        $colOpts['hosts_network_objects_id']['values'] = 
            Oft_Db_Table::getTableSelectValues('hosts', 'network_objects_id', 'name');
        $colOpts['network_objects_id']['values'] = 
            $this->findNoname();
        return $colOpts;
    }

}

?>
