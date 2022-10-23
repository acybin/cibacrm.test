<?

namespace framework\ajax\calendar;

use framework\ajax as ajax;
use framework\enum;
use framework\shape\table_striped;
use framework\load;
use framework\pdo;
use framework\tools;
use framework\dom\node;
use framework\shape\form as form;
use framework\ajax\term\term;
use framework\ajax\call\call;
use framework\shape\modal;
use framework\shape\ul;
use framework\uis_data;
use framework\ajax\user\UserAccess;

class calendar extends ajax\ajax
{
    private $_week = array('Вс', 'Пн', 'Вт', 'Ср', 'Чт', 'Пт', 'Сб');
    public static $color_masks = array('8-18', '9-19', '10-20', '11-21', '12-22');
    
    private $_fix_dinner = array('operator' => 60);
        
    public function __construct($args = array())
    {
        error_reporting(E_ALL);
        ini_set('display_errors', 1);
        
        parent::__construct('calendar');
        
        $mode = isset($args['mode']) ? $args['mode'] : '';
        $this->getWrapper()->getAttributes()->addAttr('id', 'calendar-'.$mode);
        
        $answer = '';

        $userAccess = new UserAccess();

        switch ($mode)
        {
            case 'show':
                
                $month = isset($args['month']) ? (integer) $args['month'] : date("n");
                $user_id = isset($args['user_id']) ? $args['user_id'] : load::get_user_id();
                  
                $answer = $this->_construct_calendar($month, $user_id); 
                
            break;
            
            case 'show_spec':
            
                 $user_id = isset($args['user_id']) ? $args['user_id'] : 0;
                 $addres_id = isset($args['addres_id']) ? $args['addres_id'] : array(858, 859, 860);
                 $group_array = isset($args['group_array']) ? $args['group_array'] : array();
                 
                 if (is_string($group_array)) $group_array = explode(',', $group_array);
                  
                 $current_user_id = load::get_user_id();
                 
                 $group_code = load::get_group_code();
                 $is_boss_2 = in_array($group_code, array('moderator', 'director', 'administrator'));
                 
                 $master_boss = false;
                 
                 if (count($group_array) == 1)
                 {    
                    $c_gr_array = current($group_array);
                    $master_boss = load::is_boss($user_id, $addres_id, load::get_status('groups', $c_gr_array));
                 }
                                  
                 if ($user_id)
                 {
                    $sql = "SELECT `id`, `name` FROM `model_types` WHERE `organization_id` IS NULL AND `file` IS NOT NULL ORDER BY `id` ASC";
                    $model_types = pdo::getPdo()->query($sql)->fetchAll(\PDO::FETCH_KEY_PAIR); 
                    
                    foreach ($model_types as $k => $v)
                        $model_types[$k] = tools::mb_ucfirst2($v);        
                    
                    $sql = "SELECT `k` FROM `ks` WHERE `user_id`=:user_id";
                    $stm = pdo::getPdo()->prepare($sql);
                    $stm->execute(array('user_id' => $user_id));  
                    $k = $stm->fetchColumn();
                    
                    if ($k == NULL) $k = 1;
                    
                    $sql = "SELECT `model_type_id` FROM `specs` WHERE `user_id`=:user_id";
                    $stm = pdo::getPdo()->prepare($sql);
                    $stm->execute(array('user_id' => $user_id));  
                    $specs = $stm->fetchAll(\PDO::FETCH_COLUMN);
                    
                    $t = array();
                    foreach ($specs as $spec)
                        $t[$spec] = 1;
                        
                    $specs = $t;
                     
                    $div_menu = new node('div');
                    $div_menu->getAttributes()->getClass()->addItems('spec-menu');
                    
                    $item = new form\input_box();
                    $item->getAttributes()->getClass()->addItems('form-control');
                    $item->setName('k');
                    $item->setValue($k);
                    
                    $k_span = new node('span');
                    $k_span->getAttributes()->getClass()->addItems('k-input');
                    $k_span->addChildren('Коэффициент');
                    $k_span->addChildren($item);
                    
                    if (load::is_super_user())
                    {
                        $div_menu->addChildren($k_span);
                    }
                    
                    $ul = new ul();
                    $ul->setValues($model_types);
                    $lis = $ul->getChildren('li');
                    
                    $i = 0;
                    foreach ($model_types as $k => $v)
                    {
                        $lis->getItems($i)->getAttributes()->addAttr('data-model_type_id', $k);
                        
                        $label = new node('label');
                        if ($is_boss_2 || $master_boss) $label->getAttributes()->getClass()->addItems('active');
                        
                        $fa = new node('i');
                        
                        if (isset($specs[$k]))
                            $fa->getAttributes()->getClass()->setItems(array('fa', 'fa-check-square-o'));
                        else
                            $fa->getAttributes()->getClass()->setItems(array('fa', 'fa-square-o'));
                        
                        $label->addChildren($fa);
                        $label->addChildren($lis->getItems($i)->getChildren(0));
                        
                        $lis->getItems($i)->setChildren(array($label));
                        $i++;
                    }
                    
                    $div_menu->addChildren($ul); 
                    
                    $answer = $div_menu;
                 }
                 
            break;
            
            case 'change_spec':
                
                $user_id = isset($args['user_id']) ?  (integer) $args['user_id'] : 0;
                $model_type_id = isset($args['model_type_id']) ? (integer) $args['model_type_id'] : 0; 
                $remove = isset($args['remove']) ?  (integer) $args['remove'] : 0;
                
                if ($remove)
                {
                    $sql = "DELETE FROM `specs` WHERE `user_id`=:user_id AND `model_type_id`=:model_type_id";
                    $stm = pdo::getPdo()->prepare($sql);
                    $stm->execute(array('user_id' => $user_id, 'model_type_id' => $model_type_id)); 
                }
                else
                {
                    $array = array('user_id' => $user_id, 'model_type_id' => $model_type_id);
                    $sql = "INSERT INTO `specs` SET ".pdo::prepare($array);
                    $stm = pdo::getPdo()->prepare($sql);  
                    $stm->execute($array);
                }
                
            break;
            
            case 'change_k':
            
                $user_id = isset($args['user_id']) ?  (integer) $args['user_id'] : 0;
                $k = isset($args['k']) ? (float) $args['k'] : 0; 
                
                $sql = "INSERT IGNORE INTO `ks` (`user_id`,`k`) VALUES (".$user_id.",".$k.") ON DUPLICATE KEY UPDATE `k` = $k";
                                                            
                pdo::getPdo()->query($sql);  
                
                $answer = $k;
            
            break;
            
            case 'fired':
            
                $user_id = isset($args['user_id']) ? (integer) $args['user_id'] : 0;
                $organization_id = isset($args['organization_id']) ? (integer) $args['organization_id'] : 0;
                $group_id = isset($args['group_id']) ? (string) $args['group_id'] : '';
                
                if ($user_id && $group_id)
                {
                    $user_array = array('mode' => 'update', 'table' => 'users', 'id' => $user_id, 'not_active' => 1);
                    $user_obj = new term($user_array);
                    
                    $sip_id = load::get_order($user_id, 'sip_id', 'users');  
                    
                    if ($sip_id)
                    {
                        $sip_uis_id = load::get_order($sip_id, 'sip_id', 'sips'); 
                        $employee = (integer) load::get_order($sip_id, 'employee', 'sips');
                          
                        $uis = new uis_data();
                        
                        if ($sip_uis_id)
                        {
                            $params = [];
                            $params['id'] = (integer) $sip_uis_id;
    
                            $responce = $uis->callMethod('delete.sip_lines', $params);
                        }
                        
                        if ($employee)
                        {
                            if ($group_id == 1 || $group_id == '18a')
                            {
                                if ($group_id == 1)
                                {
                                    $static_groups = call::getStaticGroups();
                                    $group_uis_id = $static_groups[$organization_id];
                                }
                                else
                                {
                                    $group_uis_id = 230476;
                                }
                                    
                                $params = [];
                                $params['filter'] = ['field' => 'id', 'operator' => '=', 'value' => $group_uis_id];
                                
                                $response = $uis->callMethod('get.group_employees', $params);
                                
                                if (isset($response['result']['data']) && $response['result']['data'])
                                {
                                    $members = $response['result']['data'][0]['members'];
                                    
                                    $t = [];
                                    foreach ($members as $member)
                                    {
                                        if ($member['employee_id'] == $employee) continue;
                                        $t[] = (integer) $member['employee_id'];
                                    }
                                    
                                    $members = $t;
                                    
                                    $params = [];
                                    $params['id'] = $group_uis_id;
                                    $params['members'] = $members;
                                    
                                    $response = $uis->callMethod('update.group_employees', $params);
                                }
                            }
                            
                            $params = [];
                            $params['id'] = (integer) $employee;
                            $response = $uis->callMethod('delete.employees', $params);
                        }
                    }
                }
                
            break;
            
            case 'recrut_table':
            
                $organization_id = isset($args['organization_id']) ? (integer) $args['organization_id'] : 0;
                
                $enum = new enum();
                $enum->setSign('');  
                
                $data_table = new table_striped();
                $data_table->getAttributes()->getClass()->addItems('table-condensed');
                
                $tbody = array();                
                
                $thead = array('id', 'Ф.И.О.', 'Логин', 'Права', 'SIP', '');   
                $group_array = array(7 => 'Владелец', 13 => 'Управляющий', 1 => 'Оператор', 2 => 'Администратор', 8 => 'Менеджер', 10 => 'Мастер', 3 => 'Курьер',
                                                21 => 'Выездной мастер');
                
                $group_ids = array_keys($group_array);
                
                $filter = [];
                
                if ($organization_id)
                {
                    $filter[] = "`address`.`organization_id` = {$organization_id}";
                    $filter[] = "`groups`.`id` IN (".implode(',', $group_ids).")";
                }
                
                if (!$organization_id)
                {
                    $filter[] = "`access`.`addres_id`= 0";
                }
                
                $filter[] = "(`users`.`not_active`  IS NULL OR `users`.`not_active` = 0)";
                $filter = " WHERE (".implode(") AND (", $filter).")";
                                
                $sql = "SELECT `users`.`id` as `user_id`,
                                    `workers`.`name` as `worker_name`, `users`.`name` as `user_name`,
                                         `sips`.`name` as `sip`, `groups`.`name` as `group_name`, `groups`.`id` as `group_id`,
                                            `role_workers`.`name` as `role`, `role_workers`.`id` as `role_id`                                    
                                            FROM `users`
                                                LEFT JOIN `workers` ON `users`.`worker_id` = `workers`.`id`
                                                    LEFT JOIN `access` ON `access`.`user_id` = `users`.`id`
                                                        LEFT JOIN `address` ON `address`.`id` = `access`.`addres_id`
                                                            LEFT JOIN `sips` ON `users`.`sip_id` = `sips`.`id` 
                                                                LEFT JOIN `groups` ON `access`.`group_id` = `groups`.`id` 
                                                                    LEFT JOIN `role_workers` ON `workers`.`role_worker_id` = `role_workers`.`id` 
                                                                       {$filter}
                                                                                ORDER BY find_in_set(`group_id`,'".implode(',', $group_ids)."'),
                                                                                    `role_workers`.`name` ASC,
                                                                                        `workers`.`name` ASC"; //echo $sql;
                                                    
                $stm = pdo::getPdo()->prepare($sql);
                $stm->execute(array());
                $users = $stm->fetchAll(\PDO::FETCH_ASSOC);
                
                $t = [];
                foreach ($users as $user)
                    $t[$user['user_id']] = $user;
                    
                $users = $t;
                
                foreach ($users as $user)
                {
                    if ($organization_id == 0)
                    {
                        $user['group_name'] = $user['role'];
                        
                        if ($user['role_id'] == 44) 
                        {
                            if ($user['sip'])
                                $user['group_id'] = '18a';
                            else
                                $user['group_id'] = '18c';
                        }
                        
                        if ($user['role_id'] == 41) $user['group_id'] = '18b';
                    }
                    
                    $a = new node('a');
                    $a->getAttributes()->getClass()->setItems(array('js-trash', 'btn', 'btn-default'));
                    
                    $a->getAttributes()->addAttr('data-id', $user['user_id']);
                    $a->getAttributes()->addAttr('data-group_id', $user['group_id']);
                    $a->addChildren('Уволить');
                        
                    $fa = new node('i');
                    $fa->getAttributes()->getClass()->setItems(array('fa', 'fa-trash-o'));
                    
                    $a->addChildren($fa);
                    
                    $tbody[] = [$user['user_id'], $user['worker_name'], $user['user_name'], $user['group_name'], $user['sip'], (string) $a];       
                }
                
                $data_table->setThead($thead);
                $data_table->setTbody($tbody);
                $enum->addItems($data_table);

                $static_groups = call::getStaticGroups();
                $static_groups[0] = 230476;
                
                if (isset($static_groups[$organization_id]))
                {
                    $p = new node('p');
                    $b = new node('b');
                    $b2 = new node('b');
                    
                    $b->addChildren($static_groups[$organization_id]);
                    $p->addChildren('ID группы UIS: ');                    
                    $p->addChildren($b);             
                    
                    $b2->addChildren(count($tbody));
                    $p->addChildren(', всего сотрудников: ');
                    $p->addChildren($b2); 
                                               
                    $enum->addItems($p);
                }
                
                $hidden = new form\hidden();
                $hidden->setName('mode');
                $hidden->setValue($mode);     
                $enum->addItems($hidden); 
                
                $hidden = new form\hidden();
                $hidden->setName('organization_id');
                $hidden->setValue($organization_id);     
                $enum->addItems($hidden); 
                
                $answer = $enum; 
            
            break;
            
            case 'recrut_modal':
            
                //$group_id = isset($args['group_id']) ? $args['group_id'] : array();                
                //$group_name = mb_strtolower(load::get_order($group_id, 'name', 'groups'));
                   
                $modal = new modal('recrut_modal');
                
                $enum = new enum();
                $enum->setSign('');
                
                $form = new node('form');
                $form->getAttributes()->getClass()->setItems(array('form','form-horizontal','form-label-left'));
                
                foreach (array('name' => 'Ф.И.О.', 'group_id' => 'Должность', 'organization_id' => 'Владелец') as $key => $value)
                {
                    if ($key == 'phone') continue;
                    
                    $div = new node('div');
                    $div->getAttributes()->getClass()->setItems(array('col-md-12', 'col-sm-12', 'col-xs-12', 'form-group'));   
                    
                    switch ($key)
                    {
                        case 'name':
                            $input = new form\input_box();
                        break;
                        case 'group_id':
                            $input = new form\select();
                            $select_values = array(7 => 'Владелец (партнер)', 13 => 'Управляющий/логист (партнер)', 1 => 'Оператор (партнер)', 2 => 'Администратор (партнер)',
                                        8 => 'Менеджер (партнер)', 10 => 'Мастер (партнер)', 3 => 'Курьер (партнер)', 21 => 'Выездной мастер (партнер)',
                                                        '18a' => 'Отдел ресайл (оператор ресайл) (CIBA)', '18b' => 'Отдел продаж (CIBA)', '18c' => 'Отдел ресайл (без фонера)/аудитор (CIBA)');
                            $input->setValues($select_values);
                        break;
                        case 'organization_id':
                            $input = new form\select();
                            $select_values = array(2830 => 'МСК 2830', 140 => 'СПБ 140', 0 => 'CIBA CRM');
                            $input->setValues($select_values);
                        break;
                    }
                    
                    $input->getAttributes()->getClass()->addItems('form-control');
                    $input->setName($key);
                    $input->setPlaceholder($value);
                    
                    $div->addChildren($input);
                    $form->addChildren($div); 
                }
                
                /*$hidden = new form\hidden();
                $hidden->setValue($group_id);
                $hidden->setName('group_id');
                
                $form->addChildren($hidden);*/
                
                $button_primary = new form\button_primary('Создать');
                $modal_footer = new node('div');
                $modal_footer->addChildren($button_primary);
                
                $modal_footer->getAttributes()->getClass()->addItems('modal-footer');
                    
                $modal->getChildren(0)->getChildren(0)->setChildren('footer', $modal_footer);  
                
                $enum->addItems($form);
                
                $modal->getAttributes()->getClass()->addItems('wrapper');
                $modal->getAttributes()->getClass()->addItems('b-modal');
                $modal->setTitle('Новый сотрудник');
                $modal->setBody($enum);
                
                $modal->setButtonclose(true);
                $modal->setBclose(false);
                $modal->setShowfooter(true);
                                
                $answer = $modal;
                
            break;
            
            case 'recrut':
            
                $name = isset($args['name']) ? (string) $args['name'] : '';
                $organization_id = isset($args['organization_id']) ? (integer) $args['organization_id'] : 0;
                $group_id = isset($args['group_id']) ? (string) $args['group_id'] : '';                
                
                $enum = new enum();
                $enum->setSign('');
                
                $name = $worker_name = tools::cut_empty($name);
                
                $all = ['name' => 'Ф.И.О.', 'group_id' => 'Должность'];
               
                $pass = true;
                
                foreach ($all as $k => $a)
                {
                    if (!isset($args[$k]) || !$args[$k])
                    {
                        if (session_id())
                        {
                            $notifys = new term(
                                        array(
                                            'mode' => 'add', 
                                            'table' => 'notifys',
                                            'text' => htmlspecialchars('Поле "' . $a .'" обязательно для заполнения!'), 
                                            'type_notify_id' => load::get_status('type_notifys', 'update_error'),
                                            'organization_id' => load::get_org_id(),
                                            'session' => session_id(),
                                            )
                                        );
                        }
                        $this->setCode('error');
                        $pass = false;  
                        break;
                    }
                }
                
                if ($pass)
                {
                    if (in_array($group_id, ['18a', '18b', '18c']))
                    {
                        $organization_id = 0;
                        $group_access_id = 18;
                    }
                    else
                    {
                        $group_access_id = $group_id = (integer) $group_id;
                        
                        if (!$organization_id)
                        {
                            $notifys = new term(
                                    array(
                                        'mode' => 'add', 
                                        'table' => 'notifys',
                                        'text' => htmlspecialchars('Поле "Владелец" обязательно для заполнения!'), 
                                        'type_notify_id' => load::get_status('type_notifys', 'update_error'),
                                        'organization_id' => load::get_org_id(),
                                        'session' => session_id(),
                                        )
                                    );
                            $this->setCode('error');
                            $pass = false;  
                        }
                    }
                }
                
                if ($pass)
                {
                    $role_worker_accord = [7 => 37, 13 => 34, 1 => 2, 2 => 28, 8 => 29, 10 => 32, 3 => 1, '18a' => 44, '18b' => 41, '18c' => 44, 21 => 48];               
                    $explode = explode(' ', $name);
                    
                    $name_str = $explode[0]. ((isset($explode[1]) && mb_strlen($explode[1]) > 0) ? ('_' . mb_substr($explode[1], 0, 1)) : '');
                    
                    $name = tools::transl_correct($name_str);
                    $name_f = $name;
                    $pass = tools::randomPassword(8);  
                    
                    $i = 0;
                    do {
                        if ($i) $name = $name_f . $i;
                        $sql = "SELECT `id` FROM `users` WHERE `name`=:name";
                        $stm = pdo::getPdo()->prepare($sql);
                        $stm->execute(array('name' => $name));
                        $user_id = $stm->fetchColumn();
                        $i++;
                    }  while ($user_id);
                    
                    $worker_obj = new term(array('mode' => 'add', 'table' => 'workers', 'name' => $worker_name, 'organization_id' => $organization_id, 
                                                        'role_worker_id' => $role_worker_accord[$group_id]));
                    $worker_id = (integer) $worker_obj->getWrapper()->getChildren(0);
                                   
                    $user_array = array('mode' => 'add', 'table' => 'users', 'name' => $name, 'pass' => $pass, 'worker_id' => $worker_id, 'button' => 1);                        
                    $user_obj = new term($user_array);
                    $user_id = (integer) $user_obj->getWrapper()->getChildren(0);
                    
                    if ($organization_id)
                    {
                        $sql = "SELECT `id` FROM `address` WHERE `organization_id`=:organization_id AND `id` != 4245";
                        $stm = pdo::getPdo()->prepare($sql);
                        $stm->execute(array('organization_id' => $organization_id));
                        $addr2 = $stm->fetchAll(\PDO::FETCH_COLUMN);
                    }
                    else
                    {
                        $addr2 = [0];
                    }     
                                    
                    $insert = [];
                    
                    foreach ($addr2 as $value)
                    {
                        $insert[] = "(".$user_id.",".$value.",".$group_access_id.")";            
                    }
                    
                    if ($insert)
                    {
                        $sql = "INSERT IGNORE INTO `access` (`user_id`, `addres_id`, `group_id`) VALUES ".implode(',', $insert);
                        pdo::getPdo()->query($sql);
                    }
                    
                    $array_str = [];
                    $array_str['Логин'] = $name;
                    $array_str['Пароль'] = $pass;
                    
                    if (in_array($group_id, [7, 13, 1, 8, '18a', '18b', 21]))
                    {
                        $uis = new uis_data();
                        
                        $params = [];

                        $params['last_name'] = $explode[0];
                        if (isset($explode[1])) $params['first_name'] = $explode[1];
                        $params['patronymic'] = (string) $organization_id;
                        $params['phone_numbers'] = [['phone_number' => '70000000000']];
                        
                        $response = $uis->callMethod('create.employees', $params);
                        
                        if (isset($response['result']['data']['id']))
                        {
                            $employee = $response['result']['data']['id'];

                            $params = [];
                            $params['employee_id'] = $employee;
                            
                            if ($group_id == '18a' || $group_id == '18b')
                            {
                                $mango_name = '74951045666';
                            }
                            else
                            {
                                $static_mangos = call::getStaticMangos();
                                $mango_name = load::get_order($static_mangos[$organization_id], 'name', 'mangos');
                            }
                            
                            $params['virtual_phone_number'] = $mango_name;
                            $response = $uis->callMethod('create.sip_lines', $params);
                            
                            if (isset($response['result']['data']['id']))
                            {
                                $array_str['Логин SIP'] = $response['result']['data']['phone_number'];
                                $array_str['Пароль SIP'] = $response['result']['data']['password'];
                                
                                $sip_obj = new term(['mode' => 'add', 'table' => 'sips', 'name' => $response['result']['data']['phone_number'], 'employee' => $employee,
                                                            'sip_id' => $response['result']['data']['id']]);
                                $sip_id = (integer) $sip_obj->getWrapper()->getChildren(0);
                                
                                $term2 = new term(['mode' => 'update', 'table' => 'users', 'id' => $user_id, 'sip_id' => $sip_id]); 
                                
                                $params = [];
                                $params['id'] = $employee;
                                $params['phone_numbers'] = [['phone_number' => $response['result']['data']['phone_number']]];
                                $responce = $uis->callMethod('update.employees', $params);                                
                                
                                if ($group_id == 1 || $group_id == '18a')
                                {
                                    if ($group_id == 1)
                                    {
                                        $static_groups = call::getStaticGroups();
                                        $group_uis_id = $static_groups[$organization_id];
                                    }
                                    else
                                    {
                                        $group_uis_id = 230476;
                                    }
                                    
                                    $params = [];
                                    $params['filter'] = ['field' => 'id', 'operator' => '=', 'value' => $group_uis_id];
                                    
                                    $response = $uis->callMethod('get.group_employees', $params);
                                    
                                    if (isset($response['result']['data']) && $response['result']['data'])
                                    {
                                        $members = $response['result']['data'][0]['members'];
                                        
                                        $t = [];
                                        foreach ($members as $member)
                                            $t[] = (integer) $member['employee_id'];
                                        
                                        $members = $t;
                                        
                                        $members[] = (integer) $employee;
                                        
                                        $params = [];
                                        $params['id'] = $group_uis_id;
                                        $params['members'] = $members;
                                        
                                        $response = $uis->callMethod('update.group_employees', $params);
                                    }
                                }
                            }
                        }
                    }
                    
                    $menu_group_accord = [7 => 'director_base', 13 => 'administrator', 1 => 'operator', 2 => 'acceptor', 
                                8 => 'manager', 10 => 'master', 21 => 'master', 3 => 'courier', '18a' => 'resaler', '18b' => 'saler', '18c' => 'resaler'];
                    
                    if (isset($menu_group_accord[$group_id]))
                    {
                        $menu_group = [];
                        
                        $menu_group[] = $menu_group_accord[$group_id];
                        
                        if ($group_id == 2) 
                        {
                            $menu_group[] = 'logist' . $organization_id;
                        }
                        
                        if ($group_id == 7 || $group_id == 13) 
                        {
                            $menu_group[] = 'logist' . $organization_id;
                            $menu_group[] = 'cash' . $organization_id;
                        }                        
                        
                        foreach ($menu_group as $m_g)
                        {
                            $sql = "SELECT `menu_id` FROM `menu_access_groups` INNER JOIN `menu_groups` ON `menu_access_groups`.`menu_group_id` = `menu_groups`.`id`
                                                WHERE `menu_groups`.`name`=:menu_group";
                            $stm = pdo::getPdo()->prepare($sql); 
                            $stm->execute(array('menu_group' => $m_g));    
                            $menus = (array) $stm->fetchAll(\PDO::FETCH_COLUMN);
                            
                            if ($menus)
                            {
                                $insert_menus = [];
                                
                                foreach ($menus as $menu_id)
                                {
                                    $insert_menus[] = "(".$user_id.",".$menu_id.")";
                                }
                                
                                $sql = "INSERT IGNORE INTO `menu_access` (`user_id`, `menu_id`) VALUES ".implode(',', $insert_menus);
                                pdo::getPdo()->query($sql);
                            }
                        }
                    }

                    if ('18c' === $group_id) {
                        $userAccess->addUserRight($user_id, 'auditor');
                    }

                    if ('18b' === $group_id) {
                        $userAccess->addUserRight($user_id, 'saler');
                    }
                    
                    $str = [];
                    foreach ($array_str as $key => $value)
                        $str[] = $key . ': ' . $value;                
                    
                    $enum->addItems(implode(PHP_EOL, $str));
                    $answer = $enum;
                }
                 
            break;
            
            /*case 'recrut':
            
                $name = isset($args['name']) ? $args['name'] : '';
                $phone = isset($args['phone']) ? $args['phone'] : '';
                $addres_id = isset($args['addres_id']) ? $args['addres_id'] : array();
                $group_id = isset($args['group_id']) ? $args['group_id'] : array();
                
                $error_str = '';
                
                $name = tools::cut_empty($name);
                $translate_role_worker = array();
                
                foreach (array('operator' => 'оператор', 'manager' => 'менеджер', 
                            'acceptor' => 'администратор', 'courier' => 'курьер', 'master' => 'мастер') as $value => $translate)
                {
                    $$value = load::get_status($value, 'groups');
                    $translate_role_worker[$$value] = $translate;
                }
                
                $explode = explode(' ', $name);
                
                $t_addr = array(); 
                foreach ($addres_id as $value)
                    $t_addr[$value] = 1;
                    
                if (isset($t_addr[858]) || isset($t_addr[859]) || isset($t_addr[860]))
                {
                    unset($t_addr[858], $t_addr[859], $t_addr[860]);
                    $t_addr[858] = 1;
                }           
                 
                if ($group_id)
                {
                    if (!$error_str && !$name)
                    {
                        $error_str = 'Введите ФИО!';
                    }
                    
                     if (!$error_str && !$phone)
                    {
                         $error_str = 'Введите телефон!';
                    }
                    
                    if (!$error_str)
                    {
                        if (count($explode) < 2)
                        {
                           $error_str = 'Введите ФИО!';   
                        }
                    }
                    
                    if (!$error_str && !$addres_id)
                    {
                        if ($group_id != $operator && $group_id != $manager)
                        {
                            $error_str = 'Укажите СЦ!';
                        }
                        else
                        {
                            if (count($t_addr) > 1)
                            {
                                $error_str = 'Этот сотрудник может быть привязан только к одному СЦ!';        
                            }    
                        }        
                    }
                    
                    if ($error_str)
                    {
                        $notifys = new term(
                            array(
                                'mode' => 'add', 
                                'table' => 'notifys',
                                'text' => $error_str,
                                'type_notify_id' => load::get_status('type_notifys', 'update_error'),
                                'session' => session_id(),
                                )
                            );
                            
                        $this->setCode('error');
                    }
                    else
                    {
                        $sql = "SELECT `name`, `id` FROM `role_workers`";
                        $role_workers = pdo::getPdo()->prepare($sql)->fetchAll(\PDO::FETCH_KEY_PAIR);
                        
                        $t = array();
                        
                        foreach ($role_workers as $key => $value)
                            $t[mb_strtolower($key)] = $value;
                            
                        $role_workers = $t;         
                          
                        if ($group_id == $operator || $group_id == $manager)
                        {
                            $organization_id = 140;
                            $addr2 = array_values(load::get_new_base_addr2());
                            
                            if ($group_id == $operator)
                                $addr2 = array_merge(array(451, 452), $addr2);
                        }
                        else
                        {
                            $organization_id = load::get_order(key($t_addr), 'organization_id', 'address'); 
                            $addr2 = array_flip($t_addr);  
                        }
                        
                        $worker_obj = new term(array('mode' => 'add', 'table' => 'workers', 'name' => $name, 'organization_id' => $organization_id, 
                                    'role_worker_id' => $role_workers[$translate_role_worker[$group_id]]));
                                    
                        $worker_id = $worker_obj->getWrapper()->getChildren(0);
                        
                        $name = tools::transl_correct($explode[0].'-'.$explode[0][1]);
                        $pass = $name.tools::randomPassword(3);
                        
                        $user_obj = new term(array('mode' => 'add', 'table' => 'users', 'name' => $name, 'pass' => $pass, 'worker_id' => $worker_id, 'button' => 1));
                        $user_id = $user_obj->getWrapper()->getChildren(0);
                        
                        $insert = array();
                         
                        foreach ($addr2 as $value)
                        {
                            $boss = ($group_id == $operator) ? 1 : 0;
                            $insert[] = "(".$user_id.",".$value.",".$boss.",".$group_id.")";            
                        }
                        
                        if ($insert)
                        {
                            $sql = "INSERT IGNORE INTO `access` (`user_id`, `addres_id`, `boss`, `group_id`) VALUES ".implode(',', $insert);
                            pdo::getPdo()->query($sql);
                        }      
                    }
                }
            
            break;*/
            
            case 'start_stop':
            
                $user_id = isset($args['user_id']) ? (integer) $args['user_id'] : 0;
                $start_stop = isset($args['start_stop']) ? (integer) $args['start_stop'] : 0;
                
                if ($user_id)
                {
                     $fa_p = new node('i');
                     $fa_p->getAttributes()->getClass()->addItems('fa');
                     $fa_p->getAttributes()->getClass()->addItems('start-stop');
                                            
                     if ($start_stop)
                     {
                        $fa_p->getAttributes()->getClass()->addItems('fa-pause');
                        $fa_p->getAttributes()->addAttr('title', 'На перерыве');
                     }
                     else
                     {
                        $fa_p->getAttributes()->getClass()->addItems('fa-play');
                        $fa_p->getAttributes()->addAttr('title', 'Работаю');
                     }
                    
                     $term = new term(array('mode' => 'update', 'table' => 'users', 'id' => $user_id, 'button' => $start_stop));
                     $answer = $fa_p; 
                }
                
            break;
            
            case 'change_interval':
            
                $user_id = isset($args['user_id']) ? (integer) $args['user_id'] : 0;
                $day = isset($args['day']) ? (string) $args['day'] : '';
                $interval = isset($args['interval']) ? (string) $args['interval'] : '';
                
                $interval_answer = '';
                
                if ($user_id && $day)
                {
                    $time = tools::get_time();
                    $current_day = date('Y-m-d', $time);
                    
                    if ($day >= $current_day)
                    {
                        if ($day == $current_day)
                        {
                            $current_h = date('H', $time);
                            $current_i = date('i', $time);
                            
                            $diff_stop = 60 * 9;
                            
                            $sql = "SELECT `start`, `stop` FROM `calendars` WHERE `user_id`=:user_id AND `day`=:day";
                            $stm = pdo::getPdo()->prepare($sql);
                            $stm->execute(array('user_id' => $user_id, 'day' => $day));  
                            $calendar_arr = $stm->fetchAll(\PDO::FETCH_ASSOC);
                                               
                            if ($calendar_arr)
                            {
                                $calendar_arr = current($calendar_arr);
                                $diff_stop = $calendar_arr['stop'] - $calendar_arr['start']; 
                            }   
                            
                            if (!$interval)
                            {
                                if ($calendar_arr)
                                {
                                    if ($calendar_arr['start'] >= ($current_h * 60 + $current_i))
                                    {
                                        $sql = "DELETE FROM `calendars` WHERE `user_id`=:user_id AND `day`=:day";
                                        $stm = pdo::getPdo()->prepare($sql);
                                        $stm->execute(array('user_id' => $user_id, 'day' => $day));
                                        $interval_answer = '';                                                
                                    }
                                    else
                                    {
                                        $interval_answer = $this->_divide_time($calendar_arr['start']).'-'.$this->_divide_time($calendar_arr['stop']);
                                    }
                                }
                                else
                                {
                                    $interval_answer = '';
                                }    
                            }
                            else
                            {
                                list($start, $stop) = $this->_mult_time($interval);
                                
                                if ($start >= ($current_h * 60 + $current_i))
                                {
                                    $stop = $start + $diff_stop;   
                                    if ($stop > 1439) $stop = 1439;                                     
                                    $interval_answer = $this->_change_start_stop($start, $stop, $user_id, $day);
                                }
                                else
                                {
                                    if ($calendar_arr)
                                    {
                                        $interval_answer = $this->_divide_time($calendar_arr['start']).'-'.$this->_divide_time($calendar_arr['stop']);
                                    }
                                    else
                                    {
                                        $interval_answer = '';    
                                    }
                                }
                            }
                        }
                        else
                        {
                            if (!$interval)
                            {
                                $sql = "DELETE FROM `calendars` WHERE `user_id`=:user_id AND `day`=:day";
                                $stm = pdo::getPdo()->prepare($sql);
                                $stm->execute(array('user_id' => $user_id, 'day' => $day));
                                $interval_answer = '';
                            }
                            else
                            {
                                list($start, $stop) = $this->_mult_time($interval);
                                $interval_answer = $this->_change_start_stop($start, $stop, $user_id, $day);
                            }
                        }
                    }       
                }
                
                $index_color = '';
                
                if ($interval_answer)
                {
                    $color_masks = calendar::$color_masks;
                    $t = array();
                    foreach ($color_masks as $color_mask)
                    {
                        $color_mask = explode('-', $color_mask);
                        $t[] = str_pad($color_mask[0], 2, '0', STR_PAD_LEFT).':00-'.str_pad($color_mask[1], 2, '0', STR_PAD_LEFT).':00';
                    }
                    
                    $index_color = array_search($interval_answer, $t);                    
                    if ($index_color === false) $index_color = 5;                    
                    $index_color = 'col-'.$index_color;
                }
                
                $answer = array($interval_answer, $index_color);
                
            break;
            
            case 'show_line':
            
                $offset_day = isset($args['iday']) ? (integer) $args['iday'] : 0;
                $group_array = isset($args['group_array']) ? $args['group_array'] : array();
                $master_ids = isset($args['user_ids']) ? $args['user_ids'] : array();
                
                if (is_string($group_array)) $group_array = explode(',', $group_array);
                if (is_string($master_ids)) $master_ids = explode(',', $master_ids);
                
                $addres_id = isset($args['addres_id']) ? $args['addres_id'] : array(858, 859, 860); 
                
                $group_code = load::get_group_code();
                $is_boss_2 = in_array($group_code, array('moderator', 'director', 'administrator'));
                $master_boss = false;
                   
                $user_id = load::get_user_id();
                
                $show_spec = false;
                
                if (count($group_array) == 1)
                {    
                    $c_gr_array = current($group_array);
                    $master_boss = load::is_boss($user_id, $addres_id, load::get_status('groups', $c_gr_array));
                    
                    if ($c_gr_array == 'manager' || $c_gr_array == 'master')
                        $show_spec = true;
                }
                
                if ($master_ids)
                {
                     $master_boss = in_array($user_id, load::get_master_office_ids()) || 
                                            load::is_boss($user_id, current($addres_id), load::get_status('groups', 'resaler'));
                }
                
                $iday = date("j") + $offset_day;
                
                list($h, $di, $s, $n, $j, $Y) = array(date("H"),date("i"),date("s"),date("n"),date("j"),date("Y"));
                
                $enum = new enum();
                $enum->setSign('');
                
                $all_days = date('t', mktime(0, 0, 0, $n, 1, $Y));
                
                $day = 24 * 60 * 60;
                
                if (!$offset_day)
                {
                    $start_stamp = mktime(0, 0, 0, $n, $iday - 7, $Y);
                }
                else
                {
                     $start_stamp = mktime(0, 0, 0, $n, $iday, $Y);    
                } 
                $end_stamp = mktime(23, 59, 59, $n, $iday + 14, $Y);
                
                $current_timestamp = tools::get_time();
                $current_time = tools::get_time();
                
                $current_timestamp_day = mktime(0, 0, 0, $n, $j, $Y);
                $current_day = date('Y-m-d', $current_timestamp);
                
                $start_day = date('Y-m-d', $start_stamp);
                $end_day = date('Y-m-d', $end_stamp);
                
                $tbody = array();
                $thead = array();                

                $scroll = new node('div');
                $scroll->getAttributes()->getClass()->setItems(array('scrolling', 'outer'));
                
                $inner = new node('div');
                $inner->getAttributes()->getClass()->addItems('inner');  
                $inner->getAttributes()->addAttr('id', 'timeline-inner');             
                
                $data_table = new table_striped();
                $data_table->getAttributes()->getClass()->addItems('table-calendar');
                    
                $thead[] = '';
                $days = array();
                $disabled = array();
                $offset = array();
                
                $br = new node('br', false);
                
                for ($i = $start_stamp; $i <= $end_stamp; $i+= $day)
                {
                    $thead[$i] = date('d.m.y', $i) . (string) $br . $this->_week[date('w', $i)];
                    $days[$i] = date('Y-m-d', $i);
                    if ($current_timestamp_day >= $i) $disabled[$i] = true;
                    
                    $offset[] = ($i - $current_timestamp_day) / $day;  
                }
                
                if ($group_array)
                {
                    $exclude_groups = array();
                    foreach ($group_array as $value)
                        $exclude_groups[] = load::get_status('groups', $value);
                             
                    $filter = array();
                    $filter[] = "`access`.`group_id` IN (".implode(',', $exclude_groups).")";
                    $filter[] = "`access`.`addres_id` IN (".implode(',', $addres_id).")";
                    $filter = " WHERE (".implode(") AND (", $filter).")";
                    
                    $sql = "SELECT `users`.`id`, `workers`.`name` as `name`,
                                            `users`.`name` as `name2`, `users`.`online` as `online`, `users`.`button`,
                                                    `users`.`not_active` as `not_active`, `access`.`group_id` as `group_id`
                                    FROM `access` 
                                      LEFT JOIN `users` ON `users`.`id` = `access`.`user_id`
                                        LEFT JOIN `workers` ON `users`.`worker_id` = `workers`.`id`   
                                            {$filter} GROUP BY `users`.`id`";
                    
                    $users = pdo::getPdo()->query($sql)->fetchAll(\PDO::FETCH_ASSOC);
                }
                
                if ($master_ids)
                {
                    $filter = array();
                    $filter[] = "`users`.`id` IN (".implode(',', $master_ids).")";
                    $filter = " WHERE (".implode(") AND (", $filter).")";
                    
                    $sql = "SELECT `users`.`id`, `workers`.`name` as `name`,
                                            `users`.`name` as `name2`, `users`.`online` as `online`, `users`.`button`,
                                                    `users`.`not_active` as `not_active`
                                    FROM `users` 
                                        LEFT JOIN `workers` ON `users`.`worker_id` = `workers`.`id`   
                                            {$filter} ORDER BY FIELD (`users`.`id`, ".implode(',', $master_ids).") ASC";
                    
                    $users = pdo::getPdo()->query($sql)->fetchAll(\PDO::FETCH_ASSOC);    
                }
                
                $control_time = 60 * 60 * 24 * 14; 
   
                $t = array();
                $user_ids = array();
                $buttons = array();
                $onlines = array();
                
                foreach ($users as $user)
                {
                    if (!$user['not_active'])
                    { 
                        $t[$user['not_active']][tools::cut_user_name($user['name'], $user['name2'])] = $user['id'];
                    }
                    else
                    {
                        if ($user['online'])
                        {
                            if (($current_time - $user['online']) <= $control_time)
                            {
                                $t[$user['not_active']][tools::cut_user_name($user['name'], $user['name2'])] = $user['id'];
                            }
                        }
                    }
                    
                    $buttons[$user['id']] = $user['button'];
                    $onlines[$user['id']] = $user['online'];
                    $user_ids[$user['id']] = true;
                }
                
                if (!$master_ids)
                {
                    foreach ($t as $key => $value)
                    {
                        ksort($value);
                        $t[$key] = $value;
                    }
                }
                
                ksort($t);
                
                $user2_array = $t;
                
                $filter = array();
                $filter[] = "`day` >= '".$start_day."'";
                $filter[] = "`day` <= '".$end_day."'";
                $filter[] = "`user_id` IN (".implode(',', array_keys($user_ids)).")";
                $filter = " WHERE (".implode(") AND (", $filter).")";
                
                list($calendars, $work, $sign, $work_times, $colors) = $this->_calendars_all_signs($filter, $current_day, $current_timestamp, $buttons, $onlines);
                
                $user_ids = array();
                
                foreach ($user2_array as $k => $users)
                {                                
                    foreach ($users as $user_name => $user_id)
                    {
                        $t_row = array();
                        foreach ($thead as $key => $col)
                        {
                            if (!$key)
                            { 
                                $span = new node('span');
                                
                                if ($show_spec)
                                {
                                    $span->getAttributes()->getClass()->addItems('name');
                                }
                                
                                $span->addChildren($user_name);
                                
                                $fa = '';
                                $fa_p = '';
                                
                                $fa = new node('i');
                                $fa->getAttributes()->getClass()->setItems(array('fa', 'fa-trash-o'));
                                 
                                if ($k == 1)
                                {
                                    $span->getAttributes()->getClass()->addItems('red');
                                }
                                
                                if (!$k)
                                {
                                    if ($is_boss_2)
                                    {
                                        $fa->getAttributes()->getClass()->addItems('fired');
                                        $fa->getAttributes()->addAttr('title', 'Уволить');
                                    }
                                    
                                    if ($is_boss_2 || $master_boss)
                                    { 
                                        if (isset($buttons[$user_id]))
                                        {
                                            $fa_p = new node('i');
                                            $fa_p->getAttributes()->getClass()->addItems('fa');
                                            $fa_p->getAttributes()->getClass()->addItems('start-stop');
                                            
                                            if ($buttons[$user_id])
                                            {
                                                $fa_p->getAttributes()->getClass()->addItems('fa-pause');
                                                $fa_p->getAttributes()->addAttr('title', 'На перерыве');
                                            }
                                            else
                                            {
                                                $fa_p->getAttributes()->getClass()->addItems('fa-play');
                                                $fa_p->getAttributes()->addAttr('title', 'Работаю');
                                            }
                                        }
                                    }
                                }
                            
                                $t_row[] = (string) $span . (string) $fa . (string) $fa_p;                                
                            } 
                            else
                            {
                                $p_val = isset($calendars[$days[$key]][$user_id]) ? $calendars[$days[$key]][$user_id] : '';
                                $p_disabled = isset($disabled[$key]) ? $disabled[$key] : false;
                                $sign_flag = isset($sign[$days[$key]][$user_id]) ? $sign[$days[$key]][$user_id] : 0;
                                $work_time = isset($work_times[$days[$key]][$user_id]) ? $work_times[$days[$key]][$user_id] : '';
                                                                                                
                                $p = new node('div');
                                $p->getAttributes()->getClass()->addItems('textarea');
                                
                                $span_value = new node('span');
                                $span_value->getAttributes()->getClass()->addItems('s_value');
                                
                                if (!($is_boss_2 || $master_boss)) $p_disabled = true;
                                //if (!($is_boss_2)) $p_disabled = true; 
                                                               
                                if ($current_day > $days[$key]) $p_val = tools::format_v($work_time, true);
                                
                                $span = new node('span');
                                $span->getAttributes()->getClass()->addItems('c_error');
                                 
                                $fa = new node('i');
                                $fa->getAttributes()->getClass()->setItems(array('fa', 'fa-warning'));
                                
                                if ($sign_flag > 0)
                                {
                                    $span->addChildren($fa);  
                                    $span->getAttributes()->addAttr('data-text', 'Опоздание '  . tools::format_v($sign_flag)); 
                                }
                                
                                if ($sign_flag == -1)
                                {
                                    //$p_val = 'Прогул'; 
                                    //$span_value->getAttributes()->getClass()->addItems('red');
                                    //$p_disabled = true;
                                }
                                
                                $span_value->addChildren($p_val);
                                                                 
                                if ($p_disabled) $p->getAttributes()->getClass()->addItems('disabled');                                
                                if (!$p_val) $p->getAttributes()->getClass()->addItems('empty');
                                
                                $p->addChildren($span_value);
                                
                                if ($sign_flag > 0 || $sign_flag == -1)
                                {
                                    $p->addChildren($span);
                                }
                                
                                if ($p_val)
                                {
                                    if (isset($colors[$days[$key]][$user_id]))
                                    {
                                        $p->getAttributes()->getClass()->addItems('col-' . $colors[$days[$key]][$user_id]);
                                    }
                                }
                                
                                $t_row[] = (string) $p;
                            }    
                        }
                        $user_ids[] = $user_id;
                        $tbody[] = $t_row;
                     }
                }                
              
                $data_table->setThead($thead);                
                $data_table->setTbody($tbody); 
                
                $count_tbody = count($tbody);
                $trs = $data_table->getChildren('tbody')->getChildren(0);

                for ($i = 0; $i < $count_tbody; $i++)
                {
                    $trs->getItems($i)->getAttributes()->addAttr('data-user_id', $user_ids[$i]);
                }
                
                $count_thead = count($thead); 
                
                $timestamp_days = array_keys($days);
                $days = array_values($days);
               
                $trh = $data_table->getChildren('thead')->getChildren(0)->getChildren(0);        
                               
                for ($i = 1; $i < $count_thead; $i++)
                {
                    $trh->getItems($i)->getAttributes()->addAttr('data-day', $days[$i-1]);
                    $trh->getItems($i)->getAttributes()->addAttr('data-day_offset', $offset[$i-1]);
                    
                    $w_day = date('w', $timestamp_days[$i-1]);
                    
                    if ($w_day == 0 || $w_day == 6)
                    {
                       $trh->getItems($i)->getAttributes()->getClass()->addItems('weekend');      
                    }
                    
                    if ($current_day == $days[$i-1])
                    {
                       $trh->getItems($i)->getAttributes()->getClass()->addItems('current_day');
                        
                       for ($j = 0; $j < $count_tbody; $j++)
                       {
                          $trs = $data_table->getChildren('tbody')->getChildren(0);
                          $td = $trs->getItems($j)->getChildren(0)->getItems($i);
                          $td->getAttributes()->getClass()->addItems('current_day');
                          
                          if (isset($work[$user_ids[$j]]))
                          {
                              if ($work[$user_ids[$j]])
                              {
                                 $td->getAttributes()->getClass()->addItems('work');
                              }
                              else
                              {
                                 $td->getAttributes()->getClass()->addItems('notwork');
                              }
                          }                            
                       }
                    }
                }
               
                $inner->addChildren($data_table);
                $scroll->addChildren($inner);
                 
                if (count($group_array) == 1)
                {    
                    /*$group_id = load::get_status('groups', current($group_array));
                    $group_name = mb_strtolower(load::get_order($group_id, 'name', 'groups'));
                    
                    $button = new node('button');
                    $button->getAttributes()->getClass()->setItems(array('btn', 'btn-primary', 'btn-sm', 'recrut'));
                    $button->getAttributes()->addAttr('title', '+ ' . $group_name);
                    $button->getAttributes()->addAttr('data-group_id', $group_id);
                    $button->addChildren('+ ' . $group_name);
                    
                    $scroll->addChildren($button);*/
                }
                
                $enum->addItems($scroll);
                
                if ($group_array)
                {
                    $hidden_obj = new form\hidden();
                    $hidden_obj->setValue(implode(',', $group_array)); 
                    $hidden_obj->setName('group_array');
                    $enum->addItems($hidden_obj);
                }
                
                if ($master_ids)
                {
                    $hidden_obj = new form\hidden();
                    $hidden_obj->setValue(implode(',', $master_ids)); 
                    $hidden_obj->setName('master_ids');
                    $enum->addItems($hidden_obj);
                }
                
                $answer = $enum;
                                                            
            break;
        }
        
        $this->getWrapper()->addChildren($answer);
    }
    
    private function _construct_calendar($month, $user_id = 0)
    {
        if (!$user_id) return '';
        
        $month_ind = date('n', mktime(0, 0, 0, $month, 1, date("Y")));
        
        $all_days = date('t', mktime(0, 0, 0, $month, 1, date("Y")));
        $first_day = date('N', mktime(0, 0, 0, $month, 1, date("Y"))) - 1;
        $last_day = 7 - date('N', mktime(0, 0, 0, $month, $all_days, date("Y")));
        $year = date("Y", mktime(0, 0, 0, $month, 1, date("Y")));
        $year_short = date("y", mktime(0, 0, 0, $month, 1, date("Y")));
        
        $month_minus_ind = date('n', mktime(0, 0, 0, $month-1, 1, date("Y")));
        $all_days_minus = date('t', mktime(0, 0, 0, $month-1, 1, date("Y")));
        $year_minus =  date("Y", mktime(0, 0, 0, $month-1, 1, date("Y")));
        
        $month_plus_ind = date('n', mktime(0, 0, 0, $month+1, 1, date("Y")));
        $year_plus =  date("Y", mktime(0, 0, 0, $month+1, 1, date("Y")));
        
        $cur_day = date("d.m.Y", tools::get_time());
        
        $first_day_stamp = date('Y-m-d', mktime(0, 0, 0, $month, 1, date("Y")));
        $end_day_stamp = date('Y-m-d', mktime(0, 0, 0, $month, $all_days, date("Y")));
               
        $month_ar = array("Январь", "Февраль", "Март", "Апрель", "Май", "Июнь", "Июль", "Август", "Сентябрь", "Октябрь", "Ноябрь", "Декабрь");
    
        $str = '';
        $str .= '<div class="calendar-wrapper">';
        $str .= '<div class="calendar-header"><p>График работы</p><p>'.$month_ar[$month_ind-1].' '.$year_short.'</p></div>';
 
        $str .= '<div class="calendar-body"><a class="calendar-nav nav-left" href="#" data-month="'.($month-1).'"><i class="fa fa-chevron-left"></i></a>';
        $str .= '<table class="calendar-one"><thead><tr><th>Пн</th><th>Вт</th><th>Ср</th><th>Чт</th><th>Пт</th><th class="red">Сб</th><th class="red">Вс</th></tr><tbody><tr>';
        
        $current_timestamp = tools::get_time();
        $current_day = date('Y-m-d', $current_timestamp);
        
        $sql = "SELECT `users`.`id`, `users`.`online` as `online`, `users`.`button` as `button` WHERE `id`=:user_id";
        $stm = pdo::getPdo()->prepare($sql);
        $stm->execute(array('user_id' => $user_id));
        $user_arr = $stm->fetchAll(\PDO::FETCH_ASSOC);    
        
        $buttons = $onlines = array();
        
        if ($user_arr)
        {
            $user_arr = current($user_arr);
            $buttons[$user_arr['id']] = $user_arr['button'];
            $onlines[$user_arr['id']] = $user_arr['online'];
        }
        
        $filter = array();
        $filter[] = "`day` >= '".$first_day_stamp."'";
        $filter[] = "`day` <= '".$end_day_stamp."'";
        $filter[] = "`user_id` = ".$user_id;
        $filter = " WHERE (".implode(") AND (", $filter).")";
        
        list($calendars, $work, $sign, $work_times, $colors) = $this->_calendars_all_signs($filter, $current_day, $current_timestamp, $buttons, $onlines);
        
        $j = $first_day;
        $d = str_pad($all_days_minus - $j + 1, 2, '0', STR_PAD_LEFT);
        $m = str_pad($month_minus_ind, 2, '0', STR_PAD_LEFT);
        
        for ($j=$first_day;$j>0;$j--)
        {
            $d = str_pad($all_days_minus - $j + 1, 2, '0', STR_PAD_LEFT);
            $m = str_pad($month_minus_ind, 2, '0', STR_PAD_LEFT);
            
            $str .= '<td data-month="'.$m.'" data-year="'.$year_minus.'" data-day="'.$d.'" class="lm">';
            $str .= '<div class="c-gray">'.$d.'</div>';
            $str .= '</td>';
        }
        
        $j = $first_day;
    
        for ($i=1;$i<=$all_days;$i++)
        {
            if ($j % 7 == 0 && $j != 0) $str .= '</tr><tr>';
                            
            $d = str_pad($i, 2, '0', STR_PAD_LEFT);
            $m = str_pad($month_ind, 2, '0', STR_PAD_LEFT);
      
            $today_str = ($d.".".$m.".".$year == $cur_day) ? ' today' : '';
            
            $disabled = ' empty';
            $d_f = $year.'-'.$m.'-'.$d;
            if ($format_cur_day == $d_f) $disabled = ' today';            
                
            $work_time = '';
            
            $str .= '<td data-month="'.$m.'" data-year="'.$year.'" data-day="'.$d.'" class="use">';
            
            $str .= '<div class="c-date'.$today_str.'">'.$d.'</div>';
            
            if (isset($calendars[$d_f]))
            {
                $calendar = $calendars[$d_f];
                //$work_time = 
                
                if ($format_cur_day > $d_f) $disabled = ' disabled';
                
                if ($format_cur_day > $d_f && $calendar['work_time'] && $d_f >= '2018-09-18') $work_time = tools::format_v($calendar['work_time']);
            }
            
            if ($work_time) $disabled .= ' col-' . $colors[$d_f];
               
            $str .= '<div class="work_time'.$disabled.'">'.$work_time.'</div>';
            
            $str .= '</td>';
            
            $j++;
        }
        
        for ($j=1;$j<=$last_day;$j++)
        {
            $d = str_pad($j, 2, '0', STR_PAD_LEFT);
            $m = str_pad($month_plus_ind, 2, '0', STR_PAD_LEFT);
            
            $str .= '<td data-month="'.$m.'" data-year="'.$year_plus.'" data-day="'.$d.'" class="lm">';
            $str .= '<div class="c-gray">'.$d.'</div>';
            $str .= '</td>';
        }
        
        $str .= '</tbody></table>';
        $str .= '<a class="calendar-nav nav-right" href="#" data-month="'.($month+1).'"><i class="fa fa-chevron-right"></i></a></div>';
        
        if ($user_id)
        {
            $str .= '<input type="hidden" name="user_id" value="'.$user_id.'"/>';
        }
        
        $str .= '</div>';
        
        return $str;
    }
    
    private function _divide_time($value)
    {
        $h = floor($value / 60);
        
        if ($h > 24) return '23:59';
        
        return str_pad($h, 2, '0', STR_PAD_LEFT). ':' . str_pad($value - $h * 60, 2, '0', STR_PAD_LEFT); 
    }
    
    public function divide_time($day, $value)
    {
        return strtotime($day.' '.$this->_divide_time($value).':00');
    }
    
    private function _mult_time($value)
    {
        $value = trim($value);
        
        $val = explode('-', $value);
        
        $pass = false;
        
        if (count($val) == 2)
        {
            $val[0] = explode(':', $val[0]);
            
            if (count($val[0]) == 2)
            {
                $val[0][0] = $s_hour = intval($val[0][0]);
                $val[0][1] = intval($val[0][1]);
                
                if ($val[0][0] >= 0 && $val[0][0] <= 24 && $val[0][1] >=0 && $val[0][1] <= 60)
                {
                    $val[0] = $val[0][0] * 60 + $val[0][1];
                   
                    $val[1] = explode(':', $val[1]);
                    
                    if (count($val[1]) == 2)
                    {
                        $val[1][0] = $e_hour = intval($val[1][0]);
                        $val[1][1] = intval($val[1][1]);
                    
                        if ($val[1][0] >= 0 && $val[1][0] <= 24 && $val[1][1] >=0 && $val[1][1] <= 60 && $e_hour >= $s_hour)
                        {
                            $val[1] = $val[1][0] * 60 + $val[1][1];    
                            $pass = true;
                        }
                    }
                }
            }       
        }
        
        if ($pass)
            return $val;
        else
            return array('', '');
    }
    
    private function _change_start_stop($start, $stop, $user_id, $day)
    {
        $fix_dinner = $this->_fix_dinner;
        
        $group_code = load::get_group_code($user_id);
        $dinner_time = isset($fix_dinner[$group_code]) ? $fix_dinner[$group_code] : 0;
        
        if ($group_code == 'operator' && load::is_boss($user_id, 0, load::get_status('groups', 'operator'))) $dinner_time = 0;
        
        $interval_answer = '';
        
        if ($start && $stop)
        {
            $sql = "INSERT INTO `calendars` (`user_id`,`day`,`start`,`stop`,`work_time`,`fix_dinner`) 
                   VALUES ({$user_id}, '{$day}', {$start}, {$stop}, 0, {$dinner_time}) ON DUPLICATE KEY UPDATE `start` = {$start}, `stop` = {$stop}";
            pdo::getPdo()->query($sql);
            
            $interval_answer = $this->_divide_time($start).'-'.$this->_divide_time($stop);
        }
        else
        {
            $sql = "SELECT `start`, `stop` FROM `calendars` WHERE `user_id`=:user_id AND `day`=:day";
            $stm = pdo::getPdo()->prepare($sql);
            $stm->execute(array('user_id' => $user_id, 'day' => $day));  
            $mas = $stm->fetchAll(\PDO::FETCH_ASSOC);
            
            if ($mas)
            {
                $mas = current($mas);
                $interval_answer = $this->_divide_time($mas['start']).'-'.$this->_divide_time($mas['stop']);
            } 
        }
        
        return $interval_answer;
    }
    
    public function users_in_calendar($users, $use_time = true)
    {
        $users = (array) $users;
        
        $current_timestamp = tools::get_time();
        
        $day = date('Y-m-d', $current_timestamp);
        
        $sql = "SELECT `start`, `stop`, `user_id` FROM `calendars` WHERE `user_id` IN (".implode(',', $users).") AND `day`=:day 
                                    AND `fact_start` IS NOT NULL";
        $stm = pdo::getPdo()->prepare($sql);
        $stm->execute(array('day' => $day));
        $calendars = $stm->fetchAll(\PDO::FETCH_ASSOC);
        
        $free_users = array();
        
        foreach ($calendars as $calendar)
        {
            if ($use_time)
            {
                $start = $this->divide_time($day, $calendar['start']);       
                $stop = $this->divide_time($day, $calendar['stop']);
                
                if ($current_timestamp >= $start && $current_timestamp <= $stop)
                    $free_users[] = $calendar['user_id'];
            }
            else
            {      
                $free_users[] = $calendar['user_id'];
            }
        }
        
        return $free_users;
    }
    
    public static function normalize_mask()
    {
        $color_masks = calendar::$color_masks;
                
        $t = array();
        foreach ($color_masks as $key => $value)
        {
            $m = explode('-', $value);
            $t[$m[0] * 60][$m[1] * 60] = $key;
        }
        
        $color_masks = $t;
        return $color_masks;
    }
    
    public function getFixDinner()
    {
        return $this->_fix_dinner;
    }
    
    private function _calendars_all_signs($filter, $current_day, $current_timestamp, $buttons, $onlines)
    {
        $color_masks = calendar::normalize_mask();
        $colors = array();
        
        $sql = "SELECT `start`, `stop`, `day`, `user_id`, `fact_start`, `work_time` FROM `calendars` {$filter}";
        $calendars = pdo::getPdo()->query($sql)->fetchAll(\PDO::FETCH_ASSOC);

        $t = array();
        $work = array();
        $sign = array();
        $work_times = array();
        
        $h3 = 60 * 60 * 3;
        $m5 = 60 * 5;
           
        foreach ($calendars as $value)
        {
            $div_start = $this->_divide_time($value['start']);
            $div_stop = $this->_divide_time($value['stop']);
            
            $t[$value['day']][$value['user_id']] = $div_start.'-'.$div_stop;
            $work_times[$value['day']][$value['user_id']] = $value['work_time']; 
            
            $start = $this->divide_time($value['day'], $value['start']);       
            $stop = $this->divide_time($value['day'], $value['stop']);
            
            if ($value['day'] == $current_day)
            {
                if ($current_timestamp >= $start && $current_timestamp <= $stop)
                {
                    if ($value['fact_start'])
                    {
                        if ($buttons[$value['user_id']])
                        {
                            $work[$value['user_id']] = false;
                        }
                        else
                        {
                            if (($current_timestamp - $onlines[$value['user_id']]) <= 60)
                            {
                                $work[$value['user_id']] = true;
                            }
                            else
                            {
                                $work[$value['user_id']] = false;
                            }
                        }
                    }
                    else
                    {
                        $work[$value['user_id']] = false;       
                    }
                }
                else
                {
                    $work[$value['user_id']] = false;
                }
            }
            
            if ($value['day'] <= $current_day && $value['day'] >= '2018-09-14')
            {
                $sign[$value['day']][$value['user_id']] = 0;
                
                if ($value['day'] != $current_day)
                {
                    if ($value['fact_start'])
                    {
                        if ($value['fact_start'] <= ($start + $h3))
                        {
                            $timestamp = (integer) $value['fact_start'] - strtotime($value['day'].' '.$div_start.':00');
                            if ($timestamp > $m5) $sign[$value['day']][$value['user_id']] = $timestamp;
                        }
                        else
                        {
                            $sign[$value['day']][$value['user_id']] = -1;    
                        }
                    }
                    else
                    {
                        $sign[$value['day']][$value['user_id']] = -1;     
                    }
                }
                else
                {
                    if ($value['fact_start'])
                    {
                         if ($value['fact_start'] <= ($start + $h3))
                         {
                            $timestamp = (integer) $value['fact_start'] - strtotime($value['day'].' '.$div_start.':00');
                            if ($timestamp > $m5) $sign[$value['day']][$value['user_id']] = $timestamp;
                         }
                         else
                         {
                             $sign[$value['day']][$value['user_id']] = -1;     
                         }
                    }
                    else
                    {
                         if ($current_timestamp > ($start + $h3))
                         {
                             $sign[$value['day']][$value['user_id']] = -1;           
                         }         
                    }
                }
            }
            
            if (isset($color_masks[$value['start']][$value['stop']]))
                $colors[$value['day']][$value['user_id']] = $color_masks[$value['start']][$value['stop']];
            else
                $colors[$value['day']][$value['user_id']] = 5;
        }          
            
        $calendars = $t;
        
        return array($calendars, $work, $sign, $work_times, $colors);
    }
}

?>