<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of Form
 *
 * @author RD
 */
class Arzf_Crud_Form extends Arzf_Form
{
    /**
     * @var Zend_Db_Table
     */
    protected $_table = null;
    
    /**
     * Valeurs par défauts fournies à la méthode populate ou setDefaults
     * @var array
     */
    protected $_defaults = array();
    
    public function __construct($options = null)
    {
        if (!isset($options['table'])) {
            throw new Oft_Exception(
                "Arzf_Form_Crud nécessite une option 'table'"
            );
        }
        
        $this->setTable($options['table']);
        unset($options['table']);
        
        parent::__construct($options);

        $this->_initFromTable();
    }
    
    
    /**
     * Initialisation du formulaire par rapport à la table.
     */
    protected function _initFromTable()
    {
        $this->setMethod('post');
        $this->setAttrib('id', get_class($this->getTable()));
        
        $table = $this->getTable();
        $columnsOptions = $table->getColumnsOptions();
        $metadatas = $table->info(Zend_Db_Table::METADATA);
        
        // Pour chaque colonne de la table
        foreach ($columnsOptions as $columnName => $columnOptions) {
            // Ignore la colonne si elle n'est pas décrite
            if (!isset($metadatas[$columnName])) {
                continue;
            }
            $metadata = $metadatas[$columnName];
            
            // Ajoute l'élément
            $this->_addFormElement(
                $columnName, $metadata, $columnOptions
            );
                        
            // Password non obligatoire en mode édition
            if ($columnOptions['password']) {
                $this->getElement($columnName)->setRequired(false);
                $this->getElement($columnName . '_Repeat')->setRequired(false);
            }
            
            // Si le champs n'est pas modifiable
            if (!$columnOptions['canModify']) {
                $this->getElement($columnName)->setRequired(false);
                $this->getElement($columnName)
                    ->setAttrib('disabled', 'disabled');
            }
        }
    }
    
    /**
     * Ajoute un element au formulaire.
     *
     * Utilise :
     *     - Les metadonnées de la table
     *     - Les options de colonnes (@see $_columnDatas)
     *
     * @param string $column Nom de la colonne
     * @param array $metadata Metadonnées de la colonne
     */
    protected function _addFormElement(
        $column, array $metadata, array $columnData)
    {
        if ($column instanceof Zend_Form_Element) {
            return $this->addElement($column);
        }
        
        $elementType    = 'Text';
        $elementOptions = array(
           'name'    => $column,
           'label'   => $columnData['name'],
//         'description'   => '',
           'allowEmpty'   => true,
           'filters' => array('StringTrim', 'StripTags'),
           'validators' => array(),
           'required' => false,
           'value' => '',
           'class' => 'ui-widget'
        );
        
        switch ($metadata['DATA_TYPE']) {
            case 'tinyint':
            case 'bigint':
            case 'int':
                if ($metadata['UNSIGNED']) {
                    $elementOptions['validators'][] =
                        array('Int', false, array(0));
                } else {
                    $elementOptions['validators'][] = 'Int';
                }
                break;
            case 'date':
                $elementType = 'Date';
                break;
        }
            
        // Valeur par défaut
        if ($metadata['DEFAULT']!==null) {
            $elementOptions['value'] = $metadata['DEFAULT'];
        }
        
        // Null accepté ?
        if (!$metadata['NULLABLE']) {
            $elementOptions['allowEmpty'] = false;
            $elementOptions['required'] = true;
        }

        // La clef primaire autoincrémentée n'est jamais editable
        if ($metadata['PRIMARY'] && $metadata['IDENTITY']) {
            $elementOptions['attribs']['disabled'] = 'disabled';
        }
        
        // Valeurs par défaut
        if (isset($columnData['values'])) {
            $elementType = 'Select';
            $elementOptions['validators'][] =
                array(
                    'InArray', false,
                    array(array_keys($columnData['values']))
                );
            $elementOptions['multiOptions'] = $columnData['values'];
        }
        
        if ($columnData['password']) {
            $elementType = 'Password';
            $this->addElement($elementType, $column, $elementOptions);
            
            $elementOptions['label'] .= ' pour validation';
            $elementOptions['name']   = $column . '_Repeat';
            $this->addElement(
                $elementType, $column . '_Repeat', $elementOptions
            );
            
            $this->getElement($column)
                ->addValidator(
                    new Oft_Validate_Password($column, $column . '_Repeat')
                );
            
            return; // Evite l'appel par défaut
        }
        
        return $this->addElement($elementType, $column, $elementOptions);
    }
    

    /**
     * Ajuste le formulaire en fonction de son contexte d'utilisation (insertion ou modification)
     *
     * @param $id Identifiant
     */
    public function adjustForm($id = null)
    {
        $newItem = $id===null;
        
        $columnsOptions = $this->getTable()->getColumnsOptions();
        
        $table = $this->getTable();
        
        foreach ($columnsOptions as $columnName => $options) {
            // Password non obligatoire en mode édition
            if ($columnsOptions[$columnName]['password']) {
                if (!$newItem) {
                    $this->getElement($columnName)->setRequired(false);
                    $this->getElement($columnName . '_Repeat')
                        ->setRequired(false);
                } else {
                    $this->getElement($columnName)->setRequired(true);
                    $this->getElement($columnName . '_Repeat')
                        ->setRequired(true);
                }
            }
            
            // Si le champs n'est pas modifiable
            if (!$newItem && !$columnsOptions[$columnName]['canModify']) {
                $this->getElement($columnName)->setRequired(false);
                $this->getElement($columnName)
                    ->setAttrib('disabled', 'disabled');
            }
        }
        
        // La primary key n'est pas requise en cas
        // de création ou de modification
        $table = $this->getTable();
        $primaryKeys = $table->info(Zend_Db_Table::PRIMARY);
        $metadatas = $table->info(Zend_Db_Table::METADATA);
        foreach ($primaryKeys as $primaryKey) {
            if ($metadatas[$primaryKey]['IDENTITY']) {
                $this->removeElement($primaryKey);
            } else if (!$newItem) {
                $this->removeElement($primaryKey);
            }
            break; // Clef primaires multiples non gérées
        }
    }
    
    /**
     * Défini la table utilisée pour construire le formulaire.
     *
     * @param string $table
     * @throws Oft_Exception
     */
    public function setTable($table)
    {
        if (!is_subclass_of($table, 'Oft_Db_Table')) {
            throw new Oft_Exception(
                "Oft_Form_Crud nécessite une table de type 'Oft_Db_Table'"
            );
        }
        
        if (is_string($table)) {
            $table = new $table;
        }
        
        $this->_table = $table;
    }
    
    /**
     * Renvoi la table utilisée pour construire le formulaire.
     * @return Oft_Db_Table
     */
    public function getTable()
    {
        return $this->_table;
    }
    
    
    /**
     * Récupère les valeurs par défaut pour l'appel de la
     * méthode isValid sans paramètres.
     *
     * @see Zend_Form::setDefaults()
     */
    public function setDefaults(array $values)
    {
        $this->_defaults = $values;
        parent::setDefaults($values);
    }
    
    /**
     * Surcharge de la méthode isValid pour le cas ou
     * aucune donnée n'est fournie.
     *
     * @see Zend_Form::isValid()
     */
    public function isValid($data)
    {
        if ($data===null) {
            $data = $this->_defaults;
        }
        return parent::isValid($data);
    }
    
    /**
     * Suppression des valeurs inutiles.
     *
     * @see Zend_Form::getValues()
     * @return array
     */
    public function getValues($suppressArrayNotation = false)
    {
        $values = parent::getValues($suppressArrayNotation);
        
        $table = $this->getTable();
        $columnsOptions = $table->getColumnsOptions();
        
        // Pour chaque colonne de la table
        foreach ($columnsOptions as $columnName => $columnsOptions) {
            // Suppression de la valeur repeat pour le password
            if ($columnsOptions['password']) {
                unset($values[$columnName . '_Repeat']);
            }
        }
        return $values;
    }
}

?>
