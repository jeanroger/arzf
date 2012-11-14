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
class ArzfNetwork_HostsController extends Arzf_NOCrud
{
    protected $_autoLeftMenu = true;
    protected $_leftMenuName = 'conf';
    protected $_title = "Gestion des enregistreurs";
    protected $_tableClass = 'ArzfNetwork_Model_Table_Hosts';
}

?>
