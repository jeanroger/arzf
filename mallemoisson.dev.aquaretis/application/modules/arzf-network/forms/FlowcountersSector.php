<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of FlowcountersSector
 *
 * @author yrda7553
 */
class ArzfNetwork_Forms_FlowcountersSector  extends Oft_Form
{
    public function init()
    {
        $this->addElement('hidden', 'action', 'changePrivilege');
        
        $this->addElement(
            'select', 'allow',
            array('Label' => 'Accès autorisé')
        );
        
        $this->allow->setMultiOptions(
            array(
                1 => 'Oui',
                0 => 'Non',
                2 => 'Par défaut (Condition ignorée)'
            )
        );
        
        $this->addElement('submit', 'submit', array('Label' => 'Ajouter'));
        
        return $this;
    }
    
    public function setRoleResource($role, $resource)
    {
        $this->addElement('hidden', 'role', $role);
        $this->addElement('hidden', 'resource', $resource);
    }
}
?>
