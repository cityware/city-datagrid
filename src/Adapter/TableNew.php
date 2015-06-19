<?php

namespace Cityware\Datagrid\Adapter;

use Zend\I18n\Translator\Translator;
use Zend\Mvc\I18n\Translator as MvcTranslator;

/**
 * Classe adaptadora para criação do datagrid em tabela
 * @category   Cityware
 * @package    Cityware_Grid
 * @subpackage Adapter
 * @copyright  Copyright (c) 2011-2011 Cityware Technologies BRA Inc. (http://www.cityware.com.br)
 */
class TableNew {

    protected $arrayData = Array();
    protected $datagridClass = Array();
    protected $datagridName;
    protected $datagridContent;
    protected $translator;
    protected static $phpEOL = PHP_EOL;
    
    /**
     * Prepara as traduções do formulário
     * @return \Cityware\Form\Adapter\ZendAdapter
     */
    private function prepareTranslator() {
        
        //Create the translator
        $translator = new MvcTranslator(new Translator());
        $this->translator = $translator;

        return $this;
    }

    /**
     * Função geradora do datagrid
     * @param  array                            $arrayData
     * @param  type                             $datagridName
     * @param  array                            $datagridClass
     * @return \Cityware\Datagrid\Adapter\Table
     */
    public function gridBuilder(array $arrayData = null, $datagridName = null, array $datagridClass = array()) {
        if (!empty($arrayData)) {
            $this->arrayData = $arrayData;
        }

        if (!empty($datagridClass)) {
            $this->datagridClass = $datagridClass;
        }

        if (!empty($datagridName)) {
            $this->datagridName = $datagridName;
        }

        $class = '';
        if (!empty($this->datagridClass)) {
            $class = join(' ', $this->datagridClass);
        }
        
        $this->prepareTranslator();
        
        $this->datagridContent = '<form method="post" class="frmDatagrid datagridCTW">';
        $this->datagridContent .= '<table title="' . $this->datagridName . '" class="table table-striped table-hover table-bordered no-more-tables ' . $class . '">' . self::$phpEOL;
        $this->datagridContent .= $this->gridHeaderBuild();
        //$this->datagridContent .= $this->gridFooterBuild();
        //$this->datagridContent .= $this->gridBodyBuild();
        $this->datagridContent .= '</table>';
        $this->datagridContent .= '</form>';

        return $this;
    }

    /**
     * Função geradora do cabeçalho do datagrid
     * @return string
     */
    private function gridHeaderBuild() {
        $html = '<thead class="table-header">' . self::$phpEOL;

        $html .= $this->gridHeaderLabel();
        if ($this->searchEnable) {
            $html .= $this->gridHeaderSearch();
        }

        $html .= '</thead>' . self::$phpEOL;

        return $html;
    }

    private function gridHeaderSearch() {
        
        $html = '<tr class="no-mobile lineSearch">' . self::$phpEOL;
        if (!isset($this->arrayData['config']['disableckeckall']) or $this->arrayData['config']['disableckeckall'] != 'true') {
            $html .= '<th><input type="checkbox" class="checkAll"></th>' . self::$phpEOL;
            //$this->datagridColumnCount++;
        }

        foreach ($this->arrayData['header'] as $key => $value) {

            $responsiveHide = $class = '';
            if (isset($value['class']) and ! empty($value['class'])) {
                $class .= $value['class'];
            }

            if (isset($value['responsive']) and ! empty($value['responsive'])) {
                $responsiveHide .= 'data-hide="' . $value['responsive'] . '"';
            }

            if (isset($value['searchable']) and $value['searchable'] == 'true') {
                $html .= "<th {$responsiveHide} class='{$class}'>" . self::$phpEOL
                        . '<input type="text" name="' . $key . '" class="form-control">' . self::$phpEOL
                        . '</th>' . self::$phpEOL;
            } else {
                $html .= "<th {$responsiveHide} class='{$class}'>&nbsp;</th>" . self::$phpEOL;
            }
        }
        
        $html .= '<th>' . self::$phpEOL
                . '<button class="btn btn-success searchFrmButton" type="button">' . self::$phpEOL
                . '<i class="fa fa-search"></i> Filtrar' . self::$phpEOL
                . '</button>' . self::$phpEOL
                . '</th>' . self::$phpEOL;
        $html .= '</tr>' . self::$phpEOL;

        return $html;
    }

    private function gridHeaderLabel() {
        
        $sortingColName = $sortingIcon = $html = $html1 = $html2 = $html3 = null;
        
        $html .= '<tr class="no-mobile lineLabel">' . self::$phpEOL;
        

        $this->searchEnable = false;

        foreach ($this->arrayData['header'] as $key => $value) {

            $responsiveHide = $class = '';
            if (isset($value['class']) and ! empty($value['class'])) {
                $class .= $value['class'];
            }

            if (isset($value['sortable']) and $value['sortable'] == 'true') {
                $class .= ' table-sorting ';
                $sortingIcon = ' <i class="order-both"></i>';
                $sortingColName = " data-sortingcol='{$key}'";
            }

            if (isset($value['searchable']) and $value['searchable'] == 'true') {
                $this->searchEnable = true;
            }

            if (isset($value['type'])) {
                switch ($value['type']) {
                    case 'datetime':
                        $class .= ' width-datetime ';
                        break;
                    case 'date':
                        $class .= ' width-date ';
                        break;
                    case 'status':
                        $class .= ' width-status ';
                        break;
                    case 'primarykey':
                        $class .= ' width-primary ';
                        break;
                    case 'integer':
                    case 'int':
                    case 'float':
                    case 'double':
                    case 'decimal':
                        $class .= ' width-number ';
                        break;
                    case 'money':
                        $class .= ' width-money ';
                        break;
                    case 'char':
                        $class .= ' width-shortstring ';
                        break;
                    case 'vencimento':
                        $class .= ' width-microstring ';
                        break;
                    case 'varchar':
                        $class .= ' width-mediumstring ';
                        break;
                    default:
                        break;
                }
            }

            if (isset($value['responsive']) and ! empty($value['responsive'])) {
                $responsiveHide .= 'data-hide="' . $value['responsive'] . '"';
            }
            
            $html2 .= "<th {$responsiveHide} class='{$class}' {$sortingColName}>" . $this->translator->translate($value['value']) . $sortingIcon .'</th>' . self::$phpEOL;
        }
        
        if (!isset($this->arrayData['config']['disableckeckall']) or $this->arrayData['config']['disableckeckall'] != 'true') {
            if($this->searchEnable){
                $html1 .= '<th class="width-select">&nbsp;</th>' . self::$phpEOL;
            } else {
                $html1 .= '<th class="width-select"><input type="checkbox" class="checkAll form-control"></th>' . self::$phpEOL;
            }
        }

        if (!isset($this->arrayData['config']['disablebuttons']) or $this->arrayData['config']['disablebuttons'] != 'true') {
            $html3 .= '<th class="width-action filter-false">' . 'Ação' . '</th>';
        }
        
        
        $html .= $html1.$html2.$html3;
                
        $html .= '</tr>' . self::$phpEOL;

        return $html;
    }

    /**
     * Função geradora do corpo do datagrid
     * @return string
     */
    private function gridBodyBuild() {
        $html = '<tbody>' . self::$phpEOL;
        $html .= '</tbody>' . self::$phpEOL;

        return $html;
    }

    /**
     * Função geradora do rodapé do datagrid
     * @return string
     */
    private function gridFooterBuild() {
        $html = '<tfoot class="table-footer">' . self::$phpEOL;
        $html .= '<tr>' . self::$phpEOL;

        if (!isset($this->arrayData['config']['disableckeckall']) or $this->arrayData['config']['disableckeckall'] != 'true') {
            $html .= '<th class="nosorter width-select">&nbsp;</th>' . self::$phpEOL;
        }

        foreach ($this->arrayData['header'] as $value) {

            $class = '';
            if (isset($value['class']) and ! empty($value['class'])) {
                $class .= $value['class'];
            }

            if (isset($value['sortable']) and $value['sortable'] == 'false') {
                $class .= ' nosorter ';
            }

            if (isset($value['searchable']) and $value['searchable'] == 'false') {
                $class .= ' filter-false ';
            }

            if (isset($value['type'])) {
                switch ($value['type']) {
                    case 'datetime':
                        $class .= ' width-datetime ';
                        break;
                    case 'date':
                        $class .= ' width-date ';
                        break;
                    case 'status':
                        $class .= ' width-status ';
                        break;
                    case 'primarykey':
                        $class .= ' width-primary ';
                        break;
                    case 'integer':
                    case 'int':
                    case 'float':
                    case 'double':
                    case 'decimal':
                        $class .= ' width-number ';
                        break;
                    case 'money':
                        $class .= ' width-money ';
                        break;
                    case 'char':
                        $class .= ' width-shortstring ';
                        break;
                    case 'varchar':
                        $class .= ' width-mediumstring ';
                        break;
                    default:
                        break;
                }
            }

            $html .= "<th class='{$class}'>" . $value['value'] . '</th>' . self::$phpEOL;
        }
        if (!isset($this->arrayData['config']['disablebuttons']) or $this->arrayData['config']['disablebuttons'] != 'true') {
            $html .= '<th class="nosorter width-action">' . 'Ação' . '</th>';
        }
        $html .= '</tr>' . self::$phpEOL;

        $html .= '</tfoot>' . self::$phpEOL;

        return $html;
    }

    /**
     * Função de retorno da datagrid
     * @return string
     */
    public function render() {
        return $this->datagridContent;
    }

}
