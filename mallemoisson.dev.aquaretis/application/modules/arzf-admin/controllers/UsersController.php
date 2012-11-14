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
class ArzfAdmin_UsersController extends Oft_Crud_Controller_Action_Abstract
{
    protected $_autoLeftMenu = true;
    protected $_title = "Gestion des utilisateurs";
    protected $_tableClass = 'Arzf_Model_Table_Users';
    
    public function getForm()
    {
        // Si le formulaire à déjà été créé
        if ($this->_form!==null) {
            return $this->_form;
        }
        
        // Ajoute le validateur 'Cuid' à remplacer par regex adresse email
        $form = parent::getForm();
        // $form->getElement('cuid')
            // ->addValidator('Cuid')
            // ->addFilter('StringToUpper');
        return $form;
    }
    
    public function supprimerAction()
    {
        $id = $this->_getParam('id', null);
        if (!$id) {
            $this->_redirector('liste');
            return;
        }
        
        // Suppression de la partie RoleUser
        $row = $this->getRow($id);
        $roleUser = new Oft_Model_Table_RoleUser();
        $roleUser->delete($this->getTable()->getAdapter()->quoteInto("cuid=?", $row->cuid));
        
        parent::supprimerAction();
    }
}
