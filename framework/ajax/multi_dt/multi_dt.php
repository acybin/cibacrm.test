<?

namespace framework\ajax\multi_dt;

use framework\ajax as ajax;
use framework\pdo;
use framework\tools;
use framework\load;
use framework\ajax\term\term;
use framework\ajax\dt\dt;
use framework\enum;
use framework\dom\node;
use framework\shape\form as form;

class multi_dt extends ajax\ajax
{
    const BLOCK_TIME = 600;
    
    public function __construct($args = array())
    {
        error_reporting(E_ALL);
        ini_set('display_errors', 1);
        
        parent::__construct('multi_dt');
        $mode = isset($args['mode']) ? $args['mode'] : '';
        
        $this->getWrapper()->getAttributes()->addAttr('id', 'multi_dt-'.$mode);
        
        $answer = '';
        
        //file_put_contents(\DOCUMENT_ROOT.'multi_dt', print_r($args, true), FILE_APPEND | LOCK_EX);
        
        switch ($mode)
        {
            case 'in':
            
                $session = isset($args['session']) ? (string) $args['session'] : '';
                $p_mode = isset($args['p_mode']) ? (string) $args['p_mode'] : '';
                $client_id = isset($args['client_id']) ? $args['client_id'] : '';
                
                $nls_source_id = isset($args['nls_source_id']) ? (integer) $args['nls_source_id'] : 0;
                $site = isset($args['site']) ? (string) $args['site'] : '';
                
                $s = isset($args['s']) ? $args['s'] : '';
                
                $tag = isset($args['tag']) ? (array) $args['tag'] : []; 
                $cm_id = isset($args['cm_id']) ? (string) $args['cm_id'] : ''; 
                
                $ip = isset($args['ip']) ? (string) $args['ip'] : '';
                $query = isset($args['query']) ? (string) $args['query'] : '';
                $user_agent = isset($args['user_agent']) ? (string) $args['user_agent'] : '';
                    
                if ($site)
                {
                    if (in_array($p_mode, ['YD', 'GA']))
                    {
                        $accord = ['YD' => 'nls_source_yd', 'GA' => 'nls_source_ga'];
                        
                        $sql = "SELECT `sites`.`nls_source_yd`, `sites`.`nls_source_ga`, `sites`.`id`
                                    FROM `sites` 
                                        WHERE `sites`.`name`=:name";
                        $stm = pdo::getPdo()->prepare($sql); 
                        $stm->execute(array('name' => $site));
                        $mas_sites = $stm->fetch(\PDO::FETCH_ASSOC);
                        
                        if (isset($mas_sites[$accord[$p_mode]])) $nls_source_id = $mas_sites[$accord[$p_mode]];
                        
                        if (isset($mas_sites['id']))
                        {
                            $sql = "SELECT `tags`.`name_type`, `tags`.`id_type`
                                        FROM `tags` WHERE `tags`.`site_id`=:site_id";
                            $stm = pdo::getPdo()->prepare($sql); 
                            $stm->execute(array('site_id' => $mas_sites['id']));
                            $tags = $stm->fetchAll(\PDO::FETCH_ASSOC);
                            
                            foreach ($tags as $tag_value)
                                $tag[$tag_value['name_type']] = $tag_value['id_type'];
                        }
                        
                        $session_ar2 = explode(':', $session);
                    
                        $session = $session_ar2[1]; 
                        $cm_id = $session_ar2[0];
                    }
                }              
                
                if (!$nls_source_id) break;
                
                $session_ar = explode('_', $cm_id);
                
                for ($i = 0; $i <= 3; $i++)
                    if (!isset($session_ar[$i]) || !$session_ar[$i] || !is_numeric($session_ar[$i])) $session_ar[$i] = 0; 
                
                if ($session == "7710547526903176844") break;
                
                $dt = new dt();
                 
                $current_time = tools::get_time();
                $now = date('Y-m-d H:i:s', $current_time);
                
                $ad = ($p_mode == 'GA');
                
                $sql = "SELECT `id` FROM `channels` WHERE `name`=:p_mode";
                $stm = pdo::getPdo()->prepare($sql); 
                $stm->execute(array('p_mode' => $p_mode));                
                $channel_id = $stm->fetchColumn();
                
                if (!$channel_id) break;
                
                $nls_source_group_id = load::get_order($nls_source_id, 'nls_source_group_id', 'nls_sources');
                
                if ($nls_source_group_id)
                {
                    $default_nls_source_id = load::get_order($nls_source_group_id, 'nls_source_id', 'nls_source_groups'); 
                    
                    $sql = "SELECT `mangos`.`id` FROM `mangos` WHERE `mangos`.`nls_source_id`=:nls_source_id 
                                                                    AND `mangos`.`channel_id`=:channel_id";
                    $stm = pdo::getPdo()->prepare($sql); 
                    $stm->execute(array('nls_source_id' => $default_nls_source_id, 'channel_id' => $channel_id));                
                    $phone_yd = $stm->fetchColumn();
                }
                else
                {
                    $sql = "SELECT `mangos`.`id` FROM `mangos` LEFT JOIN `multi_dt_phones` ON `multi_dt_phones`.`mango_id` = `mangos`.`id` 
                                                     WHERE `mangos`.`nls_source_id`=:nls_source_id 
                                                                    AND `mangos`.`channel_id`=:channel_id AND `multi_dt_phones`.`id` IS NULL";
                    $stm = pdo::getPdo()->prepare($sql); 
                    $stm->execute(array('nls_source_id' => $nls_source_id, 'channel_id' => $channel_id));                
                    $phone_yd = $stm->fetchColumn();
                    
                    if (!$phone_yd)
                    {
                        $parent = load::get_order($nls_source_id, 'parent', 'nls_sources');
                        
                        $sql = "SELECT `mangos`.`id` FROM `mangos` LEFT JOIN `multi_dt_phones` ON `multi_dt_phones`.`mango_id` = `mangos`.`id` 
                                                     WHERE `mangos`.`nls_source_id`=:nls_source_id 
                                                                    AND `mangos`.`channel_id`=:channel_id AND `multi_dt_phones`.`id` IS NULL";
                        $stm = pdo::getPdo()->prepare($sql); 
                        $stm->execute(array('nls_source_id' => $parent, 'channel_id' => $channel_id));                
                        $phone_yd = $stm->fetchColumn();
                    }
                }
                
                if (!$phone_yd) break;
                
                //if (!$s) 
                //{
                    //$answer = load::get_order($phone_yd, 'name', 'mangos');
                    //break;
                //}
                
                $addres_id = load::get_order($nls_source_id, 'addres_id', 'nls_sources');
                $organization_id = load::get_order($nls_source_id, 'organization_id', 'nls_sources');

                $phone = '';
                
                if ($addres_id)
                {
                    $sql = "SELECT `navy_services`.`phone1` FROM `navy_services` WHERE `addres_id`=:addres_id";
                    $stm = pdo::getPdo()->prepare($sql);
                    $stm->execute(array('addres_id' => $addres_id));
                    $phone = $stm->fetchColumn();
                    
                    $phone = trim(tools::cut_phone($phone));
                }
                
                /*$parent_nls_source_id = load::get_order($nls_source_id, 'parent', 'nls_sources');
                if (!$parent_nls_source_id) $parent_nls_source_id = $nls_source_id;*/
                
                if ($nls_source_group_id)
                {
                    $sql = "SELECT * FROM `multi_dt_phones` WHERE `nls_source_group_id`=:nls_source_group_id";
                    $stm = pdo::getPdo()->prepare($sql);
                    $stm->execute(array('nls_source_group_id' => $nls_source_group_id));
                    $dt_phones = (array) $stm->fetchAll(\PDO::FETCH_ASSOC);
                }
                else
                {
                    $dt_phones = [];
                }
                
                /*if (!$dt_phones)
                {
                    $sql = "SELECT `nls_sources`.`id` FROM `nls_sources` 
                            INNER JOIN `nls_source_tags` ON `nls_source_tags`.`nls_source_id` = `nls_sources`.`id`
                                    WHERE `nls_source_tags`.`name_type` = 'setka' AND `nls_source_tags`.`id_type` = 40 
                                                AND `nls_sources`.`id`=:nls_source_id";
                    $stm = pdo::getPdo()->prepare($sql); 
                    $stm->execute(array('nls_source_id' => $nls_source_id));                
                    $m2 = $stm->fetchColumn();
                    
                    if (!$m2)
                    {
                        $sql = "SELECT * FROM `multi_dt_phones` WHERE `nls_source_id` = 0";
                        $dt_phones = pdo::getPdo()->query($sql)->fetchAll(\PDO::FETCH_ASSOC);
                    }
                }*/
                
                $sql = "SELECT `id`, `mango_id`, `channel_id`, `s` FROM `multi_dt_sessions` WHERE `session`=:session ORDER BY `id` DESC LIMIT 0,1";
                $stm = pdo::getPdo()->prepare($sql);
                $stm->execute(array('session' => $session));
                $dt_session_array = $stm->fetch(\PDO::FETCH_ASSOC);
                
                $dt_session_id = 0;
                $dt_session_mango_id = 0;
                
                if ($dt_session_array)
                {
                    $dt_session_id = (integer) $dt_session_array['id'];
                    $dt_session_mango_id = (integer) $dt_session_array['mango_id'];
                    $dt_session_channel_id = (integer) $dt_session_array['channel_id'];
                    $dt_session_s = $dt_session_array['s']; 
                }
                
                $free_numbers = [];                        
                $my_number = 0;
                    
                foreach ($dt_phones as $value)
                {
                    if ($dt_session_id && $value['multi_dt_session_id'] == $dt_session_id)
                    {
                        if ($dt_session_channel_id == $channel_id)
                            if (!$s || $dt_session_s == $s)
                                $my_number = $value['mango_id'];
                        
                        if (!$my_number) $value['block_time'] = null;
                    }     
                   
                    $block_time = strtotime($value['block_time']);
                    if ($current_time - $block_time > self::BLOCK_TIME)
                    {
                        $free_numbers[$block_time][] = array($value['mango_id'], $value['id']);
                    }    
                }
                
                $t = array();
                foreach ($free_numbers as $value)
                    foreach ($value as $val)
                        $t[] = $val;
                                                        
                $count_free_numbers = count($t); 
                
                if (!$dt_session_id)
                {
                    if ($free_numbers)
                    {
                        ksort($free_numbers);
                        $free_numbers = current($free_numbers);
                        
                        $a_numbers = $free_numbers[rand(0, count($free_numbers) - 1)];
                        
                        $obj_add = new term(array('mode' => 'add', 'table' => 'multi_dt_sessions', 'mango_id' => $a_numbers[0], 'session' => $session, 
                                        'timestamp' => $now, 'update' => $now, 's' => $s, 'channel_id' => $channel_id, 'nls_source_id' => $nls_source_id, 'free_number' => $count_free_numbers,
                                                                'phone' => $phone, 'nls_source_group_id' => $nls_source_group_id));
                        $multi_dt_session_id = $obj_add->getWrapper()->getChildren(0);
                        
                        $obj = new term(array('mode' => 'update', 'table' => 'multi_dt_phones', 'id' => $a_numbers[1], 'multi_dt_session_id' => $multi_dt_session_id, 
                                        'block_time' => $now, 'channel_id' => $channel_id, 'phone' => $phone));
                                        
                        //nlc
                        $update_channel = new term(['mode' => 'update', 'table' => 'mangos', 'id' => $a_numbers[0], 'channel_id' => $channel_id, 'nls_source_id' => $nls_source_id, 'organization_id' => $organization_id]);
                        
                        $this->_add_tag($tag, $multi_dt_session_id);
                        
                        $session_ar[0] = $dt->get_campaign_id($session_ar[0], $ad);

                        $obj_stats = new term(array('mode' => 'add', 'table' => 'multi_campaign_stats', 'campaign' => $session_ar[0], 'group' => $session_ar[1],
                                                'banner' => $session_ar[2], 'phrase' => $session_ar[3], 'multi_dt_session_id' => $multi_dt_session_id, 'channel_id' => $channel_id));
                                                
                        foreach (array('gbs', 'banners', 'phrases') as $k => $v)
                        {
                           if ($ad == 1) $v = 'ad_' . $v;
                           
                           if ($v == 'phrases' || $v == 'ad_phrases')
                                $$v = $dt->fill_tables($v, $session_ar[$k + 1], $session_ar[0], $session_ar[1]);
                           else
                                $$v = $dt->fill_tables($v, $session_ar[$k + 1], $session_ar[0]);
                        }
                        
                        if ($query || $ip || $user_agent)
                        {
                             $obj_add = new term(array('mode' => 'add', 'table' => 'multi_dt_logs', 'multi_dt_session_id' => $multi_dt_session_id,
                                    'ip' => $ip, 'query' => $query, 'user_agent' => $user_agent));
                        }
                        
                        $answer = $a_numbers[0];                                
                    }
                    else
                    {
                        $obj_add = new term(array('mode' => 'add', 'table' => 'multi_dt_sessions', 'mango_id' => $phone_yd, 'session' => $session, 
                                        'timestamp' => $now, 'update' => $now, 's' => $s, 'channel_id' => $channel_id, 'nls_source_id' => $nls_source_id, 'free_number' => $count_free_numbers,
                                                        'phone' => $phone, 'nls_source_group_id' => $nls_source_group_id));
                                                        
                        $multi_dt_session_id = $obj_add->getWrapper()->getChildren(0);
                        
                        $this->_add_tag($tag, $multi_dt_session_id);
                        
                        $session_ar[0] = $dt->get_campaign_id($session_ar[0], $ad);

                        $obj_stats = new term(array('mode' => 'add', 'table' => 'multi_campaign_stats', 'campaign' => $session_ar[0], 'group' => $session_ar[1],
                                                'banner' => $session_ar[2], 'phrase' => $session_ar[3], 'multi_dt_session_id' => $multi_dt_session_id, 'channel_id' => $channel_id));
                                                
                        foreach (array('gbs', 'banners', 'phrases') as $k => $v)
                        {
                           if ($ad == 1) $v = 'ad_' . $v;
                           
                           if ($v == 'phrases' || $v == 'ad_phrases')
                                $$v = $dt->fill_tables($v, $session_ar[$k + 1], $session_ar[0], $session_ar[1]);
                           else
                                $$v = $dt->fill_tables($v, $session_ar[$k + 1], $session_ar[0]);
                        }
                        
                        if ($query || $ip || $user_agent)
                        {
                             $obj_add = new term(array('mode' => 'add', 'table' => 'multi_dt_logs', 'multi_dt_session_id' => $multi_dt_session_id,
                                    'ip' => $ip, 'query' => $query, 'user_agent' => $user_agent));
                        }
                       
                        $answer = $phone_yd;
                    }       
                }
                else
                {
                    if (!$my_number)
                    {
                        if ($free_numbers)
                        {
                            ksort($free_numbers);
                            $free_numbers = current($free_numbers);
                            
                            $a_numbers = $free_numbers[rand(0, count($free_numbers) - 1)];
                              
                            $obj_add = new term(array('mode' => 'add', 'table' => 'multi_dt_sessions', 'mango_id' => $a_numbers[0], 'session' => $session, 
                                        'timestamp' => $now, 'update' => $now, 's' => $s, 'channel_id' => $channel_id, 'nls_source_id' => $nls_source_id, 'free_number' => $count_free_numbers,
                                                                'phone' => $phone, 'nls_source_group_id' => $nls_source_group_id));
                            $multi_dt_session_id = $obj_add->getWrapper()->getChildren(0);
                            
                            $obj = new term(array('mode' => 'update', 'table' => 'multi_dt_phones', 'id' => $a_numbers[1], 'multi_dt_session_id' => $multi_dt_session_id, 
                                        'block_time' => $now, 'channel_id' => $channel_id, 'phone' => $phone));
                                        
                            //nlc
                            $update_channel = new term(['mode' => 'update', 'table' => 'mangos', 'id' => $a_numbers[0], 'channel_id' => $channel_id, 'nls_source_id' => $nls_source_id, 'organization_id' => $organization_id]);
                            
                            $this->_add_tag($tag, $multi_dt_session_id);
                            
                            $session_ar[0] = $dt->get_campaign_id($session_ar[0], $ad);
                            
                            $obj_stats = new term(array('mode' => 'add', 'table' => 'multi_campaign_stats', 'campaign' => $session_ar[0], 'group' => $session_ar[1],
                                                'banner' => $session_ar[2], 'phrase' => $session_ar[3], 'multi_dt_session_id' => $multi_dt_session_id, 'channel_id' => $channel_id));
                                                
                            foreach (array('gbs', 'banners', 'phrases') as $k => $v)
                            {
                               if ($ad == 1) $v = 'ad_' . $v;
                               
                               if ($v == 'phrases' || $v == 'ad_phrases')
                                    $$v = $dt->fill_tables($v, $session_ar[$k + 1], $session_ar[0], $session_ar[1]);
                               else
                                    $$v = $dt->fill_tables($v, $session_ar[$k + 1], $session_ar[0]);
                            }
                            
                            if ($query || $ip || $user_agent)
                            {
                                 $obj_add = new term(array('mode' => 'add', 'table' => 'multi_dt_logs', 'multi_dt_session_id' => $multi_dt_session_id,
                                        'ip' => $ip, 'query' => $query, 'user_agent' => $user_agent));
                            }
                            
                            $answer = $a_numbers[0];   
                        }
                        else
                        {
                            if ($dt_session_mango_id != $phone_yd)
                            {
                                $obj_add = new term(array('mode' => 'add', 'table' => 'multi_dt_sessions', 'mango_id' => $phone_yd, 'session' => $session, 
                                        'timestamp' => $now, 'update' => $now, 's' => $s, 'channel_id' => $channel_id, 'nls_source_id' => $nls_source_id, 'free_number' => $count_free_numbers,
                                                        'phone' => $phone, 'nls_source_group_id' => $nls_source_group_id));
                                                        
                                $multi_dt_session_id = $obj_add->getWrapper()->getChildren(0);
                        
                                $this->_add_tag($tag, $multi_dt_session_id);
                                
                                $session_ar[0] = $dt->get_campaign_id($session_ar[0], $ad);
        
                                $obj_stats = new term(array('mode' => 'add', 'table' => 'multi_campaign_stats', 'campaign' => $session_ar[0], 'group' => $session_ar[1],
                                                        'banner' => $session_ar[2], 'phrase' => $session_ar[3], 'multi_dt_session_id' => $multi_dt_session_id, 'channel_id' => $channel_id));
                                                        
                                foreach (array('gbs', 'banners', 'phrases') as $k => $v)
                                {
                                   if ($ad == 1) $v = 'ad_' . $v;
                                   
                                   if ($v == 'phrases' || $v == 'ad_phrases')
                                        $$v = $dt->fill_tables($v, $session_ar[$k + 1], $session_ar[0], $session_ar[1]);
                                   else
                                        $$v = $dt->fill_tables($v, $session_ar[$k + 1], $session_ar[0]);
                                }
                                
                                if ($query || $ip || $user_agent)
                                {
                                     $obj_add = new term(array('mode' => 'add', 'table' => 'multi_dt_logs', 'multi_dt_session_id' => $multi_dt_session_id,
                                            'ip' => $ip, 'query' => $query, 'user_agent' => $user_agent));
                                }
                                
                                $answer = $phone_yd;
                            }
                            else
                            {
                                $obj = new term(array('mode' => 'update', 'table' => 'multi_dt_sessions', 'id' => $dt_session_id, 'update' => $now, 'client_id' => $client_id));
                                    
                                $answer = $phone_yd;    
                            }  
                        }
                    }
                    else
                    {
                        $obj = new term(array('mode' => 'update', 'table' => 'multi_dt_sessions', 'id' => $dt_session_id, 'update' => $now, 'client_id' => $client_id));
                        
                        $answer = $my_number;
                    }        
                }
                
                if ($answer)
                {
                    if ($user_agent == 'api')
                    {
                        $sql = "SELECT `block_time` FROM `multi_dt_phones` WHERE `mango_id`=:mango_id";
                        $stm = pdo::getPdo()->prepare($sql);
                        $stm->execute(array('mango_id' => $answer));
                        $block_time = $stm->fetchColumn();   
                        
                        if ($block_time)
                        { 
                            $block_time = date('Y-m-d H:i:s', strtotime($block_time) + self::BLOCK_TIME);
                        }
                        else
                        {
                             list($h, $i, $s, $n, $j, $Y) = array(date("H"),date("i"),date("s"),date("n"),date("j"),date("Y"));
                             $block_time = date('Y-m-d H:i:s', mktime(23, 59, 59, $n, $j, $Y)); 
                        }
                        
                        $nls_source_id = load::get_order($answer, 'nls_source_id', 'mangos');
                        
                        $answer = ['phone' => load::get_order($answer, 'name', 'mangos'), 'expired' => $block_time, 'source_id' => (integer) $nls_source_id];
                    }
                    else
                    {
                        $answer = load::get_order($answer, 'name', 'mangos');
                    }
                }
                
            break;
            
            case 'set_client_id':
                
                $yclid = isset($args['yclid']) ? $args['yclid'] : '';
                $client_id = isset($args['client_id']) ? $args['client_id'] : '';
                
                if ($yclid && $client_id)
                {
                    $sql = "UPDATE `multi_dt_sessions` SET `client_id`=:client_id WHERE `session`=:session";
                    $stm = pdo::getPdo()->prepare($sql);
                    $stm->execute(array('session' => $yclid, 'client_id' => $client_id));
                }
                                
            break;
            
            case 'clear_dt_phone':
            
                $mango_name = isset($args['mango_name']) ? $args['mango_name'] : '';
                
                if ($mango_name)
                {
                    $sql = "SELECT `multi_dt_phones`.`id` FROM `multi_dt_phones` INNER JOIN `mangos` ON `mangos`.`id` = `multi_dt_phones`.`mango_id`
                                        WHERE `mangos`.`name`=:name";
                    $stm = pdo::getPdo()->prepare($sql);
                    $stm->execute(array('name' => $mango_name));
                    $multi_dt_phone_id = $stm->fetchColumn();
                        
                    if ($multi_dt_phone_id)
                    {
                        $sql = "UPDATE `multi_dt_phones` SET `block_time` = NULL WHERE `id`=:id";
                        $stm = pdo::getPdo()->prepare($sql);
                        $stm->execute(array('id' => $multi_dt_phone_id));
                    }
                }
            
            break;
        }
        
        $this->getWrapper()->addChildren($answer);
    }
    
    private function _add_tag($tag, $multi_dt_session_id)
    {
        $insert = [];
        foreach ($tag as $key => $value)
        {
            if ($value) 
            {
                switch ($key)
                {
                    case 'brand':
                    
                        if (!is_numeric($value))
                        {
                            $brand_name = mb_strtolower($value);
                            
                            $sql = "SELECT `id` FROM `brands` 
                                        WHERE `organization_id` IS NULL AND 
                                            (`ru_name` IS NOT NULL AND `ru_name` != '') AND LOWER(`name`)=:name"; 
                            $stm = pdo::getPdo()->prepare($sql);
                            $stm->execute(array('name' => $brand_name));
                            $id = $stm->fetchColumn();
                        }
                        else
                        {
                            $id = $value;
                        }
                         
                   break;
                   
                   case 'model_type':
                   
                        if (!is_numeric($value))
                        {
                            $model_type_name = mb_strtolower($value);
                        
                            $sql = "SELECT `id` FROM `model_types` 
                                    WHERE `organization_id` IS NULL AND (LOWER(`name`)=:name OR LOWER(`name_m`)=:name)"; 
                            $stm = pdo::getPdo()->prepare($sql);
                            $stm->execute(array('name' => $model_type_name));
                            $id = $stm->fetchColumn();
                        }
                        else
                        {
                            $id = $value;
                        }
                        
                   break;
                   default:
                        $id = 0;
               }
            
               if ($id)
               {                   
                    $insert[] = "('".$key."',".$id.",".$multi_dt_session_id.")";
               }
            }
        }
            
        if ($insert)
        {
            $sql = "INSERT INTO `multi_dt_tags` (`name_type`, `id_type`, `multi_dt_session_id`) VALUES ".implode(',', $insert);
            pdo::getPdo()->query($sql);
        }
    }
}