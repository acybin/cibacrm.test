<?

namespace framework\ajax\lot;

use framework\ajax as ajax;
use framework\pdo;
use framework\dotenv;
use framework\tools;
use framework\load;
use framework\notify;
use framework\ajax\navy\navy;
use framework\ajax\bill\bill;
use framework\ajax\term\term;
use framework\ajax\resale\resale;
use framework\shape\x_panel;
use framework\enum;
use framework\dom\node;
use framework\shape\form as form;
use framework\ajax\telegram\telegram;
use framework\navy_db;
use framework\ajax\navy_chat\navy_chat;

class lot extends ajax\ajax
{
    private $_turbo_lid_token = '';
        
    public function __construct($args)
    {
        error_reporting(E_ALL);
        ini_set('display_errors', 1);

        list($this->_turbo_lid_token) = dotenv::get_vars(['TOKEN_TURBOLEAD']);

        parent::__construct('lot');
        $mode = isset($args['mode']) ? $args['mode'] : '';
        
        $this->getWrapper()->getAttributes()->addAttr('id', 'lot-'.$mode);
         
        $answer = '';
        
        switch ($mode)
        {
            case 'send_repeat':
            
                $call_id = isset($args['call_id']) ? (integer) $args['call_id'] : 0;
                
                if ($call_id)
                {    
                    $sql = "SELECT `id` FROM `lots` WHERE `call_id`=:call_id";
                    $stm = pdo::getPdo()->prepare($sql);         
                    $stm->execute(array('call_id' => $call_id));
                    $in_base = $stm->fetchColumn();
                    
                    if (!$in_base)
                    {                    
                        $sql = "SELECT `region_id`, `offer_id` FROM `partner_orders` WHERE `call_id`=:call_id";                                
                        $stm = pdo::getPdo()->prepare($sql);
                        $stm->execute(array('call_id' => $call_id));
                        $data = $stm->fetch(\PDO::FETCH_ASSOC); 
                        
                        if ($data)
                        {
                            $region_id = $data['region_id'];                        
                            $offer_id = $data['offer_id']; 
                            
                            $parent_offer = load::get_order($offer_id, 'parent', 'offers');
                            
                            if ($parent_offer && $parent_offer != 36) $offer_id = $parent_offer;
                            
                            $sql = "SELECT `region_id` FROM `lot_prices` GROUP BY `region_id`";
                            $regions = pdo::getPdo()->query($sql)->fetchAll(\PDO::FETCH_COLUMN);         
                             
                            $sql = "SELECT `lot_prices`.`price` FROM `lot_prices` WHERE `region_id`=:region_id AND `offer_id`=:offer_id";
                            $stm = pdo::getPdo()->prepare($sql);         
                            $stm->execute(array('region_id' => $region_id, 'offer_id' => $offer_id));
                            $price = $stm->fetchColumn(); 
                            
                            if ($price)
                            {                    
                                $json = mb_convert_encoding(json_encode(['call_id' => $call_id]), 'UTF-8');
                                        
                                $keyboard = [
                                   "inline_keyboard" => [
                                         [["text" => "Купить за ".$price." RUB", "callback_data" => $json]],
                                    ],
                                ];
                                
                                $message_obj = new lot(['mode' => 'new_lot', 'call_id' => $call_id]);    
                                $message = $message_obj->getWrapper()->getChildren(0);
                                                                    
                                if ($message)
                                {
                                    $tag_id = [];

                                    $sql = "SELECT `tag_text` FROM `partner_orders` WHERE `call_id`=:call_id";
                                    $stm = pdo::getPdo()->prepare($sql);
                                    $stm->execute(['call_id' => $call_id]);
                                    $tag_text = (string) $stm->fetchColumn();
                                    if ($tag_text) $tag_id = explode(',', $tag_text);
                                    
                                    $tag_id = []; //only offer && region!
                                    
                                    if ($data['region_id'] && $data['offer_id'])
                                    {
                                        $navy_array = ['mode' => 'datatable', 'dop_filter' => ['call_id' => $call_id, 'region_id' => $data['region_id'], 'offer_id' => $data['offer_id'], 'tag_id' => $tag_id, 'auction' => 1], 's_mode' => 1];
                                        $navy_obj = new navy($navy_array);
                                                            
                                        $array = $navy_obj->getWrapper()->getChildren(0);
                                        
                                        $array = $array['data'];
                                        
                                        $telega = new telegram($this->_turbo_lid_token);
                                        
                                        if ($array)
                                        {
                                            $organization_ids = [];
                                            foreach ($array as $arr)
                                                $organization_ids[] = $arr['DT_RowData']['organization_id'];
                                            
                                            $sql = "SELECT `chat_id` FROM `lot_telegrams` WHERE `organization_id` IN (".implode(',', $organization_ids).")";
                                            $chat_ids = (array) pdo::getPdo()->query($sql)->fetchAll(\PDO::FETCH_COLUMN);
                                            
                                            foreach ($chat_ids as $chat_id)
                                            {
                                                $telega->sendMessageWithKeyboard($chat_id, $message, $keyboard);
                                            }

                                            if ($chat_ids)
                                            {
                                                $timestamp = date('Y-m-d H:i:s', tools::get_time());
                                                $term = new term(['mode' => 'add', 'table' => 'lots', 'call_id' => $call_id, 'timestamp' => $timestamp, 'price' => $price]);
                                            }
                                        }
                                    }
                                }
                            }
                        }
                    }
                 }
            
            break;
            
            case 'stat':
            
                $chat_id = isset($args['chat_id']) ? (integer) $args['chat_id'] : 0;
                
                if ($chat_id)
                {
                    $sql = "SELECT `organization_id` FROM `lot_telegrams` WHERE `chat_id`=:chat_id";        
                    $stm = pdo::getPdo()->prepare($sql);
                    $stm->execute(array('chat_id' => $chat_id));
                    $organization_id = $stm->fetchColumn();
                    
                    if ($organization_id)
                    {
                        $message = '';
                        
                        $sql = "SELECT `summ` FROM `count_transactions` WHERE `organization_id`=:organization_id";
                        $stm = pdo::getPdo()->prepare($sql);
                        $stm->execute(array('organization_id' => $organization_id));
                        $balance = (integer) $stm->fetchColumn();
                        
                        list($h, $i, $s, $n, $j, $Y) = array(date("H"),date("i"),date("s"),date("n"),date("j"),date("Y"));
                        
                        $message .= "Лидов куплено\n";
                        $message .= "-------------\n";
                        
                        foreach (
                            [
                                ['start_date' => date('Y-m-d H:i:s', mktime(0, 0, 0, $n, $j, $Y)), 'end_date' => date('Y-m-d H:i:s', mktime(23, 59, 59, $n, $j, $Y))], 
                                ['start_date' => date('Y-m-d H:i:s', mktime(0, 0, 0, $n, $j - 30, $Y)), 'end_date' => date('Y-m-d H:i:s', mktime(23, 59, 59, $n, $j, $Y))], 
                                []
                                
                            ] as $key => $value)
                        {
                            $filter = [];
                            
                            switch ($key)
                            {
                                case 0:
                                    $period = 'Сегодня';
                                break;
                                case 1:
                                    $period = 'Последние 30 дней';
                                break;
                                case 2:
                                    $period = 'Весь период';
                                break;
                            }
                             
                            if ($value)
                            {
                                $filter[] = "`transactions`.`timestamp`>='". $value['start_date'] ."'";
                                $filter[] = "`transactions`.`timestamp`<='" . $value['end_date']  ."'";      
                            }                     
                           
                            $filter[] = "`operation_id` IN (1,3)";                                             
                            $filter[] = "`transactions`.`organization_id` = " . $organization_id;
                            
                            $filter = " WHERE (".implode(") AND (", $filter).")";
                            
                            $sql = "SELECT SUM(ABS(`summ`)) as `summ`, COUNT(`transactions`.`id`) as `count` FROM `transactions` {$filter}";
                            $calls = pdo::getPdo()->query($sql)->fetch(\PDO::FETCH_ASSOC);
                                
                            $message .= $period." - <strong>".$calls['count']."</strong> на сумму <strong>".$calls['summ']." RUB</strong> \n";
                        }
                        
                        $message .= "-------------\n";
                        $message .= "Баланс <strong>" . $balance . " RUB</strong>";                        
                        
                        $telega = new telegram($this->_turbo_lid_token);
                        $telega->sendMessage($chat_id, $message);
                    }
                }
                                
            break;
            
            case 'history':
            
                $chat_id = isset($args['chat_id']) ? (integer) $args['chat_id'] : 0;
                $page = isset($args['page']) ? (integer) $args['page'] : 1;
                
                if ($chat_id)
                {
                    $sql = "SELECT `organization_id` FROM `lot_telegrams` WHERE `chat_id`=:chat_id";        
                    $stm = pdo::getPdo()->prepare($sql);
                    $stm->execute(array('chat_id' => $chat_id));
                    $organization_id = $stm->fetchColumn();
                    
                    if ($organization_id)
                    {
                        $step = 10;
                        $telega = new telegram($this->_turbo_lid_token);
                         
                        $sql = "SELECT COUNT(`call_id`) FROM `transactions` WHERE `organization_id`=:organization_id AND `operation_id` IN (1,3)";
                        $stm = pdo::getPdo()->prepare($sql);
                        $stm->execute(array('organization_id' => $organization_id));
                        $all = (integer) $stm->fetchColumn();
                        
                        if ($all == 0)
                        {
                            $message = "Купленных лидов еще нет!";
                            $telega->sendMessage($chat_id, $message);        
                        }
                        else
                        {
                            if ($page == 1)
                            {
                                $message = "Последние купленные лиды";
                                $telega->sendMessage($chat_id, $message);   
                            }
                                                    
                            $limit = ($page-1) * $step;
                            
                            $sql = "SELECT `call_id` FROM `transactions` WHERE `organization_id`=:organization_id AND `operation_id` IN (1,3) 
                                        ORDER BY `id` DESC LIMIT $limit,$step";
                            $stm = pdo::getPdo()->prepare($sql);
                            $stm->execute(array('organization_id' => $organization_id));
                            $call_ids = (array) $stm->fetchAll(\PDO::FETCH_COLUMN); 
                            
                            $count = count($call_ids) - 1;
                            
                            foreach ($call_ids as $key => $call_id) 
                            {
                                $message_obj = new lot(['mode' => 'new_lot', 'call_id' => $call_id, 'show_phone' => true, 'show_price' => true]);    
                                $message = $message_obj->getWrapper()->getChildren(0);
                                
                                $keyboard = '';
                                
                                if (($page * $step) < $all && $key == $count)
                                {
                                    $json = mb_convert_encoding(json_encode(['page' => ($page + 1)]), 'UTF-8');
                                    $keyboard = [
                                       "inline_keyboard" => [
                                             [["text" => "Следующие {$step}", "callback_data" => $json]],
                                        ],
                                    ];  
                                }
                                
                                if ($keyboard)
                                    $telega->sendMessageWithKeyboard($chat_id, $message, $keyboard);
                                else
                                    $telega->sendMessage($chat_id, $message);
                            }
                            
                        }
                    }
                }                
            
            break;
            
            case 'send_region':
            
                $chat_id = isset($args['chat_id']) ? (integer) $args['chat_id'] : 0;
                $message = isset($args['message']) ? (string) $args['message'] : 'Выбери подходящий тебе регион:';
                $start = isset($args['start']) ? (integer) $args['start'] : 0;
                $message_id = isset($args['message_id']) ? $args['message_id'] : 0;
                
                if ($chat_id)
                {
                    $sql = "SELECT `organization_id` FROM `lot_telegrams` WHERE `chat_id`=:chat_id";        
                    $stm = pdo::getPdo()->prepare($sql);
                    $stm->execute(array('chat_id' => $chat_id));
                    $organization_id = $stm->fetchColumn();
                    
                    if ($organization_id)
                    {
                        $sql = "SELECT `navy_services`.`id` FROM `navy_services`
                                                        INNER JOIN `address` ON `navy_services`.`addres_id` = `address`.`id`
                                                    WHERE `address`.`organization_id`=:organization_id";
                        $stm = pdo::getPdo()->prepare($sql); 
                        $stm->execute(array('organization_id' => $organization_id));
                        $navy_service_id = $stm->fetchColumn();
                    
                        $sql = "SELECT `citi_id` FROM `services` WHERE `id`=:service_id";
                        $stm = navy_db::getPdo()->prepare($sql); 
                        $stm->execute(array('service_id' => $navy_service_id));
                        $citi_id = $stm->fetchColumn();
                    
                        $sql = "SELECT `region_id` FROM `ag_citis` WHERE `id`=:citi_id";        
                        $stm = pdo::getPdo()->prepare($sql);
                        $stm->execute(array('citi_id' => $citi_id));
                        $region_id = $stm->fetchColumn();
                    }                
                          
                    $keyboard = [
                       "inline_keyboard" => [
                            
                        ]
                    ];
                    
                    $regions = ['Москва', 'Санкт-Петербург', 'Новосибирск', 'Екатеринбург', 'Нижний Новгород',
                                    'Казань', 'Челябинск', 'Омск', 'Самара', 'Ростов-на-Дону', 'Уфа',
                                        'Красноярск', 'Пермь', 'Воронеж'];
                    
                    $sql = "SELECT `id`, `name` FROM `regions` WHERE `name` IN ('".implode("','", $regions)."') ORDER BY `interes` ASC";
                    $datas = pdo::getPdo()->query($sql)->fetchAll(\PDO::FETCH_ASSOC);
                    
                    $j = 0;
                    
                    foreach ($datas as $key => $data)
                    {
                        $i = $key % 2;
                        
                        $params = ['region_id' => $data['id'], 'start' => $start];
                        
                        if (isset($region_id) && $region_id == $data['id']) $data['name'] = '✅ '. $data['name'];
                        
                        $json = mb_convert_encoding(json_encode($params), 'UTF-8');
                                            
                        $keyboard['inline_keyboard'][$j][] = ["text" => $data['name'], "callback_data" => $json];
                        
                        if ($i) $j++;
                    }
                    
                    if ($start)
                    {
                        $json = mb_convert_encoding(json_encode(['region_id' => 0, 'start' => $start]), 'UTF-8');
                        $keyboard['inline_keyboard'][$j][] = ["text" => 'продолжить »', "callback_data" => $json];
                    }
                                                        
                    $telega = new telegram($this->_turbo_lid_token);
                    
                    if (!$message_id)
                        $telega->sendMessageWithKeyboard($chat_id, $message, $keyboard);
                    else
                        $telega->editMessage($chat_id, $message_id, $message, $keyboard);
                }
            
            break;
            
            case 'send_offer':
            
                $chat_id = isset($args['chat_id']) ? (integer) $args['chat_id'] : 0;
                $message = isset($args['message']) ? (string) $args['message'] : 'Выбери подходящий тебе оффер:';
                $start = isset($args['start']) ? (integer) $args['start'] : 0;
                $message_id = isset($args['message_id']) ? $args['message_id'] : 0;
                 
                if ($chat_id)
                {
                    $sql = "SELECT `organization_id` FROM `lot_telegrams` WHERE `chat_id`=:chat_id";        
                    $stm = pdo::getPdo()->prepare($sql);
                    $stm->execute(array('chat_id' => $chat_id));
                    $organization_id = $stm->fetchColumn();
                    
                    if ($organization_id)
                    {
                        $offer_ids = [];
                        
                        $sql = "SELECT `navy_services`.`id` FROM `navy_services`
                                                        INNER JOIN `address` ON `navy_services`.`addres_id` = `address`.`id`
                                                    WHERE `address`.`organization_id`=:organization_id";
                        $stm = pdo::getPdo()->prepare($sql); 
                        $stm->execute(array('organization_id' => $organization_id));
                        $navy_service_id = $stm->fetchColumn();
                    
                        $sql = "SELECT `offer_id` FROM `offer_to_services` WHERE `service_id`=:service_id";
                        $stm = navy_db::getPdo()->prepare($sql); 
                        $stm->execute(array('service_id' => $navy_service_id));
                        $ag_offer_ids = $stm->fetchAll(\PDO::FETCH_COLUMN);
                        
                        if ($ag_offer_ids)
                        {
                            $sql = "SELECT `offer_id` FROM `ag_offers` WHERE `id` IN (".implode(',', $ag_offer_ids).")";        
                            $offer_ids = pdo::getPdo()->query($sql)->fetchAll(\PDO::FETCH_COLUMN);
                        }
                    }                
                
                    $keyboard = [
                       "inline_keyboard" => [
                            
                        ]
                    ];
                    
                    $datas = [
                        ['name' => 'цифровая техника', 'id' => 1],
                        ['name' => 'бытовая техника', 'id' => 2],
                        ['name' => 'фото- видеотехника', 'id' => 4],
                        ['name' => 'электротранспорт', 'id' => 5],
                        ['name' => 'автосервисы', 'id' => 10],
                        ['name' => 'эвакуатор', 'id' => 21],
                        ['name' => 'вывоз мусора', 'id' => 94],
                        ['name' => 'клининг', 'id' => 18],
                        ['name' => 'медицина','id' => 13],
                        ['name' => 'красота', 'id' => 14],
                        ['name' => 'мелкий ремонт', 'id' => 19], 
                        ['name' => 'кондиционеры', 'id' => 23], 
                        ['name' => 'монтаж дверей', 'id' => 26],
                        ['name' => 'пластиковые окна', 'id' => 24],
                    ];
                    
                    //$sql = "SELECT `id`, `name` FROM `offers` WHERE `name` IN ('".implode("','", $offers)."')";
                    //$datas = pdo::getPdo()->query($sql)->fetchAll(\PDO::FETCH_ASSOC);
                    
                    $j = 0;
                    
                    foreach ($datas as $key => $data)
                    {
                        $i = $key % 2;
                        
                        $params = ['offer_id' => $data['id'], 'start' => $start];
                        
                        $params['active'] = 1;
                        if (isset($offer_ids) && in_array($data['id'], $offer_ids)) 
                        {
                            $data['name'] = '✅ '. $data['name'];
                            $params['active'] = 0;
                        } 
                            
                        $json = mb_convert_encoding(json_encode($params), 'UTF-8');
                        
                        $keyboard['inline_keyboard'][$j][] = ["text" => $data['name'], "callback_data" => $json];
                        
                        if ($i) $j++;
                    }
                    
                    if ($start)
                    {
                        $json = mb_convert_encoding(json_encode(['offer_id' => 0, 'start' => $start]), 'UTF-8');
                        $keyboard['inline_keyboard'][$j][] = ["text" => 'продолжить »', "callback_data" => $json];
                    }
                   
                    $telega = new telegram($this->_turbo_lid_token);
                    
                    if (!$message_id)
                        $telega->sendMessageWithKeyboard($chat_id, $message, $keyboard);
                    else
                        $telega->editMessage($chat_id, $message_id, $message, $keyboard);
                }
            
            break;
            
            case 'set_offer':
            
                $chat_id = isset($args['chat_id']) ? (integer) $args['chat_id'] : 0;
                $offer_id = isset($args['offer_id']) ? (integer) $args['offer_id'] : 0;
                $start = isset($args['start']) ? (integer) $args['start'] : 0;
                $active = isset($args['active']) ? (integer) $args['active'] : 1;
                $message_id = isset($args['message_id']) ? $args['message_id'] : 0;
                
                if ($offer_id == 0)
                {
                    $lot_obj = new lot(['mode' => 'start', 'start' => ($start + 1), 'chat_id' => $chat_id]);  
                }
                
                if ($chat_id && $offer_id)
                {
                    $name = load::get_order($offer_id, 'name', 'offers');
                    
                    $sql = "SELECT `organization_id` FROM `lot_telegrams` WHERE `chat_id`=:chat_id";        
                    $stm = pdo::getPdo()->prepare($sql);
                    $stm->execute(array('chat_id' => $chat_id));
                    $organization_id = $stm->fetchColumn();
                
                    if ($organization_id)
                    {
                        $sql = "SELECT `id` FROM `ag_offers` WHERE `offer_id`=:offer_id";        
                        $stm = pdo::getPdo()->prepare($sql);
                        $stm->execute(array('offer_id' => $offer_id));
                        $ag_offer_id = $stm->fetchColumn();
                
                        $sql = "SELECT `navy_services`.`id` FROM `navy_services`
                                                INNER JOIN `address` ON `navy_services`.`addres_id` = `address`.`id`
                                                    WHERE `address`.`organization_id`=:organization_id";
                        $stm = pdo::getPdo()->prepare($sql); 
                        $stm->execute(array('organization_id' => $organization_id));
                        $navy_service_id = $stm->fetchColumn();
                        
                        if ($ag_offer_id && $navy_service_id)
                        {
                            if (!$active)
                            {
                                $sql = "DELETE FROM `offer_to_services` WHERE `service_id`=:service_id AND `offer_id`=:offer_id"; 
                                $stm = navy_db::getPdo()->prepare($sql);
                                $stm->execute(array('service_id' => $navy_service_id, 'offer_id' => $ag_offer_id));
                            }
                           
                            if ($active)
                            {
                                $sql = "SELECT `id` FROM `offer_to_services` WHERE `service_id`=:service_id AND `offer_id`=:offer_id"; 
                                $stm = navy_db::getPdo()->prepare($sql);
                                $stm->execute(array('service_id' => $navy_service_id, 'offer_id' => $ag_offer_id));
                                $has = $stm->fetchColumn();
                                
                                if (!$has)
                                {
                                    $sql = "INSERT INTO `offer_to_services` (`offer_id`, `service_id`) VALUES ($ag_offer_id,$navy_service_id)"; 
                                    navy_db::getPdo()->query($sql);    
                                }                                    
                            }

                            //$telega = new telegram($this->_turbo_lid_token);
                            
                            if ($active)
                                //$telega->sendMessage($chat_id, 'Оффер "'.$name.'" установлен!');
                                $lot_obj = new lot(['mode' => 'send_offer', 'chat_id' => $chat_id, 'message' => 'Оффер "'.$name.'" установлен!', 'start' => $start, 'message_id' => $message_id]);
                            else
                                //$telega->sendMessage($chat_id, 'Оффер "'.$name.'" снят!');
                                $lot_obj = new lot(['mode' => 'send_offer', 'chat_id' => $chat_id, 'message' => 'Оффер "'.$name.'" снят!', 'start' => $start, 'message_id' => $message_id]);
                            
                                                     
                        }
                    }
                }        
            
            break;
            
            case 'set_region':
            
                $chat_id = isset($args['chat_id']) ? (integer) $args['chat_id'] : 0;
                $region_id = isset($args['region_id']) ? (integer) $args['region_id'] : 0;
                $start = isset($args['start']) ? (integer) $args['start'] : 0;
                $message_id = isset($args['message_id']) ? $args['message_id'] : 0;
                
                if ($region_id == 0)
                {
                    $lot_obj = new lot(['mode' => 'start', 'start' => ($start + 1), 'chat_id' => $chat_id]);   
                }
                
                if ($chat_id && $region_id)
                {
                    $name = load::get_order($region_id, 'name', 'regions');
                    
                    $sql = "SELECT `organization_id` FROM `lot_telegrams` WHERE `chat_id`=:chat_id";        
                    $stm = pdo::getPdo()->prepare($sql);
                    $stm->execute(array('chat_id' => $chat_id));
                    $organization_id = $stm->fetchColumn();
                
                    if ($organization_id)
                    {
                        $sql = "SELECT `id` FROM `ag_citis` WHERE `region_id`=:region_id";        
                        $stm = pdo::getPdo()->prepare($sql);
                        $stm->execute(array('region_id' => $region_id));
                        $ag_citi_id = $stm->fetchColumn();
                
                        $sql = "SELECT `navy_services`.`id` FROM `navy_services`
                                                INNER JOIN `address` ON `navy_services`.`addres_id` = `address`.`id`
                                                    WHERE `address`.`organization_id`=:organization_id";
                        $stm = pdo::getPdo()->prepare($sql); 
                        $stm->execute(array('organization_id' => $organization_id));
                        $navy_service_id = $stm->fetchColumn();
                        
                        if ($ag_citi_id && $navy_service_id)
                        {
                            $sql = "UPDATE `services` SET `citi_id`=:citi_id WHERE `id`=:service_id"; 
                            $stm = navy_db::getPdo()->prepare($sql);
                            $stm->execute(array('citi_id' => $ag_citi_id, 'service_id' => $navy_service_id)); 
                            
                            $lot_obj = new lot(['mode' => 'send_region', 'chat_id' => $chat_id, 'message' => 'Регион "'.$name.'" установлен!', 'start' => $start, 'message_id' => $message_id]);
                            
                            //$telega = new telegram($this->_turbo_lid_token);
                            //$telega->sendMessage($chat_id, 'Регион "'.$name.'" установлен!');
                        }
                    }
                }        
            
            break;
            
            case 'start':
            
                $start = isset($args['start']) ? (integer) $args['start'] : 1;
                $chat_id = isset($args['chat_id']) ? (integer) $args['chat_id'] : 0;
                
                if ($start == 1)
                {
                    $lot_obj = new lot(['mode' => 'send_region', 'chat_id' => $chat_id, 'message' => 'Привет! Из какого ты региона?', 'start' => $start]);
                }
                        
                if ($start == 2)
                {
                    $lot_obj = new lot(['mode' => 'send_offer', 'chat_id' => $chat_id, 'message' => 'Какие офферы тебя интересуют? Выбери один или несколько и нажми "продолжить".', 'start' => $start]);
                }
                
                if ($start == 3)
                {
                    $telega = new telegram($this->_turbo_lid_token);
                    $telega->sendMessage($chat_id, 'На этом все. Жди лидов. Если захочешь изменить регион и оффер, это можно сделать через меню.');
                    tools::request_api(['op' => 'sync_navy']);
                }  
            
            break;
            
            case 'send_invoce':
            
                $chat_id = isset($args['chat_id']) ? (integer) $args['chat_id'] : 0;
                $call_id = isset($args['call_id']) ? (integer) $args['call_id'] : 0;
                $message_id = isset($args['message_id']) ? $args['message_id'] : 0;
                
                if ($chat_id && $call_id)
                {
                    $sql = "SELECT `organization_id`, `user_id` FROM `lot_telegrams` WHERE `chat_id`=:chat_id";        
                    $stm = pdo::getPdo()->prepare($sql);
                    $stm->execute(array('chat_id' => $chat_id));
                    $lot_data = $stm->fetch(\PDO::FETCH_ASSOC);
                    
                    $organization_id = $lot_data['organization_id'];
                    $user_id = $lot_data['user_id'];
                    
                    $sql = "SELECT `price` FROM `lots` WHERE `call_id`=:call_id AND `sold` = 0";        
                    $stm = pdo::getPdo()->prepare($sql);
                    $stm->execute(array('call_id' => $call_id));
                    $summ = $stm->fetchColumn();
                    
                    if ($organization_id)
                    {
                        if ($summ)
                        {                
                            $status = load::get_status('status_bills', 'not_pay');
                             
                            $obj = new term(array('mode' => 'add', 'table' => 'bills', 'summ' => $summ, 'status_bill_id' => $status, 'paymaster' => 'telegram', 
                                                            'organization_id' => $organization_id, 'user_id' => $user_id));
                            $bill_id = $obj->getWrapper()->getChildren(0);
                            
                            $obj_link = new term(array('mode' => 'add', 'table' => 'lot_bills', 'call_id' => $call_id, 'bill_id' => $bill_id));
                             
                            $title = 'Лид #' . $call_id; 
                            $message = 'Нажмите "Заплатить", чтобы произвести оплату';
                            
                            $payload = (string) $bill_id;
                            
                            $telega = new telegram($this->_turbo_lid_token);
                            $telega->sendInvoce($chat_id, $title, $message, $payload, $summ, $message_id);
                            
                            //print_r($status);        
                            //file_put_contents(\DOCUMENT_ROOT.'telega', print_r($status, true).print_r($data, true), FILE_APPEND | LOCK_EX);
                        }
                        else
                        {
                            $telega = new telegram($this->_turbo_lid_token);
                            $telega->sendMessage($chat_id, 'Лид #'.$call_id.' уже куплен! Попробуйте купить другой лид!'); 
                        }
                    }
                }
                                
            break; 
            
            case 'successful':

                $chat_id = isset($args['chat_id']) ? (integer) $args['chat_id'] : 0;
                $bill_id = isset($args['bill_id']) ? (integer) $args['bill_id'] : 0;
                $payment_id = isset($args['payment_id']) ? (string) $args['payment_id'] : '';
                
                if ($bill_id && $payment_id)
                {
                    $timestamp = tools::get_time();                
                    $time =  date('Y-m-d H:i:s', $timestamp);
                    
                    $summ = load::get_order($bill_id, 'summ', 'bills'); 
                    
                    $args = [];
                    $args['user_id'] = load::get_order($bill_id, 'user_id', 'bills'); 
                    $args['organization_id'] = $organization_id = load::get_order($bill_id, 'organization_id', 'bills');
                    
                    $sql = "SELECT `id` FROM `transactions` WHERE `bill_id`=:bill_id"; 
                    $stm = pdo::getPdo()->prepare($sql);
                    $stm->execute(array('bill_id' => $bill_id));
                    $has = $stm->fetchColumn();
                    
                    if (!$has)
                    {
                        $obj1 = new term(array('mode' => 'update', 'table' => 'bills', 'id' => $bill_id, 
                                            'status_bill_id' => load::get_status('status_bills', 'pay'), 'paymaster' => $payment_id));
                                            
                        $obj2 = new term(array('mode' => 'add', 'table' => 'transactions', 'summ' => $summ, 'bill_id' => $bill_id,
                                                 'operation_id' => load::get_status('operations', 'bill'), 'timestamp' => $time, 'user_id' => $args['user_id'],
                                                        'organization_id' => $args['organization_id']));
                                                        
                        $transaction_id = (integer) $obj2->getWrapper()->getChildren(0);
                        
                        $navy_obj = new navy_chat(['mode' => 'save_chat', 'text' => 'Баланс пополнен на ' . $summ . ' руб', 'organization_id' => $args['organization_id'], 'user_id' => 0, 'navy_chat_type_id' => 2]);
                        
                        $sql = "SELECT `call_id` FROM `lot_bills` WHERE `bill_id`=:bill_id";
                        $stm = pdo::getPdo()->prepare($sql); 
                        $stm->execute(array('bill_id' => $bill_id));
                        $call_id = $stm->fetchColumn();
                         
                        if ($call_id)
                        {                        
                            $phone_id = load::get_order($call_id, 'phone_id', 'calls');
                            $phone_name = load::get_order($phone_id, 'name', 'phones');            
                            
                            $sql = "SELECT `navy_services`.`id` 
                                FROM `navy_services` 
                                        INNER JOIN `address` ON `address`.`id` = `navy_services`.`addres_id`
                                            WHERE `address`.`organization_id`=:organization_id";
                            $stm = pdo::getPdo()->prepare($sql);
                            $stm->execute(array('organization_id' => $organization_id));
                            $navy_service_id = $stm->fetchColumn();
                            
                            if ($navy_service_id)
                            {
                                $sql = "SELECT `nyk_name2` FROM `partner_orders` WHERE `call_id`=:call_id";                                
                                $stm = pdo::getPdo()->prepare($sql);
                                $stm->execute(array('call_id' => $call_id));
                                $nyk_name2 = $stm->fetchColumn(); 
                                
                                $resale_array = ['mode' => 'app', 'phone' => $phone_name, 'navy_service_id' => $navy_service_id, 
                                                            'call_id' => $call_id, 'comment' => $nyk_name2, 'letter' => 0];
                                $resale_obj = new resale($resale_array);
                                $b_call_id = $resale_obj->getWrapper()->getChildren(0);
                                
                                $message_obj = new lot(['mode' => 'new_lot', 'call_id' => $call_id, 'show_phone' => true]);    
                                $message = $message_obj->getWrapper()->getChildren(0);                          
                                
                                $telega = new telegram($this->_turbo_lid_token);
                                
                                $telega->sendMessage($chat_id, 'Спасибо. Вы купили лид #'.$call_id.'. Данные о лиде будут высланы в следующем сообщении.'); 
                                $telega->sendMessage($chat_id, $message);
                                
                                $sql = "UPDATE `lots` SET `sold`=:organization_id WHERE `call_id`=:call_id";        
                                $stm = pdo::getPdo()->prepare($sql);
                                $stm->execute(array('organization_id' => $organization_id, 'call_id' => $call_id));
                            
                                $sql = "UPDATE `transactions` SET `summ`=:summ, `dollar`=1 WHERE `call_id`=:call_id";
                                $stm = pdo::getPdo()->prepare($sql);
                                $stm->execute(array('summ' => (-1) * $summ, 'call_id' => $b_call_id));
                            }
                        }
                    }
                }

            break;
            
            case 'new_lot':
            
                $call_id = isset($args['call_id']) ? (integer) $args['call_id'] : 0;
                $show_phone = isset($args['show_phone']) ? (bool) $args['show_phone'] : false;
                $show_price = isset($args['show_price']) ? (bool) $args['show_price'] : false;
                
                if ($call_id)
                {
                    $sql = "SELECT `regions`.`name` as `region_name`, `places`.`name` as `place_name`, `phone_name3`, `nyk_name2` 
                                FROM `partner_orders` 
                                    LEFT JOIN `regions` ON `regions`.`id` = `partner_orders`.`region_id`
                                        LEFT JOIN `places` ON `places`.`id` = `partner_orders`.`place_id` 
                                            WHERE `call_id`=:call_id";                                
                    $stm = pdo::getPdo()->prepare($sql);
                    $stm->execute(array('call_id' => $call_id));
                    $data = $stm->fetch(\PDO::FETCH_ASSOC); 
                    
                    $phone_id = load::get_order($call_id, 'phone_id', 'calls');
                    $phone = load::get_order($phone_id, 'name', 'phones');   
                    
                    if ($data && $phone)
                    {   
                        $fire = '&#128293;&#128293; ';
                        
                        if (!$show_price)
                        {                        
                            $text = $fire . '<strong>Лид</strong> #' . $call_id."\n";
                        }
                        else
                        {
                            $sql = "SELECT `a` FROM `connectors` WHERE `b`=:call_id";
                            $stm = pdo::getPdo()->prepare($sql);
                            $stm->execute(array('call_id' => $call_id));
                            $a = $stm->fetchColumn();  
                            
                            $text = '<strong>Лид</strong> #' . $a."\n";
                        }
                        
                        $place_array = implode(' ', array_unique([$data['region_name'], $data['place_name']]));
                        
                        $text .= "<strong>Регион:</strong> {$place_array} \n";
                        
                        if ($show_phone) {
                            $text .= "<strong>Телефон клиента:</strong> +{$phone} \n";   
                        }                    
                       
                        if ($data['phone_name3']) {
                            $text .= "<strong>Имя:</strong> {$data['phone_name3']} \n";
                        }
                        
                        $tag_str = [];
                    
                        $sql = "SELECT `name_type`, `id_type` FROM `partner_order_tags` 
                                        INNER JOIN `partner_orders` ON `partner_orders`.`id` = `partner_order_tags`.`partner_order_id` WHERE `partner_orders`.`call_id`=:call_id";
                        $stm = pdo::getPdo()->prepare($sql);
                        $stm->execute(array('call_id' => $call_id));
                        $t_tags = (array) $stm->fetchAll(\PDO::FETCH_ASSOC);                    
                        
                        foreach ($t_tags as $t_tag)
                        {
                            $table = $t_tag['name_type'].'s';
                            
                            if (pdo::getPdo()->query("SHOW TABLES LIKE '{$table}'")->rowCount() > 0)
                            {
                                $sql = "SELECT `name` FROM `{$table}` WHERE `id`=:id";
                                $stm = pdo::getPdo()->prepare($sql);
                                $stm->execute(array('id' => $t_tag['id_type']));
                                $tag_str[] = $stm->fetchColumn(); 
                            }
                        }
                        
                        $sql = "SELECT IF(`parent_offers`.`name` IS NOT NULL, CONCAT(`parent_offers`.`name`, ' | ', `offers`.`name`), `offers`.`name`) FROM `partner_orders` 
                                                INNER JOIN `offers` ON `offers`.`id` = `partner_orders`.`offer_id`
                                                    LEFT JOIN `offers` `parent_offers` ON `parent_offers`.`id` = `offers`.`parent` 
                                                        WHERE `partner_orders`.`call_id`=:call_id";
                        $stm = pdo::getPdo()->prepare($sql); 
                        $stm->execute(array('call_id' => $call_id));
                        $offer_name = $stm->fetchColumn();  
                        
                        if ($offer_name) {
                            $text .= "<strong>Оффер:</strong> {$offer_name} \n";
                        }
                        
                        if ($tag_str) {
                            $tag_str = implode(" | ", $tag_str);
                            $text .= "<strong>Теги:</strong> {$tag_str} \n";
                        }
                        
                        if ($data['nyk_name2']) {
                            if (!$show_phone) $data['nyk_name2'] = preg_replace('/\d/', '*', $data['nyk_name2']);
                            
                            $text .= "<strong>Комментарий:</strong> {$data['nyk_name2']} \n";
                        }
                        
                        if ($show_price) {
                            $sql = "SELECT ABS(`summ`) as `summ`, `timestamp` FROM `transactions` WHERE `call_id`=:call_id";
                            $stm = pdo::getPdo()->prepare($sql);
                            $stm->execute(array('call_id' => $call_id));
                            $transaction_array = (array) $stm->fetch(\PDO::FETCH_ASSOC);
                            
                            if ($transaction_array)
                            {
                                $price = $transaction_array['summ'];
                                $timestamp = date('d.m.y H:i', strtotime($transaction_array['timestamp']));
                                
                                $text .= "<strong>Стоимость:</strong> {$price} RUB \n";
                                $text .= "<strong>Дата покупки:</strong> {$timestamp} \n";
                            }
                        }
                        
                        $answer = $text;                    
                    }
                }
                
            break;
            
            case 'organization_add':
            
                $chat_id = isset($args['chat_id']) ? (integer) $args['chat_id'] : 0;
                
                if (!$chat_id) break;
                
                if ($chat_id)
                {
                    $sql = "SELECT `id` FROM `lot_telegrams` WHERE `chat_id`=:chat_id";
                    $stm = pdo::getPdo()->prepare($sql);
                    $stm->execute(array('chat_id' => $chat_id));    
                    $id = $stm->fetchColumn();
                    
                    if (!$id)
                    {
                        $navy = new navy(['mode' => 'organization_add', 'express' => 2]);
                        $add_obj = $navy->getWrapper()->getChildren(0);
                        
                        $term = new term(['mode' => 'add', 'table' => 'lot_telegrams', 'chat_id' => $chat_id, 'organization_id' => $add_obj[2], 'user_id' => $add_obj[3]]);
                    }
                }
            
            break;
        }
        
        $this->getWrapper()->addChildren($answer);
    }
    
    public static function get_token()
    {
        list($token) = dotenv::get_vars(['TOKEN_TURBOLEAD']);
        return $token;
    }
            
            /*case 'lot':
            
                $lot_id = isset($args['lot_id']) ? (integer) $args['lot_id'] : 0;
                $mark_down = isset($args['mark_down']) ? $args['mark_down'] : false;
                $repeat = isset($args['repeat']) ? $args['repeat'] : false;
                
                if ($lot_id)
                {
                    $lot = load::get_order($lot_id, ['call_id', 'id', 'pay', 'summ', 'organization_id', 'no_active'], 'lots', false);
                    
                    $enum = new enum();
                    $enum->setSign('');
                    
                    $director = load::is_director();
                    
                    $panel1 = new x_panel(); 
                    $panel1->getAttributes()->getClass()->addItems('lid-detail');
                    
                    $enum1 = new enum();
                    $enum1->setSign('');
                    
                    list($phone_name, $model_type_id, $brand_id, $region_id, $comment, $o_id, $phone_name2, $poser_id) = bill::get_info_about_call($lot['call_id']);
                    
                    if (!$director && !$mark_down)
                    {
                        $sql = "SELECT COUNT(*) FROM `lots` WHERE `call_id`=:call_id";
                        $stm = pdo::getPdo()->prepare($sql);
                        $stm->execute(array('call_id' => $lot['call_id']));
                        $count_partners = (integer) $stm->fetchColumn();    
                        
                        $p = new node('p');
                        $p->getAttributes()->getClass()->addItems('pull-right');  
                        $p->addChildren(tools::declOfNum($count_partners, ['партнер', 'партнера', 'партнеров']));           
                        $enum1->addItems($p);         
                    }
                    
                    if ($repeat)
                    {
                        $p = new node('p');
                        $b = new node('strong'); 
                        $b->addChildren('СНИЖЕНИЕ ЦЕНЫ');
                        $p->addChildren($b."\n");
                        $enum1->addItems($p);
                    }
                    
                    $p = new node('p');
                    $b = new node('strong'); 
                    $b->addChildren('Лид');
                    
                    $fire = '';
                    if (!$lot['pay'] && !$lot['no_active']) $fire = '&#128293;&#128293; ';
                    
                    $p->addChildren($fire . $b. ' #' . $lot['call_id']."\n");
                    
                    $enum1->addItems($p);
                    
                    foreach (['region' => 'Регион', 'brand' => 'Бренд', 'model_type' => 'Тип устройства', 'poser' => 'Проблема'] as $name => $tip_name)
                    {
                        $table = $name . 's';
                        $name_id = $name.'_id';
                        
                        $sql = "SELECT `name` FROM `{$table}` WHERE `id`=:id";
                        $stm = pdo::getPdo()->prepare($sql);         
                        $stm->execute(array('id' => $$name_id));
                        $val = $stm->fetchColumn();        
                        
                        if ($val)
                        {
                            $p = new node('p');
                            $b = new node('strong'); 
                            $b->addChildren($tip_name);
                            
                            $p->addChildren($b.': '.$val."\n");
                            
                            $enum1->addItems($p);
                        }
                    }
                    
                    if ($phone_name2)
                    {
                        $p = new node('p');
                        $b = new node('strong'); 
                        $b->addChildren('Введённое имя');
                        
                        $p->addChildren($b.': '.$phone_name2."\n");
                        
                        $enum1->addItems($p);
                    }
                    
                    if ($comment)
                    {
                        $p = new node('p');
                        $b = new node('strong'); 
                        $b->addChildren('Комментарий');
                        
                        $p->addChildren($b.': '.$comment."\n");
                        
                        $enum1->addItems($p);
                    }
                    
                    if (!$lot['pay'])
                    {
                        if ($lot['no_active'])
                        {
                            $p = new node('p');
                            $p->addChildren('Лид снят с торгов на цене ' . tools::format_price2($lot['summ']));
                            $enum1->addItems($p);
                        }
                        else
                        {
                            if ($mark_down)
                            {
                                $p = new node('p');
                                $b = new node('strong'); 
                                $b->addChildren('Стоимость');
                                
                                $p->addChildren($b.': '.tools::format_price2($lot['summ']));
                                
                                $enum1->addItems($p);
                            }
                            else
                            {
                                $button_primary = new form\button_primary('Купить за ' . tools::format_price2($lot['summ']));
                                $button_primary->getAttributes()->getClass()->addItems('js-buy');   
                                $button_primary->getAttributes()->addAttr('data-lot_id', $lot['id']);
                                $enum1->addItems($button_primary);
                            }
                        }
                    }
                    else
                    {
                        if ($lot['organization_id'] == $lot['pay'])
                        {
                            $p = new node('p');
                            $b = new node('strong'); 
                            $b->addChildren('Номер телефона клиента');
                            $p->addChildren($b.': '.tools::format_phone($phone_name));
                        
                            $enum1->addItems($p);
                            
                            $p = new node('p');
                            $p->addChildren('Лид продан за ' . tools::format_price2($lot['summ']));
                            $enum1->addItems($p);
                        }
                        else
                        {
                            $p = new node('p');
                            $p->addChildren('Лид продан за ' . tools::format_price2($lot['summ']));
                            $enum1->addItems($p);
                        }
                    }
                    
                    if ($mark_down)
                    {
                        $answer = (string) $enum1;
                        $answer = strip_tags($answer, '<strong>');
                    }
                    else
                    {
                        $panel1->setInto($enum1);                    
                        $enum->addItems($panel1);
                        
                        $hidden = new form\hidden();
                        $hidden->setName('lot_id');
                        $hidden->setValue($lot_id);     
                        $enum->addItems($hidden);
                        
                        $hidden = new form\hidden();
                        $hidden->setName('mode');
                        $hidden->setValue($mode);     
                        $enum->addItems($hidden);
                        
                        $answer = $enum;
                    }
                }
            
            break;
            
            case 'stat':
            
                $organization_id =  isset($args['organization_id']) ? (integer) $args['organization_id'] : load::get_org_id();
                
                list($h, $i, $s, $n, $j, $Y) = array(date("H"),date("i"),date("s"),date("n"),date("j"),date("Y"));
     
                $start_date = isset($args['start_date']) ? $args['start_date'] : date('Y-m-d H:i:s', mktime(0, 0, 0, $n, $j, $Y));
                $end_date = isset($args['end_date']) ? $args['end_date'] : date('Y-m-d H:i:s', mktime(23, 59, 59, $n, $j, $Y));
                
                $enum = new enum();
                $enum->setSign('');
                
                $panel1 = new x_panel(); 
                
                $enum1 = new enum();
                $enum1->setSign('');
                
                $director = load::is_director();
                
                foreach (['&#128293;&#128293; Горячих', 'Проданных', 'Сняты с торгов', 'Всего'] as $key => $value)
                {
                    $calc_value = 0;
                    $filter = [];
                    
                    $filter[] = "`lots`.`timestamp`>=:start_date";
                    $filter[] = "`lots`.`timestamp`<=:end_date"; 
                    
                    if ($director)
                    {
                        $filter[] = "`organization_id`=:organization_id";
                    }
                    
                    switch ($key)
                    {
                        case 0:
                            $filter[] = "`pay` = 0 AND `no_active` = 0";
                        break;
                        case 1:
                            $filter[] = "`pay` != 0";
                        break;
                        case 2:
                            $filter[] = "`no_active` = 1";
                        break;
                        case 3: 
                            
                        break;
                    }
                    
                    $filter = " (".implode(") AND (", $filter).")";  
                    
                    if (!$director)
                    {
                        $sql = "SELECT COUNT(*) FROM `lots` WHERE {$filter} GROUP BY `call_id`";
                        $stm = pdo::getPdo()->prepare($sql);
                        $stm->execute(array('start_date' => $start_date, 'end_date' => $end_date));    
                        $calc_value = $stm->fetchAll(\PDO::FETCH_COLUMN);
                        
                        $calc_value = count($calc_value);
                    }
                    else
                    {
                        $sql = "SELECT COUNT(*) FROM `lots` WHERE {$filter}";
                        $stm = pdo::getPdo()->prepare($sql);
                        $stm->execute(array('organization_id' => $organization_id, 'start_date' => $start_date, 'end_date' => $end_date));    
                        $calc_value = $stm->fetchColumn();
                    }
                    
                    $p = new node('p');
                    $b = new node('strong'); 
                    $b->addChildren($value);
                    $p->addChildren($b.': '.$calc_value);
                
                    $enum1->addItems($p);
                }
                
                $panel1->setInto($enum1);                    
                $enum->addItems($panel1);
                
                foreach (array('start_date', 'end_date', 'mode') as $name)
                {
                    $value = $$name;
                            
                    $hidden = new form\hidden();
                    $hidden->setName($name);
                    $hidden->setValue($value);     
                    $enum->addItems($hidden);
                }
                
                $answer = $enum;
            
            break;
            
            case 'table':
            
                $page = isset($args['page']) ? (integer) $args['page'] : 0;
                $organization_id =  isset($args['organization_id']) ? (integer) $args['organization_id'] : load::get_org_id();
                
                list($h, $i, $s, $n, $j, $Y) = array(date("H"),date("i"),date("s"),date("n"),date("j"),date("Y"));
     
                $start_date = isset($args['start_date']) ? $args['start_date'] : date('Y-m-d H:i:s', mktime(0, 0, 0, $n, $j, $Y));
                $end_date = isset($args['end_date']) ? $args['end_date'] : date('Y-m-d H:i:s', mktime(23, 59, 59, $n, $j, $Y));
                
                $region_id = isset($args['region_id']) ? $args['region_id'] : (isset($_COOKIE['region_id']) ? $_COOKIE['region_id'] : 0);
                $status = isset($args['status']) ? $args['status'] : (isset($_COOKIE['status']) ? $_COOKIE['status'] : 0);
                
                $enum = new enum();
                $enum->setSign('');
                
                $director = load::is_director();
                
                if (!$page)
                {
                    $div_one = new node('div');
                    
                    $tables_filter = new node('div');
                    $tables_filter->getAttributes()->getClass()->addItems('tables_filter'); 
                    $tables_filter->getAttributes()->getClass()->addItems('op-datatable'); 
                    
                    //regions
                    if (!$director)
                    {
                        $div = new node('div');
                        $div->getAttributes()->getClass()->addItems('filter_group');
                        
                        $select = new form\select();
                        $select->setName('region_id');
                        
                        if (isset($_COOKIE['region_id'])) { 
                            if ($_COOKIE['region_id']) {
                                $select->setValue($_COOKIE['region_id']);   
                                $select->getAttributes()->getClass()->addItems('passed');
                            }
                        }
                        
                        $sql = "SELECT `id`, `name` FROM `regions` ORDER BY `name` ASC";
                        $regions = pdo::getPdo()->query($sql)->fetchAll(\PDO::FETCH_KEY_PAIR);
                            
                        $select->setValues(array('0' => 'Все') + $regions);
                        
                        $label_obj = new node('label');
                        $label_obj->addChildren('Регион');
        
                        $div->addChildren($label_obj);
                        $div->addChildren($select);
                        
                        $div_one->addChildren($div);
                    }
                    
                    //status
                    $div = new node('div');
                    $div->getAttributes()->getClass()->addItems('filter_group');
                    
                    $select = new form\select();
                    $select->setName('status');
                    
                    if (isset($_COOKIE['status'])) { 
                        if ($_COOKIE['status']) {
                            $select->setValue($_COOKIE['status']);   
                            $select->getAttributes()->getClass()->addItems('passed');
                        }
                    }
                        
                    $select->setValues(array('0' => 'Все', '1' => 'Горячий', '2' => 'Проданный', '3' => 'Снят с торгов'));
                    
                    $label_obj = new node('label');
                    $label_obj->addChildren('Статус');
    
                    $div->addChildren($label_obj);
                    $div->addChildren($select);
                    
                    $div_one->addChildren($div);
                    
                    $tables_filter->addChildren($div_one);
                    
                    $str = '';
                    $str .= '<div class="filter_group calendar-wrapper"><label>Дата</label>';
                    $str .= '<div class="range_picker"><i class="glyphicon glyphicon-calendar fa fa-calendar"></i> <span></span> <b class="caret"></b></div>';                    
                    $str .= '</div>';
                    
                    $tables_filter->addChildren($str);                    
                    
                    $enum->addItems($tables_filter); 
                }
                
                $limit = 10;
                $start = $page * $limit;
                
                $filter = [];
                $filter[] = "`lots`.`timestamp`>=:start_date";
                $filter[] = "`lots`.`timestamp`<=:end_date";
                
                if ($director)
                {
                    $filter[] = "`organization_id`=:organization_id";
                }
                
                if ($status)
                {
                    switch ($status)
                    {
                        case 1:
                            $filter[] = "`lots`.`pay` = 0 AND `lots`.`no_active` = 0";
                        break;
                        case 2:
                            $filter[] = "`lots`.`pay` != 0";
                        break;
                        case 3:
                            $filter[] = "`lots`.`no_active` = 1";
                        break; 
                    }
                }
                
                if ($region_id)
                {
                    $filter[] = "`navy_services`.`region_id` = {$region_id}";
                }
                
                $filter = " (".implode(") AND (", $filter).")";
                
                if (!$director)
                {
                    $sql = "SELECT `lots`.`id` FROM `lots`
                                        INNER JOIN `navy_services` ON `navy_services`.`id` = `lots`.`navy_service_id`
                                            WHERE {$filter}  GROUP BY `call_id`
                                    ORDER BY `pay` ASC, `lots`.`timestamp` DESC, `lots`.`id` DESC";
                    $stm = pdo::getPdo()->prepare($sql);
                    $stm->execute(array('start_date' => $start_date, 'end_date' => $end_date));    
                    $lots = $stm->fetchAll(\PDO::FETCH_ASSOC);
                    
                    $sql = "SELECT COUNT(*) FROM `lots` 
                                        INNER JOIN `navy_services` ON `navy_services`.`id` = `lots`.`navy_service_id`
                                            WHERE {$filter} GROUP BY `call_id`";
                    $stm = pdo::getPdo()->prepare($sql);
                    $stm->execute(array('start_date' => $start_date, 'end_date' => $end_date));    
                    $total_count = $stm->fetchAll(\PDO::FETCH_COLUMN);
                    
                    $total_count = count($total_count);
                    
                    $lots =  array_slice($lots, $start, $limit);
                }
                else
                {
                                                
                    $sql = "SELECT `lots`.`id` FROM `lots` 
                                        INNER JOIN `navy_services` ON `navy_services`.`id` = `lots`.`navy_service_id`
                                            WHERE {$filter}
                                    ORDER BY `pay` ASC, `lots`.`timestamp` DESC, `lots`.`id` DESC LIMIT {$start},{$limit}";
                    $stm = pdo::getPdo()->prepare($sql);
                    $stm->execute(array('organization_id' => $organization_id, 'start_date' => $start_date, 'end_date' => $end_date));    
                    $lots = $stm->fetchAll(\PDO::FETCH_ASSOC);
                    
                    $sql = "SELECT COUNT(*) FROM `lots` 
                                        INNER JOIN `navy_services` ON `navy_services`.`id` = `lots`.`navy_service_id`
                                            WHERE {$filter}";
                    $stm = pdo::getPdo()->prepare($sql);
                    $stm->execute(array('organization_id' => $organization_id, 'start_date' => $start_date, 'end_date' => $end_date));    
                    $total_count = $stm->fetchColumn();
                }
                
                if ($lots)
                {
                    foreach ($lots as $lot)
                    {
                        $enum->addItems(new lot(['mode' => 'lot', 'lot_id' => $lot['id']]));
                    }
                }
                else
                {
                    $panel1 = new x_panel(); 
                    
                    $enum1 = new enum();
                    $enum1->setSign('');
                    
                    $p = new node('p');
                    $p->addChildren('Лидов не найдено.');
                    $enum1->addItems($p);
                    
                    $panel1->setInto($enum1);                    
                    $enum->addItems($panel1);
                }
                
                if (($start + $limit) < $total_count)
                {
                    $show_more = new node('a');    
                    $show_more->getAttributes()->getClass()->setItems(['js-show_more', 'btn', 'btn-primary']);   
                    $show_more->getAttributes()->addAttr('href', '#');
                    $show_more->addChildren('Посмотреть еще');
                    $enum->addItems($show_more);
                }
                
                foreach (array('page', 'start_date', 'end_date', 'mode') as $name)
                {
                    $value = $$name;
                            
                    $hidden = new form\hidden();
                    $hidden->setName($name);
                    $hidden->setValue($value);     
                    $enum->addItems($hidden);
                }
                
                $answer = $enum;
                
            break;*/
            
            /*
            
            case 'send':
            
                $call_id = isset($args['call_id']) ? (integer) $args['call_id'] : 0;
                
                $error = '';
                
                if (!$error)
                {
                    $sql = "SELECT `arbiter` FROM `partner_orders` WHERE `call_id`=:call_id";
                    $stm = pdo::getPdo()->prepare($sql);         
                    $stm->execute(array('call_id' => $call_id));
                    $arbiter = (integer) $stm->fetchColumn();
                    
                    if ($arbiter == 1)
                    {
                         $error = 'Звонок был переведен партнеру!';  
                    }
                } 
                
                if (!$error)
                {
                    $sql = "SELECT `id` FROM `lots` WHERE `call_id`=:call_id AND `no_active` = 0 LIMIT 0,1";
                    $stm = pdo::getPdo()->prepare($sql);         
                    $stm->execute(array('call_id' => $call_id));
                    $lot_id = (integer) $stm->fetchColumn();
                    
                    if ($lot_id)
                    {
                        $error = 'Звонок уже в аукционе!';
                    }
                }
                
                if (!$error)
                {
                    list($phone_name, $model_type_id, $brand_id, $region_id, $comment, $o_id, $phone_name2, $poser_id) = bill::get_info_about_call($call_id);
                    
                    $transalit_key = ['region_id' => 'Регион', 'brand_id' => 'Бренд', 'model_type_id' => 'Тип', 'comment' => 'Комментарий', 
                                                'phone_name' => 'Абонент', 'phone_name2' => 'Ф.И.О.'];
                                                
                    foreach ($transalit_key as $key => $n_title)
                    {
                        $name = $$key;
                        
                        if (!$name)
                        {
                            $error = 'Поле "'.$n_title.'" обязательно для заполнения!';
                        }
                    }
                }
                
                if (!$error)
                {    
                    $navy_array = ['mode' => 'datatable', 
                                            'dop_filter' => ['region_id' => $region_id, 'brand_id' => $brand_id, 'model_type_id' => $model_type_id, 'auction' => 1],
                                                      's_mode' => 1];
                    $navy_obj = new navy($navy_array);
                    
                    $array = $navy_obj->getWrapper()->getChildren(0);
                                
                    $array = $array['data'];
                    
                    if ($array)
                    {
                        if (session_id())
                        {
                            $notifys = new term(
                                array(
                                    'mode' => 'add', 
                                    'table' => 'notifys',
                                    'text' => 'Заявка отправлена!', 
                                    'type_notify_id' => load::get_status('type_notifys', 'add_success'), 
                                    'session' => session_id(),
                                    )
                                );
                            
                            exec("php ".\DOCUMENT_ROOT."admin/index.php op=lot args[mode]=send_background args[call_id]=$call_id > /dev/null &", $output, $return_var);
                        }
                    }
                    else
                    {
                        $error = 'Нет партнеров для отправки лида!';
                    }
                }  
                
                if ($error)
                {
                    if (session_id())
                    {
                        $notifys = new term(
                                    array(
                                        'mode' => 'add', 
                                        'table' => 'notifys',
                                        'text' => htmlspecialchars($error), 
                                        'type_notify_id' => load::get_status('type_notifys', 'update_error'),
                                        'session' => session_id(),
                                        )
                                    );
                                    
                         $this->setCode('error');
                    }
                }
                
            break;*/
            
            /*case 'add_bill':
                
                $lot_id = isset($args['lot_id']) ? $args['lot_id'] : 0;
                $send_telegram = isset($args['send_telegram']) ? (integer) $args['send_telegram'] : 0;
                                
                $lot_array = load::get_order($lot_id, ['summ', 'pay', 'call_id', 'organization_id', 'navy_service_id', 'no_active'], 'lots');
                $telega = new telegram($this->_turbo_lid_token);
                
                if ($lot_id)
                {
                    if ($lot_array['pay'])
                    {
                         $message = 'Лид #'.$lot_array['call_id'].' уже куплен! Попробуйте купить другой лид!';
                         
                         $notifys = new term(
                                array(
                                    'mode' => 'add', 
                                    'table' => 'notifys',
                                    'text' => $message, 
                                    'type_notify_id' => load::get_status('type_notifys', 'update_error'),
                                    'session' => session_id(),
                                    )
                                );
                                
                        $this->setCode('error');
                        
                        if ($send_telegram)
                        {
                            $telega->sendMessage($send_telegram, $message); 
                        }
                    }
                    else
                    {
                        if ($lot_array['no_active'])
                        {
                            $message = 'Лид #'.$lot_array['call_id'].' снят с торгов! Попробуйте купить другой лид!';
                         
                            $notifys = new term(
                                    array(
                                        'mode' => 'add', 
                                        'table' => 'notifys',
                                        'text' => $message, 
                                        'type_notify_id' => load::get_status('type_notifys', 'update_error'),
                                        'session' => session_id(),
                                        )
                                    );
                                    
                            $this->setCode('error');
                            
                            if ($send_telegram)
                            {
                                $telega->sendMessage($send_telegram, $message); 
                            }
                        }
                        else
                        {
                            $organization_id = $lot_array['organization_id'];
                            
                            $sql = "SELECT `summ` FROM `count_transactions` WHERE `organization_id`=:organization_id";
                            $stm = pdo::getPdo()->prepare($sql);
                            $stm->execute(array('organization_id' => $organization_id));
                            $balance = (integer) $stm->fetchColumn();
                            
                            $pass = ($balance >= $lot_array['summ']);
                            
                            $lid_type = load::get_order($organization_id, 'lid_type', 'organizations');
                            $pass = ($lid_type == 3);
                            
                            $pass = false;
                            
                            if (!$pass)
                            {
                                $notifys = new term(
                                    array(
                                        'mode' => 'add', 
                                        'table' => 'notifys',
                                        'text' => 'Пополните баланс для покупки лида!', 
                                        'type_notify_id' => load::get_status('type_notifys', 'update_error'),
                                        'session' => session_id(),
                                        )
                                    );
                                    
                                $this->setCode('error');
                                
                                if ($send_telegram)
                                {
                                    $telega->sendMessage($send_telegram, "Пополните баланс для покупки лида! <a href='https://cibacrm.com/'>Ссылка</a> для пополнения");
                                }
                            }
                            else
                            {
                                $call_id = $lot_array['call_id'];
                                
                                list($phone_name, $model_type_id, $brand_id, $region_id, $comment, $o_id) = bill::get_info_about_call($call_id);
                                
                                /*$resale_array = ['mode' => 'apppaa', 'phone' => $phone_name, 'navy_service_id' => $lot_array['navy_service_id'], 
                                                                    'call_id' => $call_id, 'comment' => $comment,
                                                                        'brand_id' => $brand_id, 'model_type_id' => $model_type_id, 'letter' => 1,
                                                                            'lot_tarif' => $lot_array['summ']];
                                
                                $resale_obj = new resale($resale_array);
                                
                                if ($resale_obj->getCode() == 'success')
                                {*/
                                    /*$sql = "SELECT `organization_id` FROM `lots` WHERE `call_id`=:call_id AND `organization_id`!=:organization_id";
                                    $stm = pdo::getPdo()->prepare($sql);
                                    $stm->execute(array('call_id' => $call_id, 'organization_id' => $lot_array['organization_id']));
                                    $organization_ids = $stm->fetchAll(\PDO::FETCH_COLUMN);
                                    
                                    $message = 'Лид #'.$call_id.' был продан за '.tools::format_price2($lot_array['summ']);
                                    
                                    foreach ($organization_ids as $organization_loop)
                                    {
                                        $request = array(
                                            'op' => 'telegram',
                                            'args' => array(
                                                'telegram_type' => 'lot',
                                                'organization_id' => $organization_loop,
                                                'message' => $message,
                                                'token' => $this->_turbo_lid_token,
                                        ));
                                            
                                        $ret = tools::request_api($request);     
                                    }                                
                                    
                                    $sql = "UPDATE `lots` SET `pay`=:organization_id WHERE `call_id`=:call_id";
                                    $stm = pdo::getPdo()->prepare($sql);
                                    $stm->execute(array('call_id' => $call_id, 'organization_id' => $organization_id));
                                    
                                    if ($send_telegram)
                                    {
                                         $message = 'Поздравляем! Вы купили лид #'.$lot_array['call_id'].' за '.tools::format_price2($lot_array['summ']);
                                         $telega->sendMessage($send_telegram, $message);  
                                         
                                         $message_obj =  new lot(['mode' => 'lot', 'mark_down' => true, 'lot_id' => $lot_id]);    
                                         $message = $message_obj->getWrapper()->getChildren(0);
                                         
                                         $telega->sendMessage($send_telegram, $message);          
                                    }
                                //}
                            }
                        }
                    }
                }
                
            break;*/
            
            /*case 'send_background':
            
                $call_id = isset($args['call_id']) ? (integer) $args['call_id'] : 0;  
               
                list($phone_name, $model_type_id, $brand_id, $region_id, $comment, $o_id) = bill::get_info_about_call($call_id);
                    
                $navy_array = ['mode' => 'datatable', 
                                        'dop_filter' => ['region_id' => $region_id, 'brand_id' => $brand_id, 'model_type_id' => $model_type_id, 'auction' => 1],
                                                  's_mode' => 1];
                $navy_obj = new navy($navy_array);
                
                $array = $navy_obj->getWrapper()->getChildren(0);
                            
                $array = $array['data'];
                     
                if ($array)
                {
                    $timestamp = date('Y-m-d H:i:s', tools::get_time());
                    $t_array = [];
                    
                    foreach ($array as $key => $value)
                    {
                        $organization_id = $value['DT_RowData']['organization_id'];
                        $t_array[$organization_id] = $value;
                    }
                    
                    $max_price = 0;
                    foreach ($t_array as $value)
                    {
                        $control_tarif = $value['DT_RowData']['control_tarif'];
                        if ($control_tarif > $max_price) $max_price = $control_tarif;
                    }                    
                   
                    $max_price = $max_price * 2;
                    
                    /*$t_array = [];
                    $t_array[] = ['DT_RowData' => ['organization_id' => 5, 'navy_service_id' => 36388]];
                    $t_array[] = ['DT_RowData' => ['organization_id' => 58, 'navy_service_id' => 36436]];*/
                    
                    /*foreach ($t_array as $value)
                    {
                        $organization_id = $value['DT_RowData']['organization_id'];
                        $navy_service_id = $value['DT_RowData']['navy_service_id'];
                        
                        $term = new term(['mode' => 'add', 'table' => 'lots', 'call_id' => $call_id, 
                                                    'organization_id' => $organization_id, 'navy_service_id' => $navy_service_id, 
                                                            'timestamp' => $timestamp, 'summ' => $max_price, 'fix_summ' => $max_price]);
                                                            
                        $lot_id = (integer) $term->getWrapper()->getChildren(0);
                        
                        $json = mb_convert_encoding(json_encode(['lot_id' => $lot_id]), 'UTF-8');
                        
                        $keyboard = [
                           "inline_keyboard" => [
                                 [["text" => "Купить", "callback_data" => $json]],
                            ],
                        ];
                        
                        $message_obj =  new lot(['mode' => 'lot', 'mark_down' => true, 'lot_id' => $lot_id]);    
                        $message = $message_obj->getWrapper()->getChildren(0);
                                                            
                        $request = array(
                                    'op' => 'telegram',
                                    'args' => array(
                                        'telegram_type' => 'lot',
                                        'organization_id' => $organization_id,
                                        'message' => $message,
                                        'keyboard' => $keyboard,
                                        'token' => $this->_turbo_lid_token,
                                ));
                                    
                        $ret = tools::request_api($request); 
                    }
                }
            
            break;*/
}