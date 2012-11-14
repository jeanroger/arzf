<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of TableRow
 *
 * @author RD
 */
class Arzf_Crud_Db_TableRow extends Zend_Db_Table_Row
{
    protected $_virtualData = array();

    public function __construct(array $config = array())
    {
        parent::__construct($config);
        $this->_mapData();
    }
    
    /**
     * Surcharge de la définition d'une valeur de colonne
     * pour la gestion des colonnes virtuelles.
     *
     * @param string $columnName
     * @param string $value
     */
    public function __set($columnName, $value)
    {
        if (!array_key_exists($columnName, $this->_virtualData)) {
            
            // Une valeur à null reste à null
            if ($value!==null) {
                $metadata = $this->getTable()->info(Zend_Db_Table::METADATA);
                if ($metadata[$columnName]['DATA_TYPE']=='date') {
                    $date = new Oft_Date();
                    $date->setDate($value);
                    $value = $date->toString(Oft_Date::SQL);
                }
            }
            
            // Ne modifie la colonne que si elle est différente de la valeur
            // initiale
            if ($value!=parent::__get($columnName)) {
               parent::__set($columnName, $value);
            }
        } else {
            $this->_virtualData[$columnName] = $value;
            
            $options = $this->getTable()->getColumnsOptions();
            $this->_modifiedFields[$options[$columnName]['master']] = true;
        }
    }
    
    
    /**
     * Surcharge de la récupération d'une valeur de colonne pour
     * la gestion des colonnes virtuelles.
     *
     * @see Zend_Db_Table_Row_Abstract::__get()
     */
    public function __get($columnName)
    {
        if (!isset($this->_virtualData[$columnName])) {
           return parent::__get($columnName);
        } else {
            return $this->_virtualData[$columnName];
        }
    }
    
    
    /**
     * @see Zend_Db_Table_Row_Abstract::__isset()
     */
    public function __isset($columnName)
    {
        if (isset($this->_virtualData[$columnName])) {
            return true;
        }
        return parent::__isset($columnName);
    }
    
    /**
     * Retourne les données réélles et virtuelles.
     *
     * @see Zend_Db_Table_Row_Abstract::toArray()
     */
    public function toArray()
    {
        return array_merge($this->_data, $this->_virtualData);
    }
    
    /**
     * Transforme les données réélles en données virtuelles
     */
    protected function _mapData()
    {
        $virtualData = $this->getTable()->mapData($this->_data);
        $virtualColumns = $this->getTable()->getVirtualColumns();
        
        $this->_virtualData = array();
        foreach ($virtualColumns as $vCol) {
            if (!array_key_exists($vCol, $virtualData)) {
                throw new Oft_Exception("Colonne virtuelle"
                    . " '$vCol' non définie");
            }
            $this->_virtualData[$vCol] = $virtualData[$vCol];
            unset($virtualData[$vCol]);
        }
        
        if (count($virtualData)) {
            throw new Oft_Exception(
                "Données virtuelles non définies pour les colonnes : "
                . implode(', ', array_keys($virtualData))
                );
        }
        
        return;
    }
    
    /**
     * Transforme les données virtuelles en données réélles
     */
    protected function _unmapData()
    {
        $data = $this->_data;
        $newData = $this->getTable()->unmapData($this->_virtualData);
        $this->_data = array_merge(
            $this->_data,
            $this->getTable()->unmapData($this->_virtualData)
        );
        return;
    }
    
    /**
     * @return Arzf_Crud_Db_Table
     * @see Zend/Db/Table/Row/Zend_Db_Table_Row_Abstract::getTable()
     */
    public function getTable()
    {
        $table = parent::getTable();
        if (!$table instanceof Arzf_Crud_Db_Table) {
            throw new Oft_Exception(
                "Seules les tables de type"
                . " Arzf_Crud_Db_Table peuvent être manipulées"
                . " par Arzf_Db_TableRowCrud"
            );
        }
        return $table;
    }
    
    /**
     * Action avant les insertions.
     *
     * @see Zend/Db/Table/Row/Zend_Db_Table_Row_Abstract::_insert()
     */
    protected function _insert()
    {
        $this->_unmapData();
    }
    
    /**
     * Action avant les modifications.
     *
     * @see Zend/Db/Table/Row/Zend_Db_Table_Row_Abstract::_update()
     */
    protected function _update()
    {
        $this->_unmapData();
    }
     
    /**
     * Action après updaten, insert et refresh.
     *
     * @see Zend/Db/Table/Row/Zend_Db_Table_Row_Abstract::_refresh()
     */
    protected function _refresh()
    {
        parent::_refresh();
        $this->_mapData();
    }
}

?>
