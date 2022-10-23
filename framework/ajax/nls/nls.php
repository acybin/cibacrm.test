<?

namespace framework\ajax\nls;

use framework\ajax as ajax;
use framework\ajax\term\term;
use framework\enum;
use framework\dotenv;
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

class nls extends ajax\ajax
{
    public function __construct($args)
    {
        parent::__construct('nls');
          
        error_reporting(E_ALL);
        ini_set('display_errors', 1);

        $mode = isset($args['mode']) ? (string) $args['mode'] : 'show';
        $answer = '';  
        
        $this->getWrapper()->getAttributes()->addAttr('id', 'nls-'.$mode);
                        
        $enum = new enum();
        $enum->setSign('');
        
        switch ($mode)
        {
             case 'region':
                
                $args['color'] = 'green';
                $navy = new navy($args);
                $enum->addItems($navy->getWrapper()->getChildren(0));
             
             break;
             
             case 'traffic':
                
                $args['color'] = 'green';
                $navy = new navy($args);
                $enum->addItems($navy->getWrapper()->getChildren(0));
                
             break;
             
             case 'setka_type':
             
                $args['color'] = 'green';
                $navy = new navy($args);
                $enum->addItems($navy->getWrapper()->getChildren(0));
                
             break;
             
             case 'setka':
             
                $args['color'] = 'green';
                $navy = new navy($args);
                $enum->addItems($navy->getWrapper()->getChildren(0));
                
             break;
             
             case 'agregator':
             
                $args['color'] = 'green';
                $navy = new navy($args);
                $enum->addItems($navy->getWrapper()->getChildren(0));
                
             break;
             
             case 'datatable':
                
                $s_mode = isset($args['s_mode']) ? (integer) $args['s_mode'] : 0;                
                $dop_filter = isset($args['dop_filter']) ? $args['dop_filter'] : array();
                
                $draw = isset($args['draw']) ? $args['draw'] : 0;        
                $start = isset($args['start']) ? $args['start'] : 0;
                $length = isset($args['length']) ? $args['length'] : 100;
            
                $order_column = isset($args['order'][0]['column']) ? $args['order'][0]['column'] : 0;
                $order_dir = isset($args['order'][0]['dir']) ? mb_strtoupper($args['order'][0]['dir']) : 'DESC';   
                
                if ($s_mode)
                {                    
                    $sql = "SELECT `name` FROM `nls_source_tag_vars`";
                    $tag_vars = pdo::getPdo()->query($sql)->fetchAll(\PDO::FETCH_COLUMN);
                    
                    $tag_vars[] = 'traffic';
                    $tag_vars[] = 'setka_type';
                    $tag_vars[] = 'agregator';
                    $tag_vars[] = 'user_tag';
                    $tag_vars[] = 'category';
                    
                    $left_join = [];
                    $select = [];
                    
                    $left_join[] = "LEFT JOIN `nls_source_tags` ON `nls_sources`.`id` = `nls_source_tags`.`nls_source_id`";
                    $left_join[] = "LEFT JOIN `regions` ON `nls_sources`.`region_id` = `regions`.`id`";
                    $left_join[] = "LEFT JOIN `organizations` ON `organizations`.`id` = `nls_sources`.`organization_id`";
                    $left_join[] = "LEFT JOIN `address` ON `address`.`id` = `nls_sources`.`addres_id`";
                    
                    $select[] = "`nls_sources`.`name` as `name`";
                    $select[] = "`nls_sources`.`id` as `id`"; 
                    $select[] = "`nls_source_tags`.`name_type` as `name_type`";
                    $select[] = "`regions`.`name` as `region_name`"; 
                    $select[] = "`organizations`.`name` as `organization_name`";
                    $select[] = "`address`.`name` as `addres_name`";
                    
                    foreach ($tag_vars as $tag_var)
                    {
                        $table = $tag_var . 's';
                        $field = $tag_var . '_name';
                        
                        $left_join[] = "LEFT JOIN `{$table}` ON `nls_source_tags`.`id_type` = `{$table}`.`id`";
                        
                        $name = 'name';
                        if ($tag_var == 'setka') $name = 'syn';
                        
                        $select[] = "`".$table."`.`".$name."` as `{$field}`";
                    }
                    
                    $filter = $this->_calc_ids($dop_filter);
                     
                    $sql = "SELECT ".implode(',', $select)." FROM `nls_sources` ".implode(' ', $left_join) ." {$filter} ORDER BY `nls_sources`.`id` ASC";
                    $tags = pdo::getPdo()->query($sql)->fetchAll(\PDO::FETCH_ASSOC);
                        
                    $parent_ids = [];
                    $t = [];
                    
                    foreach ($tags as $tag)
                    {
                        if (!isset($t[$tag['id']]['tags'])) $t[$tag['id']]['tags'] = [];
                        
                        if ($tag['name_type'])
                        {                        
                            $t[$tag['id']]['tags'][] = $tag[$tag['name_type'].'_name'];
                        }
                        
                        $t[$tag['id']]['organization_name'] = $tag['organization_name'];
                        $t[$tag['id']]['addres_name'] = $tag['addres_name'];
                        $t[$tag['id']]['region_name'] = $tag['region_name'];
                        $t[$tag['id']]['name'] = $tag['name'];
                        $t[$tag['id']]['id'] = $tag['id'];
                        $parent_ids[] = $tag['id'];
                    }
                    
                    $sub_sources = [];
                    $count_numbers = [];
                    
                    if ($parent_ids)
                    {
                        $sql = "SELECT `parent`, COUNT(*) as `count` FROM `nls_sources` WHERE `parent` IN (".implode(',', $parent_ids).") GROUP BY `parent`";
                        $sub_sources = pdo::getPdo()->query($sql)->fetchAll(\PDO::FETCH_KEY_PAIR);
                        
                        $sql = "SELECT `nls_source_id`, COUNT(*) as `count` FROM `mangos` WHERE `nls_source_id` IN (".implode(',', $parent_ids).") GROUP BY `nls_source_id`";
                        $count_mango_numbers = pdo::getPdo()->query($sql)->fetchAll(\PDO::FETCH_KEY_PAIR);
                        
                        $sql = "SELECT `nls_source_id`, COUNT(*) as `count` FROM `dt_phones` WHERE `nls_source_id` IN (".implode(',', $parent_ids).") GROUP BY `nls_source_id`";
                        $count_dt_numbers = pdo::getPdo()->query($sql)->fetchAll(\PDO::FETCH_KEY_PAIR);
                        
                        if ($sub_sources)
                        {
                            $sql = "SELECT `nls_sources`.`parent`, COUNT(*) as `count` FROM `mangos` 
                                                LEFT JOIN `nls_sources` ON `mangos`.`nls_source_id` = `nls_sources`.`id` 
                                                   LEFT JOIN `dt_phones` ON `dt_phones`.`mango_id` = `mangos`.`id`
                                            WHERE `nls_sources`.`parent` IN (".implode(',', $parent_ids).") 
                                                    AND `dt_phones`.`id` IS NULL GROUP BY `nls_sources`.`parent`";
                            $count_sub_numbers = pdo::getPdo()->query($sql)->fetchAll(\PDO::FETCH_KEY_PAIR);
                        }
                        
                        foreach ($parent_ids as $parent_id)
                        {
                           $count_mango_number = isset($count_mango_numbers[$parent_id]) ? $count_mango_numbers[$parent_id] : 0;
                           $count_dt_number = isset($count_dt_numbers[$parent_id]) ? $count_dt_numbers[$parent_id] : 0; 
                           $count_sub_number = isset($count_sub_numbers[$parent_id]) ? $count_sub_numbers[$parent_id] : 0; 
                           
                           $count_numbers[$parent_id] = $count_mango_number + $count_dt_number + $count_sub_number;   
                        }
                    }                    
                    
                    $tbody = [];
                    
                    foreach ($t as $tag)
                    {
                        $count_number = isset($count_numbers[$tag['id']]) ? $count_numbers[$tag['id']] : 0;
                        $sub_source = isset($sub_sources[$tag['id']]) ? $sub_sources[$tag['id']] : 0;
                        
                        $a2 = new node('a');
                        $a2->addChildren($sub_source);
                        $a2->getAttributes()->setAttr('href', '#');
                        $a2->getAttributes()->getClass()->setItems('js-show-sub'); 
                        
                        $a3 = new node('a');
                        $a3->addChildren($count_number);
                        $a3->getAttributes()->setAttr('href', '#');
                        $a3->getAttributes()->getClass()->setItems('js-show-mango'); 
                        
                        $tg_str = [];
                        foreach ($tag['tags'] as $tgs)
                            $tg_str[] = $this->create_tag($tgs);
                            
                        $fa = new node('i');
                        $fa->getAttributes()->getClass()->setItems(['fa', 'fa-phone-square']);                
                        
                        $a = new node('a');
                        $a->addChildren($fa);
                        $a->getAttributes()->addAttr('target', '_blank');
                        $a->getAttributes()->addAttr('href', '/resale_work/?s=' . $tag['id']); 
                        
                        $a_plus = '';
                        
                        /*if (!$sub_source)
                        {
                            $a_plus = new node('a');
                            $a_plus->getAttributes()->addAttr('href', '#');
                            
                            $plus = new node('i');
                            $plus->getAttributes()->addAttr('data-nls_source_id', $tag['id']);
                            $plus->getAttributes()->getClass()->setItems(['fa', 'fa-plus-square', 'js-plus']);
                            
                            $a_plus->addChildren($plus);
                        }*/
                                               
                        $tbody[] = [$a . (string) $a_plus . $tag['id'], $tag['name'], implode(' ', $tg_str), $tag['region_name'], $tag['organization_name'], $tag['addres_name'],
                                        (string) $a2, (string) $a3, 'DT_RowData' => ['source_id' => $tag['id']]];  
                    }
                    
                    //print_r($tbody);
                    
                    $all = count($tbody);
                    $tbody = array_slice($tbody, $start, $length);
                    
                    $a_array = array('draw' => $draw, 'recordsTotal' => $all, 'recordsFiltered' => $all, 'data' => $tbody);
                    $this->getWrapper()->addChildren($a_array);
                }
                else
                {
                    $thead = array('id', 'Название', 'Теги', 'Регион', 'Владелец', 'Карточка', 'Подысточники', 'Номера');  
                    
                    $data_table = new shape\datatable();
                    $data_table->getAttributes()->addAttr('data-table', 'nls');
                    $this->getWrapper()->getAttributes()->setAttr('data-op', 'datatable');
                    $this->getWrapper()->getAttributes()->setAttr('data-obj', 'nls');
                    $this->getWrapper()->getAttributes()->setAttr('id', 'datatable-nls');
                         
                    $this->getWrapper()->getAttributes()->getClass()->addItems('datatable-short');        
                         
                    $data_table->setThead($thead); 
                                          
                    $this->getWrapper()->addChildren($data_table);
                }
                
            break;
            
            case 'show_mango':
            
                $source_id = isset($args['source_id']) ? $args['source_id'] : 0;
                 
                $div_menu = new node('div');
                $div_menu->getAttributes()->getClass()->setItems(['show_new', 'spec-organization', 'spec-mango']);
                
                $sql = "SELECT `channels`.`name` as `channel`, `mangos`.`name` as `mango`, `mangos`.`id` as `mango_id`
                                                FROM `mangos` 
                                                    LEFT JOIN `channels` ON `mangos`.`channel_id` = `channels`.`id`
                                        WHERE `mangos`.`nls_source_id`=:nls_source_id";
                $stm = pdo::getPdo()->prepare($sql); 
                $stm->execute(array('nls_source_id' => $source_id));
                $data = $stm->fetchAll(\PDO::FETCH_ASSOC);
                
                if ($data)
                {
                    $table = new shape\table_striped();
                    $table->getAttributes()->getClass()->addItems('table-condensed');
                    
                    $tbody = [];
                    
                    foreach ($data as $value)
                    {
                        $tbody[] = [$value['mango_id']. ' ' . tools::format_phone($value['mango']), '', $this->create_tag($value['channel'])];
                    }
                    
                    $table->setTbody($tbody);
                    $table->setTfoot('Итого: ' . count($tbody));
                    $div_menu->addChildren($table);
                }
                
                $sql = "SELECT `channels`.`name` as `channel`, `mangos`.`name` as `mango`, `nls_sources`.`name` as `name`,
                                            `mangos`.`id` as `mango_id`
                                                FROM `mangos` 
                                                        LEFT JOIN `channels` ON `mangos`.`channel_id` = `channels`.`id`
                                                            LEFT JOIN `nls_sources` ON `nls_sources`.`id` = `mangos`.`nls_source_id`
                                                                LEFT JOIN `dt_phones` ON `dt_phones`.`mango_id` = `mangos`.`id`
                                                    WHERE `nls_sources`.`parent`=:nls_source_id AND `dt_phones`.`id` IS NULL
                                                             ORDER BY `nls_sources`.`name` ASC";
                $stm = pdo::getPdo()->prepare($sql); 
                $stm->execute(array('nls_source_id' => $source_id));
                $data = $stm->fetchAll(\PDO::FETCH_ASSOC);
                
                if ($data)
                {
                    $p = new node('h4');
                    $p->addChildren('Подысточники');
                    
                    $div_menu->addChildren($p);
                    
                    $table = new shape\table_striped();
                    $table->getAttributes()->getClass()->addItems('table-condensed');
                    
                    $tbody = [];
                    
                    foreach ($data as $value)
                    {
                        $tbody[] = [$value['mango_id'] . ' ' .tools::format_phone($value['mango']), $value['name'], $this->create_tag($value['channel'])];  
                    }
                    
                    $table->setTbody($tbody);
                    $table->setTfoot('Итого: ' . count($tbody));
                    $div_menu->addChildren($table);
                }
                
                $sql = "SELECT `mangos`.`name` as `mango`, `mangos`.`id` as `mango_id`
                                                FROM `dt_phones` 
                                                    LEFT JOIN `mangos` ON `mangos`.`id` = `dt_phones`.`mango_id`
                                        WHERE `dt_phones`.`nls_source_id`=:nls_source_id";
                $stm = pdo::getPdo()->prepare($sql); 
                $stm->execute(array('nls_source_id' => $source_id));
                $dt_phones = $stm->fetchAll(\PDO::FETCH_ASSOC);
                    
                if ($dt_phones)
                {
                    $p = new node('h4');
                    $p->addChildren('Динамика');
                    
                    $div_menu->addChildren($p);
                    
                    $table = new shape\table_striped();
                    $table->getAttributes()->getClass()->addItems('table-condensed');
                    
                    $tbody = [];
                    
                    foreach ($dt_phones as $value)
                    {
                        $tbody[] = [$value['mango_id']. ' ' . tools::format_phone($value['mango']), '', ''];  
                    }
                    
                    $table->setTbody($tbody);
                    $table->setTfoot('Итого: ' . count($tbody));
                    $div_menu->addChildren($table);
                }
                    
                $div_link = new node('div');
                $div_link->getAttributes()->getClass()->setItems(['pull-right', 'organization-link']);
                
                $close = new node('a');
                $close->addChildren('закрыть');
                $close->getAttributes()->setAttr('href', '#');
                $close->getAttributes()->getClass()->setItems(['js-close']); 
                    
                $div_link->addChildren($close);
                
                $div_menu->addChildren($div_link);
                
                $enum->addItems($div_menu);
            
            break;
            
            case 'show_sub':
            
                $source_id = isset($args['source_id']) ? $args['source_id'] : 0;
                 
                $div_menu = new node('div');
                $div_menu->getAttributes()->getClass()->setItems(['show_new', 'spec-organization', 'spec-sub']);
                
                $table_striped = new shape\table_striped();
                $table_striped->getAttributes()->getClass()->addItems('table-condensed');
                
                $left_join = [];
                $select = [];
                
                $left_join[] = "LEFT JOIN `nls_source_tags` ON `nls_sources`.`id` = `nls_source_tags`.`nls_source_id`";
                
                $select[] = "`nls_sources`.`id` as `id`";
                $select[] = "`nls_sources`.`name` as `name`";
                $select[] = "`nls_source_tags`.`name_type` as `name_type`";
                
                $filter = [];
                $filter[] = "`nls_sources`.`parent` = {$source_id}";
                $filter = " WHERE (".implode(") AND (", $filter).")";
                                 
                $sql = "SELECT `name` FROM `nls_source_tag_vars`";
                $tag_vars = pdo::getPdo()->query($sql)->fetchAll(\PDO::FETCH_COLUMN);
                
                foreach ($tag_vars as $tag_var)
                {
                    $table = $tag_var . 's';
                    $field = $tag_var . '_name';
                    
                    $left_join[] = "LEFT JOIN `{$table}` ON `nls_source_tags`.`id_type` = `{$table}`.`id`";
                    
                    $name = 'name';
                    if ($tag_var == 'setka') $name = 'syn';
                    
                    $select[] = "`".$table."`.`".$name."` as `{$field}`";
                }
                
                $sql = "SELECT ".implode(',', $select)." FROM `nls_sources` ".implode(' ', $left_join) ." {$filter} ORDER BY 
                            `nls_sources`.`name` ASC";
                $tags = pdo::getPdo()->query($sql)->fetchAll(\PDO::FETCH_ASSOC);
                
                if (!$tags) break;
                
                $t = [];
                foreach ($tags as $tag)
                {
                    if ($tag['name_type'])
                    {
                        $t[$tag['id']]['tags'][] = $tag[$tag['name_type'].'_name'];
                    }
                    
                    $t[$tag['id']]['name'] = $tag['name'];
                    $t[$tag['id']]['id'] = $tag['id'];
                }
                
                foreach ($t as $tag)
                {
                    $fa = new node('i');
                    $fa->getAttributes()->getClass()->setItems(['fa', 'fa-phone-square']);
                    
                    $a_plus = '';
                    
                    /*$a_plus = new node('a');
                    $a_plus->getAttributes()->addAttr('href', '#');
                    
                    $plus = new node('i');
                    $plus->getAttributes()->addAttr('data-nls_source_id', $tag['id']);
                    $plus->getAttributes()->getClass()->setItems(['fa', 'fa-plus-square', 'js-plus']);  
                    
                    $a_plus->addChildren($plus);*/                   
                    
                    $a = new node('a');
                    $a->addChildren($fa);
                    $a->getAttributes()->addAttr('target', '_blank');
                    $a->getAttributes()->addAttr('href', '/resale_work/?s=' . $tag['id']);    
                    
                    $tg_str = [];
                    if (isset($tag['tags']))
                    {
                        foreach ($tag['tags'] as $tgs)
                            $tg_str[] = $this->create_tag($tgs);
                    }
                        
                    $tbody[] = [$a . (string) $a_plus . $tag['id'], $tag['name'], implode(' ', $tg_str)];   
                }
                
                $table_striped->setTbody($tbody);
                
                $div_link = new node('div');
                $div_link->getAttributes()->getClass()->setItems(['pull-right', 'organization-link']);
                
                $close = new node('a');
                $close->addChildren('закрыть');
                $close->getAttributes()->setAttr('href', '#');
                $close->getAttributes()->getClass()->setItems(['js-close']); 
                    
                $div_link->addChildren($close);
                
                $div_menu->addChildren($table_striped);
                $div_menu->addChildren($div_link);
                
                $enum->addItems($div_menu);
                 
            break;
            
            case 'filter':            
                
                /*foreach (['region_id' => 'Регион', 'setka_id' => 'Сетка', 'traffic_id' => 'Трафик', 'agregator_id' => 'Агрегатор', 
                                'setka_type_id' => 'Тип сетки'] as $name => $label_name)
                {
                    $div = new node('div');
                    $div->getAttributes()->getClass()->addItems('filter_group');
                    
                    $select = new form\select();
                    $select->setName($name);
                    //$select->getAttributes()->addAttr('multiple', 'multiple');
                    
                    if (isset($_COOKIE[$name])) { 
                        if ($_COOKIE[$name]) {
                            $select->setValue($_COOKIE[$name]);   
                            $select->getAttributes()->getClass()->addItems('passed');
                        }
                    }
                    
                    $table = mb_substr($name, 0, -3).'s';
                    
                    $field = 'name';
                    $filter = '';
                    if ($name == 'setka_id') 
                    {
                        $field = 'syn';
                        $filter = "WHERE (`no_active` = 0 OR `no_active` IS NULL)";
                    }
                    
                    $sql = "SELECT `id`, `{$field}` FROM `{$table}` {$filter} ORDER BY `{$field}` ASC";
                    $values = pdo::getPdo()->query($sql)->fetchAll(\PDO::FETCH_KEY_PAIR);
                        
                    $select->setValues(array(0 => 'Все') + $values);
                    
                    $label_obj = new node('label');
                    $label_obj->addChildren($label_name);
    
                    $div->addChildren($label_obj);
                    $div->addChildren($select);
                    
                    $enum->addItems($div); 
                }*/
                
                $button = new form\button_primary('Аналитика');
                $button->getAttributes()->getClass()->addItems('pull-right');        
                $button->getAttributes()->getClass()->addItems('btn-sm');        
                $button->getAttributes()->addAttr('id', 'show_nls_modal');
                
                $fa = new node('i');
                $fa->getAttributes()->getClass()->setItems(['fa', 'fa-area-chart']);
                
                $button->addChildren('&nbsp;' . $fa); 
                
                $enum->addItems($button);
                
            break;
            
            case 'modal':
                
                $modal = new modal('nls_modal');
                $modal->setTitle('Аналитика');
              
                $dop_filter = isset($args['dop_filter']) ? $args['dop_filter'] : array();
                
                list($h, $i, $s, $n, $j, $Y) = array(date("H"),date("i"),date("s"),date("n"),date("j"),date("Y"));
                $start_date = date('Y-m-d H:i:s', mktime(0, 0, 0, $n, $j-6, $Y));
                $end_date = date('Y-m-d H:i:s', mktime(23, 59, 59, $n, $j, $Y));
                
                $order_column = isset($args['order_column']) ? (integer) $args['order_column'] : 0;
                $order_dir = isset($args['order_dir']) ? (integer) $args['order_dir'] : 1;     
                
                $group_args = 0;
                
                //$sources = array('SEO', 'YD', 'GA', 'YD-RS', 'VK', 'FB');
                $sql = "SELECT `id`, `name` FROM `channels` WHERE (`no_active` IS NULL OR `no_active` = 0)";
                $sources = pdo::getPdo()->query($sql)->fetchAll(\PDO::FETCH_KEY_PAIR);
                
                $source_args = array_keys($sources);
                $type_args = [];
                  
                $t = array(
                    array('id' => 0, 'name' => 'По дням'),
                    array('id' => 1, 'name' => 'По неделям'),
                    array('id' => 2, 'name' => 'По месяцам'),
                    array('id' => 3, 'name' => 'Весь период'),
                    array('id' => 4, 'name' => 'По часам'),
                );
                
                $enum_modal = new enum();
                $enum_modal->setSign('');
                
                $div_level = new node('div');
                $div_level->getAttributes()->addAttr('id', 'analytic-level');
                $div_level->getAttributes()->getClass()->addItems('wrapper');       
                
                $div_channels = new node('div');
                $div_channels->getAttributes()->getClass()->setItems(['pull-left', 'div-types']);
                
                foreach ($sources as $i => $value)
                {
                  $div = new node('div');
                  $div->getAttributes()->getClass()->addItems('checkbox');
                  
                  $label = new node('label');
                  
                  $item = new form\checkbox();
                  $item->getAttributes()->getClass()->addItems('flats');
                  $item->getAttributes()->getClass()->addItems('flat-green');
                  
                  $item->setName('channel_id['.$i.']');
                     
                  if (in_array($i, $source_args))
                       $item->getAttributes()->addAttr('checked', 'checked');     
                  
                  $label->addChildren($item);
                  $label->addChildren($value);
                  
                  $div->addChildren($label);
                  
                  $div_channels->addChildren($div);
                }
                
                $div_level->addChildren($div_channels);         
                
                $div_class = new node('div');
                $div_class->getAttributes()->getClass()->setItems(array('interval_wrapper', 'pull-right'));
                
                $i_calc = new node('i');
                $i_calc->getAttributes()->getClass()->setItems(array('fa', 'fa-calculator'));
              
                $button = new node('button');
                $button->getAttributes()->getClass()->setItems(array('btn', 'btn-primary', 'go'));
                $button->getAttributes()->addAttr('title', 'Расчет');
                $button->addChildren($i_calc);              
              
                $div = new node('div');
                $div->getAttributes()->getClass()->setItems(array('range_picker'));
              
                $calendar = new node('div');
                $calendar->getAttributes()->getClass()->setItems(array('calendar'));
              
                $i = new node('i');
                $i->getAttributes()->getClass()->setItems(array('fa', 'fa-calendar'));
              
                $calendar->addChildren($i);
                
                $span = new node('span');
                $span->getAttributes()->getClass()->setItems(array('interval'));
              
                $arrow = new node('div');
                $arrow->getAttributes()->getClass()->setItems(array('arrow'));
              
                $b = new node('b');
              
                $arrow->addChildren($b);
          
                $div->addChildren($calendar);
                $div->addChildren($span);
                $div->addChildren($arrow);
                
                $div_group = new node('div');
                $div_group->getAttributes()->getClass()->setItems('btn-group');
                $div_group->getAttributes()->addAttr('data-toggle', 'buttons');
                
                foreach ($t as $s)
                {
                    $label = new node('label');
                    $label->getAttributes()->getClass()->setItems(array('btn', 'btn-default'));     
                    
                    $checkbox = new form\radio();
                    $checkbox->setValue($s['id']);
                    $checkbox->setName('group_by');
                    
                    $label->addChildren($s['name']);
                    $label->addChildren($checkbox);
                    
                    if ($s['id'] == $group_args)
                    {
                        $label->getAttributes()->getClass()->addItems('active');
                        $checkbox->getAttributes()->addAttr('checked', 'checked');
                    }
                    
                     $div_group->addChildren($label);
                }                
                
                $div_class->addChildren($button);
                $div_class->addChildren($div_group);
                $div_class->addChildren($div);
                
                $div_level->addChildren($div_class);
                
                $div_types = new node('div');
                $div_types->getAttributes()->getClass()->setItems(array('pull-right', 'div-types'));
                
                foreach (['Разложить', 'Среднее за день', 'Без наших доходов'] as $i => $value)
                {
                  $div = new node('div');
                  $div->getAttributes()->getClass()->addItems('checkbox');
                  
                  $label = new node('label');
                  
                  $item = new form\checkbox();
                  $item->getAttributes()->getClass()->addItems('flats');
                  $item->getAttributes()->getClass()->addItems('flat-green');
                  
                  $item->setName('types['.$i.']');
                     
                  if (in_array($i, $type_args))
                       $item->getAttributes()->addAttr('checked', 'checked');     
                  
                  $label->addChildren($item);
                  $label->addChildren($value);
                  
                  $div->addChildren($label);
                  
                  $div_types->addChildren($div);
                }
                
                $div_level->addChildren($div_types);   
                
                $enum_modal->addItems($div_level);   
                
                $left_join = [];
                $select = [];
                
                $left_join[] = "LEFT JOIN `nls_source_tags` ON `nls_sources`.`id` = `nls_source_tags`.`nls_source_id`";
                $left_join[] = "LEFT JOIN `regions` ON `nls_sources`.`region_id` = `regions`.`id`";
                
                $select[] = "`nls_sources`.`name` as `name`";           
                $select[] = "`nls_sources`.`id` as `id`";               
                
                $filter = $this->_calc_ids($dop_filter);
                
                $sql = "SELECT DISTINCT ".implode(',', $select)." FROM `nls_sources` ".implode(' ', $left_join) ." {$filter} ORDER BY `nls_sources`.`id` ASC";
                $tags = pdo::getPdo()->query($sql)->fetchAll(\PDO::FETCH_ASSOC);
                
                $data_table = new shape\table_striped();
                $data_table->getAttributes()->getClass()->addItems('js-analytic');
                
                $tbody = array();
                $tbody[] = ['SUMMARY'];                
                
                /*$fa = new node('fa');
                $fa->getAttributes()->getClass()->setItems(['fa', 'fa-plus-circle']);
                
                $div_plus = new node('a');
                $div_plus->getAttributes()->addAttr('href', '#');
                $div_plus->getAttributes()->getClass()->addItems('js-plus');
                $div_plus->addChildren($fa);*/
                
                foreach ($tags as $tag)
                {
                    $tbody[] = [$tag['name']];
                }
                
                $data_table->setTbody($tbody);
                
                $trs = $data_table->getChildren('tbody')->getChildren(0);
                $count_tbody = count($tbody);
                      
                for ($i = 1; $i < $count_tbody; $i++)
                {
                    $trs->getItems($i)->getAttributes()->addAttr('data-id', $tags[$i - 1]['id']);
                    $trs->getItems($i)->getAttributes()->getClass()->addItems('show_detail');
                }
                
                $trs->getItems(0)->getAttributes()->addAttr('data-id', 0);
                $trs->getItems(0)->getAttributes()->getClass()->addItems('show_detail'); 
                
                $div_level->addChildren($data_table);
                
                foreach (array('start_date', 'end_date', 'order_column', 'order_dir') as $name)
                {
                    $value = $$name;            
                    $hidden = new form\hidden();
                    $hidden->setName($name);
                    $hidden->setValue($value);     
                    $div_level->addChildren($hidden);
                }
                
                $str = '
                    <div class="row"> 
                      <div class="col-md-2 col-sm-12 col-xs-12 ciba-filter">' .
                            (string) new nls(array('mode' => 'create_filter', 'dop_filter' => $dop_filter)) .
                      '</div>
                       <div class="col-md-10 col-sm-12 col-xs-12">
                        <div class="x_panel ciba-content-box">
                            <div class="row">
                                <div class="col-xs-12">
                                    <div class="ciba-filter-param">                      
                                    </div>
                                </div>
                            </div>' .
                            (string) $enum_modal .
                        '</div>
                    </div>
                 </div>';
                 
                
                $modal->setBody($str);
                
                $modal->setButtonclose(true);
                $modal->setBclose(false);
                
                $enum->addItems($modal);
                
            break;
            
            case 'calc':
            
                $nls_source_id = isset($args['nls_source_id']) ? (array) $args['nls_source_id'] : array();
                
                $group_by = isset($args['group_by']) ? $args['group_by'] : 0;

                list($h, $i, $s, $n, $j, $Y) = array(date("H"),date("i"),date("s"),date("n"),date("j"),date("Y"));
     
                $start_date = isset($args['start_date']) ? $args['start_date'] : date('Y-m-d H:i:s', mktime(0, 0, 0, $n, $j-6, $Y));
                $end_date  = isset($args['end_date']) ? $args['end_date'] : date('Y-m-d H:i:s', mktime(23, 59, 59, $n, $j, $Y));
                
                $sql = "SELECT `id`, `name` FROM `channels` WHERE (`no_active` IS NULL OR `no_active` = 0)";
                $sources = pdo::getPdo()->query($sql)->fetchAll(\PDO::FETCH_KEY_PAIR);
                
                $source_args = array_keys($sources);
                
                $channel_id = isset($args['channel_id']) ? array_keys($args['channel_id']) : $source_args;
                $types = isset($args['types']) ? $args['types'] : [];       
                
                $order_column = isset($args['order_column']) ? (integer) $args['order_column'] : 0;
                $order_dir = isset($args['order_dir']) ? (integer) $args['order_dir'] : 1;                
                
                if ($nls_source_id)
                {
                    //if ($start_date < '2020-02-26 00:00:00') $start_date = '2020-02-26 00:00:00';
                    
                    //$channel_id = [2];
                    //var_dump($group_by);
                    
                    $use_hour_table = false;
                    
                    $start_date_ts = strtotime($start_date);
                    $end_date_ts = strtotime($end_date); 
                    
                    $day = $step = 24 * 60 * 60;
              
                    $limit_j = 1;        
                    $d_str = 'Y-m-d';
                        
                    $transaction_g_b = 'day';
                    $g_b = 'd_date';
                    
                    $diff_day = round((($end_date_ts + 1) - $start_date_ts) / $day);
                                                         
                    if ($group_by == 1) 
                    {
                        $limit_j = 7;
                    }
                    
                    if ($group_by == 2)
                    {
                        $d_str = 'Ym';
                        $g_b = 'month';
                        $transaction_g_b = 'ym';
                    }
                    
                    if ($group_by == 3)
                    {
                        $limit_j = $diff_day;
                    }
                    
                    if ($group_by == 4)
                    {
                        $g_b = 'hour';
                        $transaction_g_b = 'h';
                        $use_hour_table = true;
                    }
                    
                    $filter3 = [];
                    foreach ($channel_id as $channel)
                    {
                        $filter3[$sources[$channel]] = 1;   
                    }
                    
                    $t_results = [];  
                    
                    $split = false;
                    if (isset($types[0]) && $types[0]) $split = true;
                    
                    if ($split)
                    {
                        $thead = ['Дата', 'ID', 'Сплит', 'Трафик', 'Клики', 'Лиды', 'Продано', 'Конверсия', 'Цена лида', 'Цена прод.лида', 'Расход', 'Доход', 'ROI', 'Маржа'];
                        $split_str = ",`nls_sources`.`id`";
                        $split_str_field = ",`nls_sources`.`id` as `nls_source_id`";
                    }
                    else
                    {
                        $thead = ['Дата', 'ID', 'Трафик', 'Клики', 'Лиды', 'Продано', 'Конверсия', 'Цена лида', 'Цена прод.лида', 'Расход', 'Доход', 'ROI', 'Маржа'];
                        $split_str = '';
                        $split_str_field = '';
                    }
                    
                    if ($group_by == 4) $thead[0] = 'Час';
                        
                    $format_price = ['Трафик', 'Клики', 'Лиды', 'Цена лида', 'Расход', 'Доход', 'Маржа'];
                    
                    $subs = [];
                    
                    $sql = "SELECT `nls_sources`.`id`, `nls_sources`.`name` FROM `nls_sources` WHERE `parent` IN (".implode(',', $nls_source_id).") ORDER BY `nls_sources`.`name` ASC";
                    $subs_array = (array) pdo::getPdo()->query($sql)->fetchAll(\PDO::FETCH_KEY_PAIR);
                    
                    $subs = array_keys($subs_array); 
                    $subs = array_merge($subs, $nls_source_id);
                          
                    $sql = "SELECT `name`, `id` FROM `nls_sources` WHERE `id` IN (".implode(',', $nls_source_id).") ORDER BY find_in_set(`id`,'".implode(',', $nls_source_id)."')";
                    $nls_array = (array) pdo::getPdo()->query($sql)->fetchAll(\PDO::FETCH_ASSOC);
                    
                    foreach ($nls_array as $ar)
                    {
                        $subs_array[$ar['id']] = $ar['name'];
                    } 
                    
                    //print_r($subs_array);
                    
                    //traffic
                    $t = [];
                    
                    for ($i = 0; $i < 2; $i++)
                    {
                        if ($i == 0)
                        {
                            $table = 'm_stats';
                            $link_table = 'sites';
                            $field = 'site_id';
                            
                            if ($use_hour_table)
                            {
                                $table = 'm_h_stats';    
                            }
                        }
                        
                        if ($i == 1)
                        {
                            $table = 'ag_m_stats';
                            $link_table = 'ag_sites';
                            $field = 'ag_site_id';
                            
                            if ($use_hour_table)
                            {
                                $table = 'ag_m_h_stats';    
                            }
                        }
                                   
                        $filter = [];
                        $filter[] = "`{$table}`.`d_date` >= '{$start_date}'";
                        $filter[] = "`{$table}`.`d_date` <= '{$end_date}'";
                        $filter[] = "`{$link_table}`.`nls_source_id` IN (".implode(',', $subs).")";
                        
                        $filter = " WHERE (".implode(") AND (", $filter).")";
                        
                        $sql = "SELECT `{$table}`.`{$g_b}`, SUM(`s`) as `s`, SUM(`y`) as `y`, SUM(`a`) as `a`, SUM(`all`) as `all`{$split_str_field} FROM `{$table}` 
                                                                    LEFT JOIN `{$link_table}` ON `{$link_table}`.`id` = `{$table}`.`{$field}`
                                                                       LEFT JOIN `nls_sources` ON `{$link_table}`.`nls_source_id` = `nls_sources`.`id`
                                                                        {$filter} GROUP BY `{$table}`.`{$g_b}`{$split_str}"; //echo $sql;
                        $leads = pdo::getPdo()->query($sql)->fetchAll(\PDO::FETCH_ASSOC);
                        
                        foreach ($leads as $lead)
                        {
                            $t_m_s_val = 0;
                            
                            if (isset($filter3['SEO']) && isset($filter3['YD']) && isset($filter3['GA']))
                            {
                                $t_m_s_val += $lead['all'];
                            }   
                            else
                            {
                                if (isset($filter3['SEO'])) $t_m_s_val += $lead['s'];
                                if (isset($filter3['YD'])) $t_m_s_val += $lead['y'];
                                if (isset($filter3['GA'])) $t_m_s_val += $lead['a']; 
                            }
                            
                            if ($split)
                            {    
                                if (!isset($t[$lead['nls_source_id']][$lead[$g_b]])) $t[$lead['nls_source_id']][$lead[$g_b]] = 0;
                                
                                $t[$lead['nls_source_id']][$lead[$g_b]] += $t_m_s_val;
                            }
                            else
                            {
                                if (!isset($t[$lead[$g_b]])) $t[$lead[$g_b]] = 0;
                                $t[$lead[$g_b]] += $t_m_s_val;
                            }
                        }
                    }
                    
                    if ($split)
                    {
                        foreach ($subs_array as $sub_id => $sub_name)
                        {
                            $val = isset($t[$sub_id]) ? $t[$sub_id] : array();
                            $results['Трафик|' . $sub_id] = $val;
                        }   
                    }
                    else
                    {
                        $results['Трафик'] = $t;
                    }
                        
                    $t1 = [];
                    $t2 = [];
                    
                    //print_r($filter3);
                    
                    if (isset($filter3['YD']) || isset($filter3['YD-RS']) || isset($filter3['GA']) || isset($filter3['VK']))
                    {
                        $add_condition = [];
                        
                        if (isset($filter3['YD'])) $add_condition[] = 'YD';
                        if (isset($filter3['YD-RS'])) $add_condition[] = 'YD-RS'; 
                        if (isset($filter3['GA'])) $add_condition[] = 'GA'; 
                        if (isset($filter3['VK'])) $add_condition[] = 'VK';
                        
                        foreach ($add_condition as $val)
                        {
                            $limit = 2;
                            if ($val == 'VK') $limit = 1;
                            
                            for ($i = 0; $i < $limit; $i++)
                            {
                                if ($i == 0)
                                {
                                    if ($val != 'VK')
                                    {
                                        $table = 'stats';
                                
                                        if ($use_hour_table)
                                        {
                                            $table = 'yd_h_stats';    
                                        }
                                        
                                        if ($val == 'GA') 
                                        {
                                            $table = 'ad_stats';
                                            
                                            if ($use_hour_table)
                                            {
                                                $table = 'ad_h_stats';   
                                            }
                                        }
                                        
                                        $link_table = 'sites';
                                        $field = 'site_id';
                                        $campaign_table = 'campaigns';
                                        $campaign_field = 'campaign_id';
                                    }
                                    else
                                    {
                                         $table = 'vk_stats';
                                         
                                         if ($use_hour_table)
                                         {
                                            $table = 'vk_h_stats';   
                                         }
                                          
                                         $link_table = 'sites';  
                                         $field = 'site_id';  
                                    }
                                }
                                
                                if ($i == 1)
                                {
                                    $table = 'ag_stats';
                            
                                    if ($use_hour_table)
                                    {
                                        $table = 'ag_h_stats';    
                                    }
                                    
                                    if ($val == 'GA') 
                                    {
                                        $table = 'ag_ad_stats';
                                        
                                        if ($use_hour_table)
                                        {
                                            $table = 'ag_ad_h_stats';   
                                        }
                                    }
                                    
                                    $link_table = 'ag_sites';
                                    $field = 'ag_site_id';
                                    $campaign_table = 'ag_campaigns';
                                    $campaign_field = 'ag_campaign_id';
                                }
                                
                                $filter = [];
                                $filter[] = "`{$table}`.`d_date` >= '{$start_date}'";
                                $filter[] = "`{$table}`.`d_date` <= '{$end_date}'";
                                
                                if ($table == 'ad_stats' || $table == 'stats')
                                    $filter[] = "`{$table}`.`nls_source_id` IN (".implode(',', $subs).")";
                                else
                                    $filter[] = "`{$link_table}`.`nls_source_id` IN (".implode(',', $subs).")";
                                
                                if ($val == 'YD') $filter[] = "`{$campaign_table}`.`type` = 'search'";
                                if ($val == 'YD-RS') $filter[] = "`{$campaign_table}`.`type` = 'network'";
                                
                                $filter = " WHERE (".implode(") AND (", $filter).")";
                                
                                if ($val == 'YD' || $val == 'YD-RS')
                                { 
                                    if ($table != 'stats')
                                    {
                                        $sql = "SELECT `{$table}`.`{$g_b}`, SUM(`cost`) as `cost`, SUM(`click`) as `click`{$split_str_field} FROM `{$table}` 
                                                                                       LEFT JOIN `{$link_table}` ON `{$table}`.`{$field}` = `{$link_table}`.`id`
                                                                                        LEFT JOIN `{$campaign_table}` ON `{$table}`.`{$campaign_field}` = `{$campaign_table}`.`id`
                                                                                          LEFT JOIN `nls_sources` ON `{$link_table}`.`nls_source_id` = `nls_sources`.`id`
                                                                                    {$filter} GROUP BY `{$table}`.`{$g_b}`{$split_str}";
                                    }
                                    else
                                    {                                            
                                        $sql = "SELECT `{$table}`.`{$g_b}`, SUM(`cost`) as `cost`, SUM(`click`) as `click`{$split_str_field} FROM `{$table}` 
                                                                                    LEFT JOIN `{$campaign_table}` ON `{$table}`.`{$campaign_field}` = `{$campaign_table}`.`id`
                                                                                      LEFT JOIN `nls_sources` ON `{$table}`.`nls_source_id` = `nls_sources`.`id`
                                                                                {$filter} GROUP BY `{$table}`.`{$g_b}`{$split_str}";
                                                                                
                                        //if (load::get_user_id() == 1) echo $sql;
                                    }                                             
                                    
                                }
                                else
                                {
                                    if ($table == 'ad_stats')
                                    {
                                        $sql = "SELECT `{$table}`.`{$g_b}`, SUM(`cost`) as `cost`, SUM(`click`) as `click`{$split_str_field} FROM `{$table}`
                                                                                           LEFT JOIN `nls_sources` ON `{$table}`.`nls_source_id` = `nls_sources`.`id`
                                                                                    {$filter} GROUP BY `{$table}`.`{$g_b}`{$split_str}";
                                                                                    
                                        //if (load::get_user_id() == 1) echo $sql;
                                    }
                                    else
                                    {
                                        $sql = "SELECT `{$table}`.`{$g_b}`, SUM(`cost`) as `cost`, SUM(`click`) as `click`{$split_str_field} FROM `{$table}` 
                                                                                       LEFT JOIN `{$link_table}` ON `{$table}`.`{$field}` = `{$link_table}`.`id`
                                                                                           LEFT JOIN `nls_sources` ON `{$link_table}`.`nls_source_id` = `nls_sources`.`id`
                                                                                    {$filter} GROUP BY `{$table}`.`{$g_b}`{$split_str}"; 
                                    }
                                }
                                
                                $leads = pdo::getPdo()->query($sql)->fetchAll(\PDO::FETCH_ASSOC);
                                
                                if ($split)
                                {
                                    foreach ($leads as $lead)
                                    {
                                        if (!isset($t1[$lead['nls_source_id']][$lead[$g_b]])) $t1[$lead['nls_source_id']][$lead[$g_b]] = 0;
                                        if (!isset($t2[$lead['nls_source_id']][$lead[$g_b]])) $t2[$lead['nls_source_id']][$lead[$g_b]] = 0;
                                        
                                        $t1[$lead['nls_source_id']][$lead[$g_b]] += $lead['click'];
                                        $t2[$lead['nls_source_id']][$lead[$g_b]] += $lead['cost'];   
                                    }
                                }
                                else
                                {
                                    foreach ($leads as $lead)
                                    {
                                        if (!isset($t1[$lead[$g_b]])) $t1[$lead[$g_b]] = 0;
                                        if (!isset($t2[$lead[$g_b]])) $t2[$lead[$g_b]] = 0;
                                        
                                        $t1[$lead[$g_b]] += $lead['click'];
                                        $t2[$lead[$g_b]] += $lead['cost'];   
                                    }
                                }
                            }
                        }
                    }
                    
                    $minus_our = (isset($types[2]) && $types[2]);
                    
                    /*if ($channel_id == $source_args)
                    {*/
                        $filter = [];
                        $filter[] = "`partner_apps`.`date_create` >= '{$start_date}'";
                        $filter[] = "`partner_apps`.`date_create` <= '{$end_date}'";
                        $filter[] = "`partner_apps`.`nls_source_id` IN (".implode(',', $subs).")";
                        
                        if ($channel_id != $source_args)
                        {
                            $filter[] = "`partner_apps`.`channel_id` IN (".implode(',', $channel_id).")";   
                        }
                        
                        if ($minus_our)
                        {
                             $filter[] = "`partner_apps`.`price` > 0";
                        }   
                        
                        $filter = " WHERE (".implode(") AND (", $filter).")";
                        
                        $sql = "SELECT SUM(`partner_apps`.`price`) as `cost`, `partner_apps`.`{$g_b}`{$split_str_field} 
                                        FROM `partner_apps`
                                    LEFT JOIN `nls_sources` ON `nls_sources`.`id` = `partner_apps`.`nls_source_id` 
                                {$filter} GROUP BY `partner_apps`.`{$g_b}`{$split_str}"; //echo $sql;
                        $leads = pdo::getPdo()->query($sql)->fetchAll(\PDO::FETCH_ASSOC);
                        
                        if ($split)
                        {
                            foreach ($leads as $lead)
                            {
                                if (!isset($t2[$lead['nls_source_id']][$lead[$g_b]])) $t2[$lead['nls_source_id']][$lead[$g_b]] = 0;
                                $t2[$lead['nls_source_id']][$lead[$g_b]] += $lead['cost'];   
                            }
                        }
                        else
                        {
                            foreach ($leads as $lead)
                            {
                                if (!isset($t2[$lead[$g_b]])) $t2[$lead[$g_b]] = 0;
                                $t2[$lead[$g_b]] += $lead['cost'];   
                            }
                        }
                    //}
                            
                    if ($split)
                    {
                        foreach ($subs_array as $sub_id => $sub_name)
                        {
                            $val1 = isset($t1[$sub_id]) ? $t1[$sub_id] : array();
                            $val2 = isset($t2[$sub_id]) ? $t2[$sub_id] : array();
                            
                            $results['Клики|' . $sub_id] = $val1;
                            $results['Расход|' . $sub_id] = $val2;
                        }   
                    }
                    else
                    {
                        $results['Клики'] = $t1;
                        $results['Расход'] = $t2;         
                    }
                    
                    $edit = false;
                         
                    $t1 = [];
                    $t2 = [];                    
                    
                    if (count($channel_id) == 1 && $group_by == 0 && count($nls_source_id) == 1)
                    {
                        $edit = true;
                    }
                    
                    $filter = [];
                    $filter[] = "`expenses`.`day` >= '{$start_date}'";
                    $filter[] = "`expenses`.`day` <= '{$end_date}'";
                    $filter[] = "`expenses`.`nls_source_id` IN (".implode(',', $subs).")";
                    $filter[] = "`expenses`.`no_active` = 0";
                     
                    if ($channel_id != $source_args)
                    {
                        $filter[] = "`expenses`.`channel_id` IN (".implode(',', $channel_id).")";   
                    }
                    
                    $filter = " WHERE (".implode(") AND (", $filter).")";
                    
                    $sql = "SELECT SUM(`cost`) as `cost`, SUM(`click`) as `click`,
                            `expenses`.`{$transaction_g_b}`{$split_str_field} 
                                        FROM `expenses` 
                                        LEFT JOIN `nls_sources` ON `nls_sources`.`id` = `expenses`.`nls_source_id` 
                                                {$filter}
                                                    GROUP BY `expenses`.`{$transaction_g_b}`{$split_str}"; //if (load::get_user_id() == 1) echo $sql;
                    $leads = pdo::getPdo()->query($sql)->fetchAll(\PDO::FETCH_ASSOC);
                    
                    if ($split)
                    {
                        foreach ($leads as $lead)
                        {
                            if (!isset($t1[$lead['nls_source_id']][$lead[$transaction_g_b]])) $t1[$lead['nls_source_id']][$lead[$transaction_g_b]] = 0;
                            if (!isset($t2[$lead['nls_source_id']][$lead[$transaction_g_b]])) $t2[$lead['nls_source_id']][$lead[$transaction_g_b]] = 0;
                            
                            $t1[$lead['nls_source_id']][$lead[$transaction_g_b]] = $lead['click'];
                            $t2[$lead['nls_source_id']][$lead[$transaction_g_b]] = $lead['cost'];   
                        }
                    }
                    else
                    {
                        foreach ($leads as $lead)
                        {
                            if (!isset($t1[$lead[$transaction_g_b]])) $t1[$lead[$transaction_g_b]] = 0;
                            if (!isset($t2[$lead[$transaction_g_b]])) $t2[$lead[$transaction_g_b]] = 0;
                            
                            $t1[$lead[$transaction_g_b]] = $lead['click'];
                            $t2[$lead[$transaction_g_b]] = $lead['cost'];   
                        }
                    }
                    
                    if ($split)
                    {
                        foreach ($subs_array as $sub_id => $sub_name)
                        {
                            $val1 = isset($t1[$sub_id]) ? $t1[$sub_id] : array();
                            $val2 = isset($t2[$sub_id]) ? $t2[$sub_id] : array();
                            
                            $results['ExКлики|' . $sub_id] = $val1;
                            $results['ExРасход|' . $sub_id] = $val2;
                        }   
                    }
                    else
                    {
                        $results['ExКлики'] = $t1;
                        $results['ExРасход'] = $t2;         
                    }
                    
                    $t1 = [];
                    
                    $filter = [];
                    $filter[] = "`transactions`.`timestamp` >= '{$start_date}'";
                    $filter[] = "`transactions`.`timestamp` <= '{$end_date}'";
                    $filter[] = "`transactions`.`nls_source_id` IN (".implode(',', $subs).")";
                     
                    if ($channel_id != $source_args)
                    {
                        $filter[] = "`transactions`.`channel_id` IN (".implode(',', $channel_id).")";   
                    }
                    
                    $dop_join = '';
                    
                    if ($minus_our)
                    {
                        $filter[] = "`partner_apps`.`price` > 0";
                        $dop_join = " INNER JOIN `partner_apps` ON `partner_apps`.`call_id` = `transactions`.`call_id`";
                    }                                   
                   
                    $filter = " WHERE (".implode(") AND (", $filter).")";
                    
                    $sql = "SELECT COUNT(*) as `lid`, `transactions`.`{$transaction_g_b}`{$split_str_field} 
                                        FROM `transactions`
                                    LEFT JOIN `nls_sources` ON `nls_sources`.`id` = `transactions`.`nls_source_id` 
                                    {$dop_join}
                                {$filter} GROUP BY `transactions`.`{$transaction_g_b}`{$split_str}"; //if (load::get_user_id() == 1) echo $sql;
                    $leads = pdo::getPdo()->query($sql)->fetchAll(\PDO::FETCH_ASSOC);
                    
                    if ($split)
                    {
                        foreach ($leads as $lead)
                        {
                            $t1[$lead['nls_source_id']][$lead[$transaction_g_b]] = $lead['lid'];   
                        }
                    }
                    else
                    {
                        foreach ($leads as $lead)
                        {
                            $t1[$lead[$transaction_g_b]] = $lead['lid'];  
                        }
                    }                    
                    
                    if ($split)
                    {
                        foreach ($subs_array as $sub_id => $sub_name)
                        {
                            $val1 = isset($t1[$sub_id]) ? $t1[$sub_id] : array();                            
                            $results['Лиды|' . $sub_id] = $val1;
                        }
                    }
                    else
                    {
                        $results['Лиды'] = $t1;
                    }
                    
                    $t1 = [];
                    $t2 = [];
                    
                    $filter = [];
                    $filter[] = "`t1`.`timestamp` >= '{$start_date}'";
                    $filter[] = "`t1`.`timestamp` <= '{$end_date}'";
                    $filter[] = "`t1`.`nls_source_id` IN (".implode(',', $subs).")";
                    $filter[] = "`t1`.`summ` != 0";
                    
                    if ($channel_id != $source_args)
                    {
                        $filter[] = "`t1`.`channel_id` IN (".implode(',', $channel_id).")";   
                    }
                    
                    $dop_join = '';
                    
                    if ($minus_our)
                    {
                        $filter[] = "`partner_apps`.`price` > 0";
                        $dop_join = " INNER JOIN `partner_apps` ON `partner_apps`.`call_id` = `t1`.`call_id`";
                    }                                     
                   
                    $filter = " WHERE (".implode(") AND (", $filter).")";
                    
                    $sql = "SELECT COUNT(`t1`.`id`) as `lid`, ABS(SUM(`t1`.`summ`)) as `cost`, `t1`.`{$transaction_g_b}`{$split_str_field} 
                                        FROM `transactions` `t1`
                                    LEFT JOIN `nls_sources` ON `nls_sources`.`id` = `t1`.`nls_source_id` 
                                        {$dop_join}
                                {$filter} GROUP BY `t1`.`{$transaction_g_b}`{$split_str}"; //if (load::get_user_id() == 2408) echo $sql;
                    $leads = pdo::getPdo()->query($sql)->fetchAll(\PDO::FETCH_ASSOC);
                    
                    if ($split)
                    {
                        foreach ($leads as $lead)
                        {
                            if (!isset($t1[$lead['nls_source_id']][$lead[$transaction_g_b]])) $t1[$lead['nls_source_id']][$lead[$transaction_g_b]] = 0;
                            if (!isset($t2[$lead['nls_source_id']][$lead[$transaction_g_b]])) $t2[$lead['nls_source_id']][$lead[$transaction_g_b]] = 0;
                            
                            $t1[$lead['nls_source_id']][$lead[$transaction_g_b]] += $lead['lid'];
                            $t2[$lead['nls_source_id']][$lead[$transaction_g_b]] += $lead['cost'];   
                        }
                    }
                    else
                    {
                        foreach ($leads as $lead)
                        {
                            if (!isset($t1[$lead[$transaction_g_b]])) $t1[$lead[$transaction_g_b]] = 0;
                            if (!isset($t2[$lead[$transaction_g_b]])) $t2[$lead[$transaction_g_b]] = 0;
                            
                            $t1[$lead[$transaction_g_b]] += $lead['lid'];
                            $t2[$lead[$transaction_g_b]] += $lead['cost'];   
                        }
                    }
                    
                    $filter = [];
                    $filter[] = "`t1`.`timestamp` >= '{$start_date}'";
                    $filter[] = "`t1`.`timestamp` <= '{$end_date}'";
                    $filter[] = "`t1`.`nls_source_id` IN (".implode(',', $subs).")";
                    //$filter[] = "`t1`.`summ` = 0";
                    $filter[] = "`t2`.`summ` != 0";
                    
                    if ($channel_id != $source_args)
                    {
                        $filter[] = "`t1`.`channel_id` IN (".implode(',', $channel_id).")";   
                    }
                    
                    $dop_join = '';
                    
                    if ($minus_our)
                    {
                        $filter[] = "`partner_apps`.`price` > 0";
                        $dop_join = " INNER JOIN `partner_apps` ON `partner_apps`.`call_id` = `t1`.`call_id`";
                    }                                    
                   
                    $filter = " WHERE (".implode(") AND (", $filter).")";
                    
                    $sql = "SELECT COUNT(`t2`.`id`) as `lid`, ABS(SUM(`t2`.`summ`)) as `cost`, `t1`.`{$transaction_g_b}`{$split_str_field} 
                                        FROM `transactions` `t1`
                                    LEFT JOIN `nls_sources` ON `nls_sources`.`id` = `t1`.`nls_source_id`
                                          INNER JOIN `connectors` ON `connectors`.`a` = `t1`.`call_id`
                                            INNER JOIN `transactions` `t2` ON `connectors`.`b_final` = `t2`.`call_id`  
                                                {$dop_join}
                                {$filter} GROUP BY `t1`.`{$transaction_g_b}`{$split_str}"; //if (load::get_user_id() == 1) echo $sql;
                    $leads = pdo::getPdo()->query($sql)->fetchAll(\PDO::FETCH_ASSOC);
                    
                    if ($split)
                    {
                        foreach ($leads as $lead)
                        {
                            if (!isset($t1[$lead['nls_source_id']][$lead[$transaction_g_b]])) $t1[$lead['nls_source_id']][$lead[$transaction_g_b]] = 0;
                            if (!isset($t2[$lead['nls_source_id']][$lead[$transaction_g_b]])) $t2[$lead['nls_source_id']][$lead[$transaction_g_b]] = 0;
                            
                            $t1[$lead['nls_source_id']][$lead[$transaction_g_b]] += $lead['lid'];
                            $t2[$lead['nls_source_id']][$lead[$transaction_g_b]] += $lead['cost'];   
                        }
                    }
                    else
                    {
                        foreach ($leads as $lead)
                        {
                            if (!isset($t1[$lead[$transaction_g_b]])) $t1[$lead[$transaction_g_b]] = 0;
                            if (!isset($t2[$lead[$transaction_g_b]])) $t2[$lead[$transaction_g_b]] = 0;
                            
                            $t1[$lead[$transaction_g_b]] += $lead['lid'];
                            $t2[$lead[$transaction_g_b]] += $lead['cost'];   
                        }
                    }
                    
                    //cashback
                    $filter = [];
                    $filter[] = "`t1`.`timestamp` >= '{$start_date}'";
                    $filter[] = "`t1`.`timestamp` <= '{$end_date}'";
                    $filter[] = "`t1`.`nls_source_id` IN (".implode(',', $subs).")";
                    //$filter[] = "`t1`.`summ` = 0";
                    $filter[] = "`t2`.`summ` != 0";
                    
                    if ($channel_id != $source_args)
                    {
                        $filter[] = "`t1`.`channel_id` IN (".implode(',', $channel_id).")";   
                    }
                    
                    $dop_join = '';
                    
                    if ($minus_our)
                    {
                        $filter[] = "`partner_apps`.`price` > 0";
                        $dop_join = " INNER JOIN `partner_apps` ON `partner_apps`.`call_id` = `t1`.`call_id`";
                    }                                    
                   
                    $filter = " WHERE (".implode(") AND (", $filter).")";
                    
                    $sql = "SELECT COUNT(`t2`.`id`) as `lid`, ABS(SUM(`t2`.`summ`)) as `cost`, `t1`.`{$transaction_g_b}`{$split_str_field} 
                                        FROM `transactions` `t1`
                                    LEFT JOIN `nls_sources` ON `nls_sources`.`id` = `t1`.`nls_source_id`
                                          INNER JOIN `cashbacks` ON `cashbacks`.`call_id` = `t1`.`call_id`
					                           INNER JOIN `connectors` ON `connectors`.`a` = `cashbacks`.`resale_call_id`
                                                    INNER JOIN `transactions` `t2` ON `connectors`.`b_final` = `t2`.`call_id`  
                                                {$dop_join}
                                {$filter} GROUP BY `t1`.`{$transaction_g_b}`{$split_str}"; //if (load::get_user_id() == 1) echo $sql;
                    $leads = pdo::getPdo()->query($sql)->fetchAll(\PDO::FETCH_ASSOC);
                    
                    if ($split)
                    {
                        foreach ($leads as $lead)
                        {
                            if (!isset($t1[$lead['nls_source_id']][$lead[$transaction_g_b]])) $t1[$lead['nls_source_id']][$lead[$transaction_g_b]] = 0;
                            if (!isset($t2[$lead['nls_source_id']][$lead[$transaction_g_b]])) $t2[$lead['nls_source_id']][$lead[$transaction_g_b]] = 0;
                            
                            $t1[$lead['nls_source_id']][$lead[$transaction_g_b]] += $lead['lid'];
                            $t2[$lead['nls_source_id']][$lead[$transaction_g_b]] += $lead['cost'];   
                        }
                    }
                    else
                    {
                        foreach ($leads as $lead)
                        {
                            if (!isset($t1[$lead[$transaction_g_b]])) $t1[$lead[$transaction_g_b]] = 0;
                            if (!isset($t2[$lead[$transaction_g_b]])) $t2[$lead[$transaction_g_b]] = 0;
                            
                            $t1[$lead[$transaction_g_b]] += $lead['lid'];
                            $t2[$lead[$transaction_g_b]] += $lead['cost'];   
                        }
                    }
                    
                    if ($split)
                    {
                        foreach ($subs_array as $sub_id => $sub_name)
                        {
                            $val1 = isset($t1[$sub_id]) ? $t1[$sub_id] : array();
                            $val2 = isset($t2[$sub_id]) ? $t2[$sub_id] : array();
                                                        
                            $results['Продано|' . $sub_id] = $val1;
                            $results['Доход|' . $sub_id] = $val2;
                        }
                    }
                    else
                    {
                        $results['Продано'] = $t1;
                        $results['Доход'] = $t2;
                    }
                    
                    //print_r($results);
                    
                    if ($group_by == 4)
                    {
                        foreach ($results as $k => $result)
                        {
                            $j = 0;
                            $vals = array();
                            
                            for ($i = 0; $i <= 23; $i++)
                            {
                                $r_d = $i;
                                $val = 0;                        
                                if (isset($result[$r_d])) $val = $result[$r_d]; 
                                
                                if (isset($types[1]))
                                {
                                    if ($types[1])
                                    {
                                        $val = $val / $diff_day;
                                    }
                                }
                                
                                $t_results[$k][$r_d] = $val;
                            }
                        }
                        
                    }
                    else
                    {
                        if ($group_by == 2)
                        {
                            $t = date('t', $start_date_ts);
                            $n = $n_s = date('n', $start_date_ts);
                            $Y = date('Y', $start_date_ts); 
                            $j = date('j', $start_date_ts);
                            
                            $t_e = date('t', $end_date_ts);
                            $n_e = date('n', $end_date_ts);
                            $Y_e = date('Y', $end_date_ts);
                            $j_e = date('j', $end_date_ts);
                            
                            $current_date = mktime(0, 0, 0, $n, 1, $Y);
                            $limit = mktime(0, 0, 0, $n_e, $t_e, $Y_e);
                            
                            $first_month_end = mktime(23, 59, 59, $n, $t, $Y) + 1;
                            $last_month_end = mktime(23, 59, 59, $n_e, $t_e, $Y_e) + 1;
                            
                            $p = 0;
                            
                            $month_array = array();
                            $limit_array = array();
                             
                            while ($current_date <= $limit)
                            {
                                $current_date = mktime(0, 0, 0, $n, 1, $Y);    
                                 
                                if (!$p) 
                                    $limit_array[] = ($first_month_end - $start_date_ts) / $day;
                                else
                                    $limit_array[] = date('t', $current_date);
                                    
                                $month_array[date("d.m.Y", $current_date)] = date("Ym", $current_date);
                                $n++;
                                $p++;
                            }
                            
                            array_pop($month_array); 
                            array_pop($limit_array);
                            array_pop($limit_array);
                            
                            $limit_array[] = date('j', $end_date_ts);
                                                   
                            if ((string) $Y_e . (string) $n_e  == (string) $Y . (string) $n_s)
                            {
                                $limit_array = array($j_e - $j + 1);    
                            }         
                        }
                        
                        //if (load::get_user_id() == 1) print_r($results);
                        
                        foreach ($results as $k => $result)
                        {
                            $j = 0;
                            $vals = array();
                            
                            if ($group_by != 2)
                            {
                                for ($i = $end_date_ts; $i >= $start_date_ts; $i -= $step)
                                {
                                    $r_d = date($d_str, $i);
                                    $d = date('d.m.Y', $i);
                                    
                                    $val = 0;                        
                                    if (isset($result[$r_d])) $val = $result[$r_d]; 
                                    
                                    $vals[] = $val;               
                                    
                                    if ($j == 0) $rem_d = $d;
                                    
                                    $j++; 
                                    
                                    if ($j == $limit_j)
                                    {
                                        if (isset($types[1]))
                                        {
                                            if ($types[1])
                                            {
                                                $t_results[$k][$rem_d] = array_sum($vals) / $limit_j;       
                                            }
                                            else
                                            {
                                                $t_results[$k][$rem_d] = array_sum($vals);              
                                            } 
                                        }
                                        else
                                        {   
                                            $t_results[$k][$rem_d] = array_sum($vals);          
                                        }        
                                        
                                        $vals = array();
                                        $j = 0;
                                    }           
                                }
                                
                                if ($j)
                                {
                                    if (isset($types[1]))
                                    {
                                        if ($types[1])
                                        {
                                            $t_results[$k][$rem_d] = array_sum($vals) / $j;       
                                        }
                                        else
                                        {
                                             $t_results[$k][$rem_d] = array_sum($vals);   
                                        }
                                    }
                                    else
                                    {
                                        $t_results[$k][$rem_d] = array_sum($vals);
                                    }
                                }
                            }
                            else
                            {
                                $p = 0;
                                foreach ($month_array as $d => $r_d)
                                {
                                    $val = 0;                        
                                    if (isset($result[$r_d])) $val = $result[$r_d];                
                                    
                                    if (isset($types[1]))
                                    {
                                        if ($types[1])
                                        {
                                            $t_results[$k][$d] = $val / $limit_array[$p];
                                        }
                                        else
                                        {
                                            $t_results[$k][$d] = $val;        
                                        }
                                    }
                                    else
                                    {
                                        $t_results[$k][$d] = $val;    
                                    }
                                    
                                    $p++;
                                }
                            }
                        }
                    }
                    
                    $tbody = [];
                    $tfoot = [];
                    $tr_nls_source_id = [];
                                        
                    if ($split)
                    {
                        $t = [];
                        
                        foreach ($t_results as $key => $value)
                        {
                            $ex_key = explode('|', $key);                            
                            $shift_k = array_shift($ex_key);
                               
                            $ex_key = implode(' ', $ex_key);
                            if (!$ex_key) continue;
                            
                            foreach ($value as $date => $val)
                                $t[$date][$ex_key][$shift_k] = $val;
                        }
                        
                        foreach ($t as $k => $v)
                        {
                            foreach ($v as $kk => $vv)
                            {
                                if ($use_hour_table)
                                    $array = array_merge(['Час' => $k, 'ID' => $kk, 'Сплит' => $subs_array[$kk]], $vv);
                                else
                                    $array = array_merge(['Дата' => $k, 'ID' => $kk, 'Сплит' => $subs_array[$kk]], $vv);
                                    
                                $tbody[] = $array;
                            }
                        }                        
                        
                        //if (load::get_user_id() == 1) {print_r($t_results); print_r($t); print_r($tbody); };  
                        
                        $t = [];
                        $am = [];
                        
                        foreach ($tbody as $k => $value)
                        {
                            if ($use_hour_table)
                                $t[$value['Час']][$value['Сплит']][] = $value;
                            else
                                $t[$value['Дата']][$value['Сплит']][] = $value;
                        }
                        
                        foreach ($t as $data => $v)
                        {
                            foreach ($v as $spl => $val)
                            {
                                $tt = [];
                                
                                foreach ($val as $key => $mas)
                                {
                                    foreach ($mas as $m => $v)
                                    {
                                        if ($m != 'Дата' && $m != 'Час' && $m != 'Сплит' && $m != 'ID')
                                            $tt[$m][] = $v; 
                                    }
                                }
                                
                                foreach ($tt as $k => $m)
                                    $tt[$k] = array_sum($m);
                                
                                if (count($val) == 1)
                                    $id = $val[0]['ID'];
                                else
                                    $id = '-';
                                
                                if ($use_hour_table)
                                    $am[] = array_merge(['Час' => $data, 'ID' => $id, 'Сплит' => $spl], $tt);
                                else
                                    $am[] = array_merge(['Дата' => $data, 'ID' => $id, 'Сплит' => $spl], $tt);      
                            }
                        }
                        
                        $tbody = $am;                                   
                    }
                    else
                    {
                        $t = [];
                        
                        if (count($nls_source_id) == 1)
                            $id = $nls_source_id[0];
                        else
                            $id = '-';

                        foreach ($t_results as $name => $value)
                        {
                            foreach ($value as $key => $val)
                                $t[$key][$name] = $val;
                        }
                        
                        foreach ($t as $key => $vv)
                        {
                            if ($use_hour_table)
                                $tbody[] = array_merge(['Час' => $key, 'ID' => $id], $vv);
                            else
                                $tbody[] = array_merge(['Дата' => $key, 'ID' => $id], $vv);
                        } 
                    }
                    
                    //if (load::get_user_id() == 1) print_r($tbody);
                     
                    foreach ($tbody as $key => $value)
                    {
                        $ex_click = $value['ExКлики'];
                        $ex_cost = $value['ExРасход'];
                        
                        $click = $value['Клики'];
                        $cost = $value['Расход'];
                        
                        unset($value['ExКлики'], $value['ExРасход']);
                        
                        $value['Клики'] += $ex_click;
                        $value['Расход'] += $ex_cost;
                        
                        $tbody[$key]['Клики'] = $value['Клики'];
                        $tbody[$key]['Расход'] = $value['Расход'];
                        
                        foreach ($value as $vk => $vv)
                        {          
                            if ($vk != 'Дата' || $vk != 'Сплит' || $vk != 'Час' || $vk != 'ID' || $vk != 'DT_RowData')
                            {
                                $tfoot[$vk][] = $vv;
                            }   
                        }
                        
                        $conversion = $value['Клики'] ? round($value['Лиды'] / $value['Клики'] * 100, 2).'%' : '0%';
                        $lid_cost = $value['Лиды'] ? round($value['Расход'] / $value['Лиды']) : '0';
                        $lid_prod_cost = $value['Продано'] ? round($value['Расход'] / $value['Продано']) : '0';
                        
                        $roi = $value['Расход'] ? round($value['Доход'] / $value['Расход'] * 100, 2).'%' : '0%';
                        $merge = $value['Доход'] - $value['Расход'];
                        
                        $tbody[$key]['Конверсия'] = $conversion;      
                        $tbody[$key]['Цена лида'] = $lid_cost;
                        $tbody[$key]['Цена прод.лида'] = $lid_prod_cost;
                        $tbody[$key]['ROI'] = $roi;
                        $tbody[$key]['Маржа'] = $merge;
                        
                        $tbody[$key]['DT_RowData']['click'] = number_format($click, 0, '.', '');
                        $tbody[$key]['DT_RowData']['cost'] = number_format($cost, 0, '.', '');
                    }
                    
                    foreach ($tfoot as $vk => $vv)
                        $tfoot[$vk] = array_sum($vv);
                        
                    $conversion = $tfoot['Клики'] ? round($tfoot['Лиды'] / $tfoot['Клики'] * 100, 2).'%' : '0%';
                    $lid_cost = $tfoot['Лиды'] ? round($tfoot['Расход'] / $tfoot['Лиды']) : '0';
                    $lid_prod_cost = $tfoot['Продано'] ? round($tfoot['Расход'] / $tfoot['Продано']) : '0';
                    
                    $roi = $tfoot['Расход'] ? round($tfoot['Доход'] / $tfoot['Расход'] * 100, 2).'%' : '0%';
                    $merge = $tfoot['Доход'] - $tfoot['Расход'];
                    
                    $tfoot['Конверсия'] = $conversion;      
                    $tfoot['Цена лида'] = $lid_cost;
                    $tfoot['Цена прод.лида'] = $lid_prod_cost;
                    $tfoot['ROI'] = $roi;
                    $tfoot['Маржа'] = $merge; 
                    
                    if ($split)
                        $desc_field = [4 => 'click', 10 => 'cost'];
                    else
                        $desc_field = [3 => 'click', 9 => 'cost'];
                    
                    $t_sort = [];
                    foreach ($tbody as $row => $val)
                    {
                        $i = 0;
                        foreach ($thead as $value)
                        {
                            if (in_array($value, $format_price)) $val[$value] = tools::format_price2($val[$value], '');
                            $t_sort[$row][$i] = $val[$value]; 
                            $i++; 
                        }
                        
                        if (isset($val['DT_RowData'])) $t_sort[$row]['DT_RowData'] = $val['DT_RowData']; 
                    }
                    
                    $tbody = $t_sort;                    
                    
                    //if (load::get_user_id() == 1) { print_r($tbody); }
                    
                    bank::getBank()->setArray('order_column', $order_column);
                    bank::getBank()->setArray('order_dir', $order_dir);
                                                
                    usort($tbody, array('framework\ajax\analytic\analytic', 'compare'));
                    
                    $tfoot['Час'] = 'Итого:';
                    $tfoot['Дата'] = 'Итого:';
                    $tfoot['Сплит'] = '-';
                    $tfoot['ID'] = '-';
                        
                    $t_sort = [];
                    $i = 0;
                    foreach ($thead as $value)
                    {
                        if (in_array($value, $format_price)) $tfoot[$value] = tools::format_price2($tfoot[$value], '');
                        $t_sort[$i] = $tfoot[$value];
                        $i++;  
                    }
                    
                    $tfoot = $t_sort;
                        
                    $data_table = new shape\table_striped();
                    $data_table->getAttributes()->getClass()->addItems('table-condensed');               
                    $data_table->setThead($thead);
                    $data_table->setTbody($tbody);
                    $data_table->setTfoots($tfoot);
                    
                    $count_thead = count($thead);
                    $count_tbody = count($tbody);
                    
                    $trh = $data_table->getChildren('thead')->getChildren(0)->getChildren(0);
                        
                    for ($i = 0; $i < $count_thead; $i++)
                    {
                        if ($order_column == $i) 
                        {
                            if (!$order_dir)
                                $trh->getItems($i)->getAttributes()->getClass()->addItems('sorting_asc');   
                            else
                                $trh->getItems($i)->getAttributes()->getClass()->addItems('sorting_desc');
                        }
                    }
                    
                    $trs = $data_table->getChildren('tbody')->getChildren(0);
                    
                    //print_r($trs);
                    
                    if ($edit)
                    {
                        for ($i = 0; $i < $count_tbody; $i++)
                        {
                            $row = $trs->getItems($i)->getChildren(0);
                            $trs->getItems($i)->getAttributes()->addAttr('data-nls_source_id', $row->getItems(1)->getChildren(0));
                            
                            for ($j = 0; $j < $count_thead; $j++)
                            {
                                if (isset($desc_field[$j]))
                                {
                                    $row->getItems($j)->getAttributes()->addAttr('contenteditable', 'true');
                                    $row->getItems($j)->getAttributes()->addAttr('data-field', $desc_field[$j]);
                                }
                            }
                        }
                    } 
                    
                    $enum->addItems($data_table);        
                }
            
            break;
            
            case 'create_filter':
            
                $dop_filter = isset($args['dop_filter']) ? $args['dop_filter'] : array();
                
                foreach (['region_id', 'traffic_id', 'setka_type_id', 'setka_id', 'agregator_id'] as $value)
                {
                    $$value = isset($dop_filter[$value]) ? $dop_filter[$value] : [];
                }
                
                $sql = "SELECT COUNT(*) FROM `regions`";
                $count_regions = pdo::getPdo()->query($sql)->fetchColumn();
                
                $sql = "SELECT COUNT(*) FROM `traffics`";
                $count_traffics = pdo::getPdo()->query($sql)->fetchColumn();
                
                $sql = "SELECT COUNT(*) FROM `setka_types`";
                $count_setka_types = pdo::getPdo()->query($sql)->fetchColumn();
                
                $sql = "SELECT COUNT(*) FROM `setkas` WHERE `no_active` IS NULL OR `no_active` = 0";
                $count_setkas = pdo::getPdo()->query($sql)->fetchColumn();
                
                $sql = "SELECT COUNT(*) FROM `agregators`";
                $count_agregators = pdo::getPdo()->query($sql)->fetchColumn();
                
                $str = '';
                foreach (['Регион' => 'region', 'Трафик' => 'traffic', 'Тип сетки' => 'setka_type', 'Сетка' => 'setka', 'Агрегатор' => 'agregator'] as $syn => $value)
                {
                    $name = 'count_' . $value . 's';
                    $name = $$name;
                    
                    $name_id = $value . '_id';
                    $name_id = $$name_id;
                    
                    $str .=
                    '<div class="x_panel ciba-compact-box ciba-filter-gorod" data-name="'.$value.'">
                        <div class="x_title">
                            <ul class="nav navbar-left panel_toolbox">
                                <li><a class="collapse-link"><i class="fa fa-chevron-down"></i></a></li>
                            </ul>
                            <h2>'.$syn.' <small>'.$name.'</small> </h2>
                            <ul class="nav navbar-right panel_toolbox">
                                <li><a class="js-times" href="#"><i class="fa fa-times"></i></a></li>
                            </ul>
                            <div class="clearfix"></div>
                        </div>
                        <div class="x_content bs-example-popovers" style="display: none;">
                            <div class="form-group wrapper">
                                <input type="text" class="form-control input-sm" placeholder="поиск">
                            </div>
                        </div>
                        <div class="x_content bs-example-popovers" style="display: none;">
                            <div class="filter-list ciba-filter_scrollbox">
                                <div class="ciba-filter-list">';
                                    
                                    $obj = new nls(['mode' => $value, 'id' => $name_id]);
                                    $str .= (string) $obj;
                                    
                                $str .= '</div>
                            </div>
                        </div>
                    </div>';
                }
                
                $enum->addItems($str);
            
            break;
            
            case 'expense_action':
            
                 $nls_source_id = isset($args['nls_source_id']) ? (integer) $args['nls_source_id'] : 0;
                 $field = isset($args['field']) ? (string) $args['field'] : '';
                 $channel = isset($args['channel']) ? (string) $args['channel'] : '';
                 $day = isset($args['day']) ? (string) $args['day'] : '';
                 $original = isset($args['original']) ? (integer) $args['original'] : 0;
                 $value = isset($args['value']) ? (string) $args['value'] : '';
                 
                 $original = intval(preg_replace("/[^0-9]/su", "", $original));
                 $value = intval(preg_replace("/[^0-9]/su", "", $value));
                 
                 $diff = ($value - $original);
                 
                 //if ($diff)
                 //{
                     $str = '$'.$channel.' = 1; return key($channel_id);';
                     $channel_id = eval($str);
                     
                     $day = explode('.', $day);
                     $day = $day[2].'-'.$day[1].'-'.$day[0];
                     
                     $timestamp = strtotime($day);
                     $ym = date('Ym', $timestamp);
                     $h = 23;
                     
                     $user_id = load::get_user_id();
                     $timestamp = date('Y-m-d H:i:s', tools::get_time());
                     
                     $sql = "INSERT INTO `expenses` (`nls_source_id`,`$field`,`channel_id`,`day`,`timestamp`,`user_id`,`ym`,`h`) 
                                    VALUES ($nls_source_id,$diff,$channel_id,'$day','$timestamp',$user_id,$ym,$h) ON DUPLICATE KEY UPDATE `$field` = $diff,
                                                    `timestamp` = '$timestamp', `user_id` = $user_id";
                                    
                     //echo $sql;
                     pdo::getPdo()->query($sql);
                 //}
                 
                 $enum->addItems(tools::format_price2($value, '')); 
                 
            break;
            
            case 'update_consultant':
            
                 $ciba_source_id = isset($args['ciba_source_id']) ? (integer) $args['ciba_source_id'] : 0;
                 $field = isset($args['field']) ? (string) $args['field'] : '';
                 $value = isset($args['value']) ? (string) $args['value'] : '';
                 $secret = isset($args['secret']) ? (string) $args['secret'] : '';
                 $test = isset($args['test']) ? (bool) $args['test'] : false;
                 
                 $error_msg = '';
                
                 foreach (['ciba_source_id', 'field', 'secret'] as $args_name)
                 {
                    if (!isset($args[$args_name]) || !$args[$args_name])
                    {
                        $error_msg = "Отсутствует $args_name"; 
                        break;
                    }
                 }
                 
                 if (!isset($args['value']))
                 {
                     $error_msg = "Отсутствует $value"; 
                 }

                 list($token) = dotenv::get_vars(['TOKEN_TO_CIBA']);
                 
                 if (!$error_msg)
                 {
                     if ($secret != $token)
                     {
                        $error_msg = "Ключ не найден";      
                     }
                 }
                 
                 if (!$error_msg)
                 {
                     $sql = "SELECT `user_id` FROM `nls_sources` WHERE `id`=:id";
                     $stm = pdo::getPdo()->prepare($sql);
                     $stm->execute(array('id' => $ciba_source_id));
                     $ciba_user_id = $stm->fetchColumn();
    
                     if (!$ciba_user_id) $error_msg = "У выбранного источника нет поставщика";
                 }
                 
                 if (!$error_msg)
                 {
                    if (!in_array($ciba_user_id, load::webmaster_ids()))
                    {
                        //$error_msg = "Поставщик источника не вебмастер";
                    }
                 }
                 
                 if (!$error_msg)
                 {
                     $field = htmlspecialchars(strip_tags($field));
                     $value = htmlspecialchars(strip_tags($value));
                 
                     if (!in_array($field, ['name', 'no_active']))
                     {
                         $error_msg = "Поле изменить нельзя";
                     }
                 }
                 
                 if (!$error_msg)
                 {
                     switch ($field)
                     {
                        case 'name':                        
                            
                            $sql = "SELECT `workers`.`name` FROM `users`
                                                INNER JOIN `workers` ON `workers`.`id` = `users`.`worker_id`    
                                                    WHERE `users`.`id`=:user_id";
                                                    
                            $stm = pdo::getPdo()->prepare($sql);
                            $stm->execute(array('user_id' => $ciba_user_id));
                            $worker_array = $stm->fetch(\PDO::FETCH_ASSOC);
                            
                            $value = trim($worker_array['name']).', '.$value;                            
                            
                        break;
                        
                        case 'no_active':
                        
                            $value = (integer) $value;
                            if (!in_array($value, [0, 1]))
                                $error_msg = "Неверное значениие";
                        
                        break;
                     }
                 }
                 
                 if (!$error_msg)   
                 {   
                     if (!$test)
                     {
                        $term = new term(['mode' => 'update', 'table' => 'nls_sources', 'id' => $ciba_source_id, $field => $value]);
                     }   
                 }
                 
                 if ($error_msg)
                 {
                    $this->setCode('error');
                    $enum->addItems($error_msg);
                 }
                 else
                 {
                    $enum->addItems($ciba_source_id);
                 }
                
            break;
            
            case 'add_consultant':
            case 'add_api':
            
                $ciba_user_id = isset($args['ciba_user_id']) ? (integer) $args['ciba_user_id'] : 0;
                $ciba_offer_id = isset($args['ciba_offer_id']) ? (string) $args['ciba_offer_id'] : 0;
                $region_name = isset($args['region_name']) ? (string) $args['region_name'] : '';
                $source_name = isset($args['source_name']) ? (string) $args['source_name'] : '';
                $secret = isset($args['secret']) ? (string) $args['secret'] : '';
                $test = isset($args['test']) ? (bool) $args['test'] : false;
                
                $error_msg = '';
                
                foreach (['ciba_user_id', 'region_name', 'source_name', 'secret'] as $args_name)
                {
                    if (!isset($args[$args_name]) || !$args[$args_name])
                    {
                        $error_msg = "Отсутствует $args_name"; 
                        break;
                    }
                }

                list($token) = dotenv::get_vars(['TOKEN_TO_CIBA']);

                if (!$error_msg)
                {
                    if ($secret != $token)
                    {
                        $error_msg = "Ключ не найден";      
                    }
                }
                
                if (!$error_msg)
                {
                    $sql = "SELECT `id` FROM `regions` WHERE LOWER(`name`)=:region_name";
                    $stm = pdo::getPdo()->prepare($sql);
                    $stm->execute(array('region_name' => mb_strtolower($region_name)));
                    $region_id = $stm->fetchColumn();
                    
                    if ($region_id === false) $error_msg = "Нет такого региона";
                }
                
                if (!$error_msg)
                {
                    $sql = "SELECT `workers`.`name`, `workers`.`id` FROM `users`
                                        INNER JOIN `workers` ON `workers`.`id` = `users`.`worker_id`    
                                            WHERE `users`.`id`=:user_id";
                                            
                    $stm = pdo::getPdo()->prepare($sql);
                    $stm->execute(array('user_id' => $ciba_user_id));
                    $worker_array = $stm->fetch(\PDO::FETCH_ASSOC);
                    
                    if (!$worker_array) $error_msg = "Нет такого пользователя";
                }
                
                if (!$error_msg)
                {
                    if (!in_array($ciba_user_id, load::webmaster_ids()))
                    {
                        //$error_msg = "Такой пользователь не вебмастер";
                    }
                }
                
                if (!$error_msg)
                {
                    if (!$test)
                    {
                        $term = new term(['mode' => 'add', 'table' => 'nls_sources', 'name' => trim($worker_array['name']).', '.$source_name,
                                                            'region_id' => $region_id, 'organization_id' => 0,
                                                                'addres_id' => 0, 'parent' => 0, 'no_active' => 0,
                                                                        'user_id' => $ciba_user_id, 'uis_line_id' => 0]);
                                                                        
                        $nls_source_id = (integer) $term->getWrapper()->getChildren(0);
                        
                        if (!$nls_source_id)
                        { 
                            $error_msg = "Источник не создан";
                        }
                    }
                    else
                    {
                        $nls_source_id = rand(10000, 99999);
                    }                    
                }
                
                if (!$error_msg)
                {
                    if (!$test)
                    {
                        if ($mode == 'add_api')
                        {
                            $array = [['traffic', 3], ['worker', $worker_array['id']]];
                            
                            if ($ciba_offer_id)
                            {
                                $array[] = ['offer', $ciba_offer_id];
                            }
                            
                            foreach ($array as $tag)
                            {
                                $tags = new term(['mode' => 'add', 'table' => 'nls_source_tags', 'nls_source_id' => $nls_source_id,
                                                    'parent' => null, 'name_type' => $tag[0], 'id_type' => $tag[1]]);
                            }
                            
                        }
                        else
                        {
                            $array = [['traffic', 3], ['worker', $worker_array['id']], ['traffic', 7]];
                             
                            if ($ciba_offer_id)
                            {
                                $array[] = ['offer', $ciba_offer_id];
                            }
                            
                            foreach ($array as $tag)
                            {
                                $tags = new term(['mode' => 'add', 'table' => 'nls_source_tags', 'nls_source_id' => $nls_source_id,
                                                    'parent' => null, 'name_type' => $tag[0], 'id_type' => $tag[1]]);
                            }
                        }
                    }
                }
                
                if ($error_msg)
                {
                    $this->setCode('error');
                    $enum->addItems($error_msg);
                }
                else
                {
                    $enum->addItems($nls_source_id);
                }
                
            break;
            
            /*case 'expense_action':
            
                 $nls_source_id = isset($args['nls_source_id ']) ? (integer) $args['nls_source_id '] : 0;
                 $cost = isset($args['cost']) ? (integer) $args['cost'] : 0;
                 $click = isset($args['click']) ? (integer) $args['click'] : 0;
                 $channel_id = isset($args['channel_id ']) ? (integer) $args['channel_id '] : 0;
                 $day = isset($args['day']) ? (string) $args['day'] : '';
                 
                 $all = array('nls_source_id' => 'Источник', 'cost' => 'Расход', 'click' => 'Клики', 'channel_id' => 'Канал', 'date' => 'Дата');
                 
                 $pass = true;
                 
                 foreach ($all as $k => $a)
                 {
                    if (!$args[$k])
                    {
                        $notifys = new term(
                                    array(
                                        'mode' => 'add', 
                                        'table' => 'notifys',
                                        'text' => htmlspecialchars('Поле "' . $a .'" обязательно для заполнения!'), 
                                        'type_notify_id' => load::get_status('type_notifys', 'update_error'),
                                        'session' => session_id(),
                                        )
                                    );
                                      
                        $this->setCode('error');
                        $pass = false;
                        break;
                    }
                 }
                 
                 if ($pass)
                 {
                     $obj = new term(array('mode' => 'add', 'table' => 'expenses', 'nls_source_id' => $nls_source_id, 'cost' => $cost,
                                            'click' => $click, 'channel_id' => $channel_id, 'day' => $date));
                                            
                     $notifys = new term(
                                array(
                                    'mode' => 'add',
                                    'table' => 'notifys',
                                    'text' => 'Расходы внесены',
                                    'type_notify_id' => load::get_status('type_notifys', 'add_success'),
                                    'session' => session_id(),
                                )
                            );
                 }
            
            break;
            
            case 'expense_modal':
            
                $nls_source_id = isset($args['nls_source_id']) ? $args['nls_source_id'] : 0;
                
                $modal = new modal('expense_modal');
                
                $enum_modal = new enum();
                $enum_modal->setSign('');
                
                $form = new node('form');
                $form->getAttributes()->getClass()->setItems(array('form','form-horizontal','form-label-left'));
                  
                foreach (array('nls_source_id' => 'Источник', 'cost' => 'Расход', 'click' => 'Клики', 'channel_id' => 'Канал', 'day' => 'Дата') as $key => $value)
                {
                    $div = new node('div');
                    $div->getAttributes()->getClass()->setItems(array('col-md-12', 'col-sm-12', 'col-xs-12', 'form-group'));                                                    
        
                    $label_node = new node('label');
                    $label_node->getAttributes()->getClass()->setItems(array('control-label', 'col-md-3', 'col-sm-3', 'col-xs-12'));
                    $label_node->addChildren($value); 
                    
                    switch ($key)
                    {
                        case 'nls_source_id':
                            $input = new form\input_box();
                        break;
                        case 'cost': case 'click':
                            $input = new form\input_box();
                        break;
                        case 'channel_id':
                             $input = new form\select();
                             
                             $sql = "SELECT `id`, `name` FROM `channels` WHERE (`no_active` IS NULL OR `no_active` = 0)";
                             $channels = pdo::getPdo()->query($sql)->fetchAll(\PDO::FETCH_KEY_PAIR);                             
                             $input->setValues($channels);    
                            
                        break;
                        case 'day':
                            $input = new form\input_box('date');
                            $input->setValue(date('d.m.Y'), tools::get_time());
                            $input->getAttributes()->getClass()->addItems('passed');
                        break;
                    }
                    
                    $input->getAttributes()->getClass()->addItems('form-control');
                    $input->getAttributes()->addAttr('id', $key);
                    $input->getAttributes()->addAttr('name', $key);
                    
                    if ($key == 'cost') 
                        $input->setPlaceholder(tools::format_price2(10000, ''));
                    else
                        if ($key == 'click')
                            $input->setPlaceholder(tools::format_price2(10, ''));
                        else
                            $input->setPlaceholder($value);
                    
                    if ($key == 'nls_source_id' && $nls_source_id)
                    {
                        $input->setValue($nls_source_id);      
                        $input->getAttributes()->getClass()->addItems('passed');
                    }
                    
                    $div_wrapper = new node('div');
                    $div_wrapper->getAttributes()->getClass()->setItems(array('col-md-9', 'col-sm-9', 'col-xs-12'));
                    $div_wrapper->addChildren($input);
                    
                    $div->addChildren($label_node);
                    $div->addChildren($div_wrapper);
                    
                    $form->addChildren($div);         
                }
                
                $button_primary = new form\button_primary('Внести');
                $modal_footer = new node('div');
                $modal_footer->addChildren($button_primary);
                
                $modal_footer->getAttributes()->getClass()->addItems('modal-footer');
                    
                $modal->getChildren(0)->getChildren(0)->setChildren('footer', $modal_footer);  
                
                $enum_modal->addItems($form);
                
                $modal->getAttributes()->getClass()->addItems('wrapper');
                $modal->getAttributes()->getClass()->addItems('b-modal');
                
                $modal->setTitle('Внести расходы');
                $modal->setBody($enum_modal);
                
                $modal->setButtonclose(true);
                $modal->setBclose(false);
                $modal->setShowfooter(true);
                                
                $enum->addItems($modal);    
            
            break;*/
        }
        
        $answer = $enum;          
        $this->getWrapper()->addChildren($answer);
    }
    
    public function create_tag($key)
    {
        return "<div class='label label-dark-success'>$key</div>";
    }
    
    private function _calc_ids($dop_filter)
    {
        $nls_source_ids = [];
        
        $do_search = false;
                    
        $search = '';
        if (isset($dop_filter['nls_search']))
        {
            $search = $dop_filter['nls_search'];
            unset($dop_filter['nls_search']);
        }
        
        if ($search)
        {
            if ($search)
            {
                $filter2 = [];
                
                foreach (array('`nls_sources`.`id`', '`nls_sources`.`name`') as $field)
                {
                    $filter2[] = "$field LIKE '%{$search}%'";
                }

                $filter2 = "(".implode(") OR (", $filter2).")";
                
                $sql = "SELECT `id` FROM `nls_sources` WHERE {$filter2}";
                $nls_source_ids[] = (array) pdo::getPdo()->query($sql)->fetchAll(\PDO::FETCH_COLUMN);
                $do_search = true;
            }
        }
        
        if (isset($dop_filter['region_id']))
        {
            if ($dop_filter['region_id'])
            {
                if (is_array($dop_filter['region_id']))
                    $filter = "`nls_sources`.`region_id` IN (".implode(',', $dop_filter['region_id']).")";
                else
                    $filter = "`nls_sources`.`region_id` = {$dop_filter['region_id']}";
                    
               $sql = "SELECT `id` FROM `nls_sources` WHERE {$filter}";
               $nls_source_ids[] = (array) pdo::getPdo()->query($sql)->fetchAll(\PDO::FETCH_COLUMN);
               $do_search = true;
               
               unset($dop_filter['region_id']);
            }
        }
        
        foreach ($dop_filter as $key => $value)
        {
            if ($value)
            {
                 $field_key = mb_substr($key, 0, -3); 
                 if (is_array($value))
                    $filter = "`nls_source_tags`.`name_type` = '$field_key' AND `nls_source_tags`.`id_type` IN (".implode(',', $value).")";
                 else
                    $filter = "`nls_source_tags`.`name_type` = '$field_key' AND `nls_source_tags`.`id_type` = $value";
                    
                $sql = "SELECT `nls_sources`.`id` FROM `nls_sources` LEFT JOIN `nls_source_tags` ON `nls_sources`.`id` = `nls_source_tags`.`nls_source_id` WHERE {$filter}";
                $nls_source_ids[] = (array) pdo::getPdo()->query($sql)->fetchAll(\PDO::FETCH_COLUMN);
                $do_search = true; 
            }
        }
        
        $peresechenie = array();
        $peresechenie_all = array();
        
        foreach ($nls_source_ids as $id)
        {
            foreach ($id as $g)
            {
                $peresechenie[] = $g;
            }
        }

        $count = count($nls_source_ids);

        foreach (array_count_values($peresechenie) as $key => $value)
        {
            if ($value == $count)
            {
                $peresechenie_all[] = $key;
            }
        }
        
        $filter = array();                    
        $filter[] = "`nls_sources`.`parent` = 0";
        
        if ($peresechenie_all)
        {
            $filter[] = "`nls_sources`.`id` IN (".implode(',', $peresechenie_all).")";
        }
        else
        {
            if ($do_search)
            {
                $filter[] = "`nls_sources`.`id` = -1";
            }
        }
        
        $filter[] = "`nls_sources`.`id` != 51961";
        
        $filter = " WHERE (".implode(") AND (", $filter).")";
        
        return $filter;               
    }
}