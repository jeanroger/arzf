<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of Table
 *
 * @author RD
 */
class Arzf_Crud_Db_Table extends Oft_Db_Table
{
    /**
     * Options des colonnes
     * @var array
     */
    protected $_columnsOptions = array();
    
    /**
     * Colonnes "virtuelles" (ie n'existant pas dans la table)
     * @var array
     */
    protected $_virtualColumns = array();
    
    /**
     * Indique si oui ou non les options ont été normalisées
     * @var bool
     */
    protected $_columnsOptionsNormalized = false;
    
    /**
     * Valeurs par défaut pour les informations de colones
     * @var array
     */
    protected static $_defaultColumnData = array(
        // Affichée ou pas
        'visible'   => true,
        // Permet la recherche ou pas (inutilisé)
        'search'    => true,
        // Champs de type 'mot de passe'
        'password'  => false,
        // Permet ou pas la modification du champs
        'canModify' => true,
        // Colonne principale (seulement pour les colonnes virtuelles)
        'master'    => false,
    );
    
    /**
     * Formulaire
     * @var Arzf_Crud_Form
     */
    protected $_form = null;
    
    /**
     * Classe d'enregistrement spécifique
     * @var string
     */
    protected $_rowClass = 'Arzf_Crud_Db_TableRow';
    
    /**
     * Définition automatique des colonnes oui ou non.
     *
     * @var bool
     */
    protected $_autoColumnOptions = false;
    
    /**
     * Retourne les colones virtuelles
     * @return array
     */
    public function getVirtualColumns()
    {
        return $this->_virtualColumns;
    }
    
    /**
     * Récupère le flag _autoColumnOptions
     * @return bool
     */
    public function getAutoColumnOptions()
    {
        return $this->_autoColumnOptions;
    }
    
    /**
     * Renvoie les options de colonnes normalisées
     * @return array
     */
    public function getColumnsOptions()
    {
        if ($this->_columnsOptionsNormalized) {
            return $this->_columnsOptions;
        }

        // Définition automatique des colonnes si demandé explicitement
        if ($this->getAutoColumnOptions()) {
            $metadatas = $this->info(self::METADATA);
            foreach ($metadatas as $column => $metadata) {
                if (!isset($this->_columnsOptions[$metadata['COLUMN_NAME']])) {
                    $this->_columnsOptions[$metadata['COLUMN_NAME']] =
                        $metadata['COLUMN_NAME'];
                }
            }
        }
        
        // TODO : Validation de _virtualColumns

        // Normalisation de _columnsOptions
        foreach ($this->_columnsOptions as $column => $columnData) {
            // Normalisation du type de donnée de la colonne
            if (is_string($columnData)) {
                $columnData = array('name' => $columnData);
            } else if (!is_array($columnData)) {
                throw new Zend_Exception('Donnée invalide pour la colonne \''
                    . $column . '\' (' . get_class($this) . ')');
            }
                
            // Utilisation des valeurs par défaut
            $this->_columnsOptions[$column] =
                array_merge(self::$_defaultColumnData, $columnData);
                
            // Nom de la colonne
            if (!isset($columnData['name'])) {
                throw new Zend_Exception('Pas de nom de colonne fourni pour \''
                    . $column . '\' (' . get_class($this) . ')');
            }
                
            // Traitement des valeurs par défaut
            //  Gère les entrées de type :
            //    - string : value1, value2, value3
            //    - array : array( 1 => 'Actif', 2 => 'Inactif' )
            if (!isset($columnData['values'])) {
                $this->_columnsOptions[$column]['values'] = array();
            } else if (is_string($columnData['values'])) {
                $this->_columnsOptions[$column]['values'] =
                    preg_split('/ *[,;] */', $columnData['values']);
            } else if (!is_array($columnData['values'])) {
                throw new Oft_Exception("Type de donnée"
                    . " invalide pour 'values'");
            }
                
            // Si vide - on supprime
            if (!count($this->_columnsOptions[$column]['values'])) {
                unset($this->_columnsOptions[$column]['values']);
            }
            
            // Validation de la colonne "master"
            if ($this->_columnsOptions[$column]['master']!==false) {
                $master = $this->_columnsOptions[$column]['master'];
                
                // Est elle déclarée virtuelle ?
                if (array_search($column, $this->_virtualColumns)===false) {
                    throw new Oft_Exception(
                        "Champs '$column' n'est pas décléré comme virtuel."
                        . " Utilisation de 'master' impossible."
                    );
                }
                
                // La colonne master est elle virtuelle ?
                if (array_search($master, $this->_virtualColumns)!==false) {
                    throw new Oft_Exception("Le champs '$master' ne peut être "
                        . "master de '$column' car il est virtuel.");
                }
            }
        }

        $this->_columnsOptionsNormalized = true;
        
        return $this->_columnsOptions;
    }
    
    /**
     * Renvoi un formulaire adapté à la table
     * @return Arzf_Crud_Form
     */
    public function getForm()
    {
        if ($this->_form===null) {
            $form = new Arzf_Crud_Form(array('table' => $this));
            $this->initForm($form);
            
            // Ajout de l'élément Submit
            $submit = new Zend_Form_Element_Submit(
                'submit', array('label' => 'Soumettre')
            );
            $submit->removeDecorator('DtDdWrapper');
            $form->addElement($submit);
            $this->_form = $form;
        }
        return $this->_form;
    }
    
    /**
     * Ajuste le formulaire - Parametrable par l'utilisateur
     * @param int $id
     * @param Arzf_Crud_Form $form
     */
    public function initForm($form)
    {
    }

    /**
     * Extraction des données virtuelles
     * @param array $data
     */
    public function mapData($data)
    {
        return array();
    }
    
    /**
     * Création des données réelles à partir des données virtuelles
     * @param array $data
     */
    public function unmapData($virtualData)
    {
        return array();
    }

    protected $_notypes = array ('hosts' => 'name', 'sectors' => 'name', 'flowcounters' => 'name');
    
    public function findNoname()
    {
    	$nonames = array();
        foreach ($this->_notypes as $noTable => $nameField)
        {
            $selectValues = Oft_Db_Table::getTableSelectValues($noTable, 'network_objects_id', $nameField);
            foreach ($selectValues as $id => $name) {
            	$nonames[$id] = $name;
            }
        }
        return $nonames;
    }
}

?>
