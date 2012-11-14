<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of ActionAbstract
 *
 * @author RD
 */
class Arzf_Crud_Controller_ActionAbstract extends Oft_Controller_Action
{
    /**
     * Titre de la page associée
     */
    protected $_title = null;
    
    /**
     * Sous titre de la page associée
     */
    protected $_subTitle = null;
    
    /**
     * Nom de la table associée
     * @var string
     */
    protected $_tableClass = null;

    /**
     * @var Oft_Db_Table
     */
    protected $_internalTable = null;

    /**
     * Règles de validation.
     * @var array
     */
    protected $_validatorRules = array(
        'id' => 'Int',
        'jqg-nd'  => array('Digits'),
        'jqg-search'  => array('Alpha'),
        'filters'  => 'Json',
        'jqg-page'  => array('Int'),
        'jqg-rows'  => array('Int'),
        'jqg-sort'  => array(
            array('Regex', '/^[a-z0-9_\-]+$/i'),
            'allowEmpty' => true
        ),
        'jqg-order'  => array(array('InArray', array('desc', 'asc'))),
    );

    /**
     * @todo Déplacer la logique de modification du menu dans createLeftMenu
     *
     * @param unknown_type $request
     * @param unknown_type $response
     * @param unknown_type $invokeArgs
     */
    public function __construct(
        Zend_Controller_Request_Abstract $request,
        Zend_Controller_Response_Abstract $response,
        array $invokeArgs = array())
    {
        parent::__construct($request, $response, $invokeArgs);

        if (!empty($this->_title)) {
            $this->_setTitle($this->_title);
        } else {
            $this->_setTitle('Gestion de la table ' . $this->_getTableName());
        }

        return;
        
        $view = $this->view;
        $controller = $request->getControllerName();
        $leftMenuItems = $view->leftMenu()->getItems();

        if (!isset($leftMenuItems['autoMenu'])
            || !isset($leftMenuItems['autoMenu']['submenu'])
            || !isset($leftMenuItems['autoMenu']['submenu'][$controller])
            ) {
            return;
        }

        if (!isset($leftMenuItems['autoMenu']['submenu'][$controller]['submenu'])) {
            $leftMenuItems['autoMenu']['submenu'][$controller]['submenu'] =
                array();
        }

        $actionItems = array(
            array(
                'name' => 'Liste',
                'link' => $view->smartUrl('liste'),
            ),
            array(
                'name' => 'Création',
                'link' => $view->smartUrl('creer'),
            ),
        );

        if ($request->getActionName()=='voir') {
            $actionItems[] = array(
                'name' => 'Supprimer',
                'link' => $view->smartUrl('supprimer'),
            );
            $actionItems[] = array(
                'name' => 'Editer',
            'link' => $view->smartUrl('editer'),
            );
        }

        $leftMenuItems['autoMenu']['submenu'][$controller]['submenu'] =
            array_merge(
                $leftMenuItems['autoMenu']['submenu'][$controller]['submenu'],
                $actionItems
            );

        $view->leftMenu()
            ->setItems($leftMenuItems['autoMenu'], 'autoMenu');
    }


    public function getValidatorRules()
    {
        $rules = $this->_validatorRules;
        $form = $this->getForm();
        $table = $this->getTable();
        $rules['id'] = $form
            ->getElement($table->getIdColumn())
            ->getValidators();
        return $rules;
    }

    /**
     * Renvoit le nom de la table
     */
    protected function _getTableName()
    {
        $table = $this->getTable();
        return $table->info(Zend_Db_Table::NAME);
    }

    /**
     * Renvoit le nom de la colonne de "nom" (colonne principale)
     */
    protected function _getNameColumn()
    {
        $table = $this->getTable();
        return $table->getNameColumn();
    }

    /**
     * Renvoit le nom de la colonne d'identifiant
     */
    protected function _getIdColumn()
    {
        $table = $this->getTable();
        return $table->getIdColumn();
    }

    /**
     * Renvoit la classe de table
     * @return string
     */
    public function getTableClass()
    {
        if (empty($this->_tableClass)) {
            throw new Oft_TechException(
                "L'attribut '_tableClass' doit être défini dans la classe '"
                . get_class($this)."'"
            );
        }

        if (!class_exists($this->_tableClass)) {
            throw new Oft_TechException(
                "La classe '{$this->_tableClass}' n'existe pas"
            );
        }
        return $this->_tableClass;
    }

    /**
     * @return Arzf_Crud_Db_Table
     */
    public function getTable()
    {
        if ($this->_internalTable===null) {
            $class = $this->getTableClass();
            $this->_internalTable = new $class;
        }
        return $this->_internalTable;
    }

    /**
     * @return array
     */
    public function getColumnsOptions()
    {
        $columnsOptions = $this->getTable()->getColumnsOptions();
        if (!count($columnsOptions)) {
            throw new Oft_TechException(
                "Crud : Les entêtes sont vides, reseignez"
                . " Arzf_Crud_Db_Table::\$_columnsOptions ou passez"
                . " Arzf_Crud_Db_Table::\$_autoColumnOptions à true"
            );
        }
        return $columnsOptions;
    }

    public function getFilterRules()
    {
        return array();
    }

    /**
     * @return Zend_Form
     */
    public function getForm()
    {
        if ($this->_form!==null) {
            return $this->_form;
        }

        $form = $this->getTable()->getForm();

        if (!$form instanceof Arzf_Crud_Form) {
           throw new Oft_Exception(
               "Le formulaire doit être une instance Arzf_Crud_Form"
           );
        }
        
        $this->_form = $form;
        return $this->_form;
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
            return true;
        }

        return true;
    }

    /**
     * Callback de transformation des enregistrement avant affichage
     * en mode liste.
     *
     * @param array $row
     * @param Zend_View $view
     */
    public function _rowDecoration(&$row, $view)
    {
        $columnsOptions = $this->getColumnsOptions();
        foreach ($columnsOptions as $columnName => $columnOption) {
            if (isset($columnOption['values'])
                && isset($columnOption['values'][$row[$columnName]])) {
                $row[$columnName] =
                    $columnOption['values'][$row[$columnName]];
            }
        }
    }

    /**
     * Retourne les données pour alimenter le formulaire.
     * Utilise dans l'ordre :
     *  - Données POST (NON SECURISE)
     *  - Données de la table
     *  - Valeurs par défaut (table)
     * @param int $id
     * @param array $data
     * @return Arzf_Crud_Db_TableRow
     */
    public function getRow($id = null, array $data = array())
    {
        $table = $this->getTable();

        if ($id===null) {
            // Defaults depuis la Arzf_Crud_Db_Table
            $row = $table->createRow($data);
        } else {
            // DB si id est fournit
            $row = $table->fetchById($id);
        }

        return $row;
    }

    /**
     * Action par défaut - redirige vers 'liste'
     */
    public function indexAction()
    {
        $this->_forward('liste');
    }

    /**
     * @return Zend_Db_Select
     */
    public function getListSelect()
    {
        return $this->getTable()->getListSelect();
    }

    public function getGridOptions()
    {
        return array(
            'link' => array(
                'action' => 'voir',
            ),
            'linkOn' => $this->_getNameColumn(),
            'page' => (int) $this->_getParam('jqg-page', 0),
            'callback' => array($this, '_rowDecoration'),
        );
    }

    /**
     * @return Zend_Paginator
     */
    public function getData()
    {
        $search = $this->_getParam('jqg-search', false);
        $filters = $this->_getParam('filters', null);
        $rows = $this->_getParam('jqg-rows', 10);
        $page = $this->_getParam('jqg-page', 1);
        $sidx = $this->_getParam('jqg-sort', null);
        $sord = $this->_getParam('jqg-order', 'desc');

        $app = $this->getApp();
        $db = $app->getDb();

        $sql = $this->getListSelect();

        if ($sidx) {
            $sql->order("$sidx $sord");
        }

        if ($search && $filters) {
            $headers = $this->getColumnsOptions();
            $filters = Zend_Json::decode($filters);
            foreach ($filters['rules'] as $rule) {
                $unsafeData = $rule['data'];
                
                if (!isset($headers[$rule['field']])) {
                    continue;
                } else {
                    // Traitement des chaines formattées par JQGrid
                    if (isset($headers[$rule['field']]['formatter'])) {
                        switch($headers[$rule['field']]['formatter']) {
                            case 'date':
                                $date = new Oft_Date($unsafeData);
                                $unsafeData = $date->toString(Oft_Date::SQL);
                                break;
                            case 'currency':
                                try {
                                    $unsafeData = Zend_Locale_Format::getNumber($unsafeData);
                                } catch (Exception $e) {
                                    ; // Valeur non touchée si elle ne correspond pas à la locale
                                }
                                break;
                        }
                    }
                }
                
                switch ($rule['op']) {
                    case 'bw':
                        $unsafeData .= '%';
                        $op = 'like';
                        break;
                    default:
                    case 'eq':
                        $op = '=';
                        break;
                }
                
                $sql->where(
                    $db->quoteIdentifier($rule['field']) . " $op ?",
                    $unsafeData
                );
            }
        }

        $paginator = Zend_Paginator::factory($sql);
        $paginator->setItemCountPerPage($rows);
        $paginator->setCurrentPageNumber($page);

        return $paginator;
    }

    public function getdataAction()
    {
        $this->_disableRendering();

        $header = $this->getColumnsOptions();
        $options = $this->getGridOptions();

        // Recherche de la colonne identifiant
        $idColumn = $this->getTable()->getIdColumn();

        // Vérification de la définition de la colonne 'id'
        if ($idColumn===null) {
            throw new Oft_TechException(
                "Pas de colonne d'identifiant définie"
            );
        }

        // Récupération des données
        $datas = $this->getData();

        // Tableau de résultat
        $results = array(
            'page' => $datas->getCurrentPageNumber(),
            'total' => count($datas),
            'records' => $datas->getTotalItemCount(),
            'rows' => array(),
        );

        // Formatage des données
        foreach ($datas as $data) {

            // Vérification des données
            if (!isset($data[$idColumn])) {
                throw new Oft_TechException(
                    "La colonne '$idColumn' n'existe pas dans les données"
                );
            }

            // Transformation si nécessaire
            if (isset($options['callback'])) {
                if (!is_callable($options['callback'])) {
                    throw new Oft_TechException(
                        'Callback invalide pour le helper ('
                        . get_class($this) . ')'
                    );
                }

                // Passage par référence de l'enregistrement
                call_user_func_array(
                    $options['callback'], array(&$data, $this->view)
                );
            }

            // Résultat
            $row = array(
                'id' => $data[$idColumn],
                'cell' => array(),
            );

            // Remplissage des données de la cellule
            foreach ($header as $col => $values) {
                if (isset($values['visible']) && !$values['visible']) {
                    continue;
                }

                if (!isset($data[$col])) {
                    throw new Oft_TechException(
                        "La colonne '$col' n'existe pas dans les données"
                    );
                }

                $row['cell'][] = $this->view->escape($data[$col]);
            }

            $results['rows'][] = $row;
        }

        echo Zend_Json::encode($results);
    }


    /**
     * Liste les entités
     */
    public function listeAction()
    {
        $buttons = array();
        $view = $this->view;
        $this->_setSubTitle(is_null($this->_subTitle) ? "Liste" : $this->_subTitle);

        $this->_disableRendering(true, false);

        $buttons[] = $view->jqSmartButton('Créer', 'creer');

        $jqGridParams = array(
            'url' => $view->smartUrl('getdata'),
            'sortname' => $this->getTable()->getIdColumn(),
            'caption'  => "Liste des éléments",
            'onSelectRow' => new Zend_Json_Expr(
                "function(ids) { document.location = '"
                . $view->smartUrl('voir') . "/id/' + ids }"
            ),
            'filter' => true,
        );

        $jqGridParams = array_merge(
            $this->getGridOptions(),
            $jqGridParams
        );
        echo $view->jqGrid(
            'list', $this->getColumnsOptions(), $jqGridParams
        );

        echo '<br /><div style="text-align: right">';
        echo implode("&nbsp;", $buttons);
        echo "</div>";
    }

    /**
     * Création d'une entité
     */
    public function creerAction()
    {
        try {
            $this->view->form = $this->getForm();
            if ($this->handleForm()!==false) {
                if ($this->getRequest()->isPost()) {
                    $this->_addMessage('La création a été réalisée');
                    $this->_redirector('liste');
                }
            } else {
                $this->_addMessage(
                    'Problème lors de la soumission',
                    self::STATUS_ERR
                );
            }
        } catch (Exception $e) {
            $this->_addMessage('Création impossible', self::STATUS_ERR);
            $this->_addMessage(
                'Création impossible : ' . $e->getMessage(),
                self::STATUS_DEBUG
            );
            $this->_redirector('liste');
        }
        $this->_renderForm();
    }

    /**
     * Edite une entité
     */
    public function editerAction()
    {
        $id = $this->_getParam('id', null);

        try {
            $this->view->form = $this->getForm();
            
            if ($this->handleForm($id)!==false) {
                if ($this->getRequest()->isPost()) {
                    $this->_addMessage('La modification a été réalisée');
                    $this->_redirector('liste');
                }
            } else {
                $this->_addMessage(
                    'Problème lors de la soumission', self::STATUS_ERR
                );
            }

        } catch (Exception $e) {
            $this->_addMessage('Edition impossible', self::STATUS_ERR);
            $this->_addMessage(
                'Edition impossible : ' . $e->getMessage(),
                self::STATUS_DEBUG
            );
        }

        $this->_renderForm($id);
    }

    /**
     * Affiche le détail d'une entité
     */
    public function voirAction()
    {
        $view = $this->view;
        $this->_disableRendering(true, false);

        $this->_setSubTitle(is_null($this->_subTitle) ? "Visualisation" : $this->_subTitle);

        $id = $this->_getParam('id', null);
        if (!$id) {
            $this->_redirector('liste');
            return;
        }

        $row = $this->getRow($id);

        $colsOpts = $this->getColumnsOptions();

        echo '<table class="datagrid ui-widget ui-widget-content"'
            . ' style="min-width: 50%">';
        echo '<thead>';
        echo '<tr>';
        echo '<th class="ui-widget-header" colspan="2">'
            . __('Visualisation')
            . '</th>';
        echo '</tr>';
        echo '</thead>';

        $this->_rowDecoration($row, $view);

        foreach ($colsOpts as $col => $opt) {
            if (!$opt['visible'] || $opt['password']) {
                continue;
            }

            echo '<tr>';
            echo "<th width=\"50%\" style=\"text-align:right\">"
                . __($opt['name'])
                . " : </th><td>"
                . $this->view->escape($row[$col])
                . "</td>";
            echo '</tr>';
        }

        echo '<tr class="buttons"><td colspan="2" style="text-align: right">';
        echo $view->jqSmartButton(
            'Editer', 'editer', null, null, array('id'=>$id)
        );
        echo '&nbsp;';
        echo $view->jqSmartButton(
            'Supprimer', 'supprimer', null, null, array('id'=>$id)
        );
        echo '</td></tr>';

        echo '</table>';

        $view->actionBar()->append(
            $view->jqSmartButton('Retour', 'liste', null, null, array())
        );
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
            $row = $this->getRow($id);
            if ($row->delete()) {
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

    /**
     * Rendu du formulaire de modification et d'insertion
     */
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
