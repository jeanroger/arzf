<?php

/**
 * Description of SupervisionController
 *
 * @author RD
 */
class ArzfSup_SupervisionController extends Oft_Controller_Action
{
    protected $_ondev = 'Fonctionnalité en cours de développement';
    
    protected $_sectors = array();
    
    
    protected function betweenTwoHoursFilter ($timestamp, $hourzeroTimestamp, $start, $stop)  {
    	if ($timestamp >= $hourzeroTimestamp +$start && $timestamp < $hourzeroTimestamp + $stop) return true;
    	return false;
    }
    
    public function resumentwAction()
    {
        $this->_addMessage($this->_ondev);
    }
    
    public function resumesctAction()
    {
        $sectors = new ArzfNetwork_Model_Table_Sectors;
        $sectorsRowset = $sectors->fetchAll();
        foreach ($sectorsRowset as $sector)
        {
            $this->_sectors[] = array('id' => $sector['network_objects_id'], 'name' => $sector['name']);
        }
        $this->view->sectors = $this->_sectors;
        
    }
    
    public function resumeenvAction()
    {
    	$sites = Oft_Db_Table::getTableSelectValues('sites', 'id', 'name');
    	$hosts = new ArzfNetwork_Model_Table_Hosts;
    	$hostsRowset = $hosts->fetchAll();
    	$hostsArray = array();
    	foreach ($hostsRowset as $host) {
    	    if ($host->active ==1) {
    	        $hostsArray[$host->network_objects_id] = array(
    	                'serial' => $host->name, 
    	                'sitename' => $sites[$host->sites_id]
    	        );
    	    }
    	}
    	$this->view->hosts = $hostsArray;
    	
    }
    
    public function getntwdataAction()
    {
        if ($this->isAjax()) {
            $this->_disableRendering();
	        // Return route as GeoJSON
	        $geojson = array(
	                'type'      => 'FeatureCollection',
	                'features'  => array()
	        );
	        
	        $polygon = array(
	                array(6.095640, 44.033722),
	                array(6.120531,44.038658),
	                array(6.117184,44.044149),
	                array(6.107399,44.044581),
	                array(6.098730, 44.041681));
	        // Add edges to GeoJSON array
	        
            $feature = array(
                    'type' => "Feature",
                    'geometry' => array(
                            'type' => 'Polygon',
                            'coordinates' => array($polygon)
                    ),
                    'properties' => array(
                            'id' => 'id',
                            'length' => 7
                    ),
                    'crs' => array(
                            'type' => 'name',
                            'properties' => array('name' => "urn:ogc:def:crs:OGC:1.3:CRS84")
                    )
            );
        
            // Add feature array to feature collection array
            array_push($geojson['features'], $feature);
        
	        // Return routing result
	        header('Content-type: application/json',true);
	        echo json_encode($geojson);
        }
    }
    
    protected function dailyCalc($valuesarray = array()){
    	$dailyarray = array();
    	//initialisation avec les timestamps de l'heure zéro de chaque jour
    	foreach ($valuesarray as $timestamp => $value) {
    		if (is_int($timestamp/86400)) $tmpdailyarray[] = $timestamp;
    	}
    	//
    	foreach ($tmpdailyarray as $hourzeroTimestamp) {
    		$minarray = array();
    		$sum = '';
    		foreach ($valuesarray as $timestamp => $value) {
    			if ($this->betweenTwoHoursFilter($timestamp, $hourzeroTimestamp, 7200, 14400)) $minarray[] = $value;
    			if ($this->betweenTwoHoursFilter($timestamp, $hourzeroTimestamp, 0, 86400)) $sum += $value;
    		}
    		//on arrondi � 2chiffres
    		$dailyarray[$hourzeroTimestamp] = array ( 
    				round($sum, 2), 
    				round(min($minarray), 2));
    	}
    	
    	return $dailyarray;
    }
    
    public function getsctdataAction()
    {
        $params = $this->getRequest()->getParams();
        if ($this->isAjax()) {
            $this->_disableRendering();
            $flowcountersSectorsTable = new ArzfNetwork_Model_Table_FlowcountersSectors();
            $sctlist = $flowcountersSectorsTable->getCptList($params['noid']);
            
            $rrdspath = getenv('RRDS_PATH');
            if (!$rrdspath) $rrdspath = '.';
			
            //initilisation du tableau de retour
            $sctTable = new ArzfNetwork_Model_Table_Sectors();
            $sctRow = $sctTable->fetchById($params['noid']);
    		list($year, $month, $day) = explode('-', $sctRow['init_date']);
    		$sctInitDate = mktime(0,0,0, $month, $day, $year);
    		$now = time();
    		$tempArray = array();
    		for ($i=$sctInitDate; $i<=$now; $i+=3600) {
    			$tempArray[$i] = 0;
    		}

            foreach ($sctlist as $cptId => $valuesTab){
            	//zap if 'position' == 'OZR'
            	if ($valuesTab['position'] == 'OZR') continue;
            	
            	//fetchrrd or generate random values
            	if (file_exists($rrdspath."/".$valuesTab['rrdfilename'])) {
            		$last = rrd_last($rrdspath."/".$valuesTab['rrdfilename']);
            		$opts = array ( "AVERAGE", "--start", $sctInitDate, "--end", $last);
            		$fetcharray = rrd_fetch($rrdspath."/".$valuesTab['rrdfilename'], $opts);
            		$cptValuesArray = $fetcharray['data']['pulse'];
            		
            	} else {
            		$cptValuesArray = Arzf_Rrdtool::virtualDataset($sctInitDate, $now, 3600, $range = array (10, 150), false);
            	}
            	//test 'position'
            	$posFactor = 1;
            	if ($valuesTab['position'] == 'OUT') $posFactor = -1;
            	
            	//manage Nan (Not a Number)
            	//apply 'kfactor' and posfactor
            	//change liters to cubic meters (/1000)
            	//and add the value to the sector value
            	foreach ($cptValuesArray as $key => $value) {
            		if (is_nan($value)) {
            			$value = 0;
            			//create alarm ($timetamp, $network_objects_id, 
            			//              $message='Une valeur n'a pas �t� re�u, les calculs secteurs en sont fauss�s')
            		}
            		
            		$cptValuesArray[$key] = ($value * $valuesTab['kfactor'])/1000 * $posFactor;
            		
            		$tempArray[$key] += $cptValuesArray[$key];
            	}
            	
            	// daily calculations
            	$dailyArray = $this->dailyCalc($tempArray);
            }
            
            // format and encode $resultArray
            $resultArray = array();
            foreach ($tempArray as $timestamp => $value) {
                if (array_key_exists($timestamp, $dailyArray)) {
            		$dailyvalue = $dailyArray[$timestamp];
            		$result = array ($timestamp, $value, $dailyvalue[0], $dailyvalue[1]);
            	} else {
            		$result = array ($timestamp, $value);
            	}
            	$resultArray[] = $result;
            }
        	echo Zend_Json::encode($resultArray);
        }
    }

    public function getsctcptlistAction ()
    {
    	$params = $this->getRequest()->getParams();
    	if ($this->isAjax()) {
    		$this->_disableRendering();
    		$flowcountersSectorsTable = new ArzfNetwork_Model_Table_FlowcountersSectors();
    		$sctlist = $flowcountersSectorsTable->getCptList($params['noid']);

            $resultArray = array();
            $inArray = array();
            $outArray = array();
            foreach ($sctlist as $cptId => $valuesTab){
            	//zap if 'position' == 'OZR'
            	if ($valuesTab['position'] == 'OZR') continue;
            	if ($valuesTab['position'] == 'IN') $inArray[] = $valuesTab['cptname'];
            	if ($valuesTab['position'] == 'OUT') $outArray[] = $valuesTab['cptname'];
            }	
            $resultArray[0] = $inArray;
            $resultArray[1] = $outArray;
            echo Zend_Json::encode($resultArray);
    	}	 
    }
    
    public function getenvdataAction()
    {
        $params = $this->getRequest()->getParams();
        if ($this->isAjax()) {
            $this->_disableRendering();
            $resultArray = array();
            
            $datasetsTable = new ArzfData_Model_Table_Datasets();
            $select = $datasetsTable->select()->setIntegrityCheck(false);
            $select->from(array('d' => 'datasets'));
            $select->where('network_objects_id = ?', $params['noid']);
            $select->join(array('dt' => 'dataset_types'), 'd.dataset_types_id = dt.id', array('dt_name' => 'dt.name', 'dt_factor' => 'dt.factor'));
            $select->join(array('hi' => 'host_inputs'), 'd.host_inputs_id = hi.id', array('hi_name' => 'hi.name'));
            $datasetsRowset = $datasetsTable->fetchAll($select);
            
            $rrdspath = getenv('RRDS_PATH');
            if (!$rrdspath) $rrdspath = '.';
            

            //initilisation du tableau de retour
            $hostsTable = new ArzfNetwork_Model_Table_Hosts();
            $hostRow = $hostsTable->fetchById($params['noid']);
            $hostName = $hostRow['name'];
            list($year, $month, $day) = explode('-', $hostRow['init_date']);
    		$hostInitDate = mktime(0,0,0, $month, $day, $year);
    		$stop = time();
    		for ($i=$stop - 604800; $i<=$stop; $i+=3600) {
    			$resultArray['ts'][] = $i;
    		}

            
            foreach ($datasetsRowset as $row) {
	                $rrdName = $hostName.'--1.'.$row['hi_name'].'.rrd';
	                $datasetValuesArray = array();
	                
	                if (file_exists($rrdspath."/".$rrdName)) {
	            		$opts = array ( "AVERAGE", "--start", $stop - 604800, "--end", $stop);
	            		$fetcharray = rrd_fetch($rrdspath."/".$rrdName, $opts);
	            		foreach($fetcharray['data'][$row['dt_name']] as $ts => $value) {
	            		    if (is_nan($value))  $value = 0;
	            		    $datasetValuesArray[] = $value * $row['dt_factor'];
	            		}
	            		
	            	} else {
	            		$datasetValuesArray = Arzf_Rrdtool::virtualDataset($hostInitDate, $now, 3600, $range = array (0, 0), false);
	            	}
                $resultArray[$row['dt_name']] = $datasetValuesArray;
            }
            
            echo Zend_Json::encode($resultArray);
        }
    }
    
}

?>
