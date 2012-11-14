<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of NOCrud
 *
 * @author yrda7553
 */
class Arzf_NOCrud extends Arzf_Crud_Controller_ActionAbstract
{
	
	/**
     * Effectue la gestion commune du formulaire
     * @param int $id
     * @return mixed (bool/Zend_Crud_Db_TableRow)
     */
    public function handleForm($id = null)
    {
        // Récupère le formulaire
        $form = $this->getForm();
        
        // Ajuste le formulaire pour l'id $id
        $form->adjustForm($id);

        if ($this->getRequest()->isPost()) {
            // Récupère les donnée de la requête HTTP
            $taintedData = $this->getRequest()->getPost();
            
            // en cas de création noid est null, on le met à 0 pour passer la validation
            $taintedData['network_objects_id'] = 0;
            
            $isValid = $form->isValid($taintedData);

            if (!$isValid) {
                return false;
            }

            // Valid => insert or update
            $data = $form->getValues();

            // Supprime les valeurs vides lors de l'insertion pour qu'elles soient gérées comme des NULL
            if ($id===null) {
                $columnsOptions = $form->getTable()->getColumnsOptions();
                foreach ($columnsOptions as $columnName => $columnsOptions) {
                    if (array_key_exists($columnName, $data) && $data[$columnName]==='') {
                            unset($data[$columnName]);
                    }
                }
            }
            
            $row = $this->getRow($id);
            
            if ($this->getRequest()->getActionName()== 'creer') {
                //création de l'objet parent
                $networkObject = new ArzfNetwork_Model_Table_NetworkObjects;
                $networkObjectRow = $networkObject->createRow();
                $networkObjectRow->save();
                $data['network_objects_id'] = $networkObjectRow->id;
            }
            
            foreach ($data as $k => $v) {
                $row->$k = $v;
            }

            $row->save();
        } else {
            // Récupère les données par défaut
            $row = $this->getRow($id);
            $isValid = $form->populate($row->toArray());
            return true;
        }

        return true;
    }

    /**
     * Suppression d'une entité
     */
    public function supprimerAction()
    {
        $id = $this->_getParam('id', null);
        if (!$id) {
            $this->_redirector('liste');
            return;
        }

        try {
            $networkObject = new ArzfNetwork_Model_Table_NetworkObjects();
            $where = $networkObject->getAdapter()->quoteInto('id = ?', $id);
            if ($networkObject->delete($where)) {
                $this->_addMessage('Supression réalisée');
            } else {
                $this->_addMessage(
                    'Suppression non réalisée', self::STATUS_ERR
                );
            }
        } catch (Exception $e) {
            $this->_addMessage("Suppression impossible", self::STATUS_ERR);
            $this->_addMessage(
                'Suppression impossible : ' . $e->getMessage(),
                self::STATUS_DEBUG
            );
        }

        $this->_redirector('liste');
    }

    protected function _renderForm($id=null)
    {
        /* @var $form Zend_Form */
        $view = $this->view;
        $form = $this->view->form;
        $this->_disableRendering(true, false);
        
        $title = is_null($id)?
            __("Création"):
            __("Modification");
        $this->_setSubTitle(is_null($this->_subTitle) ? $title : $this->_subTitle);

        $action  = $form->getAction();
        if (!$action) {
            $action = $view->smartUrl();
            if (!is_null($id)) {
                $action .= '/id/' . $id;
            }
        }
        

        echo '<form id="' . $form->getId()
            . '" action="' . $action
            . '" method="' . $form->getMethod()
            . '" enctype="' . $form->getEnctype() . '"'
            . '>';
        echo '<table class="datagrid ui-widget ui-widget-content">';
        echo '<tr>'
            . '<td class="ui-widget-header" style="text-align: center" colspan="3">'
            . $title
            . '</td></tr>';

        /* @var $elm Zend_Form_Element */
        echo '<tbody>';
        foreach ($form->getElements() as $elm) {
            //suppression du NOID des �l�ments de form
            if ($elm->getId() == 'network_objects_id') continue;
            $origDecorator = $elm->getDecorators();
//            $elm->setDecorators(array('ViewHelper', 'Errors', 'Description'));
            $elm->removeDecorator('HtmlTag');
            $elm->removeDecorator('Label');
            if ($elm instanceof Zend_Form_Element_Submit) {
                $buttons[] = $elm;
                continue;
            }
            
            echo '<tr>';
            echo '<th style="text-align: right; vertical-align: top" >';
            echo '<label for="' . $elm->getId() . '">';
            echo $elm->getLabel()
                . ($elm->isRequired()?' <b><font color="red">*</font></b>':'');
            echo ' : </label></th>';
            echo '<td>' . $elm->render($view) . '</td>';
            echo "</tr>\n";
        }

        echo '<tr class="buttons"><td colspan="2" style="text-align: right">';
        echo implode("&nbsp;", $buttons);
        echo '</td></tr>';

        echo '</tbody>';
        echo '</table>';
        echo '</form>';

        if ($id) {
            $retourLink = $view->jqSmartButton(
                'Retour', 'voir', null, null, array('id' => $id)
            );
        } else {
            $retourLink = $view->jqSmartButton(
                'Retour', 'liste', null, null, array()
            );
        }
        $view->actionBar()->append($retourLink);
    }
}

?>
