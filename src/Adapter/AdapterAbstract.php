<?php

namespace Cityware\Datagrid\Adapter;

abstract class AdapterAbstract
{
    protected $_module;
    protected $_controller;
    protected $_arrayData = Array();
    protected $_datagridClass = Array();
    protected $_datagridColumnCount = 0;
    protected $_datagridName;
    protected $_datagridContent;

    protected static $EOL = PHP_EOL;

    public function getModule()
    {
        return $this->_module;
    }

    public function setModule($module)
    {
        $this->_module = $module;
    }

    public function getController()
    {
        return $this->_controller;
    }

    public function setController($controller)
    {
        $this->_controller = $controller;
    }

    /**
     * Retorna o conteúdo da datagrid
     * @return string
     */
    public function getDatagridContent()
    {
        return $this->_datagridContent;
    }

    /**
     * Retorna o array de dados
     * @return array
     */
    public function getArrayData()
    {
        return $this->_arrayData;
    }

    /**
     * Define o array de dados
     * @param  array                            $arrayData
     * @return \Cityware\Datagrid\Adapter\Table
     */
    public function setArrayData(array $arrayData = array())
    {
        $this->_arrayData = $arrayData;

        return $this;
    }

    /**
     * Retorna o nome da tabela
     * @return string
     */
    public function getDatagridName()
    {
        return $this->_datagridName;
    }

    /**
     * Define o nome da tabela
     * @param  string                           $gridName
     * @return \Cityware\Datagrid\Adapter\Table
     */
    public function setDatagridName($datagridName = 'gridDefault')
    {
        $this->_datagridName = $datagridName;

        return $this;
    }

    /**
     * Retorna o array de classes da tabela
     * @return array
     */
    public function getDatagridClass()
    {
        return $this->_datagridClass;
    }

    /**
     * Define um array de classes a serem utilizados na tabela
     * @param  array                            $tableClass
     * @return \Cityware\Datagrid\Adapter\Table
     */
    public function setDatagridClass(array $datagridClass = array())
    {
        $this->_datagridClass = $datagridClass;

        return $this;
    }

    /**
     * Função geradora do datagrid
     * @param  array                            $arrayData
     * @param  type                             $datagridName
     * @param  array                            $datagridClass
     * @return \Cityware\Datagrid\Adapter\Table
     */
    abstract public function gridBuilder(array $arrayData = null, $datagridName = null, array $datagridClass = array());

    /**
     * Função de retorno da datagrid
     * @return string
     */
    abstract public function render();

}
