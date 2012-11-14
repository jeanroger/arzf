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
class ArzfNetwork_HostInputsController extends Arzf_Crud_Controller_ActionAbstract
{
    protected $_autoLeftMenu = true;
    protected $_leftMenuName = 'conf';
    protected $_title = "Gestion des entrées d'enregistreurs";
    protected $_tableClass = 'ArzfNetwork_Model_Table_HostInputs';
}

?>
