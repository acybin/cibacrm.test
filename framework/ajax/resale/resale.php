<?

namespace framework\ajax\resale;

use framework\ajax as ajax;
use framework\pdo;
use framework\tools;
use framework\load;
use framework\ajax\term\term;
use framework\ajax\navy\navy;
use framework\shape as shape;
use framework\shape\modal;
use framework\enum;
use framework\dom\node;
use framework\bank;
use framework\shape\form as form;
use framework\ajax\call\call;
use framework\ajax\bill\bill;
use framework\ajax\cron\cron;
use framework\navy_db;

define('BLOCK_TIME', 10 * 60);

class resale extends ajax\ajax
{
    public function __construct($args = array())
    {
        error_reporting(E_ALL);
        ini_set('display_errors', 1);
        
        parent::__construct('resale');
        $mode = isset($args['mode']) ? $args['mode'] : '';
        
        $this->getWrapper()->getAttributes()->addAttr('id', 'resale-'.$mode);
        
        $answer = '';
        
        switch ($mode)
        {
            case 'call':
                
                $time = date('Y-m-d H:i:s', tools::get_time());  
                
                if (isset($args['notification_time']))
                {
                    $time = $args['notification_time'];
                    if (($pos = mb_strpos($time, '.')) !== false)
                        $time = mb_substr($time, 0, $pos);
                }
                
                if (isset($args['start_time']))
                {
                    $time = $args['start_time'];
                    if (($pos = mb_strpos($time, '.')) !== false)
                        $time = mb_substr($time, 0, $pos);
                } 
                
                $phone = tools::cut_phone($args['contact_phone_number']);
                $mango = tools::cut_phone($args['virtual_phone_number']);  
                $session = (string) $args['call_session_id'];
                
                $type = isset($args['type']) ? (integer) $args['type'] : 0;
                $text = isset($args['text']) ? (string) $args['text'] : '';
                
                $letter = 1;
                if (isset($args['letter']))
                {
                    if ($args['letter'] == 0 || $args['letter'] == 1)
                        $letter = $args['letter'];
                    else
                        $letter = 1;      
                }
                 
                $sql = "SELECT `id` FROM `mangos` WHERE `name`=:name";            
                $stm = pdo::getPdo()->prepare($sql); 
                $stm->execute(array('name' => $mango));                
                $mango_id = $stm->fetchColumn();
               
                if ($mango_id)
                {
                    $sql = "SELECT `address`.`id` as `addres_id`, 
                                            `address`.`organization_id` as `organization_id`,
                                                `navy_services`.`region_id`,
                                                   `partner_orders`.`model_type_id`,
                                                         `partner_orders`.`brand_id`,
                                                            `partner_orders`.`nyk_name2`,
                                                               `resale_dt_sessions`.`id` as `dt_session_id`,
                                                                 `partner_orders`.`model_name` as `model_name`,
                                                                  `resale_dt_phones`.`id` as `resale_dt_phone_id`,
                                                                    `partner_orders`.`id` as `partner_order_id`,
                                                                      `partner_orders`.`offer_id` as `offer_id`,
                                                                       `partner_orders`.`place_id` as `place_id`,
                                                                       `partner_orders`.`poser_id` as `poser_id`,
                                                                       `resale_dt_sessions`.`call_id` as `a_call`,
                                                                       `resale_dt_sessions`.`real_call_id` as `a_real_call`,
                                                                        `resale_dt_sessions`.`user_id` as `user_arbiter`,
                                                                        `calls`.`mango_id` as `a_mango`,
                                                                         `calls`.`nls_source_id` as `a_nls_source_id`, 
                                                                         `navy_services`.`lid_type` as `lid_type`,
                                                                          `navy_services`.`id` as `navy_service_id`,
                                                                           `partner_orders`.`phone_name3` as `phone_name3`,
                                                                           `partner_orders`.`arbiter` as `arbiter`,
                                                                            `partner_orders`.`amount` as `amount`,
                                                                            `regions`.`name` as `region_name`,
                                                                             `organizations`.`deal` as `deal`    
                                                            FROM 
                                    `resale_dt_phones`
                                            LEFT JOIN `resale_dt_sessions` ON `resale_dt_phones`.`resale_dt_session_id` = `resale_dt_sessions`.`id`
                                                  LEFT JOIN `navy_services` ON `resale_dt_sessions`.`navy_service_id` = `navy_services`.`id`
                                                     LEFT JOIN `address` ON `navy_services`.`addres_id` = `address`.`id` 
                                                        LEFT JOIN `partner_orders` ON `partner_orders`.`call_id` = `resale_dt_sessions`.`call_id`
                                                          LEFT JOIN `calls` ON `calls`.`id` = `resale_dt_sessions`.`call_id`
                                                            LEFT JOIN `regions` ON `regions`.`id` = `navy_services`.`region_id`
                                                              LEFT JOIN `organizations` ON `address`.`organization_id` = `organizations`.`id`
                                               WHERE `resale_dt_phones`.`mango_id`=:mango_id";
                    $stm = pdo::getPdo()->prepare($sql); 
                    $stm->execute(array('mango_id' => $mango_id));  
                    $resale_dt = $stm->fetch(\PDO::FETCH_ASSOC);
                    
                    if ($resale_dt)
                    {
                        $organization_id = $resale_dt['organization_id'];
                        if (!$organization_id) $organization_id = 822;
                        
                        $addres_id = (integer) $resale_dt['addres_id'];
                        
                        $model_type_id = $resale_dt['model_type_id'];
                        $brand_id = $resale_dt['brand_id'];
                        $offer_id = $resale_dt['offer_id'];
                        $poser_id = $resale_dt['poser_id'];
                        $place_id = $resale_dt['place_id'];
                        $region_id = $resale_dt['region_id'];
                        $phone_name3 = $resale_dt['phone_name3'];
                        $dt_session_id = $resale_dt['dt_session_id'];
                        $dt_session_table = 'resale';     
                                           
                        $partner_order_id = $resale_dt['partner_order_id'];
                        $region_name = $resale_dt['region_name'];
                        
                        $model_type_name = mb_strtolower(load::get_order($model_type_id, 'name', 'model_types'));  
                        $brand_name = mb_strtolower(load::get_order($brand_id, 'name', 'brands'));  
                        $model_name = $resale_dt['model_name'];
                        $resale_dt_phone_id = $resale_dt['resale_dt_phone_id'];
                        
                        $user_arbiter = $resale_dt['user_arbiter'];
                        $arbiter = $resale_dt['arbiter'];
                        
                        $amount = $resale_dt['amount'];
                        
                        $a_call = $resale_dt['a_call'];
                        $a_real_call = $resale_dt['a_real_call'];
                                                
                        $a_mango = $resale_dt['a_mango'];
                        $a_nls_source_id = $resale_dt['a_nls_source_id'];
                        
                        $lid_type = (integer) $resale_dt['lid_type'];
                        $navy_service_id = $resale_dt['navy_service_id']; 
                        
                        $deal = $resale_dt['deal'];
                        
                        $status = load::get_status('status_calls', 'not_accept');
                        
                        $fix = true;
                        $double = false;
                        
                        $phone_id = 0;
                        
                        if ($phone) //anonim
                        {
                            $sql = "SELECT `id` FROM `phones` WHERE `name`=:name AND `organization_id`=:organization_id";            
                            $stm = pdo::getPdo()->prepare($sql); 
                            $stm->execute(array('name' => $phone, 'organization_id' => $organization_id));
                            $phone_id = $stm->fetchColumn();
                        }
                        
                        if (!$phone_id)
                        {
                             $obj = new term(array('mode' => 'add', 'table' => 'phones', 'name' => $phone, 'name2' => $phone_name3, 'organization_id' => $organization_id));                             
                             $phone_id = (integer) $obj->getWrapper()->getChildren(0);
                             $fix = false;
                        } 
                        
                        //fix*
                        if ($fix)
                        {
                            $sql = "SELECT `id` FROM `calls` WHERE `mango_id`=:mango_id AND `phone_id`=:phone_id AND `timestamp`=:time AND `type`=:type";            
                            $stm = pdo::getPdo()->prepare($sql); 
                            $stm->execute(array('mango_id' => $mango_id, 'phone_id' => $phone_id, 'time' => $time, 'type' => $type));  
                            $double = $stm->fetchColumn();
                        }
                        
                        if (!$double)
                        {
                            if (in_array($organization_id, [3118, 3177])) 
                            {
                                $a_call_info = bill::get_info_about_call($a_call);
                                $region_id = $a_call_info[3];
                            }
                            
                            $obj_array = array('mode' => 'add', 'table' => 'calls', 'mango_id' => $mango_id, 'phone_id' => $phone_id, 'organization_id' => $organization_id, 
                                'brand_id' => $brand_id, 'setka_id' => null, 'region_id' => $region_id, 
                                'addres_id' => $addres_id,
                            'session' => $session, 'type' => $type, 'status_call_id' => $status, 'timestamp' => $time,
                                    'dt_session_id' => $dt_session_id, 'correct_brand_id' => $brand_id,
                                            'model_type_id' => $model_type_id, 'text' => $text, 'dt_session_table' => $dt_session_table);
                                            
                            $obj2 = new term($obj_array);
                                                                    
                            $call_id = (integer) $obj2->getWrapper()->getChildren(0);
                            
                            $tags = [];
                                                        
                            $sql = "SELECT `name_type`, `id_type` FROM `partner_order_tags` WHERE `partner_order_id`=:id";
                            $stm = pdo::getPdo()->prepare($sql);
                            $stm->execute(array('id' => $partner_order_id));
                            $t_tags = (array) $stm->fetchAll(\PDO::FETCH_ASSOC);
                                
                            foreach ($t_tags as $tag)
                            {
                                $tags[] = $tag['name_type'] . '-' . $tag['id_type'];
                                if ($tag['name_type'] != 'model_type' && $tag['name_type'] != 'brand') 
                                {
                                    $name = $tag['name_type'] . '_id';
                                    $$name = $tag['id_type'];
                                }
                            }
                            
                            $array = array('mode' => 'add', 'table' => 'partner_orders', 'call_id' => $call_id, 
                                            'brand_id' => $brand_id, 'model_type_id' => $model_type_id, 'model_name' => $model_name,
                                                    'nyk_name2' => $text, 'region_id' => $region_id, 'phone_name3' => $phone_name3, 'offer_id' => $offer_id,
                                                            'poser_id' => $poser_id, 'tag' => $tags, 'place_id' => $place_id);
                                                    
                            $obj_partner = new term($array);
                            $goal = $this->_is_goal($call_id);
                            
                            $resaler_summ = 0;
                            
                            if (!$goal)
                            {
                                $control_tarif = 0;
                                $control_summ = ['', 0];
                                $t_max_tag = null;
                                          
                                if ($organization_id != 822)
                                {
                                    $base = 0;
                                    
                                    $sql = "SELECT `tarif_a`, `tarif_b`, `name_type`, `id_type` FROM `tarif_orgs` WHERE `organization_id`=:organization_id";
                                    $stm = pdo::getPdo()->prepare($sql); 
                                    $stm->execute(array('organization_id' => $organization_id));
                                    $show_tarif = $stm->fetchAll(\PDO::FETCH_ASSOC);
                                    
                                    foreach ($show_tarif as $tarif)
                                    {
                                        $name = $tarif['name_type'].'_tarifs';
                                        $$name[$tarif['id_type']] = !($letter) ? $tarif['tarif_a'] : $tarif['tarif_b'];
                                    }
                                    
                                    if (isset($base_tarifs)) $base = $base_tarifs[0];
                                    
                                    //detalization                                          
                                    foreach (['brand', 'model_type'] as $marker)
                                    {
                                        $mas_name = $marker.'_tarifs';
                                        $marker_id =  $marker.'_id';
                                        
                                        $mas_id = $$marker_id;
                                        
                                        if (isset($$mas_name) && $$mas_name && $mas_id)
                                        {
                                            $mas = $$mas_name;
                                            
                                            if (isset($mas[$mas_id]))
                                            {
                                                if ($mas[$mas_id] > $control_tarif) 
                                                {
                                                    $control_tarif = $mas[$mas_id];
                                                    $control_summ = [$marker, $mas_id];
                                                }   
                                            }
                                            else
                                            {
                                                if (isset($mas[0]))
                                                {
                                                    if ($mas[0] > $control_tarif)
                                                    {
                                                        $control_tarif = $mas[0];
                                                        $control_summ = [$marker, 0];
                                                    }
                                                }
                                            }
                                        }
                                    }
                                    
                                    if (!$control_tarif)
                                    {
                                        $control_tarif = $base;
                                        $control_summ = ['base', 0];
                                    }
                                    
                                    $tarif_bs = 0;
                                    $base_tarif_datas = [];                              
                                    $tag_empty = [];

                                    $service_id = $navy_service_id;
                                                            
                                    if ($service_id)
                                    {
                                        $tag_id = $t_tags;
                                        
                                        if ($tag_id)
                                        {
                                            $tag_names = [];
                                            
                                            foreach ($tag_id as $tag)
                                            {
                                                $tag_names[] = "(`b_name` = '" . $tag['name_type'] . "s' AND `b_id` = " . $tag['id_type'] . ")";
                                            }
                                            
                                            if ($tag_names)
                                            {
                                                $sql = "SELECT `a_name`, `a_id`, `b_name`, `b_id` FROM `ag_links` WHERE ".implode(' OR ', $tag_names);
                                                $array_tags = pdo::getPdo()->query($sql)->fetchAll(\PDO::FETCH_ASSOC);
                                                
                                                if ($array_tags)
                                                {
                                                    $t_array_tags = [];
                                                    $t_comibine_tags = [];
                                                    
                                                    foreach ($array_tags as $tag)
                                                    {
                                                        $tag_name = mb_substr($tag['a_name'], 0, -1);
                                                        $t_array_tags[] = "(`name_type` = '" . $tag_name. "' AND `id_type` = ".$tag['a_id'] . ")";
                                                        $t_comibine_tags[$tag_name][] = $tag['a_id'];
                                                    }                           
                                                    
                                                    $count_combine_tags = count($t_comibine_tags);
                                                    
                                                    $sql = "SELECT `id` FROM `ag_offers` WHERE `offer_id`=:offer_id";
                                                    $stm = pdo::getPdo()->prepare($sql); 
                                                    $stm->execute(array('offer_id' => $offer_id));
                                                    $ag_offer_id = $stm->fetchColumn();                                                                                                        
                                                               
                                                    $sql = "SELECT `tag_group` FROM `tags` WHERE (".implode(' OR ', $t_array_tags).")";
                                                    if (!empty($ag_offer_id)) $sql .= " AND `offer_id` = {$ag_offer_id}";
                                                                                                                                                            
                                                    $t_tag_groups = navy_db::getPdo()->query($sql)->fetchAll(\PDO::FETCH_COLUMN);
                                                    
                                                    if ($t_tag_groups)
                                                    {
                                                        $sql = "SELECT `name_type`, `id_type`, `tag_group` FROM `tags` WHERE `tag_group` IN (".implode(',', $t_tag_groups).")";
                                                        $all_tag_groups = navy_db::getPdo()->query($sql)->fetchAll(\PDO::FETCH_ASSOC);
                                                        
                                                        $t_all_groups = [];
                                                        $set_group = [];
                                                        
                                                        foreach ($all_tag_groups as $all_tag_group)
                                                            $t_all_groups[$all_tag_group['tag_group']][] = $all_tag_group;
                                                            
                                                        $t_copy_all_gropus = $t_all_groups;
                                                        
                                                        foreach ($t_all_groups as $t_all_group_key => $t_all_group_value)
                                                        {
                                                            $count_group_tags = count($t_all_group_value);
                                                            $set_group[$t_all_group_key] = $count_group_tags;
                                                            
                                                            if ($count_combine_tags >= $count_group_tags)
                                                            {
                                                                foreach ($t_all_group_value as $t_all_tag_group_key => $t_all_tag_group_value)
                                                                {
                                                                    if (isset($t_comibine_tags[$t_all_tag_group_value['name_type']]))
                                                                    {
                                                                        if (in_array($t_all_tag_group_value['id_type'], $t_comibine_tags[$t_all_tag_group_value['name_type']]))
                                                                        {
                                                                            unset($t_all_groups[$t_all_group_key][$t_all_tag_group_key]);
                                                                        }
                                                                    }       
                                                                }
                                                            }
                                                        }
                                                        
                                                        foreach ($t_all_groups as $t_all_group_key => $t_all_group_value)
                                                        {
                                                            if (!$t_all_group_value) 
                                                            {
                                                                $tag_empty[$t_all_group_key] = $set_group[$t_all_group_key];
                                                            }
                                                        }
                                                    }
                                                }
                                            }
                                            
                                            if ($tag_empty)
                                            {
                                               $sql = "SELECT `tarif_a`, `tarif_b`, `tag_group_id` FROM `service_to_tag_groups`
                                                                    WHERE `tag_group_id` IN (".implode(',', array_keys($tag_empty)).")
                                                                        AND `service_to_tag_groups`.`no_active` = 0 AND `service_id`=:service_id";                                                        
                                               $stm = navy_db::getPdo()->prepare($sql);
                                               $stm->execute(array('service_id' => $service_id));
                                               $tag_datas = $stm->fetchAll(\PDO::FETCH_ASSOC);
                                               
                                               foreach ($tag_datas as $tag_data)
                                               {
                                                    if (!$t_max_tag) 
                                                    {
                                                        $t_max_tag = $tag_data['tag_group_id'];
                                                        $tarif_bs = !($letter) ? $tag_data['tarif_a'] : $tag_data['tarif_b'];
                                                    }
                                                    else
                                                    {
                                                        if ($tag_empty[$tag_data['tag_group_id']] > $tag_empty[$t_max_tag])
                                                        {
                                                            $t_max_tag = $tag_data['tag_group_id'];
                                                            $tarif_bs = !($letter) ? $tag_data['tarif_a'] : $tag_data['tarif_b'];
                                                        }
                                                    }
                                                }
                                            }
                                        }
                                        
                                        $sql = "SELECT `tarif_a`, `tarif_b` FROM `base_tarifs` WHERE `service_id`=:service_id";
                                        $stm = navy_db::getPdo()->prepare($sql);
                                        $stm->execute(array('service_id' => $service_id));
                                        $base_tarif_datas = $stm->fetch(\PDO::FETCH_ASSOC);
                                        
                                        $base_tarif_datas = !$letter ? (isset($base_tarif_datas['tarif_a']) ? $base_tarif_datas['tarif_a'] : 0) : (isset($base_tarif_datas['tarif_b']) ? $base_tarif_datas['tarif_b'] : 0);
                                        
                                        $base = $base_tarif_datas;

                                        $control_tarif = $tarif_bs ? $tarif_bs : $base;
                                        
                                        $control_tarif = tools::calc_tarif_procent($service_id, $control_tarif, $amount);
                                    }
                                    
                                    $resaler_summ = $control_tarif;   
                                    
                                    /*if ($flow_k)
                                    {
                                        $control_tarif = round($flow_k * $control_tarif);
                                    }*/
                                    
                                    //file_put_contents(\DOCUMENT_ROOT.'detalization', print_r($brand_tarifs, true).print_r($model_type_tarifs, true).$call_id.PHP_EOL.PHP_EOL, FILE_APPEND | LOCK_EX); 
                                    
                                    /*if ($lid_type == 4 || $lid_type == 2)
                                    {
                                        $sql = "SELECT `lost` FROM `sr_balances` WHERE `organization_id`=:organization_id";
                                        $stm = pdo::getPdo()->prepare($sql);         
                                        $stm->execute(array('organization_id' => $organization_id));
                                        $lost = (integer) $stm->fetchColumn();
                                        
                                        $price_val = 100;
                                        
                                        if ($lost >= $control_tarif)
                                            $price_val = $control_tarif;
                                        
                                        $resaler_summ = floor($price_val / 10);   
                                    }*/
                                    
                                    if (($lid_type == 4 || $lid_type == 2 || $lid_type == 3) && !$deal)
                                    {
                                        $sql = "SELECT `lost` FROM `count_transactions` WHERE `organization_id`=:organization_id";
                                        $stm = pdo::getPdo()->prepare($sql);         
                                        $stm->execute(array('organization_id' => $organization_id));
                                        $lost = $stm->fetchColumn();
                                        
                                        if ($lost !== null)
                                        {
                                            $price_val = 100;
                                            
                                            if ($lost >= $control_tarif)
                                                $price_val = $control_tarif;
                                            
                                            $resaler_summ = $price_val;
                                            
                                            //$resaler_summ = floor($price_val / 10); 
                                        }
                                    }
                                    
                                    if ($lid_type == 5 || $lid_type == 6)
                                    {
                                        $resaler_summ = 0;
                                    }
                                    
                                    $koeff_resaler = 1;
                                   
                                    if ($region_name == 'Москва')
                                        $koeff_resaler = 1;
                                        
                                    if ($region_name == 'Санкт-Петербург')
                                        $koeff_resaler = 1;
                                    
                                    $resaler_summ = round($resaler_summ * $koeff_resaler / 100, 2);
                                 }
                                    
                                 if ($lid_type == 1)
                                 {
                                    $resaler_summ = 0.5;   
                                 }
                                 
                                 if ($lid_type == 0)
                                 {
                                    $resaler_summ = 0;
                                 }
                                 
                                 if ($deal)
                                 {
                                    $sql = "SELECT `summ` FROM `deal_tarifs` WHERE `navy_service_id`=:navy_service_id";
                                    $stm = pdo::getPdo()->prepare($sql);
                                    $stm->execute(array('navy_service_id' => $navy_service_id));
                                    $deal_tarif = $stm->fetchColumn();
                                    
                                    if ($deal_tarif)
                                        $resaler_summ = $deal_tarif;
                                    else   
                                        if (!$resaler_summ) $resaler_summ = 0.5;
                                        
                                    $control_tarif = 0;
                                 }
                                
                                 /*$sql = "SELECT `app_type` FROM `partner_mangos` WHERE `mango_id`=:mango_id";
                                 $stm = pdo::getPdo()->prepare($sql);
                                 $stm->execute(array('mango_id' => $a_mango));
                                 $app_type = $stm->fetchColumn();*/
                                 
                                 /*if (($organization_id == 140 || $organization_id == 1552) && $app_type == 'fix')
                                 {
                                     $tarif_summ = 400;
                                 }*/ 
                                 
                                 $operation_id = ($type == 0) ? load::get_status('operations', 'goal') : load::get_status('operations', 'goal_app');
                                 
                                 if ($lid_type == 3 || $lid_type == 6)
                                    $field = 'summ'; 
                                 else
                                    $field = 'reserve_summ';
                                    
                                 $dollar = null;
                    
                                if ($field == 'summ')
                                {
                                    $sql = "SELECT `base_tarifs`.`base` 
                                                        FROM `navy_services` 
                                                                    LEFT JOIN `base_tarifs` ON `navy_services`.`region_id` = `base_tarifs`.`region_id` 
                                                            WHERE `navy_services`.`addres_id`=:addres_id AND 
                                                                (`navy_services`.`no_active` IS NULL OR `navy_services`.`no_active` = 0)";
                                    $stm = pdo::getPdo()->prepare($sql); 
                                    $stm->execute(array('addres_id' => $addres_id));
                                    $base_tarif_org = (integer) $stm->fetchColumn();
                                    
                                    if ($base_tarif_org)
                                    {
                                        $kfc = 1.66;
                                        
                                        if ($control_tarif < $base_tarif_org)
                                        {
                                            $dollar = 1;
                                        }
                                        else
                                        {
                                            if ($control_tarif < ($base_tarif_org * $kfc))
                                                $dollar = 2;
                                            else
                                                $dollar = 3;
                                        }
                                    }
                                 }
                                 
                                 if (in_array($organization_id, [0, 822]))
                                 {
                                    $cron = new cron(['mode' => '']);
                                    list($dollar, $dollar_summ) = $cron->calc_dollar($call_id);
                                 }
                                
                                 $tarif_summ = (-1) * $control_tarif;    
                                 
                                 $mango_array = load::get_order($mango_id, ['channel_id', 'nls_source_id'], 'mangos');
                                 $nls_source_id = $mango_array['nls_source_id'];
                                 $channel_id = $mango_array['channel_id'];
                                 
                                 $transaction_array =
                                            array(
                                                'mode' => 'add',
                                                'table' => 'transactions',
                                                $field => $tarif_summ,
                                                'organization_id' => $organization_id,
                                                'timestamp' => $time,
                                                'operation_id' => $operation_id,
                                                'call_id' => $call_id,
                                                'dollar' => $dollar,
                                                'nls_source_id' => $nls_source_id,
                                                'channel_id' => $channel_id,
                                                'tag_group_id' => $t_max_tag,
                                            );
                                 
                                 $transaction_array['id_type'] = $control_summ[1];
                                 $transaction_array['name_type'] = $control_summ[0];
                                 $transaction_array['letter'] = $letter;
                                
                                 $transaction_obj = new term($transaction_array);
                                 
                                 $transaction_id = $transaction_obj->getWrapper()->getChildren(0);
                    
                                 if (in_array($organization_id, [0, 822]) && $dollar_summ)
                                 {
                                    $dollar_summ_obj = new term(array('mode' => 'add', 'table' => 'dollar_summs', 'transaction_id' => $transaction_id, 'dollar_summ' => $dollar_summ));
                                 }
                                 
                                 $sql = "SELECT `connectors`.`a` FROM `connectors`
                                           INNER JOIN `cashbacks` ON connectors.b = cashbacks.call_id
                                			        WHERE `cashbacks`.`resale_call_id`=:a";
                                 $stm = pdo::getPdo()->prepare($sql);
                                 $stm->execute(array('a' => $a_call));
                                 $a_web = $stm->fetchColumn();
                                 
                                 if ($a_web)
                                    $a_web_call = $a_web;
                                 else
                                    $a_web_call = $a_call;    
                                    
                                 /*$sql = "SELECT `user_id` FROM `nls_sources` WHERE `id`=:nls_source_id";
                                 $stm = pdo::getPdo()->prepare($sql);                 
                                 $stm->execute(array('nls_source_id' => $a_nls_source_id));
                                 $webmaster_id = (integer) $stm->fetchColumn();*/
                                 
                                 $sql = "SELECT `nls_sources`.`user_id` FROM `nls_sources` 
                                            INNER JOIN `calls` ON `calls`.`nls_source_id` = `nls_sources`.`id`
                                                    WHERE `calls`.`id`=:call_id";
                                $stm = pdo::getPdo()->prepare($sql);                 
                                $stm->execute(array('call_id' => $a_web_call));
                                $webmaster_id = (integer) $stm->fetchColumn();
                                 
                                 if ($webmaster_id) //if ($app_type == 'fix')
                                 {
                                    $sql = "SELECT `id`, `user_id`, `date_create`, `price` FROM `partner_apps` WHERE `call_id`=:call_id";
                                    $stm = pdo::getPdo()->prepare($sql);                 
                                    $stm->execute(array('call_id' => $a_web_call));
                                    $partner_app_array = $stm->fetch(\PDO::FETCH_ASSOC);
                                    
                                    if (!empty($partner_app_array) && !$partner_app_array['price'])
                                    {
                                        $base_web_array = [];
                                        $control_web_tarif = 0;
                                                               
                                        $sql = "SELECT `tarif_webs`.`tarif`, `tarif_webs_default`.`region_id`, `tarif_webs_default`.`name_type`,
                                                                    `tarif_webs_default`.`id_type`, `tarif_webs_default`.`type` 
                                                                                    FROM `tarif_webs` 
                                                                INNER JOIN `tarif_webs_default` ON `tarif_webs`.`tarif_webs_default_id` = `tarif_webs_default`.`id` 
                                                                            WHERE `tarif_webs`.`user_id`=:webmaster_id AND `tarif_webs_default`.`is_active` = 1 
                                                                                                AND (`tarif_webs_default`.`is_deleted` = 0 OR `tarif_webs_default`.`is_deleted` IS NULL)
                                                                                                    AND (`tarif_webs_default`.`region_id`=:region_id OR `tarif_webs_default`.`region_id` = 0)";
                                        $stm = pdo::getPdo()->prepare($sql); 
                                        $stm->execute(array('webmaster_id' => $webmaster_id, 'region_id' => $region_id));
                                        $show_web_tarif = $stm->fetchAll(\PDO::FETCH_ASSOC);
                                        
                                        foreach ($show_web_tarif as $tarif)
                                        {
                                            $name = $tarif['name_type'].'_web_tarifs';
                                            $$name[$tarif['region_id']][$tarif['id_type']] = ['tarif' => $tarif['tarif'], 'type' => $tarif['type']];
                                        }
                                        
                                        if (isset($base_web_tarifs)) 
                                        {
                                            if (isset($base_web_tarifs[$region_id]))
                                            {
                                                $base_web_array = $base_web_tarifs[$region_id][0];
                                            }
                                            else
                                            {
                                                if (isset($base_web_tarifs[0][0]))
                                                {
                                                    $base_web_array = $base_web_tarifs[0][0];
                                                }
                                            }
                                        }
                                        
                                        $marker_array = ['brand', 'model_type'];
                                        
                                        if (in_array($offer_id, load::unordinary_offres()))
                                        {
                                            $sql = "SELECT `tag_tables`.`name` FROM `offer_to_tag_tables` 
                                                        INNER JOIN `tag_tables` ON `offer_to_tag_tables`.`tag_table_id` = `tag_tables`.`id`
                                                            WHERE `offer_to_tag_tables`.`offer_id`=:offer_id";
                                            $stm = pdo::getPdo()->prepare($sql);
                                            $stm->execute(array('offer_id' => $offer_id));
                                            $marker_array = (array) $stm->fetchAll(\PDO::FETCH_COLUMN);
                                            
                                            $marker_array[] = 'offer';
                                        }
                                        
                                        foreach ($marker_array as $marker)
                                        {
                                            $mas_name = $marker.'_web_tarifs';
                                            $marker_id =  $marker.'_id';
                                            
                                            if (!isset($$marker_id)) continue;
                                            
                                            $mas_id = $$marker_id;
                                            
                                            if (isset($$mas_name) && $$mas_name)
                                            {
                                                $mas = $$mas_name;
                                                
                                                if ($marker == 'offer' && !isset($mas[$region_id][$mas_id]) && !isset($mas[0][$mas_id]))
                                                {
                                                    $parent = load::get_order($mas_id, 'parent', 'offers');
                                                    if ($parent) $mas_id = $parent;
                                                }
                                                                                    
                                                if (isset($mas[$region_id]))
                                                {
                                                    if (isset($mas[$region_id][$mas_id]))
                                                    {
                                                        $calc = $this->_calc_percent($mas[$region_id][$mas_id], $control_tarif);
                                                        
                                                        if ($calc > $control_web_tarif) 
                                                        {
                                                            $control_web_tarif = $calc;
                                                        }
                                                    }
                                                    else
                                                    {
                                                        if (isset($mas[$region_id][0]))
                                                        {
                                                            $calc = $this->_calc_percent($mas[$region_id][0], $control_tarif);
                                                            
                                                            if ($calc > $control_web_tarif)
                                                            {
                                                                $control_web_tarif = $calc;
                                                            }
                                                        }
                                                        else
                                                        {
                                                            if (isset($mas[0][$mas_id]))
                                                            {
                                                                $calc = $this->_calc_percent($mas[0][$mas_id], $control_tarif);
                                                                
                                                                if ($calc > $control_web_tarif) 
                                                                {
                                                                    $control_web_tarif = $calc;
                                                                }
                                                            }
                                                            else
                                                            {
                                                                if (isset($mas[0][0]))
                                                                {
                                                                    $calc = $this->_calc_percent($mas[0][0], $control_tarif);
                                                                    
                                                                    if ($calc > $control_web_tarif)
                                                                    {
                                                                        $control_web_tarif = $calc;
                                                                    }
                                                                }
                                                            } 
                                                        }
                                                    }
                                                }
                                                else
                                                {
                                                    if (isset($mas[0][$mas_id]))
                                                    {
                                                        $calc = $this->_calc_percent($mas[0][$mas_id], $control_tarif);
                                                        
                                                        if ($calc > $control_web_tarif) 
                                                        {
                                                            $control_web_tarif = $calc;
                                                        }
                                                    }
                                                    else
                                                    {
                                                        if (isset($mas[0][0]))
                                                        {
                                                            $calc = $this->_calc_percent($mas[0][0], $control_tarif);
                                                            
                                                            if ($calc > $control_web_tarif)
                                                            {
                                                                $control_web_tarif = $calc;
                                                            }
                                                        }
                                                    }            
                                                }
                                            }
                                        }
                                        
                                        if (!$control_web_tarif && $base_web_array)
                                        {
                                            $control_web_tarif = $this->_calc_percent($base_web_array, $control_tarif);   
                                        }
                                        
                                        $partner_price = $control_web_tarif;
                                        
                                        /*$update_args = ['id' => $partner_app_array['id'], 'price' => $partner_price];
                                                                
                                        $sql = "UPDATE `partner_apps` SET ".pdo::prepare($update_args)." WHERE `id`=:id";
                                        $stm = pdo::getPdo()->prepare($sql);
                                        $stm->execute($update_args);
                                        
                                        $sql = "UPDATE `partner_orders` SET `summ`=:summ WHERE `call_id`=:call_id";
                                        $stm = pdo::getPdo()->prepare($sql);
                                        $stm->execute(['summ' => $partner_price, 'call_id' => $a_call]);*/
                                        
                                        $minute_payment = 10;
                                        
                                        $wait_time = 0;
                                        $wait_minute = 0;
                                        
                                        if ($type == 1) 
                                        {
                                            $wait_time = 120;
                                            $wait_minute = 2;
                                        }
                                        
                                        $date_payment = date('Y-m-d H:i:s', strtotime($time) + $minute_payment * 60 + $wait_time);
                                        $wait_obj = new term(['mode' => 'add', 'table' => 'web_waits', 'call_id' => $call_id, 'summ' => $partner_price,
                                                                    'date_create' => $time, 'date_payment' => $date_payment,
                                                                        'minute_payment' => $minute_payment + $wait_minute, 'minute_lost' => 0, 'pay' => null, 'a' => $a_web_call]);
                                        
                                    }
                                }
                                    
                                /*if ($field == 'summ' && $control_tarif)
                                {
                                    exec("php ".\DOCUMENT_ROOT."admin/index.php op=cron args[mode]=calc_webmaster_norm args[call_id]=$call_id args[a]=$a_call > /dev/null &", $output, $return_var);
                                }*/
                                
                                exec("php ".\DOCUMENT_ROOT."admin/index.php op=external args[mode]=send args[call_id]=$call_id args[a]=$a_web_call > /dev/null &", $output, $return_var);
                            }
                            else
                            {
                                tools::reset_approve($goal);    
                                exec("php ".\DOCUMENT_ROOT."admin/index.php op=external args[mode]=send_repeat args[call_id]=$call_id args[original_call_id]=$goal > /dev/null &", $output, $return_var);
                            }
                                    
                            $sql = "UPDATE `resale_dt_phones` SET `block_time` = NULL, `user_id` = NULL WHERE `id`=:id";
                            $stm = pdo::getPdo()->prepare($sql);
                            $stm->execute(array('id' => $resale_dt_phone_id)); 
                             
                            if (!in_array($organization_id, [3118, 3177]))
                            {
                                $goal_array = ['mode' => 'update', 'table' => 'partner_orders', 'id' => $partner_order_id, 'date_recall' => null, 'new_nyk_id' => load::get_status('new_nyks', 'goal')];
                                
                                if ($user_arbiter)
                                {
                                    $goal_array['arbiter'] = 1;
                                    
                                    if ($arbiter != 1)
                                    {
                                        $goal_array['date_arbiter'] = $time;
                                        $goal_array['user_arbiter'] = $user_arbiter;
                                    }
                                }
                                else
                                {
                                    $goal_array['arbiter'] = 9;
                                }                                 
                                                 
                                $obj_partner = new term($goal_array);
                            }
                            
                            $array = array('mode' => 'add', 'table' => 'connectors', 'a' => $a_call, 'b' => $call_id, 'auto' => ($user_arbiter) ? 0 : 1, 'a_real' => $a_real_call, 'b_final' => $call_id);
                                                                                
                            $obj_connectors = new term($array);
                            
                            //connectors_final
                            $sql = "UPDATE `connectors` 
                    	               INNER JOIN `cashbacks` ON connectors.b_final = cashbacks.call_id
                    	                   SET connectors.b_final=:b_final
                    					       WHERE `cashbacks`.`resale_call_id`=:a";
                            $stm = pdo::getPdo()->prepare($sql);
                            $stm->execute(array('b_final' => $call_id, 'a' => $a_call));
                            
                            $new_nyk_id = load::get_status('new_nyks', 'goal');
                        
                            $sql = "UPDATE `partner_orders` SET `new_nyk_id`=:new_nyk_id WHERE `call_id`=:call_id";
                            $stm = pdo::getPdo()->prepare($sql);
                            $stm->execute(array('call_id' => $call_id, 'new_nyk_id' => $new_nyk_id));
                            
                            exec("php ".\DOCUMENT_ROOT."admin/index.php op=cron args[mode]=call_to_group args[call_id]=$call_id > /dev/null &", $output, $return_var);
                                                                                    
                            //exec("php ".\DOCUMENT_ROOT."admin/index.php op=call args[mode]=limit args[organization_id]={$organization_id} > /dev/null &", $output, $return_var);
                            exec("php ".\DOCUMENT_ROOT."admin/index.php op=cron args[mode]=payment_for_accounter args[call_id]=$call_id > /dev/null &", $output, $return_var);
                            exec("php ".\DOCUMENT_ROOT."admin/index.php op=cron args[mode]=payment_for_resaler args[call_id]=$call_id args[resaler_summ]=$resaler_summ > /dev/null &", $output, $return_var);
                            exec("php ".\DOCUMENT_ROOT."admin/index.php op=cron args[mode]=interes_sdr args[organization_id]=$organization_id > /dev/null &", $output, $return_var);
                            
                            
                            exec("php ".\DOCUMENT_ROOT."admin/index.php op=cron args[mode]=resale_snapshots args[call_id]=$call_id > /dev/null &", $output, $return_var);
                            
                            $answer = $call_id;
                        }
                    }
                }
            
            break;
            
            case 'app':
            
                $letter = isset($args['letter']) ? $args['letter'] : 1;
                $real_call_id = isset($args['real_call_id']) ? $args['real_call_id'] : 0;
                
                $all = ['phone' => 'Абонент', 'navy_service_id' => 'Карточка', 'call_id' => 'Звонок'];
                $pass = true;
                
                foreach ($all as $k => $a)
                {
                    if (!$args[$k])
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
                    $navy_obj = new navy(['mode' => 'show_resale_phone', 'service_id' => $args['navy_service_id'], 'call_id' => $args['call_id'], 'show_phone' => true,
                                                'real_call_id' => $real_call_id]);
                    $phone = (string) $navy_obj->getWrapper()->getChildren(0);
                    
                    if ($phone)
                    {
                        $time = date('Y-m-d H:i:s', tools::get_time());  
                        
                        $ar = [];
                        $ar['mode'] = 'call';
                        $ar['notification_time'] = $time;
                        $ar['contact_phone_number'] = $args['phone'];
                        $ar['virtual_phone_number'] = $phone;
                        $ar['call_session_id'] = uniqid();
                        $ar['type'] = 1;
                        $ar['text'] = isset($args['comment']) ? $args['comment'] : '';
                        $ar['letter'] = $letter;
                        
                        $call_obj = new resale($ar);
                        $call_id = (integer) $call_obj->getWrapper()->getChildren(0);
                        
                        $addres_id = (integer) load::get_order($args['navy_service_id'], 'addres_id', 'navy_services');
                        $organization_id = 0;
                        
                        if ($addres_id)
                        {
                            $organization_id = (integer) load::get_order($addres_id, 'organization_id', 'address'); 
                            $email = (array) load::get_order($organization_id, 'email', 'organizations');
                            $phone = load::get_order($organization_id, 'phone', 'organizations');
                            
                            $pay = (integer) load::get_order($addres_id, 'pay', 'address'); 
                                                
                            //echo $email;
                            
                            if ($organization_id == 2515) 
                            {
                                $email[] = 'callcentre@ymservice.ru';
                                $email[] = 'v.mullo@ymservice.ru';
                                $email[] = 'ziatdinov@ymservice.ru';
                            }
                            
                            /*$html = '';
                            
                            $html .= "<p><strong>Номер телефона клиента:</strong> ".$args["phone"]."</p>";
                            
                            if ($args["brand_id"]) 
                                $html .= "<p><strong>Бренд:</strong> ".load::get_order($args['brand_id'], 'name', 'brands')."</p>";
                            
                            if ($args["model_type_id"] )  
                                $html .= "<p><strong>Тип устройства:</strong> ".load::get_order($args['model_type_id'], 'name', 'model_types')."</p>";
                                
                            if ($args["comment"] )  
                                $html .= "<p><strong>Комментарий:</strong> ".$args['comment']."</p>";*/
                                
                            $calls_arr = load::get_order($call_id, ['phone_id', 'brand_id', 'model_type_id', 'text', 'region_id', 'organization_id'], 'calls');
                            $phone_id = $calls_arr['phone_id'];

                            $text = $calls_arr['text'];
                            $region_id = $calls_arr['region_id'];
        
                            $phones_arr = load::get_order($phone_id, ['name', 'name2'], 'phones');
                            $name = $phones_arr['name']; //номер телефона
                            $name2 = $phones_arr['name2']; //имя
                            
                            $region = load::get_order($region_id, 'name', 'regions');
                            $organization_id = $calls_arr['organization_id'];
                            
                            $sql = "SELECT `places`.`name` FROM `partner_orders` 
                                        INNER JOIN `places` ON `partner_orders`.`place_id` = `places`.`id` WHERE `partner_orders`.`call_id`=:call_id";
                            $stm = pdo::getPdo()->prepare($sql);       
                            $stm->execute(array('call_id' => $call_id));
                            $place_name = $stm->fetchColumn();
                            
                            $place_array = implode(' ', array_unique([$region, $place_name])); 
                            
                            $message = "";
                            if ($region) {
                                $message .= "<p><strong>Регион:</strong> {$place_array} ($organization_id).</p>";
                            }
                            if ($name) {
                                $message .= "<p><strong>Телефон клиента:</strong> +{$name}.</p>";
                            }                            
                            if ($name2) {
                                $message .= "<p><strong>Имя:</strong> {$name2}</p>";
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
                                $message .= "<p><strong>Оффер:</strong> {$offer_name}.</p>";
                            }
                            
                            if ($tag_str) {
                                $tag_str = implode(" | ", $tag_str);
                                $message .= "<p><strong>Теги:</strong> {$tag_str}.</p>";
                            }                            
                            
                            if ($text) {
                                $message .= "<p><strong>Комментарий:</strong> {$text}.</p>";
                            }
                                
                            foreach ($email as $one_mail)
                            {
                                if ($one_mail) tools::mail_gateway($one_mail, $message, "navyservice.net :новая заявка");  
                            }                            
                            //echo $message;
                            
                            if ($phone && ($pay == 0 || $pay == 2))
                            {
                                $request = array(
                                            'op' => 'sms',
                                            'args' => array(
                                                'mode' => 'send',
                                                'order_id' => -1,
                                                'type_id' => 54,
                                                'phone' => tools::cut_phone($phone),
                                        ));
                                        
                                $request['args']['dop_function'] = ['[phone]' => '+' . tools::cut_phone($args["phone"])];                            
                                $ret = tools::request_api($request);
                            }
                            
                            //file_put_contents(\DOCUMENT_ROOT.'send_telegram', "php ".\DOCUMENT_ROOT."admin/index.php op=cron args[mode]=send_telegram args[call_id]=$call_id > /dev/null &".PHP_EOL, FILE_APPEND | LOCK_EX); 
                            exec("php ".\DOCUMENT_ROOT."admin/index.php op=cron args[mode]=send_telegram args[call_id]=$call_id > /dev/null &", $output, $return_var);
                            
                            $answer = $call_id;
                        }
                    }
                    else
                        $pass = false;
                    
                    if ($pass)
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
                        }
                    }
                    else
                    {
                        if (session_id())
                        {
                            $notifys = new term(
                                            array(
                                                'mode' => 'add', 
                                                'table' => 'notifys',
                                                'text' => 'Заявка не отправлена! Попробуйте позже!', 
                                                'type_notify_id' => load::get_status('type_notifys', 'update_error'),
                                                'session' => session_id(),
                                                )
                                            );
                        }
                                          
                        $this->setCode('error');   
                    }                                         
                }     
                                
            break;
            
            case 'in':
            
                $service_id = isset($args['service_id']) ? (int) $args['service_id'] : 0;
                $call_id = isset($args['call_id']) ? (int) $args['call_id'] : 0;
                $real_call_id = isset($args['real_call_id']) ? $args['real_call_id'] : 0;
                $phone = isset($args['phone']) ? (string) $args['phone'] : '';
                $user_id = isset($args['user_id']) ? $args['user_id'] : 0;
                 
                $phone = trim(tools::cut_phone($phone));
                 
                //if ($user_id)
                //{
                    $sql = "UPDATE `resale_dt_phones` SET `block_time` = NULL, `user_id` = NULL WHERE `user_id`=:user_id";
                    $stm = pdo::getPdo()->prepare($sql);
                    $stm->execute(array('user_id' => $user_id));
                //}
                
                if (!$service_id) break;
                
                $phone_yd = 1728;
                                
                if (!$phone_yd || !$call_id) break;
                
                $current_time = tools::get_time();
                $now = date('Y-m-d H:i:s', $current_time);
                               
                $sql = "SELECT `resale_dt_phones`.`mango_id` as `mango_id`, `resale_dt_phones`.`id` as `id`,
                                               `resale_dt_phones`.`block_time` as `block_time` FROM `resale_dt_phones`";
                $dt_phones = pdo::getPdo()->query($sql)->fetchAll(\PDO::FETCH_ASSOC);
                    
                $free_numbers = [];
                    
                foreach ($dt_phones as $value)
                {
                    $block_time = strtotime($value['block_time']);
                    if ($current_time - $block_time > \BLOCK_TIME)
                    {
                        $free_numbers[$block_time][] = array($value['mango_id'], $value['id']);
                    }    
                }
                    
                //новый
                $t = array();
                foreach ($free_numbers as $value)
                    foreach ($value as $val)
                        $t[] = $val;
                                                        
                $count_free_numbers = count($t);
                
                $color = '';
                    
                if ($free_numbers)
                {
                    ksort($free_numbers);
                    $free_numbers = current($free_numbers);
                    
                    $a_numbers = $free_numbers[rand(0, count($free_numbers) - 1)];
                    
                    $obj_add = new term(array('mode' => 'add', 'table' => 'resale_dt_sessions', 'mango_id' => $a_numbers[0], 'phone' => $phone,
                                    'timestamp' => $now, 'free_number' => $count_free_numbers, 'user_id' => $user_id, 'navy_service_id' => $service_id,
                                            'call_id' => $call_id, 'real_call_id' => $real_call_id));
                    $dt_session_id = $obj_add->getWrapper()->getChildren(0);
                 
                    $obj = new term(array('mode' => 'update', 'table' => 'resale_dt_phones', 'id' => $a_numbers[1], 'resale_dt_session_id' => $dt_session_id, 
                                    'block_time' => $now,  'phone' => $phone, 'user_id' => $user_id, 'navy_service_id' => $service_id,
                                                'call_id' => $call_id, 'real_call_id' => $real_call_id));
                    
                    $answer = $a_numbers[0];
                    $color = 'green';                                
                }
                else
                {
                    $obj = new term(array('mode' => 'add', 'table' => 'resale_dt_sessions', 'mango_id' => $phone_yd, 
                                    'timestamp' => $now, 'free_number' => $count_free_numbers, 'user_id' => $user_id, 'navy_service_id' => $service_id,
                                                'call_id' => $call_id, 'real_call_id' => $real_call_id));
                       
                    $answer = $phone_yd;
                    $color = 'red';
                }
                
                if ($answer)
                {
                    $answer = [load::get_order($answer, 'name', 'mangos'), $color];
                }             
                 
            break;
            
            case 'interactive':
                
                $code = 0;
                $mango = tools::cut_phone($args['numb']);
                
                $sql = "SELECT `resale_dt_phones`.`phone`, 
                               `resale_dt_phones`.`call_id`, 
                                  `address`.`organization_id` as `organization_id`,
                                     `navy_services`.`region_id`
                                        FROM `resale_dt_phones`
                                LEFT JOIN `mangos` ON `mangos`.`id` = `resale_dt_phones`.`mango_id` 
                                    LEFT JOIN `resale_dt_sessions` ON `resale_dt_phones`.`resale_dt_session_id` = `resale_dt_sessions`.`id`
                                        LEFT JOIN `navy_services` ON `resale_dt_sessions`.`navy_service_id` = `navy_services`.`id`
                                            LEFT JOIN `address` ON `navy_services`.`addres_id` = `address`.`id` 
                            WHERE `mangos`.`name`=:mango_name";
                            
                $stm = pdo::getPdo()->prepare($sql);
                $stm->execute(array('mango_name' => $mango));
                $array = $stm->fetch(\PDO::FETCH_ASSOC);
                
                $phone = $array['phone'];
                $call_id = $array['call_id'];
                $organization_id = $array['organization_id'];
                $region_id = $array['region_id']; 
                
                //$organization_include = [3322, 3311, 3194, 3333, 3273, 3239, 3339, 3335];
                $robot = load::get_order($organization_id, 'robot', 'organizations');                
                
                $sql = "SELECT `nyk_name2` FROM `partner_orders` WHERE `call_id`=:call_id";
                $stm = pdo::getPdo()->prepare($sql); 
                $stm->execute(array('call_id' => $call_id));
                $call_array = $stm->fetch(\PDO::FETCH_ASSOC);
         
                $text = $call_array['nyk_name2'];
                
                $region = load::get_order($region_id, 'name', 'regions');
                
                $text = '';                
                
                $sql = "SELECT `places`.`name` FROM `partner_orders` 
                                        INNER JOIN `places` ON `partner_orders`.`place_id` = `places`.`id` WHERE `partner_orders`.`call_id`=:call_id";
                $stm = pdo::getPdo()->prepare($sql);       
                $stm->execute(array('call_id' => $call_id));
                $place_name = $stm->fetchColumn();
                            
                $place_array = implode(' ', array_unique([$region, $place_name]));
                
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

                $sql = "SELECT IF(`parent_offers`.`name` IS NOT NULL, CONCAT(`parent_offers`.`name`, ' ', `offers`.`name`), `offers`.`name`) FROM `partner_orders` 
                                INNER JOIN `offers` ON `offers`.`id` = `partner_orders`.`offer_id`
                                    LEFT JOIN `offers` `parent_offers` ON `parent_offers`.`id` = `offers`.`parent` 
                                        WHERE `partner_orders`.`call_id`=:call_id";
                $stm = pdo::getPdo()->prepare($sql); 
                $stm->execute(array('call_id' => $call_id));
                $offer_name = $stm->fetchColumn();
                
                $operator_text = '';
                
                if ($offer_name || $tag_str || $text || $region)
                {
                    $operator_text = 'Здравствуйте. Сообщаю вам следующую информацию о клиенте:';
                    
                    if ($region) $operator_text .= ' ' . $region;
                    
                    if ($offer_name) $operator_text .= ' ' . $offer_name;
                    
                    if ($tag_str) 
                    {
                        $tag_str = implode(" ", $tag_str);
                        $operator_text .= ' ' . $tag_str;
                    }  
                    
                    if ($text) 
                    {
                        $operator_text .= ' ' . $text;
                    }
                    
                    $operator_text .= '. Соединяю.';      
                }
                
                if ($phone)
                {
                    switch ($phone)
                    {
                        /*case '74951068200': case '74951043035':
                            $answer = json_encode(array("returned_code" => 1)); 
                        break;*/
                        case '78612025019':
                            $answer = json_encode(array("returned_code" => 1)); //краснодар виталий 
                        break;
                        case '78126071363': case '78126042011': case '78125012271': case '78126034084':
                            $answer = json_encode(array("returned_code" => 2));
                        break;
                        case '74951508561':
                            $answer = json_encode(array("returned_code" => 3)); //москва виталий
                        break;
                        /*case '74956416471':
                            $answer = json_encode(array("returned_code" => 4));
                        break;*/
                        case '78633221298':
                            $answer = json_encode(array("returned_code" => 4)); //ростов виталий
                        break;
                        case '79037660470':
                            $goal = $this->_is_goal($call_id);
                            if (!$goal)
                                $answer = json_encode(array("returned_code" => 5));
                            else
                                $answer = json_encode(array("phones" => array($phone)));
                        break;
                        /*case '74993808017':
                            $answer = json_encode(array("returned_code" => 6));
                        break;*/
                        /*case '74950252347':
                            $answer = json_encode(array("returned_code" => 7));
                        break;*/
                        case '78122453609':
                            $answer = json_encode(array("returned_code" => 7));
                        break;
                        case '74951312310':
                            $answer = json_encode(array("phones" => array($phone), "operator_media" => "Гелия 1-1.mp3"), JSON_UNESCAPED_UNICODE);
                        break;
                        default:
                            //$answer = json_encode(array("phones" => array($phone), "operator_text" => "Звонок с портала Нависервис"), JSON_UNESCAPED_UNICODE);
                            //$answer = json_encode(array("phones" => array($phone), "operator_media" => "Гелия 1-1.mp3"), JSON_UNESCAPED_UNICODE);
                            $array = array("phones" => array($phone));
                            
                            if ($organization_id == 3496) $operator_text = "Вывоз мусора от сиба. " . $operator_text; 
                            
                            if ($operator_text && $robot) 
                            {
                                $array["operator_text"] = $operator_text;
                            }                          
                            
                            $answer = json_encode($array, JSON_UNESCAPED_UNICODE);
                    }
                }
                else
                {
                    $answer = json_encode(array("returned_code" => $code));
                }
                
            break;
        }
        
        //if (load::get_user_id() == 1) echo 'test';
        
        $this->getWrapper()->addChildren($answer);
    }
    
    private function _is_goal($call_id)
    {
        $call_arr = load::get_order($call_id, ['phone_id', 'timestamp', 'type'], 'calls', false);
        
        //if ($call_arr['type'] == 0)
            $tarif_period = (integer) 30 * 24 * 60 * 60;
        //else
            //$tarif_period = (integer) 3 * 24 * 60 * 60;
        
        $sql = "SELECT `mango_id` FROM `resale_dt_phones`";
        $resale_mangos =  pdo::getPdo()->query($sql)->fetchAll(\PDO::FETCH_COLUMN);
          
        $start_period = date('Y-m-d H:i:s', strtotime($call_arr['timestamp']) - $tarif_period);
        $end_period = $call_arr['timestamp'];
        
        $sql = "SELECT `calls`.`id` FROM `calls` INNER JOIN `transactions` ON `transactions`.`call_id` = `calls`.`id`
                        WHERE `mango_id` IN (".implode(',', $resale_mangos).") AND `phone_id`=:phone_id AND `calls`.`timestamp`>=:time AND `calls`.`timestamp`<:time2";
        $stm = pdo::getPdo()->prepare($sql); 
        $stm->execute(array('phone_id' => $call_arr['phone_id'], 'time' => $start_period, 'time2' => $end_period));
                                        
        $goal = $stm->fetchColumn();
        return $goal;
    }
    
    public function calc_percent($array, $summ)
    {
        return $this->_calc_percent($array, $summ);    
    }
    
    private function _calc_percent($array, $summ)
    {
        if ($array['type'] == 'fix')
            $tarif = $array['tarif'];
        else
            $tarif = floor($array['tarif'] * abs($summ) / 100); 
            
        return $tarif;
    }
}

?>