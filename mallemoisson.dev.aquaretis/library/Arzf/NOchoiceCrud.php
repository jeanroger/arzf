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
class Arzf_NOchoiceCrud extends Arzf_Crud_Controller_ActionAbstract
{
	public function populatenoselectAction()
    {
        $params = $this->getRequest()->getParams();
        if ($this->isAjax()) {
            $this->_disableRendering();
            $networkObjectArray = Oft_Db_Table::getTableSelectValues($params['objtype'], 'network_objects_id', 'name');
            $items = array();
            foreach ( $networkObjectArray as $key => $value) {
                $item = array('id'=>$key,'name'=>$value);
                $items[] = $item;
            }
            echo Zend_Json::encode($items);
        }
    }

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
            //suppression de l'objtype qui ne fait pas partie du form automatique
            unset($taintedData['objtype']);
            
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
            foreach ($data as $k => $v) {
                $row->$k = $v;
            }

            $row->save();
        } else {
            // Récupère les données par défaut
            $row = $this->getRow($id);
            $isValid = $form->populate($row->toArray());

            if ($this->getRequest()->getActionName()== 'creer') {
                $onchange = '$.getJSON("'.$this->view->smartUrl('populatenoselect').'/objtype/" + $(this).val(), function(json){
                        var options = "";
                        for (var i = 0; i < json.length; i++) {
                            options += "<option label=\"" + json[i].name + "\" value=\"" + json[i].id + "\">" + json[i].name + "</option>";
                        };
                        $("select#network_objects_id").html(options);
                    });';
                $form->addElement('select', 'objtype', array(
                    'label' => 'Type d\'objet',
                    //'order' => -1,
                    'onchange' => $onchange,
                    'MultiOptions' => array(
                        'nochoice' => '---', 
                        'hosts' => 'hosts', 
                        'sectors' => 'sectors', 
                        'flowcounters' => 'flowcounters'),
                ));
                $form->getElement('network_objects_id')->setMultiOptions(array('---'));
            }

            return true;
        }

        return true;
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
        
        ///prise en charge du select objtype
        if ($this->getRequest()->getActionName()== 'creer') {
            $elm = $form->getElement('objtype');
            $origDecorator = $elm->getDecorators();
            $elm->removeDecorator('HtmlTag');
            $elm->removeDecorator('Label');
            echo '<tr>';
            echo '<th style="text-align: right; vertical-align: top" >';
            echo '<label for="' . $elm->getId() . '">';
            echo $elm->getLabel()
                . ($elm->isRequired()?' <b><font color="red">*</font></b>':'');
            echo ' : </label></th>';
            echo '<td>' . $elm->render($view) . '</td>';
            echo "</tr>\n";
        }
        
        foreach ($form->getElements() as $elm) {
            //passer le select objtype
            if ($elm->getId() == 'objtype') continue;
            
            $origDecorator = $elm->getDecorators();
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
