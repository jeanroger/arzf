<?php
/**
 * Contrôleur par défaut
 *
 * @package App
 */

/**
 * @package App
 */
class ArzfReporting_IndexController extends Oft_Controller_Action
{
    /**
     * Règles de validation définies pour le contrôleur
     * @var array
     */
    protected $_validatorRules = array(
        'id' => array('Int')
    );
    
    protected $_tmpPath = NULL;
    
    public function init() {
        $this->_tmpPath = APP_ROOT."/public/tmp/";
    }
    
    public function addOnLoad()
    {
        $view = $this->view;
        $jquery = $view->jquery();
        
        $jquery
            ->addStylesheet($view->baseUrlMedia('jqgrid/3.8.1/css/ui.jqgrid.css'))
            ->addJavascriptFile(
                $view->baseUrlMedia(
                	'jqgrid/3.8.1/js/i18n/grid.locale-'
                    . Oft_App::getInstance()->getLocale()->getLanguage() . '.js'
                )
            )
            ->addJavascriptFile($view->baseUrlMedia('jqgrid/3.8.1/js/jquery.jqGrid.min.js'));
    }
    
    /**
     * Point d'entrée par défaut de l'application
     */
    public function indexAction ()
    {
		$this->addOnLoad();
		
	}

	public function getdslistAction ()
	{
	    if ($this->isAjax()) {
	        $this->_disableRendering();
	        
	        $datasetsTable = new ArzfData_Model_Table_Datasets();
	        $select = $datasetsTable->select()->setIntegrityCheck(false);
	        $select->from(array('d' => 'datasets'));
	        $select->join(array('dt' => 'dataset_types'), 'd.dataset_types_id = dt.id', array('dt_description' => 'dt.description'));
	        $datasetsRowset = $datasetsTable->fetchAll($select);

	        $nbrows = count($datasetsRowset);
	        $nbrowsByPage = 10;
	        
	        $result->page = 1;
	        $result->total = ceil($nbrows/$nbrowsByPage);
	        $result->records = $nbrows;
	        
	        $hostsTable = new ArzfNetwork_Model_Table_Hosts();
	        foreach ($datasetsRowset as $key => $row) {
	            $nositeselect = $hostsTable->select()->setIntegrityCheck(false);
	            $nositeselect->from(array('h' => 'hosts'));
            	$nositeselect->where('network_objects_id = ?', $row['hosts_network_objects_id']);
	            $nositeselect->join(array('s' => 'sites'), 'h.sites_id = s.id', array('s_name' => 's.name'));
	            $nosRowset = $hostsTable->fetchAll($nositeselect);
//				$siteRow = $hostsTable->fetchById($row['sites_id']);
				$noRow = $nosRowset->current();
	            
	            $result->rows[$key]['id'] = $row['id'];
	            $result->rows[$key]['cell'] = array($row['name'], $row['dt_description'], $noRow['s_name']);
	        } 
	        //print_r($datasetsRowset); 
	        echo Zend_Json::encode($result);
	    }
	}
	
	public function compilexportAction ()
	{
	    if ($this->isAjax()) {
	        $this->_disableRendering();
	        $params = $this->getRequest()->getParams();
	        
	        $rrdspath = getenv('RRDS_PATH');
	        $zip = new ZipArchive();
	        $sufix = 'aquaretis_'.date('d-m-Y', $params['from']).'_'.date('d-m-Y', $params['to']).'_';
	        $tmpzipfile = tempnam($this->_tmpPath, $sufix);//die($tmpzipfile);
	        
	        if ($zip->open($tmpzipfile, ZIPARCHIVE::CREATE)==TRUE) {
	        
		        $datasetsTable = new ArzfData_Model_Table_Datasets();
		        $resultArray = array();
		        $nbValidDs = 0;
		        $slctArray = explode(',', $params['slctds']);
		        foreach ($slctArray as $dsId) {
		            $select = $datasetsTable->select()->setIntegrityCheck(false);
		            $select->from(array('d' => 'datasets'));
		            $select->where('d.id = ?', $dsId);
            		$select->join(array('dt' => 'dataset_types'), 'd.dataset_types_id = dt.id', array('dt_name' => 'dt.name', 'dt_factor' => 'dt.factor'));
		            $select->join(array('h' => 'hosts'), 'd.hosts_network_objects_id  = h.network_objects_id', array('h_name' => 'h.name'));
		            $select->join(array('hi' => 'host_inputs'), 'd.host_inputs_id = hi.id', array('hi_name' => 'hi.name'));
		            $datasetsRowset = $datasetsTable->fetchAll($select);
		            $row = $datasetsRowset->current();
		            
		            $rrdName = $row['h_name'].'--1.'.$row['hi_name'].'.rrd';
		            if (file_exists($rrdspath."/".$rrdName)) {
		                $opts = array ( "AVERAGE", "--start", $params['from'], "--end", $params['to']);
		                $fetcharray = rrd_fetch($rrdspath."/".$rrdName, $opts);
			            $tmpcsv = tempnam ($this->_tmpPath, "csv-");
				        $handle = fopen($tmpcsv, "w") or die("impossible ouvrir csv $tmpcsv");
		                foreach($fetcharray['data'][$row['dt_name']] as $ts => $value) {
		                    if (is_nan($value))  $value = 0;
			        		fwrite($handle, "$ts;$value\n") or die('impossible ecrire csv');
		                }
			        	fclose($handle);
			        	$zip->addFile($tmpcsv, $row['h_name'].'--1.'.$row['hi_name'].".csv") or die("et merde !");
			        	$nbValidDs++;
		            }
		        }
	        } else {
	            exit("Impossible d'ouvrir <$tmpzipfile>\n");
	        }
	        
	        $zip->close();
	        
	        array_map('unlink', glob("$this->_tmpPath/csv-*"));
	        $resultArray['nbvalidds'] = $nbValidDs;
	        if ($nbValidDs == 0) {
	            unlink($tmpzipfile);
	            $resultArray['zipfile'] = '';
	        } else {
		        rename($tmpzipfile, $tmpzipfile.'.zip');
		        $zipfileexplode = explode('/', $tmpzipfile);
		        $zipfile = end($zipfileexplode);
	            $resultArray['zipfile'] = $zipfile.'.zip';
		    }
	        echo Zend_Json::encode($resultArray);
	    }
	}
	
	public function downloadxportfileAction () {
	    $this->_disableRendering();
	    $params = $this->getRequest()->getParams();

	    header("Content-Type: application/force-download");
	    header("Content-Transfer-Encoding: binary");
	    header("Content-Length: ".filesize($this->_tmpPath.$params['zipfile']));
	    header("Content-Disposition: attachment");
	    header("Expires: 0");
	    header("Cache-Control: no-cache, must-revalidate");
	    header("Pragma: no-cache");
	    readfile($this->_tmpPath.$params['zipfile']);
	    exit();
	}

	public function cleanxportfileAction () {
	    $this->_disableRendering();
	    $params = $this->getRequest()->getParams();print_r($params);
	    unlink($this->_tmpPath.$params['zipfile']) or die($params['zipfile']);
	}
}

