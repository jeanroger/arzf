<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of HostsController
 *
 * @author yrda7553
 */
class ArzfData_DatasetsController extends Arzf_NOchoiceCrud
{
    protected $_autoLeftMenu = true;
    protected $_leftMenuName = 'conf';
    protected $_title = "Gestion des datasets";
    protected $_tableClass = 'ArzfData_Model_Table_Datasets';
}

?>
