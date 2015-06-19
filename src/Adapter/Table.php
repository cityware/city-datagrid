<?php

namespace Cityware\Datagrid\Adapter;

/**
 * Classe adaptadora para criação do datagrid em tabela
 * @category   Cityware
 * @package    Cityware_Grid
 * @subpackage Adapter
 * @copyright  Copyright (c) 2011-2011 Cityware Technologies BRA Inc. (http://www.cityware.com.br)
 */
class Table extends AdapterAbstract implements AdapterInterface
{
    /**
     * Função geradora do datagrid
     * @param  array                            $arrayData
     * @param  type                             $datagridName
     * @param  array                            $datagridClass
     * @return \Cityware\Datagrid\Adapter\Table
     */
    public function gridBuilder(array $arrayData = null, $datagridName = null, array $datagridClass = array())
    {
        if (!empty($arrayData)) {
            $this->_arrayData = $arrayData;
        }

        if (!empty($datagridClass)) {
            $this->_datagridClass = $datagridClass;
        }

        if (!empty($datagridName)) {
            $this->_datagridName = $datagridName;
        }

        $class = '';
        if (!empty($this->_datagridClass)) {
            $class = join(' ', $this->_datagridClass);
        }

        $this->_datagridContent = '<table id="tableDatagrid" title="' . $this->_datagridName . '" class="tablesorter footable ' . $class . '">' . self::$EOL;
        $this->_datagridContent .= $this->gridHeaderBuild();
        $this->_datagridContent .= $this->gridFooterBuild();
        $this->_datagridContent .= $this->gridBodyBuild();
        $this->_datagridContent .= '</table>';

        return $this;
    }

    /**
     * Função geradora do cabeçalho do datagrid
     * @return string
     */
    private function gridHeaderBuild()
    {
        $html = '<thead class="table-header">' . self::$EOL;
        $html .= '<tr class="no-mobile">' . self::$EOL;
        if (!isset($this->_arrayData['config']['disableckeckall']) or $this->_arrayData['config']['disableckeckall'] != 'true') {
            $html .= '<th class="nosorter selectAll width-select filter-false">&nbsp;</th>' . self::$EOL;
            $this->_datagridColumnCount++;
        }

        foreach ($this->_arrayData['header'] as $value) {

            $responsiveHide = $class = '';
            if (isset($value['class']) and !empty($value['class'])) {
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

            if (isset($value['responsive']) and !empty($value['responsive'])) {
                $responsiveHide .= 'data-hide="'.$value['responsive'].'"';
            }

            $html .= "<th {$responsiveHide} class='{$class}'>" . $value['value'] . '</th>' . self::$EOL;
            $this->_datagridColumnCount++;
        }

        if (!isset($this->_arrayData['config']['disablebuttons']) or $this->_arrayData['config']['disablebuttons'] != 'true') {
            $html .= '<th class="nosorter width-action filter-false">' . 'Ação' . '</th>';
            $this->_datagridColumnCount++;
        }
        $html .= '</tr>' . self::$EOL . '</thead>' . self::$EOL;

        return $html;
    }

    /**
     * Função geradora do corpo do datagrid
     * @return string
     */
    private function gridBodyBuild()
    {
        $html = '<tbody>' . self::$EOL;
        $html .= '</tbody>' . self::$EOL;

        return $html;
    }

    /**
     * Função geradora do rodapé do datagrid
     * @return string
     */
    private function gridFooterBuild()
    {
        $html = '<tfoot class="table-header">' . self::$EOL;
        $html .= '<tr>' . self::$EOL;

        if (!isset($this->_arrayData['config']['disableckeckall']) or $this->_arrayData['config']['disableckeckall'] != 'true') {
            $html .= '<th class="nosorter width-select">&nbsp;</th>' . self::$EOL;
        }

        foreach ($this->_arrayData['header'] as $value) {

            $class = '';
            if (isset($value['class']) and !empty($value['class'])) {
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

            $html .= "<th class='{$class}'>" . $value['value'] . '</th>' . self::$EOL;
        }
        if (!isset($this->_arrayData['config']['disablebuttons']) or $this->_arrayData['config']['disablebuttons'] != 'true') {
            $html .= '<th class="nosorter width-action">' . 'Ação' . '</th>';
        }
        $html .= '</tr>' . self::$EOL;

        $html .= '<tr><th colspan="' . $this->_datagridColumnCount . '" class="pager form-horizontal text-left">
                            <button type="button" class="btn first"><i class="glyphicon glyphicon-step-backward"></i></button>
                            <button type="button" class="btn prev"><i class="glyphicon glyphicon-arrow-left"></i></button>
                            <span class="pagedisplay"></span> <!-- this can be any element, including an input -->
                            <button type="button" class="btn next"><i class="glyphicon glyphicon-arrow-right"></i></button>
                            <button type="button" class="btn last"><i class="glyphicon glyphicon-step-forward"></i></button>
                            <select class="pagesize input-sm" title="Select page size">
                                <option selected="selected" value="10">10</option>
                                <option value="20">20</option>
                                <option value="30">30</option>
                                <option value="40">40</option>
                                <option value="50">50</option>
                                <option value="100">100</option>
                            </select>
                            <select class="pagenum input-sm" title="Select page number"></select>
                        </th>
                    </tr>' . self::$EOL;

        $html .= '</tfoot>' . self::$EOL;

        return $html;
    }

    /**
     * Função de retorno da datagrid
     * @return string
     */
    public function render()
    {
        return $this->_datagridContent;
    }

}
