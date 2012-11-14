<?php
/**
 * OFT Framework
 *
 * @package Oft_Modules
 * @subpackage OftAdmin_Controller
 */


/**
 * @author Eric Colinet
 * @package Oft_Modules
 * @subpackage OftAdmin_Controller
 */
class ArzfAdmin_RoleUserController extends Oft_Crud_Controller_Action_Abstract
{
    protected $_autoLeftMenu = true;
    protected $_title = "Associations utilisateurs / groupes";
    protected $_tableClass = 'Arzf_Model_Table_RoleUser';
}

