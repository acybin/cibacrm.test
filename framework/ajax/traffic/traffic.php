<?

namespace framework\ajax\traffic;

use framework\ajax as ajax;
use framework\ajax\term\term;
use framework\enum;
use framework\shape\alert;
use framework\load;
use framework\tools;
use framework\dom\node;
use framework\shape;
use framework\shape\x_panel;
use framework\pdo;
use framework\shape\form as form;
use framework\shape\modal;
use framework\ajax\navy\navy;
use framework\bank;

class traffic extends ajax\ajax
{
    public function __construct($args)
    {
        parent::__construct('traffic');
          
        error_reporting(E_ALL);
        ini_set('display_errors', 1);

        $mode = isset($args['mode']) ? (string) $args['mode'] : 'show';
        $answer = '';  
        
        $this->getWrapper()->getAttributes()->addAttr('id', 'traffic-'.$mode);
        $this->getWrapper()->getAttributes()->getClass()->addItems('offer_table_wrapper');
                        
        $enum = new enum();
        $enum->setSign('');
        
        switch ($mode)
        {
             case 'datatable':
             
                $s_mode = isset($args['s_mode']) ? (integer) $args['s_mode'] : 0;                
                $dop_filter = isset($args['dop_filter']) ? $args['dop_filter'] : array();
                
                $draw = isset($args['draw']) ? $args['draw'] : 0;        
                $start = isset($args['start']) ? $args['start'] : 0;
                $length = isset($args['length']) ? $args['length'] : 100;
            
                $order_column = isset($args['order'][0]['column']) ? $args['order'][0]['column'] : 0;
                $order_dir = isset($args['order'][0]['dir']) ? mb_strtoupper($args['order'][0]['dir']) : 'DESC';   
                
                list($h, $i, $s, $n, $j, $Y) = array(date("H"),date("i"),date("s"),date("n"),date("j"),date("Y"));
                
                if (isset($dop_filter['start_date']))
                {
                    $start_date = $dop_filter['start_date'];
                    unset($dop_filter['start_date']);
                }
                else
                    $start_date = date('Y-m-d H:i:s', mktime(0, 0, 0, $n, $j - 30, $Y));
                    
                if (isset($dop_filter['end_date']))
                {
                    $end_date = $dop_filter['end_date'];
                    unset($dop_filter['end_date']);
                }
                else
                    $end_date = date('Y-m-d H:i:s', mktime(23, 59, 59, $n, $j, $Y));      
                    
                $thead = array(
                        'источник', 
                        'всего', 
                        'уникальных', 
                        'оплаченных', 
                        'проданных', 
                        'approve', 
                        '<оплата>',	
                        '<продажа>', 
                        'качество', 
                        'оплата', 
                        'доход', 
                        'маржа',
                        'roi', 
                        'возвраты',
                        'сумма возвратов',
                        '% возвратов',
                        'сервислид',
                        'рукисплеч', 
                        'спам', 
                        'повторно', 
                        'запчасть', 
                        'гарантия',
                        'техподдержка', 
                        'автосброс', 
                        'не актуален', 
                        'в базе', 
                        'некому',
                    );
                    
                $partner_array = [3075, 3067];
                $nls_context = [46913, 47104, 47130, 47131, 47129]; 
                
                if ($s_mode)
                {
                    $tbody = array();                    
                    $original_data = array();
                    
                    $search = '';
                    if (isset($dop_filter['traffic_search']))
                    {
                        $search = $dop_filter['traffic_search'];
                        unset($dop_filter['traffic_search']);
                    }
                    
                    $minus = false;
                    if (isset($dop_filter['minus']))
                    {
                        $minus = $dop_filter['minus'];
                        unset($dop_filter['minus']);
                    }
                    
                    $t1 = [];
                    $t2 = [];
                    $all = [];
                    
                    /*webmaster*/
                    /*уникальных + маркер*/
                    $filter = array();
                    $filter[] = "`transactions`.`timestamp` >= '{$start_date}'"; 
                    $filter[] = "`transactions`.`timestamp` <= '{$end_date}'";
                    
                    $filter = " WHERE (".implode(") AND (", $filter).")" ;
                    
                    $sql = "SELECT `partner_apps`.`user_id` as `user_id`,
                                                    `new_nyks`.`code`,
                                                        COUNT(*) as `count` 
                                                                    FROM `transactions`  
                                        INNER JOIN `partner_orders` ON `partner_orders`.`call_id` = `transactions`.`call_id`
                                            INNER JOIN `partner_apps` ON `partner_apps`.`call_id` = `transactions`.`call_id`
                                                    LEFT JOIN `new_nyks` ON `new_nyks`.`id` = `partner_orders`.`new_nyk_id`
                                                     $filter
                                                GROUP BY `partner_apps`.`user_id`,
                                                    `partner_orders`.`new_nyk_id`"; //echo $sql;

                    $data = pdo::getPdo()->query($sql)->fetchAll(\PDO::FETCH_ASSOC);
                    
                    foreach ($data as $value)
                    {
                        $nls_source_id = $value['user_id'];
                        $value['code'] = 'new_nyk_' . $value['code']; 
                                                                                    
                        if (!isset($t1[$nls_source_id][$value['code']])) $t1[$nls_source_id][$value['code']] = 0; 
                        if (!isset($t1[$nls_source_id]['unique'])) $t1[$nls_source_id]['unique'] = 0; 
                        
                        if (!isset($all[$value['code']])) $all[$value['code']] = 0; 
                        if (!isset($all['unique'])) $all['unique'] = 0;  
                                              
                        $t1[$nls_source_id][$value['code']] += $value['count'];                        
                        $t1[$nls_source_id]['unique'] += $value['count'];
                        
                        $all[$value['code']] += $value['count'];
                        $all['unique'] += $value['count'];
                    }
                    
                    /*оплаченных + оплата*/
                    $filter = array();
                    $filter[] = "`partner_apps`.`date_create` >= '{$start_date}'"; 
                    $filter[] = "`partner_apps`.`date_create` <= '{$end_date}'";
                    $filter[] = "`partner_apps`.`price` > 0";
                    
                    $filter = " WHERE (".implode(") AND (", $filter).")" ;
                    
                    $sql = "SELECT `partner_apps`.`user_id` as `user_id`,
                                                        COUNT(*) as `count`,
                                                          SUM(`price`) as `price` 
                                                             FROM `partner_apps`
                                                     $filter
                                                GROUP BY `partner_apps`.`user_id`"; //echo $sql;

                    $data = pdo::getPdo()->query($sql)->fetchAll(\PDO::FETCH_ASSOC);                    
                    
                    foreach ($data as $value)
                    {
                        $nls_source_id = $value['user_id'];
                        
                        if (!isset($all['pay'])) $all['pay'] = 0; 
                        if (!isset($all['pay_money'])) $all['pay_money'] = 0; 
                                                                      
                        $t1[$nls_source_id]['pay'] = $value['count'];
                        $t1[$nls_source_id]['pay_money'] = $value['price'];  
                        
                        $all['pay'] += $value['count'];
                        $all['pay_money'] += $value['price'];                      
                    }
                    
                    /*проданных (прямые)*/
                    $filter = array();
                    $filter[] = "`t1`.`timestamp` >= '{$start_date}'"; 
                    $filter[] = "`t1`.`timestamp` <= '{$end_date}'";
                    $filter[] = "`t1`.`summ` != 0";    
                    
                    if ($minus)
                    {
                        $filter[] = "`partner_apps`.`price` > 0";    
                    }
                     
                    $filter = " WHERE (".implode(") AND (", $filter).")" ;
                      
                    $sql = "SELECT `partner_apps`.`user_id` as `user_id`,
                                                        COUNT(*) as `count`,
                                                            ABS(SUM(`t1`.`summ`)) as `cost`  
                                                                    FROM `transactions` `t1`
                                                  INNER JOIN `partner_apps` ON `partner_apps`.`call_id` = `t1`.`call_id`
                                                     $filter
                                                GROUP BY `partner_apps`.`user_id`"; //echo $sql;

                    $data = pdo::getPdo()->query($sql)->fetchAll(\PDO::FETCH_ASSOC);
                    
                    foreach ($data as $value)
                    {
                        $nls_source_id = $value['user_id'];     
                         
                        if (!isset($t1[$nls_source_id]['sold'])) $t1[$nls_source_id]['sold'] = 0;
                        if (!isset($t1[$nls_source_id]['sold_money'])) $t1[$nls_source_id]['sold_money'] = 0;
                        
                        if (!isset($all['sold'])) $all['sold'] = 0;
                        if (!isset($all['sold_money'])) $all['sold_money'] = 0;
                        
                        $t1[$nls_source_id]['sold'] += $value['count'];
                        $t1[$nls_source_id]['sold_money'] += $value['cost'];
                        
                        $all['sold'] += $value['count'];
                        $all['sold_money'] += $value['cost'];
                    }
                    
                    /*проданных (ресайл)*/
                    $filter = array();
                    $filter[] = "`t1`.`timestamp` >= '{$start_date}'"; 
                    $filter[] = "`t1`.`timestamp` <= '{$end_date}'";
                    $filter[] = "`t2`.`summ` != 0";   
                    
                    if ($minus)
                    {
                        $filter[] = "`partner_apps`.`price` > 0";    
                    }
                    
                    $filter = " WHERE (".implode(") AND (", $filter).")" ; 
                     
                    $sql = "SELECT `partner_apps`.`user_id` as `user_id`,
                                                        COUNT(*) as `count`,
                                                            ABS(SUM(`t2`.`summ`)) as `cost` 
                                                                    FROM `transactions` `t1`
                                                  INNER JOIN `connectors` ON `connectors`.`a` = `t1`.`call_id`
                                                        INNER JOIN `transactions` `t2` ON `connectors`.`b` = `t2`.`call_id`  
                                                            INNER JOIN `partner_apps` ON `partner_apps`.`call_id` = `t1`.`call_id`
                                                     $filter
                                                GROUP BY `partner_apps`.`user_id`"; //echo $sql;

                    $data = pdo::getPdo()->query($sql)->fetchAll(\PDO::FETCH_ASSOC);
                    
                    foreach ($data as $value)
                    {
                        $nls_source_id = $value['user_id'];     
                         
                        if (!isset($t1[$nls_source_id]['sold'])) $t1[$nls_source_id]['sold'] = 0;
                        if (!isset($t1[$nls_source_id]['sold_money'])) $t1[$nls_source_id]['sold_money'] = 0;
                        
                        if (!isset($all['sold'])) $all['sold'] = 0;
                        if (!isset($all['sold_money'])) $all['sold_money'] = 0;
                        
                        $t1[$nls_source_id]['sold'] += $value['count'];
                        $t1[$nls_source_id]['sold_money'] += $value['cost'];
                        
                        $all['sold'] += $value['count'];
                        $all['sold_money'] += $value['cost'];
                    }
                    
                    /*всего*/
                    $filter = array();
                    $filter[] = "`calls`.`timestamp` >= '{$start_date}'"; 
                    $filter[] = "`calls`.`timestamp` <= '{$end_date}'";
                    
                    $filter = " WHERE (".implode(") AND (", $filter).")" ;
                    
                    $sql = "SELECT `partner_apps`.`user_id` as `user_id`,
                                                        COUNT(*) as `count` 
                                                                    FROM `calls`  
                                            INNER JOIN `partner_apps` ON `partner_apps`.`call_id` = `calls`.`id`
                                                     $filter
                                                GROUP BY `partner_apps`.`user_id`"; //echo $sql;

                    $data = pdo::getPdo()->query($sql)->fetchAll(\PDO::FETCH_ASSOC);
                    
                    foreach ($data as $value)
                    {
                        $nls_source_id = $value['user_id'];             
                        
                        if (!isset($t1[$nls_source_id]['all'])) $t1[$nls_source_id]['all'] = 0;  
                        
                        if (!isset($all['all'])) $all['all'] = 0; 
                                   
                        $t1[$nls_source_id]['all'] += $value['count'];
                        
                        $all['all'] += $value['count'];
                    }
                    
                    /*сервислид, руки из плеч*/                    
                    $filter = array();
                    $filter[] = "`t1`.`timestamp` >= '{$start_date}'"; 
                    $filter[] = "`t1`.`timestamp` <= '{$end_date}'";
                    $filter[] = "`t2`.`organization_id` IN (".implode(',', $partner_array).")";
                    
                    $filter = " WHERE (".implode(") AND (", $filter).")" ; 
                     
                    $sql = "SELECT `partner_apps`.`user_id` as `user_id`,
                                                        COUNT(*) as `count`,
                                                            `t2`.`organization_id`
                                                                    FROM `transactions` `t1`
                                                  INNER JOIN `connectors` ON `connectors`.`a` = `t1`.`call_id`
                                                        INNER JOIN `transactions` `t2` ON `connectors`.`b` = `t2`.`call_id`  
                                                            INNER JOIN `partner_apps` ON `partner_apps`.`call_id` = `t1`.`call_id`
                                                     $filter
                                                GROUP BY `partner_apps`.`user_id`,
                                                                `t2`.`organization_id`"; //echo $sql;

                    $data = pdo::getPdo()->query($sql)->fetchAll(\PDO::FETCH_ASSOC);
                    
                    foreach ($data as $value)
                    {
                        $nls_source_id = $value['user_id'];
                        $value['organization_id'] = 'partner_' . $value['organization_id']; 
                                                                                    
                        if (!isset($t1[$nls_source_id][$value['organization_id']])) $t1[$nls_source_id][$value['organization_id']] = 0; 
                        
                        if (!isset($all[$value['organization_id']])) $all[$value['organization_id']] = 0; 
                                              
                        $t1[$nls_source_id][$value['organization_id']] += $value['count'];
                        
                        $all[$value['organization_id']] += $value['count'];
                    }
                    
                    /*возврат*/                    
                    /*возврат (прямые)*/
                    $filter = array();
                    $filter[] = "`t1`.`timestamp` >= '{$start_date}'"; 
                    $filter[] = "`t1`.`timestamp` <= '{$end_date}'";
                    $filter[] = "`cashbacks`.`approve` = 1";    
                    
                    if ($minus)
                    {
                        $filter[] = "`partner_apps`.`price` > 0";    
                    }
                     
                    $filter = " WHERE (".implode(") AND (", $filter).")" ;
                      
                    $sql = "SELECT `partner_apps`.`user_id` as `user_id`,
                                                        COUNT(*) as `count`,
                                                            ABS(SUM(`t1`.`double_summ`)) as `cost`  
                                                                    FROM `transactions` `t1`
                                                  INNER JOIN `partner_apps` ON `partner_apps`.`call_id` = `t1`.`call_id`
                                                        INNER JOIN `cashbacks` ON `cashbacks`.`call_id` = `t1`.`call_id`
                                                     $filter
                                                GROUP BY `partner_apps`.`user_id`"; //echo $sql;

                    $data = pdo::getPdo()->query($sql)->fetchAll(\PDO::FETCH_ASSOC);
                    
                    foreach ($data as $value)
                    {
                        $nls_source_id = $value['user_id'];     
                         
                        if (!isset($t1[$nls_source_id]['return'])) $t1[$nls_source_id]['return'] = 0;
                        if (!isset($t1[$nls_source_id]['return_money'])) $t1[$nls_source_id]['return_money'] = 0;
                        
                        if (!isset($all['return'])) $all['return'] = 0;
                        if (!isset($all['return_money'])) $all['return_money'] = 0;
                        
                        $t1[$nls_source_id]['return'] += $value['count'];
                        $t1[$nls_source_id]['return_money'] += $value['cost'];
                        
                        $all['return'] += $value['count'];
                        $all['return_money'] += $value['cost'];
                    }
                    
                    /*возврат (ресайл)*/
                    $filter = array();
                    $filter[] = "`t1`.`timestamp` >= '{$start_date}'"; 
                    $filter[] = "`t1`.`timestamp` <= '{$end_date}'";
                    $filter[] = "`cashbacks`.`approve` = 1";   
                    
                    if ($minus)
                    {
                        $filter[] = "`partner_apps`.`price` > 0";    
                    }
                    
                    $filter = " WHERE (".implode(") AND (", $filter).")" ; 
                     
                    $sql = "SELECT `partner_apps`.`user_id` as `user_id`,
                                                        COUNT(*) as `count`,
                                                            ABS(SUM(`t2`.`double_summ`)) as `cost` 
                                                                    FROM `transactions` `t1`
                                                  INNER JOIN `connectors` ON `connectors`.`a` = `t1`.`call_id`
                                                        INNER JOIN `transactions` `t2` ON `connectors`.`b` = `t2`.`call_id`  
                                                            INNER JOIN `partner_apps` ON `partner_apps`.`call_id` = `t1`.`call_id`
                                                                INNER JOIN `cashbacks` ON `cashbacks`.`call_id` = `t2`.`call_id`
                                                     $filter
                                                GROUP BY `partner_apps`.`user_id`"; //echo $sql;

                    $data = pdo::getPdo()->query($sql)->fetchAll(\PDO::FETCH_ASSOC);
                    
                    foreach ($data as $value)
                    {
                        $nls_source_id = $value['user_id'];     
                         
                        if (!isset($t1[$nls_source_id]['return'])) $t1[$nls_source_id]['return'] = 0;
                        if (!isset($t1[$nls_source_id]['return_money'])) $t1[$nls_source_id]['return_money'] = 0;
                        
                        if (!isset($all['return'])) $all['return'] = 0;
                        if (!isset($all['return_money'])) $all['return_money'] = 0;
                        
                        $t1[$nls_source_id]['return'] += $value['count'];
                        $t1[$nls_source_id]['return_money'] += $value['cost'];
                        
                        $all['return'] += $value['count'];
                        $all['return_money'] += $value['cost'];
                    }
                    
                    /*$filter = array();
                    $filter[] = "`calls`.`timestamp` >= '{$start_date}'"; 
                    $filter[] = "`calls`.`timestamp` <= '{$end_date}'";
                    $filter[] = "`cashbacks`.`approve` = 1";
                    
                    $filter = " WHERE (".implode(") AND (", $filter).")" ;
                    
                    $sql = "SELECT `partner_apps`.`user_id` as `user_id`,
                                                        COUNT(*) as `count` 
                                                                    FROM `calls`  
                                            INNER JOIN `partner_apps` ON `partner_apps`.`call_id` = `calls`.`id`
                                                INNER JOIN `cashbacks` ON `cashbacks`.`call_id` = `calls`.`id`
                                                     $filter
                                                GROUP BY `partner_apps`.`user_id`"; //echo $sql;

                    $data = pdo::getPdo()->query($sql)->fetchAll(\PDO::FETCH_ASSOC);
                    
                    foreach ($data as $value)
                    {
                        $nls_source_id = $value['user_id'];             
                        
                        if (!isset($t1[$nls_source_id]['return'])) $t1[$nls_source_id]['return'] = 0;  
                        
                        if (!isset($all['return'])) $all['return'] = 0; 
                                   
                        $t1[$nls_source_id]['return'] += $value['count'];
                        
                        $all['return'] += $value['count'];
                    }*/                    
                    
                    if ($t1)
                    {
                        $sql = "SELECT `users`.`id`, `workers`.`name` FROM `users` 
                                                LEFT JOIN `workers` ON `users`.`worker_id` = `workers`.`id`
                                            WHERE `users`.`id` IN (".implode(',', array_keys($t1)).")";
                        $nls_names =  pdo::getPdo()->query($sql)->fetchAll(\PDO::FETCH_KEY_PAIR);
                    }
                    
                    $tt1 = [];
                    foreach ($t1 as $nls_id => $value)
                    {
                        $value['name'] = $nls_names[$nls_id];
                        $tt1[]  = $value;  
                    }
                    
                    $t1 = $tt1;
                    unset($tt1);                                          
                    /*end webmaster*/                    
                                        
                    /*nls*/   
                    /*уникальных + маркер*/                        
                    $nls = [];               
                    $sql = "SELECT `nls_sources`.`id` FROM `nls_sources` LEFT JOIN `nls_source_tags` ON `nls_sources`.`id` = `nls_source_tags`.`nls_source_id` WHERE `nls_source_tags`.`name_type` = 'setka' 
                                    AND `nls_source_tags`.`id_type` IN (38,39,40) AND `nls_sources`.`no_active` = 0 AND `nls_sources`.`parent` = 0";
                    $nls = array_merge($nls, pdo::getPdo()->query($sql)->fetchAll(\PDO::FETCH_COLUMN));
                    
                    $nls = array_merge($nls, $nls_context);
                    
                    $sql = "SELECT `id`, `name` FROM `nls_sources` WHERE `id` IN (".implode(',', $nls).")";
                    $nls_names =  pdo::getPdo()->query($sql)->fetchAll(\PDO::FETCH_KEY_PAIR);
                                     
                    $sql = "SELECT `id` FROM `nls_sources` WHERE `parent` IN (".implode(',', $nls).")";
                    $nls = array_merge($nls, pdo::getPdo()->query($sql)->fetchAll(\PDO::FETCH_COLUMN));
                    
                    $filter = array();
                    $filter[] = "`transactions`.`timestamp` >= '{$start_date}'"; 
                    $filter[] = "`transactions`.`timestamp` <= '{$end_date}'";
                    $filter[] = "`transactions`.`nls_source_id` IN (".implode(',', $nls).")";
                    
                    $filter = " WHERE (".implode(") AND (", $filter).")" ;
                            
                    $sql = "SELECT `nls_sources`.`id` as `id`,
                                                  `nls_sources`.`parent` as `parent_id`,
                                                    `new_nyks`.`code`,
                                                        COUNT(*) as `count` 
                                                                    FROM `transactions`  
                                        INNER JOIN `partner_orders` ON `partner_orders`.`call_id` = `transactions`.`call_id`
                                            INNER JOIN `nls_sources` ON `nls_sources`.`id` = `transactions`.`nls_source_id`
                                                 LEFT JOIN `new_nyks` ON `new_nyks`.`id` = `partner_orders`.`new_nyk_id`
                                                     $filter
                                                GROUP BY `transactions`.`nls_source_id`,
                                                    `partner_orders`.`new_nyk_id`"; //echo $sql;

                    $data = pdo::getPdo()->query($sql)->fetchAll(\PDO::FETCH_ASSOC);
                    
                    foreach ($data as $value)
                    {
                        $nls_source_id = $value['parent_id'] ? $value['parent_id'] : $value['id']; 
                        $value['code'] = 'new_nyk_' . $value['code'];
                                                                                   
                        if (!isset($t2[$nls_source_id][$value['code']])) $t2[$nls_source_id][$value['code']] = 0;   
                        if (!isset($t2[$nls_source_id]['unique'])) $t2[$nls_source_id]['unique'] = 0;   
                        
                        if (!isset($all[$value['code']])) $all[$value['code']] = 0; 
                        if (!isset($all['unique'])) $all['unique'] = 0; 
                                              
                        $t2[$nls_source_id][$value['code']] += $value['count'];
                        $t2[$nls_source_id]['unique'] += $value['count'];
                        
                        $all[$value['code']] += $value['count'];
                        $all['unique'] += $value['count'];
                    }
                    
                    /*оплаченных*/
                    foreach ($t2 as $nls_source_id => $value)
                    {
                        if (!isset($all['pay'])) $all['pay'] = 0; 
                         
                        $t2[$nls_source_id]['pay'] = $value['unique'];
                        
                        $all['pay'] += $value['unique'];
                    }
                        
                    /*оплата*/
                    $filter = array();
                    $filter[] = "`stats`.`d_date` >= '{$start_date}'"; 
                    $filter[] = "`stats`.`d_date` <= '{$end_date}'";
                    $filter[] = "`stats`.`nls_source_id` IN (".implode(',', $nls).")";
                    
                    $filter = " WHERE (".implode(") AND (", $filter).")" ;
                    
                    $sql = "SELECT `nls_sources`.`id` as `id`,
                                            `nls_sources`.`parent` as `parent_id`, SUM(`cost`) as `price`
                                        FROM `stats` 
                                             INNER JOIN `nls_sources` ON `nls_sources`.`id` = `stats`.`nls_source_id`
                                                        $filter
                                                GROUP BY `stats`.`nls_source_id`";
                                            
                    $data = pdo::getPdo()->query($sql)->fetchAll(\PDO::FETCH_ASSOC);
                    
                    foreach ($data as $value)
                    {
                        $nls_source_id = $value['parent_id'] ? $value['parent_id'] : $value['id'];     
                         
                        if (!isset($t2[$nls_source_id]['pay_money'])) $t2[$nls_source_id]['pay_money'] = 0;    
                        
                        if (!isset($all['pay_money'])) $all['pay_money'] = 0; 
                                                 
                        $t2[$nls_source_id]['pay_money'] += $value['price'];
                        
                        $all['pay_money'] += $value['price'];
                    }
                    
                    $filter = array();
                    $filter[] = "`ad_stats`.`d_date` >= '{$start_date}'"; 
                    $filter[] = "`ad_stats`.`d_date` <= '{$end_date}'";
                    $filter[] = "`ad_stats`.`nls_source_id` IN (".implode(',', $nls).")";
                    
                    $filter = " WHERE (".implode(") AND (", $filter).")" ;
                    
                    $sql = "SELECT `nls_sources`.`id` as `id`,
                                            `nls_sources`.`parent` as `parent_id`, SUM(`cost`) as `price`
                                        FROM `ad_stats` 
                                             INNER JOIN `nls_sources` ON `nls_sources`.`id` = `ad_stats`.`nls_source_id`
                                                        $filter
                                                GROUP BY `ad_stats`.`nls_source_id`";
                                            
                    $data = pdo::getPdo()->query($sql)->fetchAll(\PDO::FETCH_ASSOC);
                    
                    foreach ($data as $value)
                    {
                        $nls_source_id = $value['parent_id'] ? $value['parent_id'] : $value['id'];     
                         
                        if (!isset($t2[$nls_source_id]['pay_money'])) $t2[$nls_source_id]['pay_money'] = 0; 
                        
                        if (!isset($all['pay_money'])) $all['pay_money'] = 0; 
                                                   
                        $t2[$nls_source_id]['pay_money'] += $value['price'];
                        
                        $all['pay_money'] += $value['price'];
                    }
                                                
                    /*проданных (прямые)*/
                    $filter = array();
                    $filter[] = "`t1`.`timestamp` >= '{$start_date}'"; 
                    $filter[] = "`t1`.`timestamp` <= '{$end_date}'";
                    $filter[] = "`t1`.`nls_source_id` IN (".implode(',', $nls).")";
                    $filter[] = "`t1`.`summ` != 0";   
                    
                    $filter = " WHERE (".implode(") AND (", $filter).")" ; 
                     
                    $sql = "SELECT `nls_sources`.`id` as `id`,
                                                  `nls_sources`.`parent` as `parent_id`,
                                                        COUNT(*) as `count`,
                                                               ABS(SUM(`t1`.`summ`)) as `cost` 
                                                                    FROM `transactions` `t1`
                                            INNER JOIN `nls_sources` ON `nls_sources`.`id` = `t1`.`nls_source_id`
                                                     $filter
                                                GROUP BY `t1`.`nls_source_id`"; //echo $sql;

                    $data = pdo::getPdo()->query($sql)->fetchAll(\PDO::FETCH_ASSOC);
                    
                    foreach ($data as $value)
                    {
                        $nls_source_id = $value['parent_id'] ? $value['parent_id'] : $value['id'];     
                         
                        if (!isset($t2[$nls_source_id]['sold'])) $t2[$nls_source_id]['sold'] = 0;
                        if (!isset($t2[$nls_source_id]['sold_money'])) $t2[$nls_source_id]['sold_money'] = 0;
                        
                        if (!isset($all['sold'])) $all['sold'] = 0;
                        if (!isset($all['sold_money'])) $all['sold_money'] = 0;
                        
                        $t2[$nls_source_id]['sold'] += $value['count'];
                        $t2[$nls_source_id]['sold_money'] += $value['cost'];
                        
                        $all['sold'] += $value['count'];
                        $all['sold_money'] += $value['cost'];
                    }
                    
                    /*проданных (ресайл)*/
                    $filter = array();
                    $filter[] = "`t1`.`timestamp` >= '{$start_date}'"; 
                    $filter[] = "`t1`.`timestamp` <= '{$end_date}'";
                    $filter[] = "`t1`.`nls_source_id` IN (".implode(',', $nls).")";
                    $filter[] = "`t2`.`summ` != 0";    
                    
                    $filter = " WHERE (".implode(") AND (", $filter).")" ;
                     
                    $sql = "SELECT `nls_sources`.`id` as `id`,
                                                  `nls_sources`.`parent` as `parent_id`,
                                                        COUNT(*) as `count`,
                                                            ABS(SUM(`t2`.`summ`)) as `cost` 
                                                                    FROM `transactions` `t1`
                                                  INNER JOIN `connectors` ON `connectors`.`a` = `t1`.`call_id`
                                                        INNER JOIN `transactions` `t2` ON `connectors`.`b` = `t2`.`call_id`  
                                                            INNER JOIN `nls_sources` ON `nls_sources`.`id` = `t1`.`nls_source_id`
                                                     $filter
                                                GROUP BY `t1`.`nls_source_id`"; //echo $sql;

                    $data = pdo::getPdo()->query($sql)->fetchAll(\PDO::FETCH_ASSOC);
                    
                    foreach ($data as $value)
                    {
                        $nls_source_id = $value['parent_id'] ? $value['parent_id'] : $value['id'];
                              
                        if (!isset($t2[$nls_source_id]['sold'])) $t2[$nls_source_id]['sold'] = 0;
                        if (!isset($t2[$nls_source_id]['sold_money'])) $t2[$nls_source_id]['sold_money'] = 0;
                        
                        if (!isset($all['sold'])) $all['sold'] = 0;
                        if (!isset($all['sold_money'])) $all['sold_money'] = 0;
                        
                        $t2[$nls_source_id]['sold'] += $value['count'];
                        $t2[$nls_source_id]['sold_money'] += $value['cost'];
                        
                        $all['sold'] += $value['count'];
                        $all['sold_money'] += $value['cost'];
                    }
                    
                    /*всего*/
                    $filter = array();
                    $filter[] = "`calls`.`timestamp` >= '{$start_date}'"; 
                    $filter[] = "`calls`.`timestamp` <= '{$end_date}'";
                    $filter[] = "`calls`.`nls_source_id` IN (".implode(',', $nls).")";
                    
                    $filter = " WHERE (".implode(") AND (", $filter).")" ;
                            
                    $sql = "SELECT `nls_sources`.`id` as `id`,
                                                  `nls_sources`.`parent` as `parent_id`,
                                                        COUNT(*) as `count` 
                                                                    FROM `calls`  
                                            INNER JOIN `nls_sources` ON `nls_sources`.`id` = `calls`.`nls_source_id`
                                                     $filter
                                                GROUP BY `calls`.`nls_source_id`"; //echo $sql;

                    $data = pdo::getPdo()->query($sql)->fetchAll(\PDO::FETCH_ASSOC);
                    
                    foreach ($data as $value)
                    {
                        $nls_source_id = $value['parent_id'] ? $value['parent_id'] : $value['id'];          
                        
                        if (!isset($t2[$nls_source_id]['all'])) $t2[$nls_source_id]['all'] = 0;     
                        
                        if (!isset($all['all'])) $all['all'] = 0; 
                                                                                             
                        $t2[$nls_source_id]['all'] += $value['count'];
                        
                        $all['all'] += $value['count'];
                    }
                    
                    /*сервислид, руки из плеч*/       
                    $filter = array();
                    $filter[] = "`t1`.`timestamp` >= '{$start_date}'"; 
                    $filter[] = "`t1`.`timestamp` <= '{$end_date}'";
                    $filter[] = "`t1`.`nls_source_id` IN (".implode(',', $nls).")";
                    $filter[] = "`t2`.`organization_id` IN (".implode(',', $partner_array).")";
                    
                    $filter = " WHERE (".implode(") AND (", $filter).")" ;
                     
                    $sql = "SELECT `nls_sources`.`id` as `id`,
                                                  `nls_sources`.`parent` as `parent_id`,
                                                        COUNT(*) as `count`,
                                                            `t2`.`organization_id`
                                                                    FROM `transactions` `t1`
                                                  INNER JOIN `connectors` ON `connectors`.`a` = `t1`.`call_id`
                                                        INNER JOIN `transactions` `t2` ON `connectors`.`b` = `t2`.`call_id`  
                                                            INNER JOIN `nls_sources` ON `nls_sources`.`id` = `t1`.`nls_source_id`
                                                     $filter
                                                GROUP BY `t1`.`nls_source_id`,
                                                                `t2`.`organization_id`"; //echo $sql;

                    $data = pdo::getPdo()->query($sql)->fetchAll(\PDO::FETCH_ASSOC);
                    
                    foreach ($data as $value)
                    {
                        $nls_source_id = $value['parent_id'] ? $value['parent_id'] : $value['id'];
                        $value['organization_id'] = 'partner_' . $value['organization_id']; 
                               
                        if (!isset($t2[$nls_source_id][$value['organization_id']])) $t2[$nls_source_id][$value['organization_id']] = 0; 
                        
                        if (!isset($all[$value['organization_id']])) $all[$value['organization_id']] = 0; 
                                              
                        $t2[$nls_source_id][$value['organization_id']] += $value['count'];
                        
                        $all[$value['organization_id']] += $value['count'];
                    }
                    
                    /*возврат*/
                    /*возврат (прямые)*/
                    $filter = array();
                    $filter[] = "`t1`.`timestamp` >= '{$start_date}'"; 
                    $filter[] = "`t1`.`timestamp` <= '{$end_date}'";
                    $filter[] = "`t1`.`nls_source_id` IN (".implode(',', $nls).")";
                    $filter[] = "`cashbacks`.`approve` = 1";   
                    
                    $filter = " WHERE (".implode(") AND (", $filter).")" ; 
                     
                    $sql = "SELECT `nls_sources`.`id` as `id`,
                                                  `nls_sources`.`parent` as `parent_id`,
                                                        COUNT(*) as `count`,
                                                               ABS(SUM(`t1`.`double_summ`)) as `cost` 
                                                                    FROM `transactions` `t1`
                                            INNER JOIN `nls_sources` ON `nls_sources`.`id` = `t1`.`nls_source_id`
                                                INNER JOIN `cashbacks` ON `cashbacks`.`call_id` = `t1`.`call_id`
                                                     $filter
                                                GROUP BY `t1`.`nls_source_id`"; //echo $sql;

                    $data = pdo::getPdo()->query($sql)->fetchAll(\PDO::FETCH_ASSOC);
                    
                    foreach ($data as $value)
                    {
                        $nls_source_id = $value['parent_id'] ? $value['parent_id'] : $value['id'];     
                         
                        if (!isset($t2[$nls_source_id]['return'])) $t2[$nls_source_id]['return'] = 0;
                        if (!isset($t2[$nls_source_id]['return_money'])) $t2[$nls_source_id]['return_money'] = 0;
                        
                        if (!isset($all['return'])) $all['return'] = 0;
                        if (!isset($all['return_money'])) $all['return_money'] = 0;
                        
                        $t2[$nls_source_id]['return'] += $value['count'];
                        $t2[$nls_source_id]['return_money'] += $value['cost'];
                        
                        $all['return'] += $value['count'];
                        $all['return_money'] += $value['cost'];
                    }
                    
                    /*возврат (ресайл)*/ 
                    $filter = array();
                    $filter[] = "`t1`.`timestamp` >= '{$start_date}'"; 
                    $filter[] = "`t1`.`timestamp` <= '{$end_date}'";
                    $filter[] = "`t1`.`nls_source_id` IN (".implode(',', $nls).")";
                    $filter[] = "`cashbacks`.`approve` = 1";    
                    
                    $filter = " WHERE (".implode(") AND (", $filter).")" ;
                     
                    $sql = "SELECT `nls_sources`.`id` as `id`,
                                                  `nls_sources`.`parent` as `parent_id`,
                                                        COUNT(*) as `count`,
                                                            ABS(SUM(`t2`.`double_summ`)) as `cost` 
                                                                    FROM `transactions` `t1`
                                                  INNER JOIN `connectors` ON `connectors`.`a` = `t1`.`call_id`
                                                        INNER JOIN `transactions` `t2` ON `connectors`.`b` = `t2`.`call_id`  
                                                            INNER JOIN `nls_sources` ON `nls_sources`.`id` = `t1`.`nls_source_id`
                                                                INNER JOIN `cashbacks` ON `cashbacks`.`call_id` = `t2`.`call_id`
                                                     $filter
                                                GROUP BY `t1`.`nls_source_id`"; //echo $sql;

                    $data = pdo::getPdo()->query($sql)->fetchAll(\PDO::FETCH_ASSOC);
                    
                    foreach ($data as $value)
                    {
                        $nls_source_id = $value['parent_id'] ? $value['parent_id'] : $value['id'];
                              
                        if (!isset($t2[$nls_source_id]['return'])) $t2[$nls_source_id]['return'] = 0;
                        if (!isset($t2[$nls_source_id]['return_money'])) $t2[$nls_source_id]['return_money'] = 0;
                        
                        if (!isset($all['return'])) $all['return'] = 0;
                        if (!isset($all['return_money'])) $all['return_money'] = 0;
                        
                        $t2[$nls_source_id]['return'] += $value['count'];
                        $t2[$nls_source_id]['return_money'] += $value['cost'];
                        
                        $all['return'] += $value['count'];
                        $all['return_money'] += $value['cost'];
                    }
                                       
                    /*$filter = array();
                    $filter[] = "`calls`.`timestamp` >= '{$start_date}'"; 
                    $filter[] = "`calls`.`timestamp` <= '{$end_date}'";
                    $filter[] = "`calls`.`nls_source_id` IN (".implode(',', $nls).")";
                    $filter[] = "`cashbacks`.`approve` = 1";
                    
                    $filter = " WHERE (".implode(") AND (", $filter).")" ;
                    
                    $sql = "SELECT `nls_sources`.`id` as `id`,
                                                  `nls_sources`.`parent` as `parent_id`,
                                                        COUNT(*) as `count` 
                                                                    FROM `calls`  
                                            INNER JOIN `nls_sources` ON `nls_sources`.`id` = `calls`.`nls_source_id`
                                                INNER JOIN `cashbacks` ON `cashbacks`.`call_id` = `calls`.`id`
                                                     $filter
                                                GROUP BY `calls`.`nls_source_id`"; //echo $sql;

                    $data = pdo::getPdo()->query($sql)->fetchAll(\PDO::FETCH_ASSOC);
                    
                    foreach ($data as $value)
                    {
                        $nls_source_id = $value['parent_id'] ? $value['parent_id'] : $value['id'];               
                        
                        if (!isset($t2[$nls_source_id]['return'])) $t2[$nls_source_id]['return'] = 0;  
                        
                        if (!isset($all['return'])) $all['return'] = 0; 
                                                                                                
                        $t2[$nls_source_id]['return'] += $value['count'];
                        
                        $all['return'] += $value['count'];
                    }*/                    
                    
                    $tt2 = [];
                    foreach ($t2 as $nls_id => $value)
                    {
                        $value['name'] = $nls_names[$nls_id];
                        $tt2[]  = $value;  
                    }
                    
                    $t2 = $tt2;
                    unset($tt2);                     
                    /*end nls*/
                    
                    $t = array_merge($t1, $t2);
                    
                    $labels = [
                        'name',
                        'all', 
                        'unique',
                         
                        'pay', 
                        'sold',
                        'approve',
                        
                        'avg_pay',
                        'avg_sold',
                        'quality',
                        
                        'pay_money',
                        'sold_money',
                        
                        'merge',
                        'roi',
                        
                        'return',
                        'return_money',
                        'return_procent',
                        
                        'partner_3075',
                        'partner_3067',
                        
                        'new_nyk_spam', 
                        'new_nyk_repeat', 
                        'new_nyk_complect', 
                        'new_nyk_garantee', 
                        'new_nyk_tech', 
                        'new_nyk_no_answer', 
                        'new_nyk_no_actual', 
                        'new_nyk_in_base', 
                        'new_nyk_nothing',                     
                        ];
                    
                    $i = 0;
                    foreach ($t as $name => $code)
                    {
                        $t_row = [];
                        foreach ($labels as $code)
                        {
                            $t_row[$code] = (string) (isset($t[$name][$code]) ? $t[$name][$code] : 0);
                        }
                        
                        $t_row['pay_money'] = round($t_row['pay_money']);
                        
                        $t_row['merge'] = $t_row['sold_money'] - $t_row['pay_money'];
                        $t_row['roi'] = $t_row['pay_money'] ? round($t_row['sold_money'] / $t_row['pay_money'] * 100, 2).'%' : '0%';
                        $t_row['approve'] = $t_row['unique'] ? round($t_row['pay'] / $t_row['unique'] * 100, 2).'%' : '0%';
                        
                        $t_row['avg_pay'] = ($t_row['pay']) ? ceil($t_row['pay_money'] / $t_row['pay']) : 0;
                        $t_row['avg_sold'] = ($t_row['sold']) ? ceil($t_row['sold_money'] / $t_row['sold']) : 0;
                        $t_row['quality'] = ($t_row['unique']) ? ceil($t_row['merge'] / $t_row['unique']) : 0; 
                        
                        $t_row['return_procent'] = $t_row['unique'] ? round($t_row['return'] / $t_row['unique'] * 100, 2).'%' : '0%';
                        
                        $row = [];
                        foreach ($labels as $code)
                            $row[] = $t_row[$code];
                        
                        $tbody[] = $row;
                        $original_data[] = $row;
                        
                        $i++;   
                    }
                    unset($t);
                    
                    foreach ($labels as $code)
                    {
                        $all[$code] = isset($all[$code]) ? $all[$code] : 0;
                    }
                    
                    $all['name'] = 'Итого';
                    $all['pay_money'] = round($all['pay_money']);
                        
                    $all['merge'] = $all['sold_money'] - $all['pay_money'];
                    $all['roi'] = $all['pay_money'] ? round($all['sold_money'] / $all['pay_money'] * 100, 2).'%' : '0%';
                    $all['approve'] = $all['unique'] ? round($all['pay'] / $all['unique'] * 100, 2).'%' : '0%';
                    
                    $all['avg_pay'] = ($all['pay']) ? ceil($all['pay_money'] / $all['pay']) : 0;
                    $all['avg_sold'] = ($all['sold']) ? ceil($all['sold_money'] / $all['sold']) : 0;
                    $all['quality'] = ($all['unique']) ? ceil($all['merge'] / $all['unique']) : 0; 
                    
                    $all['return_procent'] = $all['unique'] ? round($all['return'] / $all['unique'] * 100, 2).'%' : '0%';
                    
                    $row = [];
                    foreach ($labels as $code)
                        $row[] = $all[$code];
                        
                    $all = $row;
                    
                    $count_tbody = count($tbody);
                    
                    bank::getBank()->setArray('order_column', $order_column);
                        
                    if ($order_dir == 'ASC') 
                        $order_dir = 0;
                    else
                        $order_dir = 1;
                        
                    bank::getBank()->setArray('order_dir', $order_dir);
                                                
                    usort($tbody, array('framework\ajax\analytic\analytic', 'compare'));
                    usort($original_data, array('framework\ajax\analytic\analytic', 'compare'));
                    
                    if ($s_mode == 2)
                    {
                        $file_name = '/uploads/traffic/'.uniqid().'.csv';
                        $main_file = \DOCUMENT_ROOT. $file_name;
                        $str = '';
                        
                        $str .= implode(';', $thead).PHP_EOL;
                        
                        $original_data = array_merge([$all], $original_data);
                        
                        foreach ($original_data as $value)
                        {
                            $str .= implode(';', $value).PHP_EOL;
                        }
                        
                        file_put_contents($main_file, iconv('utf-8', 'windows-1251', $str)); 
                        $this->getWrapper()->addChildren($file_name);
                    }
                    else
                    {
                        $tbody = array_slice($tbody, $start, $length);
                        
                        $add_classes = [0 => 'name', 8 => 'cell-green', 12 => 'cell-orange', 5 => 'cell-blue', 15 => 'cell-red'];
                        
                        $tbody = array_merge([$all], $tbody);
                         
                        foreach ($tbody as $key => $row)
                        {
                            foreach ($row as $k => $col)
                            {
                                $add_class = isset($add_classes[$k]) ? (' ' . $add_classes[$k]) : '';
                                
                                $title = '';
                                if ($k == 0) $title = ' data-tooltip="'.$col.'"'; 
                                
                                $tbody[$key][$k] = "<div class='cell$add_class'$title>" . $col . '</div>';   
                            }
                        }
                        
                        $a_array = array('draw' => $draw, 'recordsTotal' => $count_tbody, 'recordsFiltered' => $count_tbody, 'data' => $tbody);
                            
                        $a_array['summ'] = '';
                            
                        $a_node = new node('a');
                        $a_node->getAttributes()->addAttr('id', 'download-traffic');
                        $a_node->getAttributes()->addAttr('href', '#'); 
                        $a_node->getAttributes()->addAttr('title', 'Скачать'); 
                         
                        $i_node = new node('i');
                        $i_node->getAttributes()->getClass()->setItems(array('fa', 'fa-download', 'fa-2x'));
                        $a_node->addChildren($i_node);
                        
                        $a_array['summ'] .=  $a_node;
                        
                        $this->getWrapper()->addChildren($a_array);
                    }
                }
                else
                {
                    $data_table = new shape\datatable();
                    //$data_table->setStriped(false);
                    $data_table->setResponsive(false);
                    
                    $data_table->getAttributes()->getClass()->addItems('offer_table');
                    
                    $data_table->getAttributes()->addAttr('data-table', 'traffic');
                    $this->getWrapper()->getAttributes()->setAttr('data-op', 'datatable');
                    $this->getWrapper()->getAttributes()->setAttr('data-obj', 'traffic');
                    $this->getWrapper()->getAttributes()->setAttr('id', 'datatable-traffic');        
                         
                    $data_table->setThead($thead); 
                                          
                    $this->getWrapper()->addChildren($data_table);
                }
             
             break;
             
             case 'filter':
             
                //go
                $i_calc = new node('i');
                $i_calc->getAttributes()->getClass()->setItems(array('fa', 'fa-calculator'));
              
                $button = new node('button');
                $button->getAttributes()->getClass()->setItems(array('btn', 'btn-primary', 'go'));
                $button->getAttributes()->addAttr('title', 'Расчет');
                $button->addChildren('Расчет'); 
                $button->addChildren($i_calc);  
                
                $enum->addItems($button);
                
                $div = new node('div');
                $div->getAttributes()->getClass()->addItems('checkbox');
                  
                $checkbox = new form\checkbox();
                $checkbox->getAttributes()->getClass()->addItems('flats');
                                
                $checkbox->setName('minus');
                
                if (isset($_COOKIE['minus']))
                { 
                    if ($_COOKIE['minus'])
                    {
                        $checkbox->getAttributes()->addAttr('checked', 'checked');   
                    }
                } 
                
                $div = new node('div');
                $div->getAttributes()->getClass()->addItems('filter_group');
                $div->addChildren($checkbox);
                $div->addChildren('Без наших доходов');
                    
                $enum->addItems($div); 
                
             break;
        }
        
        $answer = $enum;          
        $this->getWrapper()->addChildren($answer);
    }
}

?>