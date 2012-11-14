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
class ArzfNetwork_SitesController extends Arzf_Crud_Controller_ActionAbstract
{
    protected $_autoLeftMenu = true;
    protected $_leftMenuName = 'conf';
    protected $_title = "Gestion des sites";
    protected $_tableClass = 'ArzfNetwork_Model_Table_Sites';
}

?>
