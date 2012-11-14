<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of FlowcountersSectors
 *
 * @author yrda7553
 */
class ArzfNetwork_Model_Table_FlowcountersSectors extends Arzf_Crud_Db_Table
{
    protected $_name = 'flowcounters_sectors';
    protected $_nameColumn = 'flowcounters_sectors';
    protected $_columnsOptions = array(
        'id' => array(
            'name'    => 'id',
            'visible' => false,
        ),
        'sectors_network_objects_id' => 'Nom du secteur',
        'flowcounters_network_objects_id' => 'Nom du compteur',
//        'position'    => 'Position',
        'position'    => array(
           'name'   => 'Position',
           'values' => array(
               'IN' => 'IN',
               'OUT' => 'OUT',
               'OZR' => 'OZR',
           )
        ),
    );
    
    public function getColumnsOptions() {
        $colOpts = parent::getColumnsOptions();
        $colOpts['sectors_network_objects_id']['values'] = 
            Oft_Db_Table::getTableSelectValues('sectors', 'network_objects_id', 'name');
        $colOpts['flowcounters_network_objects_id']['values'] = 
            Oft_Db_Table::getTableSelectValues('flowcounters', 'network_objects_id', 'name');
        return $colOpts;
    }

    /**
     * Renvoie la liste de compteurs (IN et OUT) pour un secteur donné
     *
     */
    public function getCptList($sectors_network_objects_id)
    {
    	$cptList = array();
    	$rows = $this->fetchAll($this->select()->where('sectors_network_objects_id = ?', $sectors_network_objects_id));
    	$flowcountersTable = new ArzfNetwork_Model_Table_Flowcounters();
    	$datasetsTable = new ArzfData_Model_Table_Datasets();
    	$hostsTable = new ArzfNetwork_Model_Table_Hosts();
    	$hostInputsTable = new ArzfNetwork_Model_Table_HostInputs();

    	foreach ($rows as $row) {
    		$position = $row['position'];    		
    		$flowcountersRow = $flowcountersTable->fetchRow(
    			$flowcountersTable->select()
    				->where('network_objects_id = ?', $row['flowcounters_network_objects_id'])
    			);
    		$kfactor = $flowcountersRow['kfactor'];
    		$cptname = $flowcountersRow['name'];
    		
    		$datasetsRow = $datasetsTable->fetchRow(
    			$datasetsTable->select()
    				->where('network_objects_id = ?', $row['flowcounters_network_objects_id'])
    			);
    		
    		$hostInputRow = $hostInputsTable->fetchById($datasetsRow['host_inputs_id']);
     		$hostRow = $hostsTable->fetchById($datasetsRow['hosts_network_objects_id']);
    		$rrdFileName = $hostRow['name'].'--1.'.$hostInputRow['name'].'.rrd';
    		
    		$cptList[$row['flowcounters_network_objects_id']] = array(
    				'cptname' => $cptname,
    				'kfactor' => $kfactor, 
    				'position' => $position,
    				'rrdfilename' => $rrdFileName
    			);
    	}
    	return $cptList;
    }
    
}

?>
    