<?php

/**
 * Description of sectors
 *
 * @author RD
 */
class ArzfNetwork_Model_Table_Sectors extends Arzf_Crud_Db_Table 
{
    protected $_name = 'sectors';
    protected $_nameColumn = 'sector';
    
    protected $_columnsOptions = array(
        'network_objects_id' => array(
            'name'    => 'id',
            'visible' => false,
        ),
        'name' => 'Nom du secteur',
    	'init_date' => 'Date d\'initialisation',
    );


}

?>
