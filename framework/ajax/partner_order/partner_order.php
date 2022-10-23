<?

namespace framework\ajax\partner_order;

use framework\ajax as ajax;
use framework\pdo;
use framework\load;
use framework\ajax\term\term;
use framework\ajax\lid\lid;
use framework\ajax\bill\bill;
use framework\shape as shape;
use framework\log;
use framework\shape\form as form;
use framework\tools;
use framework\ajax\logist\logist;
use framework\dom\node as node;
use framework\ajax\resale_work\resale_work;
use framework\ajax\call\call;
use framework\ajax\cashback\cashback;
use framework\ajax\blacklist\blacklist;
use framework\ajax\user\UserAccess;

class partner_order extends ajax\ajax
{
    public function __construct($args = array())
    {
        error_reporting(E_ALL);
        ini_set('display_errors', 1);
        
        parent::__construct('partner_order');
        $mode = isset($args['mode']) ? $args['mode'] : '';
        
        $this->getWrapper()->getAttributes()->addAttr('id', 'partner_order-'.$mode);

        $answer = '';

        $userAccess = new UserAccess();

        switch ($mode)
        {
            case 'tag':
            
                $page = isset($args['page']) ? $args['page'] : 1;                 
                $q = isset($args['q']) ? mb_strtolower($args['q']) : '';
                $offer_id = isset($args['offer_id']) ? $args['offer_id'] : 0;
                
                $sql = "SELECT DISTINCT `tag_tables`.`name`, `tag_tables`.`desc` FROM `tag_tables` 
                                    INNER JOIN `offer_to_tag_tables` ON `offer_to_tag_tables`.`tag_table_id` = `tag_tables`.`id` 
                                         INNER JOIN `offers` ON `offer_to_tag_tables`.`offer_id` = `offers`.`id`
                                            ORDER BY `offers`.`sort` ASC, `offers`.`id` ASC, `tag_tables`.`sort` ASC, `tag_tables`.`id` ASC";
                $offer_array = pdo::getPdo()->query($sql)->fetchAll(\PDO::FETCH_KEY_PAIR);
                
                $item_array = [];
                $t_childrens = [];
                $total_count = 0;
                
                foreach ($offer_array as $name => $option_text)
                {
                    switch ($name)
                    {
                        case 'brand':
                        
                            $condition = array();
                
                            $condition[] = "`organization_id` IS NULL AND (`ru_name` IS NOT NULL AND `ru_name` != '')";
                            
                            $sql = "SELECT `name`, `ru_name`, `id` FROM `brands` WHERE (".implode(' AND ', $condition).") ORDER BY `sort` ASC, `id` ASC";
                            $datas = pdo::getPdo()->query($sql)->fetchAll(\PDO::FETCH_ASSOC);
                            
                            $sql = "SELECT count(*) FROM `brands` WHERE (".implode(' AND ', $condition).")"; 
                            $total_count += (integer) pdo::getPdo()->query($sql)->fetchColumn();
                            
                            foreach ($datas as $value)
                                $t_childrens[] = array('id' => $name . '-' . $value['id'], 'text' => $value['name']);           
                            
                        break;
                        
                        case 'model_type':
                        
                            $condition = array();
                
                            $condition[] = "`organization_id` IS NULL";
                            $condition[] = "`id` != 2764";
                            
                            $sql = "SELECT `name`, `id` FROM `model_types` WHERE (".implode(' AND ', $condition).") ORDER BY `sort` ASC, `id` ASC";
                            $datas = pdo::getPdo()->query($sql)->fetchAll(\PDO::FETCH_ASSOC);
                            
                            $sql = "SELECT count(*) FROM `model_types` WHERE (".implode(' AND ', $condition).")"; 
                            $total_count += (integer) pdo::getPdo()->query($sql)->fetchColumn();
                            
                            foreach ($datas as $value)
                                $t_childrens[] = array('id' => $name . '-' . $value['id'], 'text' => $value['name']);                      
                            
                        break;
                        
                        case 'poser':
                        
                            $sql = "SELECT `name`, `id` FROM `posers` ORDER BY `sort` ASC, `id` ASC";
                            $datas = pdo::getPdo()->query($sql)->fetchAll(\PDO::FETCH_ASSOC);
                            
                            $sql = "SELECT count(*) FROM `posers`"; 
                            $total_count += (integer) pdo::getPdo()->query($sql)->fetchColumn();
                            
                            foreach ($datas as $value)
                                $t_childrens[] = array('id' => $name . '-' . $value['id'], 'text' => $value['name']);  
                        
                        break;
                        
                        case 'avtoservis_marka':
                        
                            $sql = "SELECT `name`, `ru_name`, `id` FROM `avtoservis_markas` ORDER BY `sort` ASC, `id` ASC";
                            $datas = pdo::getPdo()->query($sql)->fetchAll(\PDO::FETCH_ASSOC);
                            
                            $sql = "SELECT count(*) FROM `avtoservis_markas`"; 
                            $total_count += (integer) pdo::getPdo()->query($sql)->fetchColumn();
                            
                            foreach ($datas as $value)
                                $t_childrens[] = array('id' => $name . '-' . $value['id'], 'text' => $value['name']);           
                            
                        break;
                        
                        default:
                        
                            $sql = "SELECT `name`, `id` FROM `{$name}s` ORDER BY `sort` ASC, `id` ASC";
                            $datas = pdo::getPdo()->query($sql)->fetchAll(\PDO::FETCH_ASSOC);
                            
                            $sql = "SELECT count(*) FROM `{$name}s`"; 
                            $total_count += (integer) pdo::getPdo()->query($sql)->fetchColumn();
                            
                            foreach ($datas as $value)
                                $t_childrens[] = array('id' => $name . '-' . $value['id'], 'text' => $value['name']);
                    }
                }
                
                foreach ($t_childrens as $key_children => $children)
                {
                    if ($q && mb_strpos(mb_strtolower($children['text']), $q) === false)
                        unset($t_childrens[$key_children]);
                }
                
                $page = ($page-1) * 30;
                
                $t_childrens = array_slice($t_childrens, $page, 30);
                
                foreach ($t_childrens as $children)
                {
                    $parent_name = explode('-', $children['id']);
                    $parent_name = $parent_name[0];
                    
                    if (!isset($item_array[$parent_name]))  
                        $item_array[$parent_name] = ['id' => $parent_name, 'text' => $offer_array[$parent_name], 'children' => []];
                        
                    $item_array[$parent_name]['children'][] = $children;
                }
                
                $item_array = array_values($item_array);
                
                $answer = array('items' => $item_array, 'total_count' => $total_count, 'incomplete_results' => false);
            
            break;
            
            case 'model_type':
             
                $page = isset($args['page']) ? $args['page'] : 1;                 
                $q = isset($args['q']) ? $args['q'] : '';  
                
                $condition = array();
                
                $condition[] = "`organization_id` IS NULL";
                $condition[] = "`id` != 2764";
                
                if ($q) $condition[] = "`name` LIKE '%$q%'";
                    
                $page = ($page-1) * 30;
                
                $sql = "SELECT `name`, `id` FROM `model_types` WHERE (".implode(' AND ', $condition).") ORDER BY `id` ASC LIMIT $page,30";
                $datas = pdo::getPdo()->query($sql)->fetchAll(\PDO::FETCH_ASSOC);
                
                $sql = "SELECT count(*) FROM `model_types` WHERE (".implode(' AND ', $condition).")"; 
                $total_count = pdo::getPdo()->query($sql)->fetchColumn();   
                
                $model_types = [];
                if (!$page) $model_types[] = array('id' => 0, 'text' => 'неизвестно');
                    
                foreach ($datas as $value)
                    $model_types[] = array('id' => $value['id'], 'text' => $value['name']);         
                
                $answer = array('items' => $model_types, 'total_count' => $total_count, 'incomplete_results' => false);
                
            break;
            
            case 'brand':
                
                $page = isset($args['page']) ? $args['page'] : 1;                 
                $q = isset($args['q']) ? $args['q'] : '';  
                
                $condition = array();
                
                $condition[] = "`organization_id` IS NULL AND (`ru_name` IS NOT NULL AND `ru_name` != '')";
                if ($q) $condition[] = "`name` LIKE '%$q%' OR `ru_name` LIKE '%$q'";
                    
                $page = ($page-1) * 30;
                
                $sql = "SELECT `name`, `ru_name`, `id` FROM `brands` WHERE (".implode(' AND ', $condition).") ORDER BY `id` ASC LIMIT $page,30";
                $datas = pdo::getPdo()->query($sql)->fetchAll(\PDO::FETCH_ASSOC);
                
                $sql = "SELECT count(*) FROM `brands` WHERE (".implode(' AND ', $condition).")"; 
                $total_count = pdo::getPdo()->query($sql)->fetchColumn();   
                
                $brands = [];
                if (!$page) $brands[] = array('id' => 0, 'text' => 'неизвестно');
                    
                /*foreach ($datas as $value)
                    $brands[] = array('id' => $value['id'], 'text' => $value['name'] . ' | ' .$value['ru_name']);*/
                    
                foreach ($datas as $value)
                    $brands[] = array('id' => $value['id'], 'text' => $value['name']);           
                
                $answer = array('items' => $brands, 'total_count' => $total_count, 'incomplete_results' => false);
            
            break;
            
            case 'region': case 'place':
            
                $page = isset($args['page']) ? $args['page'] : 1;                 
                $q = isset($args['q']) ? $args['q'] : ''; 
                
                $table = $mode . 's';
                
                $condition = array();
                
                if ($q) $condition[] = "`{$table}`.`name` LIKE '%$q%'";
                    
                $page = ($page-1) * 30;
                
                $filter = '';
                
                if ($condition)
                {
                    $filter = "WHERE (".implode(' AND ', $condition).")"; 
                }
                
                if ($mode == 'place')
                {
                    $sql = "SELECT `places`.`id` FROM `places` WHERE `name` IN (SELECT `places`.`name` FROM `places` GROUP BY `name` HAVING COUNT(*) > 1)";
                    $double_places = pdo::getPdo()->query($sql)->fetchAll(\PDO::FETCH_COLUMN);                
                
                    $sql = "SELECT `places`.`name`, `places`.`id`, `regions`.`name` as `region_name` FROM `{$table}` 
                                 LEFT JOIN `regions` ON `regions`.`id` = `places`.`region_id`
                                        $filter ORDER BY `name` ASC LIMIT $page,30"; 
                    $datas = pdo::getPdo()->query($sql)->fetchAll(\PDO::FETCH_ASSOC);
                    
                    foreach ($datas as $key => $value)
                    {
                        if (in_array($value['id'], $double_places) && $value['region_name'])
                            $datas[$key]['name'] .= ' (' . $value['region_name'] . ')';
                    }
                }
                else
                {
                    $sql = "SELECT `name`, `id` FROM `regions` $filter ORDER BY `name` ASC LIMIT $page,30"; 
                    $datas = pdo::getPdo()->query($sql)->fetchAll(\PDO::FETCH_ASSOC);
                }
                
                $sql = "SELECT count(*) FROM `{$table}` $filter"; 
                $total_count = pdo::getPdo()->query($sql)->fetchColumn();   
                
                $regions = [];
                if (!$page) $regions[] = array('id' => 0, 'text' => 'неизвестно');
                    
                foreach ($datas as $value)
                    $regions[] = array('id' => $value['id'], 'text' => $value['name']);           
                
                $answer = array('items' => $regions, 'total_count' => $total_count, 'incomplete_results' => false);
            
            break;
            
            case 'block':
             
               $args['table'] = 'partner_orders';
                              
               $obj = new logist($args); 
               $this->getWrapper()->addChildren($obj->getWrapper()->getChildren(0)); 
               $this->setCode($obj->getCode());
                
             break;
            
            case 'update_record':
            
                $id = isset($args['id']) ? (integer) $args['id'] : 0;
                $field = isset($args['field']) ? (string) $args['field'] : '';
                $text = isset($args['text']) ? $args['text'] : '';                
                $organization_id = isset($args['organization_id']) ? $args['organization_id'] : 0; 
                
                $params = array('field' => $field);
                
                //if ($field != 'tag')
                //{
                    $count = log::log_count('partner_orders', $field, $id);
                            
                    if ($count > 1)
                    {
                        $params['log_count'] = $count;
                    }
                //}
                
                if ($field == 'region_id')
                {
                    $params['name'] = load::get_order((integer) $text, 'name', 'regions');
                }
                
                if ($field == 'tag_text' && is_array($text)) $text = implode(',', $text);
                        
                //if (load::get_user_id() == 1)
                //{
                    if ($field == 'new_nyk_id')
                    {
                        $text = (integer) $text;
                        $call_id = load::get_order($id, 'call_id', 'partner_orders');
                        
                        $call_obj = new call(['mode' => '']);
                        $call_type = load::get_order($call_id, 'type', 'calls');  
                        
                        if ($call_type == 0)
                            $tarif_period = (integer) 30 * 24 * 60 * 60;
                        else
                            $tarif_period = (integer) 3 * 24 * 60 * 60;
                            
                        $goal = $call_obj->is_goal($call_id, $tarif_period, $call_type);
                        
                        if ($goal)
                        {
                            $call_id = $goal;
                            
                            $sql = "SELECT `id` FROM `partner_orders` WHERE `call_id`=:call_id";
                            $stm = pdo::getPdo()->prepare($sql); 
                            $stm->execute(array('call_id' => $call_id));
                            $id = $stm->fetchColumn();     
                        }
                        
                        $new_nyk_id = load::get_order($id, 'new_nyk_id', 'partner_orders');
                        $is_saler = (load::get_order($call_id, 'nls_source_id', 'calls') == 51961);
                        
                        if ($new_nyk_id != load::get_status('new_nyks', 'goal') && $new_nyk_id != load::get_status('new_nyks', 'in_base'))
                        {
                            if ($text)
                            {
                                $color = load::get_order($text, 'color', 'new_nyks');
                                
                                if ($color == 'red')
                                {
                                    $arbiter_obj = new partner_order(['mode' => 'arbiter_action_resale', 'partner_order_id' => $id, 
                                                                                'call_id' => $call_id, 'value' => 2, 'show_color' => 1, 'is_saler' => $is_saler]);
                                    $params['arbiter'] = $arbiter_obj->getWrapper()->getChildren(0);
                                }
                                
                                if ($color == 'yellow')
                                {
                                    $count_recall = (integer) load::get_order($id, 'count_recall', 'partner_orders');
                                    $plus_time = [0 => 10 * 60, 1 => 30 * 60, 2 => 3 * 60 * 60];
                                    
                                    $plus_time = isset($plus_time[$count_recall]) ? $plus_time[$count_recall] : 0;
                                    
                                    $arbiter = null;
                                    if ($text == 26) 
                                    {
                                        $arbiter = 8;
                                        $plus_time = 60 * 60 * 24;
                                    }
                                    
                                    if ($is_saler)
                                    {
                                        if ($text == 26) $arbiter = null;
                                        if ($text == 17) $arbiter = 8; 
                                    }
                                    
                                    $time = date('Y-m-d H:i:s', tools::get_time() + $plus_time);
                                    $arbiter_obj = new partner_order(['mode' => 'save_date_recall_resale', 'partner_order_id' => $id, 
                                                                            'call_id' => $call_id, 'value' => $time, 'show_color' => 1, 'arbiter' => $arbiter, 
                                                                                'is_saler' => $is_saler, 'change_marker' => 1]);
                                                                            
                                    $params['arbiter'] = $arbiter_obj->getWrapper()->getChildren(0);                                    
                                }
                                
                                if ($color == 'blue')
                                {
                                    $arbiter_obj = new partner_order(['mode' => 'arbiter_action_resale', 'partner_order_id' => $id, 
                                                                                'call_id' => $call_id, 'value' => 3, 'show_color' => 1, 'is_saler' => $is_saler]);
                                    $params['arbiter'] = $arbiter_obj->getWrapper()->getChildren(0);
                                }
                                
                                if ($color == 'green')
                                {
                                    $arbiter_obj = new partner_order(['mode' => 'arbiter_action_resale', 'partner_order_id' => $id, 
                                                                                'call_id' => $call_id, 'value' => 1, 'show_color' => 1, 'is_saler' => $is_saler]);
                                    $params['arbiter'] = $arbiter_obj->getWrapper()->getChildren(0);
                                }
                            }
                            else
                            {
                                $arbiter_obj = new partner_order(['mode' => 'arbiter_action_resale', 'partner_order_id' => $id, 
                                                                                'call_id' => $call_id, 'value' => 0, 'show_color' => 1, 'is_saler' => $is_saler]);
                                $params['arbiter'] = $arbiter_obj->getWrapper()->getChildren(0);
                            }
                            
                            $nls_source_id = load::get_order($call_id, 'nls_source_id', 'calls');
                            $webmaster_id = load::get_order($nls_source_id, 'user_id', 'nls_sources');
                            
                            if ($webmaster_id)
                            {
                                exec("php ".\DOCUMENT_ROOT."admin/index.php op=cron args[mode]=send_event args[event]=status_update args[call_id]=$call_id args[source_id]=$nls_source_id > /dev/null &", $output, $return_var);
                            }
                        }
                    }
                    
                    if ($field == 'place_id')
                    {
                        $sql = "SELECT `region_id`, `name` FROM `places` WHERE `id`=:id";
                        $stm = pdo::getPdo()->prepare($sql);
                        $stm->execute(array('id' => (integer) $text));
                        $place_array = $stm->fetch(\PDO::FETCH_ASSOC);
                        
                        $region_id = $place_array['region_id'];
                        $params['name'] = $place_array['name']; 
                        
                        exec("php ".\DOCUMENT_ROOT."admin/index.php op=partner_order args[mode]=update_record args[id]=$id args[field]=region_id args[text]=$region_id > /dev/null &", $output, $return_var);
                    } 
                    
                    //if (load::get_user_id() == 1) print_r($params);
                //}
                
                $notifys = new term(
                        array(
                            'mode' => 'add', 
                            'table' => 'notifys',
                            'text' => $text, 
                            'type_notify_id' => load::get_status('type_notifys', 'update_ciba_excel'),
                            'table_name' => 'partner_orders',
                            'record_id' => $id, 
                            'params' => serialize($params),
                            'organization_id' => $organization_id,
                            )
                        );
                
            break;
            
            case 'add':
            
                $call_id = isset($args['call_id']) ? (integer) $args['call_id'] : 0;
                
                if ($call_id)
                {
                    $double_args = $args;
                    unset($double_args['mode'], $double_args['call_id']);
                    
                    $a = new term(array('mode' => 'add', 'table' => 'partner_orders','call_id' => $call_id));
                        
                    $id = (integer) $a->getWrapper()->getChildren(0);
                    
                    $b_array = array_merge(array('mode' => 'update', 'table' => 'partner_orders', 'id' => $id), $double_args);
                        
                    $b = new term($b_array);
                    
                    $answer = $id;
                }
            
            break;
            
            case 'datatable':
                
                $s_mode = isset($args['s_mode']) ? (integer) $args['s_mode'] : 0;
                
                if ($s_mode)
                {  
                    $obj = new lid(array_merge($args, array('mode' => 'datatable_call'))); 
                    $this->getWrapper()->addChildren($obj->getWrapper()->getChildren(0)); 
                    $this->setCode($obj->getCode());
                }
                else
                {
                    $thead = [];
                    
                    if (load::is_webmaster())
                    {
                        if (load::copy_webmaster())
                            $thead = array('Номер', 'Время', 'Абонент', 'Ф.И.О.', 'Регион', 'Источник', 'no-sort3' => 'Теги', 'Маркер', 'Комментарий', 'no-sort' => 'Запись');
                        else
                            $thead = array('Номер', 'Время', 'Абонент', 'Ф.И.О.', 'Регион', 'Источник', 'no-sort3' => 'Теги', 'Маркер', 'Комментарий', 'no-sort' => 'Запись', '$');
                    }
                    else
                    {
                        $group_code = load::get_group_code();
                        list($use_director_account, $addres_ids) = load::use_director_accout(); 
    
                        if (lid::isCashbackOrg(load::get_org_id())) 
                        {
                            if ($group_code == 'director' && $use_director_account)
                                $thead = array('Номер', 'Время', 'Абонент', 'Ф.И.О.', 'Регион', 'Источник', 'no-sort3' => 'Теги', 'Маркер', 'Комментарий', 'no-sort' => 'Запись', 'Прибыль', 'no-sort2' => '');
                            else
                                $thead = array('Номер', 'Время', 'Абонент', 'Ф.И.О.', 'Регион', 'Источник', 'no-sort3' => 'Теги', 'Маркер', 'Комментарий', 'no-sort' => 'Запись', 'no-sort2' => '');
                        }
                        else 
                        {
                            if ($group_code == 'director' && $use_director_account)
                                $thead = array('Номер', 'Время', 'Абонент', 'Ф.И.О.', 'Регион', 'Источник', 'no-sort3' => 'Теги', 'Маркер', 'Комментарий', 'no-sort' => 'Запись', 'Прибыль');
                            else
                                $thead = array('Номер', 'Время', 'Абонент', 'Ф.И.О.', 'Регион', 'Источник', 'no-sort3' => 'Теги', 'Маркер', 'Комментарий', 'no-sort' => 'Запись');
                        }
                    }                                       

                    $data_table = new shape\datatable();
                    $data_table->getAttributes()->addAttr('data-table', 'partner_orders');
                    $data_table->setResponsive(false);
                    
                    $data_table->getAttributes()->getClass()->setItems(['table', 'table-bordered', 'nowrap', 'datatable']);
                    
                    $data_table->getAttributes()->getClass()->addItems('ciba_excel');
                    $data_table->getAttributes()->getClass()->addItems('small');
                    //$data_table->getAttributes()->getClass()->addItems('history_phone');
                    $data_table->getAttributes()->getClass()->addItems('datatable-standart');
                     
                    $this->getWrapper()->getAttributes()->setAttr('data-op', 'datatable');
                    $this->getWrapper()->getAttributes()->setAttr('data-obj', 'partner_order');
                    $this->getWrapper()->getAttributes()->setAttr('id', 'datatable-lid');
                    $this->getWrapper()->getAttributes()->getClass()->addItems('datatable-short');
                         
                    $data_table->setThead($thead); 
                                          
                    $this->getWrapper()->addChildren($data_table);       
                }
                
            break;
            
            case 'datatable_resale':
                
                $s_mode = isset($args['s_mode']) ? (integer) $args['s_mode'] : 0;
                $is_saler = isset($args['is_saler']) ? $args['is_saler'] : 0;
                
                if ($s_mode)
                {  
                    $obj = new lid(array_merge($args, array('mode' => 'datatable_resale'))); 
                    $this->getWrapper()->addChildren($obj->getWrapper()->getChildren(0)); 
                    $this->setCode($obj->getCode());
                }
                else
                {
                    $thead = [];
                    
                    if (load::is_summ_edit()) 
                    {
                        $thead = array('Номер', 'Время', 'Абонент', 'Ф.И.О.', 'Регион', 'Город', 'Источник', 'Оффер', 'Теги', 'Маркер', 'Комментарий', 'no-sort' => 'Запись', 'Сумма', 'Веб $', 'no-sort-2' => '', 'no-sort-3' => '');
                    }
                    
                    if (!$thead)
                    {
                        $thead = array('Номер', 'Время', 'Абонент', 'Ф.И.О.', 'Регион', 'Город', 'Источник', 'Оффер', 'Теги', 'Маркер', 'Комментарий', 'no-sort' => 'Запись', 'Сумма', 'no-sort-2' => '', 'no-sort-3' => '');   
                    }
                    
                    if ($is_saler)
                    {
                        $thead = array('Номер', 'Время', 'Абонент', 'Ф.И.О.', 'Регион', 'Город',  'Источник', 'Оффер', 'Теги', 'Маркер', 'Комментарий', 'no-sort' => 'Запись', 'no-sort-2' => '', 'no-sort-3' => '');
                    }

                    $data_table = new shape\datatable();
                    $data_table->getAttributes()->addAttr('data-table', 'partner_orders');
                    $data_table->getAttributes()->getClass()->addItems('ciba_excel');
                    $data_table->getAttributes()->getClass()->addItems('small');
                    $data_table->getAttributes()->getClass()->addItems('history_phone');
                    
                    $data_table->getAttributes()->getClass()->addItems('datatable-tag');
                    
                    if ($is_saler) $data_table->getAttributes()->getClass()->addItems('is_saler_table');                     
                      
                    $this->getWrapper()->getAttributes()->setAttr('data-op', 'datatable');
                    $this->getWrapper()->getAttributes()->setAttr('data-obj', 'partner_order');
                    $this->getWrapper()->getAttributes()->setAttr('id', 'datatable-lid');
                    $this->getWrapper()->getAttributes()->getClass()->addItems('datatable-short');
                         
                    $data_table->setThead($thead); 
                                          
                    $this->getWrapper()->addChildren($data_table);       
                }
                
            break;
            
            case 'show_color_approve':
            
                $partner_order_id = isset($args['partner_order_id']) ? (integer) $args['partner_order_id'] : 0;
                $call_id = isset($args['call_id']) ? (integer) $args['call_id'] : 0;
                
                if ($call_id)
                {
                    $organization_id = load::get_order($call_id, 'organization_id', 'calls');
                    $modr = load::get_order($organization_id, 'modr', 'organizations');
                    
                    $user_id = load::get_user_id();
                     
                    $pass = false;

                    $all_to_cashback_access = $userAccess->getUsersByRight('all_to_cashback');
                    $ex_session = array_merge(load::get_auditor(), $all_to_cashback_access);
                    
                    if (lid::isCashbackOrg($organization_id) && (in_array($user_id, $ex_session) || $user_id == $modr)) $pass = true;
                    
                    if ($pass)
                    {
                        $colors = ['gray', 'green', 'red'];
                        
                        $icons = ['mail-reply', 'thumbs-o-up', 'thumbs-o-down'];
                        $title = ['В ресайл', 'Одобрить', 'Отклонить'];
                        
                        unset($colors[0]);
                        
                        $div = new node('div');
                        $div->getAttributes()->getClass()->setItems(['spec-organization', 'spec-color', 'spec-approve']);
                        
                        $div_arbiter_link = new node('div');
                        $div_arbiter_link->getAttributes()->getClass()->setItems(['arbiter-link']);
                        
                        foreach ($colors as $key => $color)
                        {
                            $div_arbiter = new node('div');
                            $div_arbiter->getAttributes()->getClass()->addItems('js-arbiter-switch');
                            $div_arbiter->getAttributes()->getClass()->addItems('js-approve-switch');
                            $div_arbiter->getAttributes()->getClass()->addItems($color);
                            $div_arbiter->getAttributes()->setAttr('data-val', $key);
                            
                            $fa = new node('fa');
                            $fa->getAttributes()->getClass()->addItems('fa');
                            $fa->getAttributes()->getClass()->addItems('fa-' . $icons[$key]);
                            $fa->getAttributes()->getClass()->addItems('arbiter-icon');
                            
                            $div_arbiter->addChildren($fa);
                            $div_arbiter->getAttributes()->addAttr('title', $title[$key]);
                            $div_arbiter_link->addChildren($div_arbiter);
                        }
                        
                        $div->addChildren($div_arbiter_link);
                        
                        $div_link = new node('div');
                        $div_link->getAttributes()->getClass()->setItems(['pull-right', 'organization-link']);
                        
                        $close = new node('a');
                        $close->addChildren('закрыть');
                        $close->getAttributes()->setAttr('href', '#');
                        $close->getAttributes()->getClass()->setItems(['js-close']); 
                            
                        $div_link->addChildren($close);
                        $div->addChildren($div_link);
                        
                        $answer = $div;
                    }
                }
                
            break;
            
            case 'show_color_audit':
            
                $partner_order_id = isset($args['partner_order_id']) ? (integer) $args['partner_order_id'] : 0;
                $call_id = isset($args['call_id']) ? (integer) $args['call_id'] : 0;
                
                $user_id = load::get_user_id();
                $show_audit = $userAccess->getUsersByRight('show_audit');
                
                if (!in_array($user_id, array_merge($show_audit, load::get_auditor()))) break;
                
                if ($partner_order_id)
                {
                    $sql = "SELECT `arbiter`, `comment`, `audit_marker_id` FROM `audit_comments` WHERE `partner_order_id`=:partner_order_id";
                    $stm = pdo::getPdo()->prepare($sql);
                    $stm->execute(['partner_order_id' => $partner_order_id]);
                    $array = $stm->fetch(\PDO::FETCH_ASSOC);
                    
                    $colors = ['gray', 'green', 'red'];
                    
                    $icons = ['question', 'thumbs-o-up', 'thumbs-o-down'];
                    $title = ['Не прослушано', 'Хороший', 'Плохой'];
                    
                    $div = new node('div');
                    $div->getAttributes()->getClass()->setItems(['spec-organization', 'spec-color', 'spec-audit']);
                    
                    $div_arbiter_link = new node('div');
                    $div_arbiter_link->getAttributes()->getClass()->setItems(['arbiter-link']);
                    
                    foreach ($colors as $key => $color)
                    {
                        $div_arbiter = new node('div');
                        $div_arbiter->getAttributes()->getClass()->addItems('js-arbiter-switch');
                        $div_arbiter->getAttributes()->getClass()->addItems('js-audit-switch');
                        $div_arbiter->getAttributes()->getClass()->addItems($color);
                        $div_arbiter->getAttributes()->setAttr('data-val', $key);
                        
                        if (isset($array['arbiter']) && $key == $array['arbiter'])
                            $div_arbiter->getAttributes()->getClass()->addItems('checked');
                        
                        $fa = new node('fa');
                        $fa->getAttributes()->getClass()->addItems('fa');
                        $fa->getAttributes()->getClass()->addItems('fa-' . $icons[$key]);
                        $fa->getAttributes()->getClass()->addItems('arbiter-icon');
                        
                        $div_arbiter->addChildren($fa);
                        $div_arbiter->getAttributes()->addAttr('title', $title[$key]);
                        $div_arbiter_link->addChildren($div_arbiter);
                    }
                    
                    $div->addChildren($div_arbiter_link);
                    
                    $form = new node('form');
                    $form->getAttributes()->getClass()->setItems(array('form','form-horizontal','form-label-left'));
                    
                    $div_wrapper = new node('div');
                    $div_wrapper->getAttributes()->getClass()->setItems(array('col-md-12', 'col-sm-12', 'col-xs-12', 'form-group', 'form-group-double'));
                    
                    $textarea = new form\textarea();
                    $textarea->setPlaceholder('Комментарий');
                    $textarea->getAttributes()->getClass()->addItems('form-control');
                    $textarea->getAttributes()->addAttr('name', 'comment');
                    $textarea->getAttributes()->addAttr('id', 'audit-comment');
                    
                    if (!empty($array['comment']))
                    {
                        $textarea->setValue($array['comment']);
                        $textarea->getAttributes()->getClass()->addItems('passed');     
                    }
                    
                    $div_wrapper->addChildren($textarea);
                    $form->addChildren($div_wrapper);
                    
                    $div_wrapper = new node('div');
                    $div_wrapper->getAttributes()->getClass()->setItems(array('col-md-12', 'col-sm-12', 'col-xs-12', 'form-group', 'form-group-double'));
                    
                    $select = new form\select();
                    $select->setPlaceholder('Маркер');
                    $select->getAttributes()->getClass()->addItems('form-control');
                    $select->getAttributes()->addAttr('name', 'marker_id');
                    $select->getAttributes()->addAttr('id', 'audit-marker_id');
                    
                    $audit_markers = array(0 => 'Не задан') + load::get_catalog('audit_markers');
                    $select->setValues($audit_markers);
                    
                    if (!empty($array['audit_marker_id']))
                    {
                        $select->setValue($array['audit_marker_id']);
                        $select->getAttributes()->getClass()->addItems('passed');     
                    }
                    
                    $div_wrapper->addChildren($select);
                    $form->addChildren($div_wrapper);
                    
                    $div->addChildren($form);
                    
                    $div_link = new node('div');
                    $div_link->getAttributes()->getClass()->setItems(['pull-right', 'organization-link']);
                    
                    $fa_lock = new node('fa');
                    $fa_lock->getAttributes()->getClass()->addItems('fa');
                    $fa_lock->getAttributes()->getClass()->addItems('fa-lock');
                    
                    $user_id = load::get_user_id();
                
                    $a_black = new node('a');
                    $a_black->addChildren($fa_lock);
                    $a_black->addChildren('&nbsp;в чс');
                    $a_black->getAttributes()->setAttr('href', '#');
                    $a_black->getAttributes()->getClass()->setItems(['js-blacklist']); 
                            
                    $div_link->addChildren($a_black);
                    
                    $a = new node('a');
                    $a->addChildren('сохранить');
                    $a->getAttributes()->setAttr('href', '#');
                    $a->getAttributes()->getClass()->setItems(['js-save-audit']);                
                            
                    $div_link->addChildren($a);
                    
                    $close = new node('a');
                    $close->addChildren('закрыть');
                    $close->getAttributes()->setAttr('href', '#');
                    $close->getAttributes()->getClass()->setItems(['js-close']); 
                        
                    $div_link->addChildren($close);
                    $div->addChildren($div_link);
                    
                    $answer = $div;
                }
                
            break;
            
            case 'show_color_saler':
            
                $partner_order_id = isset($args['partner_order_id']) ? (integer) $args['partner_order_id'] : 0;
                
                if ($partner_order_id) 
                    $mas = load::get_order($partner_order_id, ['date_recall', 'arbiter', 'call_id'], 'partner_orders');
                else
                    $mas = ['date_recall' => '', 'arbiter' => null, 'call_id' => 0];
                
                $div = new node('div');
                $div->getAttributes()->getClass()->setItems(['spec-organization', 'spec-color', 'spec-saler']);
                
                $item = new form\input_box('date');
                $item->setName('date_recall');
                $item->setPlaceholder('Перезвонить');
                $item->getAttributes()->getClass()->addItems('form-control');
                
                if ($mas['date_recall'] && $mas['date_recall'] != '0000-00-00 00:00:00') 
                {
                    $item->setValue($mas['date_recall']);
                    $item->getAttributes()->getClass()->addItems('passed');
                }
                
                $div_link = new node('div');
                $div_link->getAttributes()->getClass()->setItems(['pull-right', 'organization-link']);
                
                $close = new node('a');
                $close->addChildren('закрыть');
                $close->getAttributes()->setAttr('href', '#');
                $close->getAttributes()->getClass()->setItems(['js-close']); 
                    
                $div_link->addChildren($close);
                
                $div->addChildren($item);
                $div->addChildren($div_link);
                
                $answer = $div;
            
            break;
            
            case 'show_color':
            
                break;
                
                //if (load::get_user_id() != 1) break;
                
                $partner_order_id = isset($args['partner_order_id']) ? (integer) $args['partner_order_id'] : 0;
                
                $colors = ['gray', 'green', 'red', 'blue'];
                
                $user_id = load::get_user_id();
                $arbiter_exlude = in_array($user_id, [1, 1517]);
                
                unset($colors[1]);
                
                //if (!$arbiter_exlude) unset($colors[1]);
                
                $icons = ['question', 'thumbs-o-up', 'thumbs-o-down', 'bookmark-o'];
                $title = ['Необработано', 'Передано', 'Отклонено', 'Некому'];
                
                if ($partner_order_id) 
                    $mas = load::get_order($partner_order_id, ['date_recall', 'arbiter', 'call_id'], 'partner_orders');
                else
                    $mas = ['date_recall' => '', 'arbiter' => null, 'call_id' => 0];
                    
                if ($mas['arbiter'] == 1 || $mas['arbiter'] == 4 || $mas['arbiter'] == 5) break;
                    
                if ($mas['call_id'])
                    $organization_id = (integer) load::get_order($mas['call_id'], 'organization_id', 'calls');
                else
                    $organization_id = null;
                    
                if (!in_array($organization_id, [0, 822], true))
                    $colors[0] = 'black';
                
                $div = new node('div');
                $div->getAttributes()->getClass()->setItems(['spec-organization', 'spec-color']);
                
                $div_arbiter_link = new node('div');
                $div_arbiter_link->getAttributes()->getClass()->setItems(['arbiter-link']);
                
                foreach ($colors as $key => $color)
                {
                    $div_arbiter = new node('div');
                    $div_arbiter->getAttributes()->getClass()->addItems('js-arbiter-switch');
                    $div_arbiter->getAttributes()->getClass()->addItems($color);
                    $div_arbiter->getAttributes()->setAttr('data-val', $key);
                    
                    if ($mas['arbiter'] !== null)
                    {
                        if ($key == $mas['arbiter']) $div_arbiter->getAttributes()->getClass()->addItems('checked');
                    }
                    
                    $fa = new node('fa');
                    $fa->getAttributes()->getClass()->addItems('fa');
                    $fa->getAttributes()->getClass()->addItems('fa-' . $icons[$key]);
                    $fa->getAttributes()->getClass()->addItems('arbiter-icon');
                    
                    $div_arbiter->addChildren($fa);
                    $div_arbiter->getAttributes()->addAttr('title', $title[$key]);
                    $div_arbiter_link->addChildren($div_arbiter);
                }
                
                $div->addChildren($div_arbiter_link);
                
                $item = new form\input_box('date');
                $item->setName('date_recall');
                $item->setPlaceholder('Перезвонить');
                $item->getAttributes()->getClass()->addItems('form-control');
                
                if ($mas['date_recall'] && $mas['date_recall'] != '0000-00-00 00:00:00') 
                {
                    $item->setValue($mas['date_recall']);
                    $item->getAttributes()->getClass()->addItems('passed');
                }
                
                $div_link = new node('div');
                $div_link->getAttributes()->getClass()->setItems(['pull-right', 'organization-link']);
                
                $close = new node('a');
                $close->addChildren('закрыть');
                $close->getAttributes()->setAttr('href', '#');
                $close->getAttributes()->getClass()->setItems(['js-close']); 
                    
                $div_link->addChildren($close);
                
                $div->addChildren($item);
                $div->addChildren($div_link);
                
                $answer = $div;
            
            break;
            
            case 'save_date_recall_resale':
                
                $value = isset($args['value']) ? (string) $args['value'] : '';
                $arbiter = isset($args['arbiter']) ? (integer) $args['arbiter'] : '';
                $partner_order_id = isset($args['partner_order_id']) ? (integer) $args['partner_order_id'] : 0;
                $call_id = isset($args['call_id']) ? (integer) $args['call_id'] : 0;
                $is_saler = isset($args['is_saler']) ? $args['is_saler'] : 0;
                $change_marker = isset($args['change_marker']) ? $args['change_marker'] : 0;
                
                if ($call_id)
                {
                    $color_0 = 'gray';
                    
                    if ($call_id)
                        $organization_id = (integer) load::get_order($call_id, 'organization_id', 'calls');
                    else
                        $organization_id = null;
                        
                    if (!in_array($organization_id, [0, 822], true))
                        $color_0 = 'black';
                    
                    if (!$partner_order_id)
                    {
                        $call_array = array('mode' => 'add', 'table' => 'partner_orders', 'call_id' => $call_id);
                        $obj_partner = new term($call_array);
                        $partner_order_id = (integer) $obj_partner->getWrapper()->getChildren(0);
                    }
                    
                    if (!$value) $value = null;
                    
                    $user_id = load::get_user_id();
                    $time = date('Y-m-d H:i:s', tools::get_time());
                    
                    if ($value === null) 
                    {
                        $user_id = null;
                        $time = null;
                    }
                    
                    $obj_array = array('mode' => 'update', 'table' => 'partner_orders', 'id' => $partner_order_id, 'date_recall' => $value, 'arbiter' => $arbiter,
                                                    'date_arbiter' => $time, 'user_arbiter' => $user_id);
                                                    
                    if ($is_saler && !$change_marker) unset($obj_array['arbiter']);
                    
                    $obj = new term($obj_array);
                                        
                    $obj_id = $obj->getWrapper()->getChildren(0);   
                    
                    $div_arbiter = new node('div');
                    $div_arbiter->getAttributes()->getClass()->addItems('js-arbiter');
                    
                    if ($is_saler) $div_arbiter->getAttributes()->getClass()->addItems('js-saler');
                    
                    if ($value)
                    {
                        $div_arbiter->getAttributes()->getClass()->addItems('orange');
                        $data_tooltip = 'Перезвонить';
                    }
                    else
                    {
                        $div_arbiter->getAttributes()->getClass()->addItems($color_0);    
                        $data_tooltip = 'Необработано';
                    }
                    
                    $br = new node('br', false);
                    
                    if ($user_id)
                    {
                        $sql = "SELECT `workers`.`name` FROM `users`
                                      LEFT JOIN `workers` ON `users`.`worker_id` = `workers`.`id`
                                            WHERE `users`.`id`=:user_id";
                        $stm = pdo::getPdo()->prepare($sql); 
                        $stm->execute(array('user_id' => $user_id));
                        $user_arbiter = $stm->fetchColumn(); 
                        
                        if ($user_arbiter)
                        {
                            $name = explode(' ', $user_arbiter);
                    
                            if (isset($name[1]))
                                $add = $name[0] . ' ' . mb_substr($name[1], 0, 1) . '.';
                            else
                                $add = $name[0];
                                
                            $data_tooltip .= (string) $br;
                            $data_tooltip .= $add;
                        }
                        
                        if ($time)
                        {
                            $data_tooltip .= (string) $br;
                            $data_tooltip .= date('d.m.y H:i', strtotime($time));
                        }
                    }
                    
                    $div_arbiter->getAttributes()->addAttr('data-tooltip', htmlspecialchars($data_tooltip));
                    
                    //$this->_update_group($call_id, 3);
                                            
                    $checkbox = $div_arbiter; 
                        
                    $answer = (string) $checkbox.'|'.$obj_id;
                }
                
            break;
            
            case 'save_date_recall':
            
                $value = isset($args['value']) ? (string) $args['value'] : '';
                $partner_order_id = isset($args['partner_order_id']) ? (integer) $args['partner_order_id'] : 0;
                $call_id = isset($args['call_id']) ? (integer) $args['call_id'] : 0;
                
                if ($call_id) {
                    if (!$partner_order_id) {
                        $call_array = array('mode' => 'add', 'table' => 'partner_orders', 'call_id' => $call_id);
                        $obj_partner = new term($call_array);
                        $partner_order_id = (integer) $obj_partner->getWrapper()->getChildren(0);
                    }
                    
                    if (!$value) $value = null;
                    
                    $response = $this->_update_group($call_id, 3);

                    if ($response) {
                        $obj = new term(array('mode' => 'update', 'table' => 'partner_orders', 'id' => $partner_order_id, 'date_recall' => $value, 'arbiter' => null,
                                                    'date_arbiter' => null));
                    }
                    else {
                        $obj = new term(
                            array(
                                'mode' => 'add',
                                'table' => 'notifys',
                                'text' => 'Меняется только последний звонок!',
                                'type_notify_id' => load::get_status('type_notifys', 'update_error'),
                                'session' => session_id()
                            )
                        );
                    }
                    $obj_id = $obj->getWrapper()->getChildren(0);   
                    
                    $div_arbiter = new node('div');
                    $div_arbiter->getAttributes()->getClass()->addItems('js-arbiter');
                    
                    if ($response) {
                        if ($value) {
                            $div_arbiter->getAttributes()->getClass()->addItems('orange');
                            $div_arbiter->getAttributes()->addAttr('data-tooltip', 'Перезвонить');
                        }
                        else {
                            $div_arbiter->getAttributes()->getClass()->addItems('gray');  
                            $div_arbiter->getAttributes()->addAttr('data-tooltip', 'Необработано');  
                        }
                    }
                    else {
                        $sql = "
                            SELECT `arbiter`, `date_recall`
                            FROM `partner_orders`
                            WHERE `call_id`=:call_id
                        ";
                        $colors = ['gray', 'green', 'red' ];
                        $stm = pdo::getPdo()->prepare($sql);
                        $stm->execute(['call_id' => $call_id]);
                        $same_color = $stm->fetchAll(\PDO::FETCH_ASSOC);
                        if ($same_color) {
                            $same_color = current($same_color);
                            if ($same_color['arbiter'] != NULL) {
                                $same_color = (integer) $same_color['arbiter'];
                                $div_arbiter->getAttributes()->getClass()->addItems($colors[$same_color]);
                            }
                            else if ($same_color['arbiter'] == NULL && $same_color['date_recall'] == NULL) {
                                $div_arbiter->getAttributes()->getClass()->addItems('gray');
                                $div_arbiter->getAttributes()->addAttr('data-tooltip', 'Необработано');
                            }
                            else if ($same_color['arbiter'] == NULL && $same_color['date_recall'] != NULL) {
                                $div_arbiter->getAttributes()->getClass()->addItems('orange');
                                $div_arbiter->getAttributes()->addAttr('data-tooltip', 'Перезвонить');
                            }
                        }                            
                    }
                    
                    $checkbox = $div_arbiter;                         
                    $answer = (string) $checkbox.';'.$obj_id;                                                                                 
                }                
            
            break;
            
            case 'audit_action':
            
                $value = isset($args['value']) ? (integer) $args['value'] : 0;
                $partner_order_id = isset($args['partner_order_id']) ? (integer) $args['partner_order_id'] : 0;
                $call_id = isset($args['call_id']) ? (integer) $args['call_id'] : 0;
                $show_color = isset($args['show_color']) ? (bool) $args['show_color'] : false;
                $comment = isset($args['comment']) ? (string) $args['comment'] : '';
                $marker_id = isset($args['marker_id']) ? (integer) $args['marker_id'] : 0;
                
                if ($call_id)
                {
                    $obj_id = $partner_order_id;
                    
                    $user_id = load::get_user_id();
                    $time = date('Y-m-d H:i:s', tools::get_time());
                    $comment = htmlspecialchars(strip_tags($comment));
                    
                    $sql = "INSERT IGNORE INTO `audit_comments` (`partner_order_id`, `comment`, `audit_marker_id`, `user_id`, `date`, `arbiter`) 
                                VALUES ({$partner_order_id}, '{$comment}', {$marker_id}, {$user_id}, '{$time}', {$value})
                             ON DUPLICATE KEY UPDATE `comment` = '{$comment}', `audit_marker_id` = {$marker_id}, `user_id` = {$user_id}, `date` = '{$time}', `arbiter` = {$value}";
                    pdo::getPdo()->query($sql);
                    
                    if ($show_color)
                    {
                        $br = new node('br', false);
                        
                        $colors = [0 => 'gray', 1 => 'green', 2 => 'red'];
                        $tooltips = [0 => 'Не прослушано', 1 => 'Хороший', 2 => 'Плохой'];
                        
                        $div_arbiter = new node('div');
                        $div_arbiter->getAttributes()->getClass()->addItems('js-arbiter');
                        $div_arbiter->getAttributes()->getClass()->addItems('js-audit');
                        
                        $div_arbiter->getAttributes()->getClass()->addItems($colors[$value]);
                        $div_arbiter->getAttributes()->getClass()->addItems('approve-square');
                
                        $data_tooltip = $tooltips[$value];
                        
                        if ($comment)
                        {
                            $data_tooltip .= (string) $br;
                            $data_tooltip .= $comment;
                        }
                        
                        if ($marker_id)
                        {
                            $data_tooltip .= (string) $br;
                            $data_tooltip .= tools::mb_ucfirst2(load::get_order($marker_id, 'name', 'audit_markers'));
                        }
                        
                        if ($user_id)
                        {
                            $sql = "SELECT `workers`.`name` FROM `users`
                                          LEFT JOIN `workers` ON `users`.`worker_id` = `workers`.`id`
                                                WHERE `users`.`id`=:user_id";
                            $stm = pdo::getPdo()->prepare($sql); 
                            $stm->execute(array('user_id' => $user_id));
                            $user_arbiter = $stm->fetchColumn(); 
                            
                            if ($user_arbiter)
                            {
                                $name = explode(' ', $user_arbiter);
                        
                                if (isset($name[1]))
                                    $add = $name[0] . ' ' . mb_substr($name[1], 0, 1) . '.';
                                else
                                    $add = $name[0];
                                    
                                $data_tooltip .= (string) $br;
                                $data_tooltip .= $add;
                            }
                        }
                        
                        if ($time)
                        {
                            $data_tooltip .= (string) $br;
                            $data_tooltip .= date('d.m.y H:i', strtotime($time));
                        }
                        
                        $div_arbiter->getAttributes()->addAttr('data-tooltip', htmlspecialchars($data_tooltip));
                        
                        $checkbox = $div_arbiter;
                    } 
                    
                    $answer = (string) $checkbox.'|'.$obj_id;
                }
                
            break;
            
            case 'audit_blacklist':
            
                $call_id = isset($args['call_id']) ? (integer) $args['call_id'] : 0;
                
                if ($call_id)
                {
                    $phone_id = load::get_order($call_id, 'phone_id', 'calls');
                    $phone_name = load::get_order($phone_id, 'name', 'phones');
                    
                    $timestamp = load::get_order($call_id, 'timestamp', 'calls');
                    $timestamp = date('d.m.y H:i', strtotime($timestamp));
                    
                    $sql = "SELECT `offer_id` FROM `partner_orders` WHERE `call_id`=:call_id";
                    $stm = pdo::getPdo()->prepare($sql);
                    $stm->execute(['call_id' => $call_id]); 
                    $offer_id = $stm->fetchColumn();
                    
                    $parent = load::get_order($offer_id, 'parent', 'offers');
                    if ($parent) $offer_id = $parent;
                    
                    $black_obj = new blacklist(['mode' => 'record', 'offer_id' => $offer_id, 
                                'comment' => 'на основании звонка '.$call_id.' от '.$timestamp, 'phones' => $phone_name]);
                }
            
            break;
            
            case 'approve_action_resale':
            
                $value = isset($args['value']) ? (integer) $args['value'] : 0;
                $partner_order_id = isset($args['partner_order_id']) ? (integer) $args['partner_order_id'] : 0;
                $call_id = isset($args['call_id']) ? (integer) $args['call_id'] : 0;
                $show_color = isset($args['show_color']) ? (bool) $args['show_color'] : false;
                $pass = isset($args['pass']) ? (bool) $args['pass'] : null;
                $all_to_cashback = isset($args['all_to_cashback']) ? $args['all_to_cashback'] : false;
                
                if ($call_id)
                {
                    if (!$partner_order_id)
                    {
                        $call_array = array('mode' => 'add', 'table' => 'partner_orders', 'call_id' => $call_id);
                        $obj_partner = new term($call_array);
                        $partner_order_id = (integer) $obj_partner->getWrapper()->getChildren(0);
                    }
                    
                    $organization_id = load::get_order($call_id, 'organization_id', 'calls');
                    $modr = load::get_order($organization_id, 'modr', 'organizations');
                    
                    $user_id = load::get_user_id();
                    $time = date('Y-m-d H:i:s', tools::get_time());

                    $all_to_cashback_flag = $userAccess->isUserHasRight($user_id, 'all_to_cashback');
                    if ($all_to_cashback_flag) $all_to_cashback = true;
                     
                    if (!isset($pass))
                    {
                        $pass = false;
                        $ex_session = in_array($user_id, load::get_auditor()) || $all_to_cashback_flag;

                        if (lid::isCashbackOrg($organization_id) && ($ex_session || $user_id == $modr)) $pass = true;
                    }
                    
                    if ($pass)
                    {
                        $no_pass_cash = false;
                    
                        $sql = "SELECT `approve`, `id`, `resale_call_id` FROM `cashbacks` WHERE `call_id`=:call_id";
                        $stm = pdo::getPdo()->prepare($sql);
                        $stm->execute(['call_id' => $call_id]); 
                        $approve_array = (array) $stm->fetch(\PDO::FETCH_ASSOC);
                        
                        if (!empty($approve_array['approve']))
                        {
                            $cashback_obj = new cashback(array('mode' => 'send_return', 'call_id' => $call_id, 'check_in_base' => false, 'resale_call_id' => $approve_array['resale_call_id']));
                            
                            if ($cashback_obj->getCode() == 'success')
                            {
                                $sql = "DELETE FROM `cashbacks` WHERE `id`=:id";
                                $stm = pdo::getPdo()->prepare($sql);
                                $stm->execute(['id' => $approve_array['id']]); 
                            }
                            else
                            {
                                $no_pass_cash = true;
                            }
                        }
                        
                        if (!$no_pass_cash)                      
                        {
                            $checkbox  = '';
                            $obj_id = $partner_order_id; 
                            
                            switch ($value)
                            {
                                case 1:
                                    
                                    $sql = "SELECT `type`, `resale_call_id`, `transaction_id`, `id` FROM `cashbacks` WHERE `call_id`=:call_id";
                                    $stm = pdo::getPdo()->prepare($sql);
                                    $stm->execute(['call_id' => $call_id]);
                                    $cashback_array = $stm->fetch(\PDO::FETCH_ASSOC);
                                    
                                    $double_summ = 0;
                                    
                                    if ($cashback_array)
                                    {
                                        $sql = "SELECT `transactions`.`summ`, `transactions`.`double_summ`  FROM `transactions`
                                                        WHERE `id`=:transaction_id";
                                        $stm = pdo::getPdo()->prepare($sql);
                                        $stm->execute(['transaction_id' => $cashback_array['transaction_id']]);
                                        $transaction_array = $stm->fetch(\PDO::FETCH_ASSOC);
                                        
                                        if ($cashback_array['type'] == 0 && !$all_to_cashback)
                                        {
                                            if ($cashback_array['resale_call_id'])
                                            {
                                                $sql = "SELECT `transactions`.`id` FROM `transactions`
                                                                INNER JOIN `connectors` ON `connectors`.`b` = `transactions`.`call_id`
                                                                        WHERE `connectors`.`a`=:resale_call_id
                                                                            AND `transactions`.`summ` != 0";
                                                
                                                $stm = pdo::getPdo()->prepare($sql);
                                                $stm->execute(['resale_call_id' => $cashback_array['resale_call_id']]);
                                                $transaction_id = $stm->fetchColumn(); 
                                                
                                                $transaction_summ = abs($transaction_array['summ']); //dont work with double
                                                
                                                if ($transaction_id)
                                                {
                                                    $summ = (-1) * floor($transaction_summ * 0.7);
                                                    $double_summ = (-1) * ($transaction_summ + $summ);
                                                    
                                                    $sql = "UPDATE `transactions` SET `double_summ`=:double_summ, `summ`=:summ WHERE `call_id`=:call_id";
                                                    $stm = pdo::getPdo()->prepare($sql); 
                                                    $stm->execute(array('call_id' => $call_id, 'double_summ' => $double_summ, 'summ' => $summ));
                                                }
                                            }
                                        }
                                        else
                                        {
                                            $summ = 0;
                                            $double_summ = (integer) $transaction_array['summ'] + (integer) $transaction_array['double_summ'];
                                        
                                            $sql = "UPDATE `transactions` SET `double_summ`=:double_summ, `summ`=:summ WHERE `call_id`=:call_id";
                                            $stm = pdo::getPdo()->prepare($sql); 
                                            $stm->execute(array('call_id' => $call_id, 'double_summ' => $double_summ, 'summ' => $summ));
                                            
                                            $sql = "UPDATE `resaler_payes` SET `no_active` = 1, `double_summ` = `summ`, `summ` = 0 WHERE `call_id`=:call_id";
                                            $stm = pdo::getPdo()->prepare($sql); 
                                            $stm->execute(array('call_id' => $call_id));
                                        }
                                    }
                                    
                                    $sql = "UPDATE `cashbacks` SET `approve` = 1, `user_arbiter`=:user_id, `date_arbiter`=:date_arbiter WHERE `call_id`=:call_id";
                                    $stm = pdo::getPdo()->prepare($sql);
                                    $stm->execute(['call_id' => $call_id, 'user_id' => $user_id, 'date_arbiter' => $time]);
                                    
                                    $sql = "UPDATE `web_waits` SET `pay` = 0 WHERE `call_id`=:call_id";
                                    $stm = pdo::getPdo()->prepare($sql);
                                    $stm->execute(['call_id' => $call_id]);
                                        
                                    log::log(['id' => $partner_order_id, 'cashback' => '1,'. abs($double_summ)], 'partner_orders');
                                    
                                    $params = array('field' => 'cashback', 'class' => 'btn-success');
                    
                                    $count = log::log_count('partner_orders', 'cashback', $partner_order_id);
                                        
                                    if ($count > 1)
                                    {
                                        $params['log_count'] = $count;
                                    }
                                    
                                    $notifys = new term(
                                        array(
                                            'mode' => 'add', 
                                            'table' => 'notifys',
                                            'text' => 'Возврат '. abs($double_summ), 
                                            'type_notify_id' => load::get_status('type_notifys', 'update_ciba_excel'),
                                            'table_name' => 'partner_orders',
                                            'record_id' => $partner_order_id, 
                                            'params' => serialize($params),
                                            'organization_id' => $organization_id,
                                            )
                                        );
                                    
                                break;
                                
                                case 2:                         
                                    
                                    $sql = "SELECT `type`, `resale_call_id`, `transaction_id`, `id` FROM `cashbacks` WHERE `call_id`=:call_id";
                                    $stm = pdo::getPdo()->prepare($sql);
                                    $stm->execute(['call_id' => $call_id]);
                                    $cashback_array = $stm->fetch(\PDO::FETCH_ASSOC);
                                    
                                    if ($cashback_array)
                                    {
                                        $sql = "SELECT `transactions`.`summ`, `transactions`.`double_summ` FROM `transactions`
                                                            WHERE `id`=:transaction_id";
                                        $stm = pdo::getPdo()->prepare($sql);
                                        $stm->execute(['transaction_id' => $cashback_array['transaction_id']]);
                                        $transaction_array = $stm->fetch(\PDO::FETCH_ASSOC);
                                        
                                        $summ = (integer) $transaction_array['summ'] + (integer) $transaction_array['double_summ'];
                                        
                                        $sql = "UPDATE `transactions` SET `summ`=:summ, `double_summ` = 0 WHERE `call_id`=:call_id";
                                        $stm = pdo::getPdo()->prepare($sql); 
                                        $stm->execute(array('call_id' => $call_id, 'summ' => $summ));
                                        
                                        $sql = "UPDATE `resaler_payes` SET `no_active` = 0, `summ` = `double_summ`, `double_summ` = 0 WHERE `call_id`=:call_id";
                                        $stm = pdo::getPdo()->prepare($sql); 
                                        $stm->execute(array('call_id' => $call_id));
                                    }
                                    
                                    $sql = "UPDATE `cashbacks` SET `approve` = 2, `user_arbiter`=:user_id, `date_arbiter`=:date_arbiter WHERE `call_id`=:call_id";
                                    $stm = pdo::getPdo()->prepare($sql);
                                    $stm->execute(['call_id' => $call_id, 'user_id' => $user_id, 'date_arbiter' => $time]); 
                                    
                                    $sql = "UPDATE `web_waits` SET `minute_lost` = `minute_payment` - 2, `pay` = NULL WHERE `call_id`=:call_id";
                                    $stm = pdo::getPdo()->prepare($sql);
                                    $stm->execute(['call_id' => $call_id]);
                                    
                                    log::log(['id' => $partner_order_id, 'cashback' => 2], 'partner_orders');
                                    
                                    $params = array('field' => 'cashback', 'class' => 'btn-danger');
                    
                                    $count = log::log_count('partner_orders', 'cashback', $partner_order_id);
                                        
                                    if ($count > 1)
                                    {
                                        $params['log_count'] = $count;
                                    }
                                    
                                    $notifys = new term(
                                        array(
                                            'mode' => 'add', 
                                            'table' => 'notifys',
                                            'text' => 'Возврат отклонен', 
                                            'type_notify_id' => load::get_status('type_notifys', 'update_ciba_excel'),
                                            'table_name' => 'partner_orders',
                                            'record_id' => $partner_order_id, 
                                            'params' => serialize($params),
                                            'organization_id' => $organization_id,
                                            )
                                        );
                                    
                                break;
                            }
                             
                            if ($show_color)
                            {
                                $br = new node('br', false);
                                
                                $colors = [1 => 'green', 2 => 'red'];
                                $tooltips = [1 => 'Одобрен', 2 => 'Отклонен'];
                                
                                $div_arbiter = new node('div');
                                $div_arbiter->getAttributes()->getClass()->addItems('js-arbiter');
                                $div_arbiter->getAttributes()->getClass()->addItems('js-approve');
                                
                                if ($value == 1)
                                {
                                    $fa = new node('fa');
                                    $fa->getAttributes()->getClass()->addItems('fa');
                                    $fa->getAttributes()->getClass()->addItems('fa-thumbs-o-up');
                                    $div_arbiter->addChildren($fa);                             
                                }
                                
                                if ($value == 2)
                                {
                                    $fa = new node('fa');
                                    $fa->getAttributes()->getClass()->addItems('fa');
                                    $fa->getAttributes()->getClass()->addItems('fa-thumbs-o-down');
                                    $div_arbiter->addChildren($fa);                     
                                }
                                
                                $div_arbiter->getAttributes()->getClass()->addItems($colors[$value]);
                                $div_arbiter->getAttributes()->getClass()->addItems('approve-square');
                        
                                $data_tooltip = $tooltips[$value];
                                
                                if ($user_id)
                                {
                                    $sql = "SELECT `workers`.`name` FROM `users`
                                                  LEFT JOIN `workers` ON `users`.`worker_id` = `workers`.`id`
                                                        WHERE `users`.`id`=:user_id";
                                    $stm = pdo::getPdo()->prepare($sql); 
                                    $stm->execute(array('user_id' => $user_id));
                                    $user_arbiter = $stm->fetchColumn(); 
                                    
                                    if ($user_arbiter)
                                    {
                                        $name = explode(' ', $user_arbiter);
                                
                                        if (isset($name[1]))
                                            $add = $name[0] . ' ' . mb_substr($name[1], 0, 1) . '.';
                                        else
                                            $add = $name[0];
                                            
                                        $data_tooltip .= (string) $br;
                                        $data_tooltip .= $add;
                                    }
                                }
                                
                                if ($time)
                                {
                                    $data_tooltip .= (string) $br;
                                    $data_tooltip .= date('d.m.y H:i', strtotime($time));
                                }
                                
                                $div_arbiter->getAttributes()->addAttr('data-tooltip', htmlspecialchars($data_tooltip));
                                
                                $checkbox = $div_arbiter;
                            } 
                            
                            $answer = (string) $checkbox.'|'.$obj_id;
                        }
                    }                    
                }
                
            break;
            
            case 'arbiter_action_resale':
                
                $value = isset($args['value']) ? (integer) $args['value'] : 0;
                $partner_order_id = isset($args['partner_order_id']) ? (integer) $args['partner_order_id'] : 0;
                $call_id = isset($args['call_id']) ? (integer) $args['call_id'] : 0;
                $show_color = isset($args['show_color']) ? (bool) $args['show_color'] : false;
                $is_saler = isset($args['is_saler']) ? $args['is_saler'] : 0;
                
                if ($call_id)
                {
                    if (!$partner_order_id)
                    {
                        $call_array = array('mode' => 'add', 'table' => 'partner_orders', 'call_id' => $call_id);
                        $obj_partner = new term($call_array);
                        $partner_order_id = (integer) $obj_partner->getWrapper()->getChildren(0);
                    }
                        
                    $checkbox = new form\checkbox();
                    $checkbox->getAttributes()->getClass()->addItems('js-switch');
                    
                    $time = (!$value) ? null : date('Y-m-d H:i:s', tools::get_time());
                    $user_id = (!$value) ? null : load::get_user_id();
                                   
                    if (!$value) 
                    {
                        $checkbox->getAttributes()->getClass()->addItems('js-switch-disabled');
                        $checkbox->getAttributes()->addAttr('readonly', 'readonly');
                        $obj = new term(array('mode' => 'update', 'table' => 'partner_orders', 'id' => $partner_order_id, 'arbiter' => $value, 
                                'date_arbiter' => $time, 'date_recall' => null, 'user_arbiter' => $user_id));
                                
                        //$this->_update_group($call_id, 0);
                    }
                    else
                    {
                        $checkbox->getAttributes()->getClass()->addItems('js-switch-enabled');
                        
                        if ($value == 1) 
                        {
                            $checkbox->getAttributes()->addAttr('checked', 'checked');
                            $obj = new term(array('mode' => 'update', 'table' => 'partner_orders', 'id' => $partner_order_id, 'arbiter' => $value, 
                                'date_arbiter' => $time, 'date_recall' => null, 'user_arbiter' => $user_id));
                                
                            //$this->_update_group($call_id, 1);
                        }
                        else
                        {
                            $checkbox->getAttributes()->addAttr('readonly', 'readonly');
                            $obj = new term(array('mode' => 'update', 'table' => 'partner_orders', 'id' => $partner_order_id, 'arbiter' => $value, 
                                'date_arbiter' => $time, 'date_recall' => null, 'user_arbiter' => $user_id));
                                
                            //$this->_update_group($call_id, 2);
                        }  
                    }
                                
                    $obj_id = $obj->getWrapper()->getChildren(0);   
                    
                    if ($show_color)
                    {
                        $br = new node('br', false);
                        
                        $colors = ['gray', 'green', 'red', 'blue'];
                                  
                        if ($call_id)
                            $organization_id = (integer) load::get_order($call_id, 'organization_id', 'calls');
                        else
                            $organization_id = null;
                            
                        if (!in_array($organization_id, [0, 822], true))
                            $colors[0] = 'black';
                        
                        $tooltips = ['Необработано', 'Передано', 'Отклонено', 'Некому'];
                        $div_arbiter = new node('div');
                        $div_arbiter->getAttributes()->getClass()->addItems('js-arbiter');
                        $div_arbiter->getAttributes()->getClass()->addItems($colors[$value]);
                        
                        //if ($is_saler) $div_arbiter->getAttributes()->getClass()->addItems('js-saler');
                
                        $data_tooltip = $tooltips[$value];
                        
                        if ($user_id)
                        {
                            $sql = "SELECT `workers`.`name` FROM `users`
                                          LEFT JOIN `workers` ON `users`.`worker_id` = `workers`.`id`
                                                WHERE `users`.`id`=:user_id";
                            $stm = pdo::getPdo()->prepare($sql); 
                            $stm->execute(array('user_id' => $user_id));
                            $user_arbiter = $stm->fetchColumn(); 
                            
                            if ($user_arbiter)
                            {
                                $name = explode(' ', $user_arbiter);
                        
                                if (isset($name[1]))
                                    $add = $name[0] . ' ' . mb_substr($name[1], 0, 1) . '.';
                                else
                                    $add = $name[0];
                                    
                                $data_tooltip .= (string) $br;
                                $data_tooltip .= $add;
                            }
                        }
                        
                        if ($time)
                        {
                            $data_tooltip .= (string) $br;
                            $data_tooltip .= date('d.m.y H:i', strtotime($time));
                        }
                        
                        $div_arbiter->getAttributes()->addAttr('data-tooltip', htmlspecialchars($data_tooltip));
                        
                        $checkbox = $div_arbiter;
                    }
                    
                    $work_str = '';
                    
                    //if (load::get_user_id() == 1)
                    //{
                        if ($value == 3)
                        {
                            //$steps = '3075-3067,3171,3139,3223,3367,3118';

                            $sql = "SELECT `value` FROM `customs` WHERE `code`='action_steps'";
                            $steps = pdo::getPdo()->query($sql)->fetchColumn();

                            $step = 0;
                            exec("php ".\DOCUMENT_ROOT."admin/index.php op=resale_work args[mode]=start_send args[call_id]=$call_id args[partner_order_id]=$partner_order_id args[step]=$step args[steps]=$steps > /dev/null &", $output, $return_var);                                
                        }
                        
                        if ($value == 2)
                        {
                            //$index = rand(0, 0);
                            //exec("php ".\DOCUMENT_ROOT."admin/index.php op=resale_work args[mode]=send_evak args[call_id]=$call_id args[index]=$index > /dev/null &", $output, $return_var);
                        }
                    //}
                                       
                    $answer = ($work_str) ? ($work_str) : ((string) $checkbox.'|'.$obj_id);
                }                
            
            break;
            
            case 'arbiter_action':
            
                $value = isset($args['value']) ? (integer) $args['value'] : 0;
                $partner_order_id = isset($args['partner_order_id']) ? (integer) $args['partner_order_id'] : 0;
                $call_id = isset($args['call_id']) ? (integer) $args['call_id'] : 0;
                $show_color = isset($args['show_color']) ? (bool) $args['show_color'] : false;
                
                if ($call_id)
                {
                    if (!$partner_order_id)
                    {
                        $call_array = array('mode' => 'add', 'table' => 'partner_orders', 'call_id' => $call_id);
                        $obj_partner = new term($call_array);
                        $partner_order_id = (integer) $obj_partner->getWrapper()->getChildren(0);
                    }
                        
                    $checkbox = new form\checkbox();
                    $checkbox->getAttributes()->getClass()->addItems('js-switch');
                    $time = date('Y-m-d H:i:s', tools::get_time());
                                   
                    if (!$value) { // == 0?
                        $checkbox->getAttributes()->getClass()->addItems('js-switch-disabled');
                        $checkbox->getAttributes()->addAttr('readonly', 'readonly');
                                                        
                        $response = $this->_update_group($call_id, 0);
                        if ($response) {
                            $obj = new term(array('mode' => 'update', 'table' => 'partner_orders', 'id' => $partner_order_id, 'arbiter' => $value, 
                                'date_arbiter' => $time, 'date_recall' => null));
                        }
                        else {
                            $obj = new term(
                                array(
                                    'mode' => 'add',
                                    'table' => 'notifys',
                                    'text' => 'Меняется только последний звонок!',
                                    'type_notify_id' => load::get_status('type_notifys', 'update_error'),
                                    'session' => session_id()
                                )
                            );
                        }
                    }
                    else {
                        $checkbox->getAttributes()->getClass()->addItems('js-switch-enabled');
                        
                        if ($value == 1) {
                            $checkbox->getAttributes()->addAttr('checked', 'checked');
                            $response = $this->_update_group($call_id, 1);
                            if ($response) {
                                $obj = new term(array('mode' => 'update', 'table' => 'partner_orders', 'id' => $partner_order_id, 'arbiter' => $value, 
                                'date_arbiter' => $time, 'date_recall' => null));
                            }
                            else {
                                $obj = new term(
                                    array(
                                        'mode' => 'add',
                                        'table' => 'notifys',
                                        'text' => 'Меняется только последний звонок!',
                                        'type_notify_id' => load::get_status('type_notifys', 'update_error'),
                                        'session' => session_id()
                                    )
                                );
                            }
                        }
                        else {
                            $checkbox->getAttributes()->addAttr('readonly', 'readonly');
                            $response = $this->_update_group($call_id, 2);
                            if ($response) {
                                $obj = new term(array('mode' => 'update', 'table' => 'partner_orders', 'id' => $partner_order_id, 'arbiter' => $value, 
                                'date_arbiter' => $time, 'date_recall' => null));
                            }
                            else {
                                $obj = new term(
                                    array(
                                        'mode' => 'add',
                                        'table' => 'notifys',
                                        'text' => 'Меняется только последний звонок!',
                                        'type_notify_id' => load::get_status('type_notifys', 'update_error'),
                                        'session' => session_id()
                                    )
                                );
                            }                              
                        }  
                    }
                                
                    $obj_id = $obj->getWrapper()->getChildren(0);   
                    
                    if ($show_color) {
                        $colors = ['gray', 'green', 'red' ];
                        $tooltips = ['Необработано', 'Передано', 'Отклонено'];
                        $div_arbiter = new node('div');
                        $div_arbiter->getAttributes()->getClass()->addItems('js-arbiter');
                        if ($response) {
                            $div_arbiter->getAttributes()->getClass()->addItems($colors[$value]);
                            $div_arbiter->getAttributes()->addAttr('data-tooltip', $tooltips[$value]);
                        }
                        else {
                            $sql = "
                                SELECT `arbiter`, `date_recall`
                                FROM `partner_orders`
                                WHERE `call_id`=:call_id
                            ";
                            $stm = pdo::getPdo()->prepare($sql);
                            $stm->execute(['call_id' => $call_id]);
                            $same_color = $stm->fetchAll(\PDO::FETCH_ASSOC);
                            if ($same_color) {
                                $same_color = current($same_color);
                                if ($same_color['arbiter'] != NULL) {
                                    $same_color = (integer) $same_color['arbiter'];
                                    $div_arbiter->getAttributes()->getClass()->addItems($colors[$same_color]);
                                    $div_arbiter->getAttributes()->addAttr('data-tooltip', $colors[$value]);
                                }
                                else if ($same_color['arbiter'] == NULL && $same_color['date_recall'] == NULL) {
                                    $div_arbiter->getAttributes()->getClass()->addItems('gray');
                                    $div_arbiter->getAttributes()->addAttr('data-tooltip', 'Необработано');
                                }
                                else if ($same_color['arbiter'] == NULL && $same_color['date_recall'] != NULL) {
                                    $div_arbiter->getAttributes()->getClass()->addItems('orange');
                                    $div_arbiter->getAttributes()->addAttr('data-tooltip', 'Перезвонить');
                                    
                                }
                            }                            
                        }
                        $checkbox = $div_arbiter;
                    }                   
                    $answer = (string) $checkbox.';'.$obj_id;                    
                }
            
            break;
        }
        
        $this->getWrapper()->addChildren($answer);
    }
    
    private function _update_group($call_id, $offset)
    {
        $sql = "
            SELECT `id`, `color` 
            FROM `call_groups`
            WHERE `call_id`=:call_id
        ";
        $stm = pdo::getPdo()->prepare($sql);
        $stm->execute(['call_id' => $call_id]);
        $call_group_array = $stm->fetchAll(\PDO::FETCH_ASSOC);
        
        if ($call_group_array) {
            $call_group_array = current($call_group_array);
            
            $color = (string) $call_group_array['color'];
            $call_group_id = $call_group_array['id'];            
            
            $t = [0, 0, 0, 0];
            $t[$offset] = 1;                                    

            $color_str = '';
            
            foreach ($t as $value) {
                $color_str .= $value;
            }
            $update_obj = new term(array('mode' => 'update', 'table' => 'call_groups', 'id' => $call_group_id, 'color' => $color_str));
            $response = true;
        }
        else {
            $response = false;
        }
        return $response;
    }
}

?>