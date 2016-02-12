<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Cityware\Datagrid\Models;

use Zend\Config\Factory AS ZendConfigFile;
use Zend\Session\Container as SessionContainer;

/**
 * Description of Datagrid
 *
 * @author fabricio.xavier
 */
class DatagridDb {

    private $request = null, $translate = null, $sessionAdapter;

    public function __construct($requestParams, $translate) {
        $this->request = $requestParams;
        $this->translate = $translate;

        $sessionNamespace = new SessionContainer('globalRoute');
        $sessionNamespace->setExpirationSeconds(3600);
        $this->sessionAdapter = $sessionNamespace;
    }

    /**
     * Função de preparação de Limit e Offset da Datagrid
     * @return array
     */
    public function prepareLimitOffset() {
        $returnLimitOffset = Array();
        $returnLimitOffset['limit'] = (isset($this->request['itensPage']) and ! empty($this->request['itensPage'])) ? $this->request['itensPage'] : 10;
        $returnLimitOffset['offset'] = 0;
        return $returnLimitOffset;
    }

    /**
     * Prepara valores e colunas de ordenação
     * @return array
     */
    public function prepareOrderParam() {

        if (isset($this->request['order']) and ! empty($this->request['order'])) {
            $paramOrder = $this->request['order'];
            $returnOrder = Array();
            if (strlen($paramOrder) > 3) {
                if (is_int(strpos($paramOrder, "[")) and is_int(strpos($paramOrder, "]"))) {
                    $matches2 = $matches = null;
                    preg_match_all("/\[[^\]].*?\]/", $paramOrder, $matches, PREG_PATTERN_ORDER);
                    preg_match_all('/\=(.+)/s', $paramOrder, $matches2);
                    $returnOrder[0]['indexColumn'] = str_replace("]", '', str_replace("[", '', $matches[0][0]));
                    $returnOrder[0]['valueColumn'] = str_replace("=", '', $matches2[1][0]);
                }
            }
            return $returnOrder;
        } else {
            return null;
        }
    }

    /**
     * Prepara valores e colunas de filtro
     * @return array
     */
    public function prepareSearchParam() {
        if (isset($this->request['searchFields']) and ! empty($this->request['searchFields'])) {
            $returnSearch = Array();
            $countSearchFields = 0;

            foreach ($this->request['searchFields'] as $key => $value) {
                $returnSearch[$countSearchFields]['indexColumn'] = $key;
                $returnSearch[$countSearchFields]['valueColumn'] = $value;
                $countSearchFields++;
            }
            return $returnSearch;
        } else {
            return null;
        }
    }

    /**
     * Prepara o valor de busca para coluna do tipo Status
     * @param string $valueColumn
     * @param array $getParamsFieldGrid
     * @param string $columNamesSearchSort
     * @return string
     */
    private function prepareSearchStatus($valueColumn, $getParamsFieldGrid, $columNamesSearchSort) {
        $valuesTranslate = explode(",", $this->translate->translate($columNamesSearchSort . '_values'));
        $valuesTranslateFlip = array_flip($valuesTranslate);
        $valuesIni = explode(",", $getParamsFieldGrid[$columNamesSearchSort]['values']);
        return (isset($valuesIni[$valuesTranslateFlip[$valueColumn]]) and ! empty($valuesIni[$valuesTranslateFlip[$valueColumn]])) ? $valuesIni[$valuesTranslateFlip[$valueColumn]] : null;
    }

    /**
     * Função que acessa ao bando de dados retornando o recordset
     * @param string $url
     * @param boolean $isDumpGrid
     * @param boolean $useLimit
     * @return array
     */
    private function getDataRs(array $options = array(), $useLimit = true) {

        /* Arquivo de configuração do datagrid */
        $configGrid = ZendConfigFile::fromFile($this->sessionAdapter->moduleIni . $this->request['__CONTROLLER__'] . DS . ((isset($options['iniGridName']) and ! empty($options['iniGridName'])) ? $options['iniGridName'] . ".ini" : "datagrid.ini"));
        $getParamsFieldGrid = $configGrid['gridfieldsconfig'];
        $getParamsGrid = $configGrid['gridconfig'];

        /* Colunas do datagrid */
        $columNames = Array();
        $primaryKey = null;
        foreach ($getParamsFieldGrid as $key => $value) {
            if (!isset($value['notshowfield']) or $value['notshowfield'] != 'true') {
                $columNames[] = $key;
                if (strtolower($value['type']) == 'primarykey') {
                    $primaryKey = $key;
                }
            }
        }

        /* Colunas do datagrid para busca */
        $columNamesSearchSort = Array();
        foreach ($getParamsFieldGrid as $key => $value) {
            $columNamesSearchSort[] = $key;
        }

        /* Seleciona a tabela padrão do datagrid */
        $db = \Cityware\Db\Factory::factory('zend');
        $platform = $db->getAdapter()->getPlatform();



        if (isset($getParamsGrid['grid']['schema']) and $getParamsGrid['grid']['schema'] != "") {
            $db->from($getParamsGrid['grid']['table'], $getParamsGrid['grid']['tableAlias'], $getParamsGrid['grid']['schema']);
        } else {
            $db->from($getParamsGrid['grid']['table'], $getParamsGrid['grid']['tableAlias']);
        }

        if (isset($getParamsGrid['grid']['tableAlias']) and ! empty($getParamsGrid['grid']['tableAlias'])) {
            $aliasDefaultTable = "{$getParamsGrid['grid']['tableAlias']}.";
        } else {
            $aliasDefaultTable = null;
        }

        /* Define o WHERE no select da tabela do datagrid */
        if (isset($getParamsGrid['grid']['where'])) {
            $whereVerify = $getParamsGrid['grid']['where'];
            if (!empty($whereVerify)) {
                foreach ($whereVerify as $key => $value) {
                    /* Verifica se na where contem variável PHP e prepara o mesmo senão define somente a where */
                    $db->where($this->preparePhpTagWhere($value));
                }
            }
        }

        /* Define o WHERE condicional no select da tabela do datagrid */
        if (isset($getParamsGrid['grid']['condwherecolumn'])) {
            $whereVerify = $getParamsGrid['grid']['condwherecolumn'];
            if (!empty($whereVerify)) {
                foreach ($whereVerify as $key => $value) {
                    /* Verifica se na where contem variável PHP e prepara o mesmo senão define somente a where */
                    if (isset($getParamsGrid['grid']['condwhererequest'][$key]) and ! empty($getParamsGrid['grid']['condwhererequest'][$key])) {
                        if (isset($this->request[$getParamsGrid['grid']['condwhererequest'][$key]])) {
                            $db->where($aliasDefaultTable . $value . " = '" . $this->request[$getParamsGrid['grid']['condwhererequest'][$key]] . "'");
                        }
                    } else {
                        if (isset($this->request[$value])) {
                            $db->where($aliasDefaultTable . $value . " = '" . $this->request[$value] . "'");
                        }
                    }
                }
            }
        }

        /* Prepara campos de acordo com as refencias Join do banco caso houver */
        $referenceJoin = Array();
        $index = 1;
        foreach ($getParamsFieldGrid as $key => $value) {
            /* Verifica se o campo é relacionado e monta a query com base nos dados de relacionamento */
            if ((isset($getParamsFieldGrid[$key]['relationship']) and $getParamsFieldGrid[$key]['relationship'] == 'true')) {

                /* Monta o JOIN do campo para multiplos joins */
                foreach ($getParamsFieldGrid[$key]['jointablefk'] as $keyJoin => $valueJoin) {
                    /* Verifica se foi definido o tipo de relacionamento JOIN */
                    if (isset($getParamsFieldGrid[$key]['jointype'][$keyJoin]) and ! empty($getParamsFieldGrid[$key]['jointype'][$keyJoin])) {
                        $relationtype = strtolower($getParamsFieldGrid[$key]['jointype'][$keyJoin]);
                    } else {
                        $relationtype = 'inner';
                    }

                    /* Define o ALIAS da tabela de JOIN */
                    $aliasJoin = (isset($getParamsFieldGrid[$key]['joinalias'][$keyJoin]) and ! empty($getParamsFieldGrid[$key]['joinalias'][$keyJoin])) ? $getParamsFieldGrid[$key]['joinalias'][$keyJoin] : "f{$index}";

                    /* Define o SCHEMA da tabela de relacionamento do JOIN */
                    $schema = (isset($getParamsFieldGrid[$key]['joinschema'][$keyJoin]) and ! empty($getParamsFieldGrid[$key]['joinschema'][$keyJoin])) ? $getParamsFieldGrid[$key]['joinschema'][$keyJoin] : null;

                    /* Define a condição do JOIN nos modos normal e invertido */
                    if (isset($getParamsFieldGrid[$key]['joininverse'][$keyJoin]) and $getParamsFieldGrid[$key]['joininverse'][$keyJoin] == 'true') {
                        $condition = "{$aliasJoin}.{$getParamsFieldGrid[$key]['joinfieldpk'][$keyJoin]} = {$getParamsGrid['grid']['tableAlias']}.{$getParamsFieldGrid[$key]['joinfieldfk'][$keyJoin]}";
                    } else {
                        $condition = "{$aliasDefaultTable}{$getParamsFieldGrid[$key]['joinfieldfk'][$keyJoin]} = {$aliasJoin}.{$getParamsFieldGrid[$key]['joinfieldpk'][$keyJoin]}";
                    }

                    /* Define os paramentros do JOIN */
                    $db->join($getParamsFieldGrid[$key]['jointablefk'][$keyJoin], $aliasJoin, $condition, $relationtype, $schema);

                    /* Define os campos selecionados pelo JOIN */
                    if (isset($getParamsFieldGrid[$key]['joinfieldshow'][$keyJoin]) and ! empty($getParamsFieldGrid[$key]['joinfieldshow'][$keyJoin])) {
                        $db->select("{$aliasJoin}.{$getParamsFieldGrid[$key]['joinfieldshow'][$keyJoin]}", $getParamsFieldGrid[$key]['name']);


                        /* Define a referência do JOIN para o datatables */
                        $referenceJoin['join'][$getParamsFieldGrid[$key]['name']]['field'] = $getParamsFieldGrid[$key]['joinfieldshow'][$keyJoin];
                        $referenceJoin['join'][$getParamsFieldGrid[$key]['name']]['table'] = $getParamsFieldGrid[$key]['jointablefk'][$keyJoin];
                        $referenceJoin['join'][$getParamsFieldGrid[$key]['name']]['alias'] = $aliasJoin;
                    }
                    $index++;
                }
            } else {
                /* Define os campos selecionado da tabela principal */
                if (!isset($getParamsFieldGrid[$key]['notshowfield']) or $getParamsFieldGrid[$key]['notshowfield'] != 'true') {
                    $aliasWhereDefaultTable = str_replace(".", "", $aliasDefaultTable);
                    $db->select("{$aliasDefaultTable}{$key}");
                }
            }
        }

        /* Habilita ou desabilita o select para lixeira */
        if (isset($options['trash']) and $options['trash'] == true) {
            $aliasWhereDefaultTable = str_replace(".", "", $aliasDefaultTable);
            $db->where($platform->quoteIdentifier($aliasWhereDefaultTable) . '.' . $platform->quoteIdentifier("ind_status") . " = 'L'");
        } else {
            $aliasWhereDefaultTable = str_replace(".", "", $aliasDefaultTable);
            $db->where($platform->quoteIdentifier($aliasWhereDefaultTable) . '.' . $platform->quoteIdentifier("ind_status") . " IN ('A','B')");
        }

        /* Define parametros de busca */
        $whereSearch = null;
        $searchParamns = $this->prepareSearchParam();
        if (!empty($searchParamns)) {
            for ($i = 0; $i < count($searchParamns); $i++) {
                $indexColumn = $searchParamns[$i]['indexColumn'];
                $valueColumn = $searchParamns[$i]['valueColumn'];

                /* Verifica se o campo é de relacionamento e redefine o campo de busca para o campo de visualização
                 * se não for define o campo de busca como campo da tabela principal */
                if (isset($getParamsFieldGrid[$indexColumn]['relationship']) and $getParamsFieldGrid[$indexColumn]['relationship'] == 'true') {
                    $fieldKey = "{$referenceJoin['join'][$indexColumn]['alias']}.{$referenceJoin['join'][$indexColumn]['field']}";
                } else {
                    $fieldKey = "{$aliasDefaultTable}{$getParamsFieldGrid[$indexColumn]['name']}";
                }

                switch (strtolower($getParamsFieldGrid[$indexColumn]['type'])) {
                    case 'integer': case 'int2': case 'int4': case 'int8': case 'primarykey': case 'double': case 'float': case 'float4': case 'float8': case 'decimal':
                        $whereSearch = "CAST({$fieldKey} AS TEXT) iLIKE '%{$valueColumn}%'";
                        break;
                    case 'text': case 'string': case 'varchar': case 'char':
                        $whereSearch = "CAST({$fieldKey} AS TEXT) iLIKE '%{$valueColumn}%'";
                        break;
                    case 'datetime': case 'timestamp': case 'date':
                        $whereValue = \Cityware\Format\FieldGrid::fieldMask($valueColumn, 'DATE', 'Y-m-d');
                        $whereSearch = "CAST({$fieldKey} AS TEXT) iLIKE '%{$whereValue}%'";
                        break;
                    case 'status':
                        $whereValue = $this->prepareSearchStatus($valueColumn, $getParamsFieldGrid, $indexColumn);
                        $whereSearch = "{$fieldKey} IN ('{$whereValue}')";
                        break;
                    default:
                        $whereSearch = "CAST({$fieldKey} AS TEXT) iLIKE '%{$valueColumn}%'";
                        break;
                }
                /* Definição de busca do datagrid */
                if (!empty($whereSearch)) {
                    $db->where($whereSearch);
                }
            }
        }

        /* Ordenação do datagrid */
        $orderParamns = $this->prepareOrderParam();

        if (!empty($orderParamns)) {

            for ($i = 0; $i < count($orderParamns); $i++) {

                if (isset($getParamsGrid['grid']['disableckeckall']) and $getParamsGrid['grid']['disableckeckall'] == 'true') {
                    $indexColumn = $orderParamns[$i]['indexColumn'];
                } else {
                    $indexColumn = $orderParamns[$i]['indexColumn'] - 1;
                }
                $valueColumn = $orderParamns[$i]['valueColumn'];

                $typeOrder = ($valueColumn == 'asc' ) ? 'ASC' : 'DESC';

                /* Verifica se o campo é de relacionamento e redefine o campo de ordenação para o campo de visualização
                 * se não for define o campo de busca como campo da tabela principal */
                if (isset($getParamsFieldGrid[$columNamesSearchSort[$indexColumn]]['relationship']) and $getParamsFieldGrid[$columNamesSearchSort[$indexColumn]]['relationship'] == 'true') {
                    $fieldKey = "{$referenceJoin['join'][$columNamesSearchSort[$indexColumn]]['alias']}.{$referenceJoin['join'][$columNamesSearchSort[$indexColumn]]['field']}";
                } else {
                    $fieldKey = "{$aliasDefaultTable}{$getParamsFieldGrid[$columNamesSearchSort[$indexColumn]]['name']}";
                }

                /* Define a ordenação */
                $db->orderBy("{$fieldKey} {$typeOrder}");
            }
        } else {
            /* Define a ordenação */
            $orderColumn = (isset($getParamsGrid['grid']['ordercolumn']) and ! empty($getParamsGrid['grid']['ordercolumn'])) ? $getParamsGrid['grid']['ordercolumn'] : $primaryKey;
            $db->orderBy("{$aliasDefaultTable}{$orderColumn} {$getParamsGrid['grid']['orderdefault']}");
        }

        /* Executa o select do datagrid */
        $db->setDebug(false);
        $db->setExplan(false);

        if (!isset($getParamsGrid['grid']['disablelimit']) or $getParamsGrid['grid']['disablelimit'] != 'true') {
            /* Paginação do datagrid */
            if ($useLimit) {
                $limitOffset = $this->prepareLimitOffset();
                $db->limit($limitOffset['limit'], $limitOffset['offset']);
            }

            $page = (isset($this->request['ajaxPage']) and ! empty($this->request['ajaxPage'])) ? $this->request['ajaxPage'] : 0;
            $limitPage = (isset($this->request['itensPage']) and ! empty($this->request['itensPage'])) ? $this->request['itensPage'] : 10;

            $rsDatagrid = $db->executeSelectQuery(true, $page, $limitPage);
        } else {
            $rsDatagrid = $db->executeSelectQuery(true, 1, 99999999999);
        }


        return $rsDatagrid;
    }

    /**
     * Função que pega os dados da grid de acordo com o parametro
     * @param string $url
     * @param boolean $isDumpGrid
     * @return array 
     */
    public function recordSet($url, array $options = Array(), $useLimit = true, array $recordSetData = null) {

        /* Arquivo de configuração do datagrid */
        $configGrid = ZendConfigFile::fromFile($this->sessionAdapter->moduleIni . $this->request['__CONTROLLER__'] . DS . ((isset($options['iniGridName']) and ! empty($options['iniGridName'])) ? $options['iniGridName'] . ".ini" : "datagrid.ini"));
        $getParamsFieldGrid = $configGrid['gridfieldsconfig'];
        $getGridConfig = $configGrid['gridconfig']['grid'];

        if (isset($configGrid['gridbuttons'])) {
            $getParamsGridButtons = $configGrid['gridbuttons'];
        }

        $row = Array();

        /* Colunas do datagrid */
        $headerColNames = $columNames = Array();
        $primaryKey = null;

        $countHeaders = 0;

        foreach ($getParamsFieldGrid as $key => $value) {
            $columNames[] = $key;
            $headerColNames[] = $this->translate->translate($key);
            if (strtolower($value['type']) == 'primarykey') {
                $primaryKey = $key;
            }
            $countHeaders ++;
        }

        /* Colunas do datagrid para busca */
        $columNamesSearchSort = Array();
        // Verificar se a montagem da datagrid está utilizando checkbox para seleção
        if (!isset($getGridConfig['disableckeckall']) or $getGridConfig['disableckeckall'] != 'true') {
            $columNamesSearchSort[] = null;
            $countHeaders += 1;
        }
        // Pega os nomes das colunas para busca
        foreach ($getParamsFieldGrid as $key => $value) {
            $columNamesSearchSort[] = $key;
        }
        // Verificar se a montagem da datagrid está habilitados os botões de ação
        if (!isset($getGridConfig['disablebuttons']) or $getGridConfig['disablebuttons'] != 'true') {
            $columNamesSearchSort[] = null;
            $countHeaders += 1;
        }

        /* Recebe os dados do banco */
        if (!empty($recordSetData)) {
            $rsData = $recordSetData;
        } else {
            $rsData = $this->getDataRs($options, $useLimit);
        }

        $pagination = $rsData['page'];

        /* Formata a saida dos registros */
        $output = Array();
        $output['total_rows'] = $pagination->getTotalItemCount();
        $output['headers'] = $headerColNames;
        $output['countColumns'] = $countHeaders;

        $output['aSorting']['tableColNames'] = $columNames;

        $output['aPagination'] = json_decode(json_encode($pagination->getPages()), true);
        if (!isset($getGridConfig['disablelimit']) or $getGridConfig['disablelimit'] != 'true') {
            $output['aPagination']['itensPage'] = (isset($this->request['itensPage']) and ! empty($this->request['itensPage'])) ? $this->request['itensPage'] : 10;
        } else {
            $output['aPagination']['itensPage'] = 99999999999;
        }

        /* Popula os dados de registro do datagrid */
        $primaryValue = null;
        foreach ($rsData['db'] as $key => $value) {

            // Verificar se a montagem da datagrid á ou não para exportação
            if (!isset($options['export']) or $options['export'] !== true) {
                // Verificar se a montagem da datagrid está utilizando checkbox para seleção
                if (!isset($getGridConfig['disableckeckall']) or $getGridConfig['disableckeckall'] != 'true') {
                    foreach ($columNames as $name) {
                        switch (strtolower($getParamsFieldGrid[$name]['type'])) {
                            case 'integer': case 'int2': case 'int4': case 'int8': case 'primarykey':
                                if ($name == $primaryKey) {
                                    $row[$key][] = '<input type="checkbox" class="selection" name="idselect[]" value="' . $value[$name] . '" />';
                                }
                                break;
                        }
                    }
                }
            }

            foreach ($columNames as $name) {

                // Verifica e formata o valor de acordo com o tipo do campo
                switch (strtolower($getParamsFieldGrid[$name]['type'])) {
                    case 'integer': case 'int2': case 'int4': case 'int8': case 'primarykey':
                        $row[$key][] = (int) $value[$name];
                        if ($name == $primaryKey) {
                            $primaryValue = $value[$name];
                        }
                        break;

                    case 'text': case 'string': case 'varchar': case 'char':
                        $row[$key][] = (string) $value[$name];
                        break;

                    case 'datetime': case 'timestamp':
                        $format = (isset($getParamsFieldGrid[$name]['format']) and ! empty($getParamsFieldGrid[$name]['format'])) ? $getParamsFieldGrid[$name]['format'] : 'd/m/Y H:i:s';
                        $row[$key][] = \Cityware\Format\FieldGrid::fieldMask($value[$name], "DATETIME", $format);
                        break;

                    case 'date':
                        $format = (isset($getParamsFieldGrid[$name]['format']) and ! empty($getParamsFieldGrid[$name]['format'])) ? $getParamsFieldGrid[$name]['format'] : 'd/m/Y';
                        $row[$key][] = \Cityware\Format\FieldGrid::fieldMask($value[$name], "DATE", $format);
                        break;

                    case 'double': case 'float': case 'float4': case 'float8': case 'decimal':
                        $precision = (isset($getParamsFieldGrid[$name]['precision']) and ! empty($getParamsFieldGrid[$name]['precision'])) ? $getParamsFieldGrid[$name]['precision'] : 2;
                        $row[$key][] = \Cityware\Format\Number::decimalNumber((float) $value[$name], $precision, $this->translate->getLocale());
                        break;

                    case 'money':
                        $precision = (isset($getParamsFieldGrid[$name]['precision']) and ! empty($getParamsFieldGrid[$name]['precision'])) ? $getParamsFieldGrid[$name]['precision'] : 2;
                        $row[$key][] = \Cityware\Format\Number::currency((float) $value[$name], $precision, $this->translate->getLocale());
                        break;

                    case 'status':
                        $row[$key][] = \Cityware\Format\FieldGrid::fieldMask($value[$name], "STATUS");
                        break;

                    case 'boolean':
                        $row[$key][] = \Cityware\Format\FieldGrid::fieldMask($value[$name], "BOOLEAN");
                        break;

                    case 'custom':
                        $aValuesCustom = explode(',', $getParamsFieldGrid[$name]['values']);
                        $valuesCustomTranslate = explode(",", $this->translate->translate($name . '_values'));
                        foreach ($aValuesCustom as $keyCustom => $valueCustom) {
                            if ($valueCustom == $value[$name]) {
                                $return = $valuesCustomTranslate[$keyCustom];
                            }
                        }
                        $row[$key][] = $return;
                        break;

                    default:
                        $row[$key][] = $value[$name];
                        break;
                }
            }

            // Verificar se a montagem da datagrid á ou não para exportação
            if (!isset($options['export']) or $options['export'] !== true) {
                // Verificar se a montagem da datagrid está habilitados os botões de ação
                if (!isset($getGridConfig['disablebuttons']) or $getGridConfig['disablebuttons'] != 'true') {
                    // Verifica se é Lixeira ou não
                    if (isset($options['trash']) and $options['trash'] == true) {
                        $returnButtons = '<a title="Restaurar" href="' . $url . '/gorestore/id/' . $primaryValue . '" data-id="' . $primaryValue . '" class="btn btn-info restorebtn"><i class="fa fa-reply icon-white"></i></a>'
                                . '<a title="Excluir" href="' . $url . '/godelete/id/' . $primaryValue . '" data-id="' . $primaryValue . '" class="btn btn-danger deletebtn"><i class="fa fa-trash-o"></i></a>';
                    } else {
                        if (isset($getParamsGridButtons) and ! empty($getParamsGridButtons)) {
                            $aButtons = Array();

                            if (isset($getParamsGridButtons['button']['custom']) and ! empty($getParamsGridButtons['button']['custom'])) {
                                if (isset($getParamsGridButtons['button']['custom']['url']) and ! empty($getParamsGridButtons['button']['custom']['url'])) {
                                    foreach ($getParamsGridButtons['button']['custom']['url'] as $keyButtonCustom => $valueButtonCustom) {
                                        if (!empty($getParamsGridButtons['button']['custom']['url'][$keyButtonCustom])) {
                                            $urlButton = $getParamsGridButtons['button']['custom']['url'][$keyButtonCustom];
                                        } else {
                                            throw new \Exception('Nenhuma url definida para o botão personalizado no indice "' . $key . '"!', 500);
                                        }

                                        $urlButton = $this->preparePhpTagWhere($urlButton, false);

                                        $name = (isset($getParamsGridButtons['button']['custom']['name'][$keyButtonCustom]) and ! empty($getParamsGridButtons['button']['custom']['name'][$keyButtonCustom])) ? $getParamsGridButtons['button']['custom']['name'][$keyButtonCustom] : '';
                                        $extraClass = (isset($getParamsGridButtons['button']['custom']['extraClass'][$keyButtonCustom]) and ! empty($getParamsGridButtons['button']['custom']['extraClass'][$keyButtonCustom])) ? $getParamsGridButtons['button']['custom']['extraClass'][$keyButtonCustom] : '';
                                        $btColor = (isset($getParamsGridButtons['button']['custom']['classBtColor'][$keyButtonCustom]) and ! empty($getParamsGridButtons['button']['custom']['classBtColor'][$keyButtonCustom])) ? $getParamsGridButtons['button']['custom']['classBtColor'][$keyButtonCustom] : 'btn-success';
                                        $classIcon = (isset($getParamsGridButtons['button']['custom']['classIcon'][$keyButtonCustom]) and ! empty($getParamsGridButtons['button']['custom']['classIcon'][$keyButtonCustom])) ? $getParamsGridButtons['button']['custom']['classIcon'][$keyButtonCustom] : 'fa fa-pencil';

                                        $aButtons[] = '<a title="' . $name . '" href="' . $urlButton . '/id/' . $primaryValue . '" data-id="' . $primaryValue . '" class="' . $extraClass . 'btn ' . $btColor . '"><i class="' . $classIcon . '"></i></a>';
                                    }
                                } else {
                                    throw new \Exception('Nenhuma url definida para o botão personalizado!', 500);
                                }
                            } else {
                                foreach ($getParamsGridButtons['button'] as $keyButons => $valueButons) {
                                    $classConfirm = $dataConfirm = null;
                                                                       
                                    if(is_array($valueButons) and (isset($valueButons['confirm']) and $valueButons['confirm'] = true)){
                                        $dataConfirm = ' data-confirm="'.$valueButons['confirmmsg'].'"';
                                        $classConfirm = ' '.$valueButons['confirmclass'];
                                    }
                                    
                                    if ($keyButons == 'edit' and ( (is_array($valueButons)) ? $getParamsGridButtons['button'][$keyButons]['show'] == 'true' : $getParamsGridButtons['button']['edit'] == 'true')) {
                                        $aButtons[] = '<a title="Editar" href="' . $url . '/edit/id/' . $primaryValue . '" data-id="' . $primaryValue . '" '.$dataConfirm.' class="btn btn-success editbtn '.$classConfirm.'"><i class="fa fa-pencil"></i></a>';
                                    } else if ($keyButons == 'active' and ( (is_array($valueButons)) ? $getParamsGridButtons['button'][$keyButons]['show'] == 'true' : $getParamsGridButtons['button']['edit'] == 'true')) {
                                        $aButtons[] = '<a title="Ativar" href="' . $url . '/goactive/id/' . $primaryValue . '" data-id="' . $primaryValue . '" '.$dataConfirm.' class="btn btn-primary activebtn '.$classConfirm.'"><i class="fa fa-check-square-o"></i></a>';
                                    } else if ($keyButons == 'block' and ( (is_array($valueButons)) ? $getParamsGridButtons['button'][$keyButons]['show'] == 'true' : $getParamsGridButtons['button']['edit'] == 'true')) {
                                        $aButtons[] = '<a title="Bloquear" href="' . $url . '/goblock/id/' . $primaryValue . '" data-id="' . $primaryValue . '" '.$dataConfirm.' class="btn btn-warning blockbtn '.$classConfirm.'"><i class="fa fa-ban"></i></a>';
                                    } else if ($keyButons == 'trash' and ( (is_array($valueButons)) ? $getParamsGridButtons['button'][$keyButons]['show'] == 'true' : $getParamsGridButtons['button']['edit'] == 'true')) {
                                        $aButtons[] = '<a title="Lixeira" href="' . $url . '/gotrash/id/' . $primaryValue . '" data-id="' . $primaryValue . '" '.$dataConfirm.' class="btn btn-danger trashbtn '.$classConfirm.'"><i class="fa fa-recycle"></i></a>';
                                    }
                                }
                            }
                            $returnButtons = implode('', $aButtons);
                        } else {
                            $returnButtons = '<a title="Editar" href="' . $url . '/edit/id/' . $primaryValue . '" data-id="' . $primaryValue . '" class="btn btn-success editbtn"><i class="fa fa-pencil"></i></a>'
                                    . '<a title="Ativar" href="' . $url . '/goactive/id/' . $primaryValue . '" data-id="' . $primaryValue . '" class="btn btn-primary activebtn"><i class="fa fa-check-square-o"></i></a>'
                                    . '<a title="Bloquear" href="' . $url . '/goblock/id/' . $primaryValue . '" data-id="' . $primaryValue . '" class="btn btn-warning blockbtn"><i class="fa fa-ban"></i></a>'
                                    . '<a title="Lixeira" href="' . $url . '/gotrash/id/' . $primaryValue . '" data-id="' . $primaryValue . '" class="btn btn-danger trashbtn"><i class="fa fa-recycle"></i></a>';
                        }
                    }

                    $row[$key][] = $returnButtons;
                }
            }
            $output['rows'] = $row;
        }



        /* Retorna os dados do datagrid */
        return $output;
    }

    /**
     * Função de define o status dos registros selecionados da datagrid
     * @param string $varStatus 
     */
    public function defineStatus($varStatus) {

        $options = Array();

        /* Pega o request da página */
        if (isset($this->request['idselect']) and ! empty($this->request['idselect'])) {
            $regIds = $this->request['idselect'];
        } else if (isset($this->request['id']) and ! empty($this->request['id'])) {
            $regIds = Array($this->request['id']);
        } else {
            throw new \Exception('Nenhum identificador definido', 500);
        }

        /* Arquivo de configuração do datagrid */
        $configGrid = ZendConfigFile::fromFile($this->sessionAdapter->moduleIni . $this->request['__CONTROLLER__'] . DS . ((isset($options['iniGridName']) and ! empty($options['iniGridName'])) ? $options['iniGridName'] . ".ini" : "datagrid.ini"));
        $getParamsFieldGrid = $configGrid['gridfieldsconfig'];
        $getParamsGrid = $configGrid['gridconfig'];

        /* Coluna Primária do datagrid */
        $primaryKey = null;
        foreach ($getParamsFieldGrid as $key => $value) {
            if (strtolower($value['type']) == 'primarykey') {
                $primaryKey = $key;
            }
        }

        /* Seleciona a tabela padrão do datagrid */
        $db = \Cityware\Db\Factory::factory('zend');

        if (isset($getParamsGrid['grid']['schema']) and $getParamsGrid['grid']['schema'] != "") {
            $db->from($getParamsGrid['grid']['table'], null, $getParamsGrid['grid']['schema']);
        } else {
            $db->from($getParamsGrid['grid']['table']);
        }

        /* Verifica se é array ou não definindo o where */
        if (is_array($regIds)) {
            $inRegs = implode("','", $regIds);
            $db->where("{$primaryKey} IN ('{$inRegs}')");
        } else {
            $db->where("{$primaryKey} = '{$regIds}'");
        }

        /* Define o status */
        $db->update('ind_status', $varStatus);

        /* Executa a atualização */
        $db->setDebug(false);
        $db->executeUpdateQuery();
    }

    /**
     * Verifica se na where contem variável PHP e prepara o mesmo ou somente define a where
     * @param string $value
     * @return string
     */
    public function preparePhpTagWhere($value, $isWhere = true) {

        if (strpos($value, "{") and strpos($value, "}")) {
            /* Pega por expressão regular as veriáveis PHP */
            $return = $arr = $arr2 = array();
            preg_match_all("/'\{(.*)\}'/U", $value, $arr);
            foreach ($arr[1] as $key2 => $value2) {
                $replace = null;
                eval('$replace = ' . $value2 . ';');
                $arr2[$key2] = ($isWhere) ? "'" . $replace . "'" : $replace;
            }
            if (count($arr[0]) > 1) {
                $valueTemp = $value;
                /* Monta a definição da where */
                foreach ($arr[0] as $key3 => $value3) {
                    $valueTemp = str_replace($value3, $arr2[$key3], $valueTemp);
                }
                $return = $valueTemp;
            } else {
                /* Monta a definição da where */
                foreach ($arr[0] as $key3 => $value3) {
                    $return = str_replace($value3, $arr2[$key3], $value);
                }
            }
            return $return;
        } else {
            return $value;
        }
    }

}
