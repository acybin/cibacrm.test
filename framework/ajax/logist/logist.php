<?

namespace framework\ajax\logist;

use framework\ajax as ajax;
use framework\pdo;
use framework\tools;
use framework\enum;
use framework\load;
use framework\log;
use framework\ajax\order\privilegie;
use framework\ajax\cash\cash;
use framework\ajax\s_list\s_list;
use framework\shape\form as form;
use framework\ajax\term\term;
use framework\shape as shape;
use framework\dom\node;
use Dompdf\Dompdf;
use framework\ajax\get_log\get_log;

class logist extends ajax\ajax
{
    private $_log_counts = array();
    private $_mode_log = array('Отправить', 'Отправлен');
    
    public function __construct($args = array())
    {
        parent::__construct('list');
        $mode = isset($args['mode']) ? $args['mode'] : '';
        
        $this->getWrapper()->getAttributes()->addAttr('id', 'logist-'.$mode);
        
        $answer = ''; 
        
        switch ($mode)
        {   
             case 'metro':
             
                $page = isset($args['page']) ? $args['page'] : 1;  
                $organization_id = isset($args['organization_id']) ? $args['organization_id'] : load::get_org_id();
                
                $sql = "SELECT `citi_id` FROM `address` WHERE `organization_id`=:organization_id";
                $stm = pdo::getPdo()->prepare($sql);       
                $stm->execute(array('organization_id' => $organization_id));
                $citis = $stm->fetchAll(\PDO::FETCH_COLUMN);
                
                $q = isset($args['q']) ? $args['q'] : '';  
                
                $condition = array();
                
                $condition[] = "`citi_id` IN (".implode(',', $citis).")";
                
                if ($q)
                    $condition[] = "`name` LIKE '%$q%'";
                else
                    $condition[] = "1";
                    
                $page = ($page-1) * 30;
                
                $sql = "SELECT `name`, `id` FROM `metros` WHERE (".implode(' AND ', $condition).") ORDER BY `metros`.`name` ASC LIMIT $page,30";
                $datas = pdo::getPdo()->query($sql)->fetchAll(\PDO::FETCH_ASSOC);
                
                $sql = "SELECT count(*) FROM `metros` WHERE (".implode(' AND ', $condition).")"; 
                $total_count = pdo::getPdo()->query($sql)->fetchColumn();   
                
                $metros = [];
                    
                foreach ($datas as $value)
                    $metros[] = array('id' => $value['id'], 'text' => $value['name']);         
                
                $answer = array('items' => $metros, 'total_count' => $total_count, 'incomplete_results' => false);
                
             break;
             
             case 'update_record':
             
                $id = isset($args['id']) ? (integer) $args['id'] : 0;
                $field = isset($args['field']) ? (string) $args['field'] : '';
                $text = isset($args['text']) ? (string) $args['text'] : '';
                $organization_id = isset($args['organization_id']) ? $args['organization_id'] : load::get_org_id();
                
                if ($id && $field)
                {
                    $params = array('field' => $field);
                    
                    if ($field == 'price' || $field == 'price_fact')
                    {
                        $sql = "SELECT `status_logists`.`plus` FROM `logists` 
                                            LEFT JOIN `status_logists` ON `status_logists`.`id` = `logists`.`status_logist_id`
                                        WHERE `logists`.`id`=:id";
                        $stm = pdo::getPdo()->prepare($sql);       
                        $stm->execute(array('id' => $id));
                        $plus = $stm->fetchColumn();                      
                        
                        if ($text)
                        {
                            if ($plus !== null)
                            {
                                if ($plus == 0)
                                {
                                   $params['color'] = 'red';
                                   $text = '-' . $text;
                                }
                                else
                                {
                                    $params['color'] = 'green';
                                    $text = '+' . $text;
                                }
                            }
                        }                              
                    }
                    
                    if ($field == 'status_logist_id')
                    {
                        $logist_array = load::get_order($id, array('price', 'price_fact'), 'logists');                        

                        $obj_1 = new logist(array('mode' => 'update_record', 'field' => 'price', 'id' => $id, 'text' => $logist_array['price']));
                        $obj_2 = new logist(array('mode' => 'update_record', 'field' => 'price_fact', 'id' => $id, 'text' => $logist_array['price_fact']));
                        
                        /*if ((integer) $text == load::get_status('status_logists', 'sklad'))
                        {
                            $obj_3 = new logist(array('mode' => 'update_record', 'field' => 'order_id', 'id' => $id, 'text' => ''));     
                        }*/ 
                    }
                    
                    if ($field == 'metro_id')
                    {
                        $sql = "SELECT `line_id` FROM `metro_to_lines` WHERE `metro_id`=:metro_id";
                        $stm = pdo::getPdo()->prepare($sql);       
                        $stm->execute(array('metro_id' => (integer) $text));
                        $lines = $stm->fetchAll(\PDO::FETCH_COLUMN);
                        
                        if ($lines)
                        {
                            $enum = new enum();
                            $enum->setSign('');
                                                        
                            foreach ($lines as $line_id)
                            {
                                $subway = new node('span');
                                $subway->getAttributes()->getClass()->addItems('subway');
                                $subway->getAttributes()->getClass()->addItems(logist::metro_colour($line_id));
                                                
                                $enum->addItems($subway);
                            }
                            
                            $params['add_symbol'] = (string) $enum;
                        }                        
                    }
                    
                    $count = log::log_count('logists', $field, $id);
                        
                    if ($count > 1)
                    {
                        $params['log_count'] = $count;
                    }
                    
                    if ($field == 'status_logist_id')
                    {
                        $end = load::get_order((integer) $text, 'end', 'status_logists');
                        $go = (integer) load::get_order($id, 'go', 'logists');
                        
                        if (!$end)
                        {
                            $params['log_count'] = $this->_mode_log[$go];
                            
                            if ($go) $params['log_class'] = 'active';
                        }                        
                    }                 
                    
                    $notifys = new term(
                                array(
                                    'mode' => 'add', 
                                    'table' => 'notifys',
                                    'text' => $text, 
                                    'type_notify_id' => load::get_status('type_notifys', 'update_ciba_excel'),
                                    'table_name' => 'logists',
                                    'record_id' => $id, 
                                    'params' => serialize($params),
                                    'organization_id' => $organization_id,
                                    )
                                );
                }
             
             break;
             
             case 'check_returning':
             
                 $id = isset($args['id']) ? (integer) $args['id'] : 0;
                 $addr = isset($args['addres_id']) ? $args['addres_id'] : array();
                 $organization_id = isset($args['organization_id']) ? $args['organization_id'] : load::get_org_id();
                                  
                 if ($id)
                 {
                    $m_list = load::get_order($id, 'm_list', 'logists');
                    $type_f = load::get_order($id, 'type_f', 'logists');
                    
                    if ($m_list)
                    {
                        $pass = false;
                        
                        if ($m_list == 'бк')
                        {
                            $order_id = load::get_order($id, 'order_id', 'logists');
                            $addres_id = load::get_order($id, 'addres_id', 'logists');
                            
                            if ($order_id)
                            {
                                $sql = "SELECT `price`, `price_fact`, `courier`, `order_id`, `metro`, `id` FROM `logists` WHERE `id`=:id";
                                $stm = pdo::getPdo()->prepare($sql);       
                                $stm->execute(array('id' => $id));
                                $summs = $stm->fetchAll(\PDO::FETCH_ASSOC);
                                
                                $sql = "SELECT `id` FROM `cashs` WHERE `m_list`=:m_list AND `order_id`=:order_id AND `status_cash_id`=:status_cash_id AND `organization_id`=:organization_id";
                                $stm = pdo::getPdo()->prepare($sql);       
                                $stm->execute(array('m_list' => $m_list, 'order_id' => $order_id, 'status_cash_id' => load::get_status('status_cashs', 'returning'), 'organization_id' => $organization_id));
                                $cash_id = $stm->fetchColumn();
                                $pass = true;
                            }
                        }
                        else
                        {
                            $sql = "SELECT `price`, `price_fact`, `courier`, `order_id`, `metro`, `id` FROM `logists` WHERE `m_list`=:m_list AND `status_logist_id`=:status_logist_id";
                            $stm = pdo::getPdo()->prepare($sql);       
                            $stm->execute(array('m_list' => $m_list, 'status_logist_id' => load::get_status('status_logists', 'returning')));
                            $summs = $stm->fetchAll(\PDO::FETCH_ASSOC);
                            
                            $sql = "SELECT `id` FROM `cashs` WHERE `m_list`=:m_list AND `status_cash_id`=:status_cash_id AND `organization_id`=:organization_id";
                            $stm = pdo::getPdo()->prepare($sql);       
                            $stm->execute(array('m_list' => $m_list, 'status_cash_id' => load::get_status('status_cashs', 'returning'), 'organization_id' => $organization_id));
                            $cash_id = $stm->fetchColumn();
                            $pass = true;
                        }
                        
                        if ($pass)
                        {
                            if ($summs)
                            {
                                $t = 0; 
                                 
                                foreach ($summs as $sum)
                                {
                                    if ($sum['price_fact'])
                                    {
                                       $t_sum = (integer) $sum['price_fact'];
                                    }
                                    else
                                    {
                                       $obj_1 = new term(array('mode' => 'update', 'table' => 'logists', 'id' => $sum['id'], 'price_fact' => $sum['price']));
                                       $obj_2 = new logist(array('mode' => 'update_record', 'field' => 'price_fact', 'id' => $sum['id'], 'text' => $sum['price']));
                                       $t_sum = (integer) $sum['price']; 
                                    }
                                    
                                    $t += $t_sum;                         
                                }
                                
                                $summa = $t;
                                    
                                if (!$cash_id)
                                { 
                                   $term_array = array('mode' => 'add', 'table' => 'cashs', 'user_id' => 0, 'summ' => $summa,
                                         'status_cash_id' => load::get_status('status_cashs', 'returning'), 'comment' => 'Возврат по МЛ', 'm_list' => $m_list,
                                                'get' => 1021, 'put' => $sum['courier'], 'organization_id' => $organization_id, 'type_f' => $type_f);
                                                
                                   if ($m_list == 'бк') 
                                   {
                                       $term_array['order_id'] = $order_id;
                                       $term_array['addres_id'] = $addres_id;
                                   }
                                   
                                   $cash_obj = new term($term_array);
                                            
                                   $cash_id = $cash_obj->getWrapper()->getChildren(0);
                                   $cash_obj2 = new cash(array('mode' =>'copy', 'id' => $cash_id, 'addres_id' => $addr, 'type' => $type_f)); 
                                }
                                else
                                {
                                   $cash_obj = new term(array('mode' => 'update', 'table' => 'cashs', 'id' => $cash_id, 'summ' => $summa));         
                                }                                   
                          
                               $key = (integer) load::get_order($id, 'order_id', 'logists');
                               $value = (integer) load::get_order($id, 'price_fact', 'logists');                                   
                               $value_2 = (integer) load::get_order($id, 'price', 'logists');
                               
                               $value = ($value) ? $value : $value_2;
                               
                               if ($key)
                               {
                                   $p_summ = (integer) load::get_order($key, 'p_summ');
                                   $z_order_obj = new term(array('mode' =>'update', 'table' => 'orders', 'id' => $key, 'p_summ' => $p_summ - $value));
                               }
                                     
                            }
                        }
                    }
                 }
                
             break;
             
             case 'check_podr':
                
                 $id = isset($args['id']) ? (integer) $args['id'] : 0;
                 $addr = isset($args['addres_id']) ? $args['addres_id'] : array();
                 $organization_id = isset($args['organization_id']) ? $args['organization_id'] : load::get_org_id();
                 
                 if ($id)
                 {
                    $m_list = load::get_order($id, 'm_list', 'logists');
                    $type_f = load::get_order($id, 'type_f', 'logists');
                    
                    if ($m_list)
                    {
                        $pass = false;
                        
                        if ($m_list == 'бк')
                        {
                            $order_id = load::get_order($id, 'order_id', 'logists');
                            $addres_id = load::get_order($id, 'addres_id', 'logists');
                            
                            if ($order_id)
                            {
                                $sql = "SELECT `price`, `price_fact`, `courier`, `order_id`, `metro`, `id` FROM `logists` WHERE `id`=:id";
                                $stm = pdo::getPdo()->prepare($sql);       
                                $stm->execute(array('id' => $id));
                                $summs = $stm->fetchAll(\PDO::FETCH_ASSOC);
                                
                                $sql = "SELECT `id` FROM `cashs` WHERE `m_list`=:m_list AND `order_id`=:order_id AND `status_cash_id`=:status_cash_id AND `organization_id`=:organization_id";
                                $stm = pdo::getPdo()->prepare($sql);       
                                $stm->execute(array('m_list' => $m_list, 'order_id' => $order_id, 'status_cash_id' => load::get_status('status_cashs', 'podr'), 'organization_id' => $organization_id));
                                $cash_id = $stm->fetchColumn();
                                $pass = true;
                            }
                        }
                        else
                        {
                            $sql = "SELECT `price`, `price_fact`, `courier`, `order_id`, `metro`, `id` FROM `logists` WHERE `m_list`=:m_list AND `status_logist_id`=:status_logist_id";
                            $stm = pdo::getPdo()->prepare($sql);       
                            $stm->execute(array('m_list' => $m_list, 'status_logist_id' => load::get_status('status_logists', 'puting_aut')));
                            $summs = $stm->fetchAll(\PDO::FETCH_ASSOC);
                        
                            $sql = "SELECT `id` FROM `cashs` WHERE `m_list`=:m_list AND `status_cash_id`=:status_cash_id AND `organization_id`=:organization_id";
                            $stm = pdo::getPdo()->prepare($sql);       
                            $stm->execute(array('m_list' => $m_list, 'status_cash_id' => load::get_status('status_cashs', 'podr'), 'organization_id' => $organization_id));
                            $cash_id = $stm->fetchColumn(); 
                            $pass = true; 
                        }
                        
                        if ($pass)
                        {
                            if ($summs)
                            {
                                $t = 0;
                                 
                                foreach ($summs as $sum)
                                {
                                    if ($sum['price_fact'])
                                    {
                                       $t_sum = (integer) $sum['price_fact'];
                                    }
                                    else
                                    {
                                       $obj_1 = new term(array('mode' => 'update', 'table' => 'logists', 'id' => $sum['id'], 'price_fact' => $sum['price']));
                                       $obj_2 = new logist(array('mode' => 'update_record', 'field' => 'price_fact', 'id' => $sum['id'], 'text' => $sum['price']));
                                       $t_sum = (integer) $sum['price'];
                                    }
                                    
                                    $t += $t_sum;                                  
                                }
                                
                                $summa = $t;
                                
                                if (!$cash_id)
                                { 
                                   $term_array = array('mode' => 'add', 'table' => 'cashs', 'user_id' => 0, 'summ' => $summa,
                                         'status_cash_id' => load::get_status('status_cashs', 'podr'), 'comment' => 'Подряд по МЛ', 'm_list' => $m_list,
                                                'get' => $sum['courier'], 'put' => 1021, 'organization_id' => $organization_id, 'type_f' => $type_f);
                                                
                                   if ($m_list == 'бк') 
                                   {
                                       $term_array['order_id'] = $order_id;
                                       $term_array['addres_id'] = $addres_id;
                                   }
                                   
                                   $cash_obj = new term($term_array);
                                            
                                   $cash_id = $cash_obj->getWrapper()->getChildren(0);
                                   $cash_obj2 = new cash(array('mode' =>'copy', 'id' => $cash_id, 'addres_id' => $addr, 'type' => $type_f)); 
                                }
                                else
                                {
                                   $cash_obj = new term(array('mode' => 'update', 'table' => 'cashs', 'id' => $cash_id, 'summ' => $summa));         
                                }                                   
                               
                               $key = (integer) load::get_order($id, 'order_id', 'logists');
                               $value = (integer) load::get_order($id, 'price_fact', 'logists');
                               $value_2 = (integer) load::get_order($id, 'price', 'logists');
                                   
                               $value = ($value) ? $value : $value_2;
                                    
                               if ($key)
                               {
                                    $podr = (integer) load::get_order($key, 'podr');
                                    $z_order_obj = new term(array('mode' =>'update', 'table' => 'orders', 'id' => $key, 'podr' => $podr + $value));
                               }
                            }
                        }
                    }
                 }
             
             break;
             
             case 'check_cash':
             
                 $id = isset($args['id']) ? (integer) $args['id'] : 0;
                 $addr = isset($args['addres_id']) ? $args['addres_id'] : array();
                 $organization_id = isset($args['organization_id']) ? $args['organization_id'] : load::get_org_id();
                 
                 if ($id)
                 {
                    $m_list = load::get_order($id, 'm_list', 'logists');
                    $type_f = load::get_order($id, 'type_f', 'logists');
                      
                    if ($m_list)
                    {
                        $pass = false;
                        
                        if ($m_list == 'бк')
                        {
                            $order_id = load::get_order($id, 'order_id', 'logists');
                            $addres_id = load::get_order($id, 'addres_id', 'logists');
                            
                            if ($order_id)
                            {
                                $sql = "SELECT `price`, `price_fact`, `courier`, `order_id`, `metro`, `id` FROM `logists` WHERE `id`=:id";
                                $stm = pdo::getPdo()->prepare($sql);       
                                $stm->execute(array('id' => $id));
                                $summs = $stm->fetchAll(\PDO::FETCH_ASSOC);
                                
                                $sql = "SELECT `id` FROM `cashs` WHERE `m_list`=:m_list AND `order_id`=:order_id AND `status_cash_id`=:status_cash_id AND `organization_id`=:organization_id";
                                $stm = pdo::getPdo()->prepare($sql);       
                                $stm->execute(array('m_list' => $m_list, 'order_id' => $order_id, 'status_cash_id' => load::get_status('status_cashs', 'purchase_all'), 'organization_id' => $organization_id));
                                $cash_id = $stm->fetchColumn();
                                $pass = true;
                            }
                        }
                        else
                        {
                            $sql = "SELECT `price`, `price_fact`, `courier`, `order_id`, `metro`, `id` FROM `logists` WHERE `m_list`=:m_list AND `status_logist_id`=:status_logist_id
                                                                    AND `organization_id`=:organization_id AND `type_f`=:type_f";
                            $stm = pdo::getPdo()->prepare($sql);       
                            $stm->execute(array('m_list' => $m_list, 'status_logist_id' => load::get_status('status_logists', 'get'), 'organization_id' => $organization_id,
                                                                        'type_f' => $type_f));
                            $summs = $stm->fetchAll(\PDO::FETCH_ASSOC);
                        
                            $sql = "SELECT `id` FROM `cashs` WHERE `m_list`=:m_list AND `status_cash_id`=:status_cash_id AND `organization_id`=:organization_id
                                                                                                        AND `type_f`=:type_f";
                            $stm = pdo::getPdo()->prepare($sql);       
                            $stm->execute(array('m_list' => $m_list, 'status_cash_id' => load::get_status('status_cashs', 'purchase_all'), 'organization_id' => $organization_id,
                                                                                'type_f' => $type_f));
                            $cash_id = $stm->fetchColumn(); 
                            $pass = true; 
                        }
                        
                        if ($pass)
                        {
                            if ($summs)
                            {
                                $t = 0;
                                
                                foreach ($summs as $sum)
                                {
                                    if ($sum['price_fact'])
                                    {
                                       $t_sum = (integer) $sum['price_fact'];
                                    }
                                    else
                                    {
                                       $obj_1 = new term(array('mode' => 'update', 'table' => 'logists', 'id' => $sum['id'], 'price_fact' => $sum['price']));
                                       $obj_2 = new logist(array('mode' => 'update_record', 'field' => 'price_fact', 'id' => $sum['id'], 'text' => $sum['price']));
                                       $t_sum = (integer) $sum['price'];
                                    }
                                    
                                    $t += $t_sum;                                 
                                }
                                
                                $summa = $t;
                                
                                if (!$cash_id)
                                { 
                                   $term_array = array('mode' => 'add', 'table' => 'cashs', 'user_id' => 0, 'summ' => $summa,
                                         'status_cash_id' => load::get_status('status_cashs', 'purchase_all'), 'comment' => 'Закупка по МЛ', 'm_list' => $m_list,
                                                'get' => $sum['courier'], 'put' => 1021, 'organization_id' => $organization_id, 'type_f' => $type_f);
                                                
                                   if ($m_list == 'бк') 
                                   {
                                       $term_array['order_id'] = $order_id;
                                       $term_array['addres_id'] = $addres_id;
                                   }
                                   
                                   $cash_obj = new term($term_array);
                                            
                                   $cash_id = $cash_obj->getWrapper()->getChildren(0);
                                   $cash_obj2 = new cash(array('mode' =>'copy', 'id' => $cash_id, 'addres_id' => $addr, 'type' => $type_f)); 
                                }
                                else
                                {
                                   $cash_obj = new term(array('mode' => 'update', 'table' => 'cashs', 'id' => $cash_id, 'summ' => $summa));         
                                }                                   
                                
                               $key = (integer) load::get_order($id, 'order_id', 'logists');
                               $value = (integer) load::get_order($id, 'price_fact', 'logists');
                               $value_2 = (integer) load::get_order($id, 'price', 'logists');
                               
                               $value = ($value) ? $value : $value_2;
                                
                               if ($key)
                               {
                                   $p_summ = (integer) load::get_order($key, 'p_summ');
                                   $z_order_obj = new term(array('mode' =>'update', 'table' => 'orders', 'id' => $key, 'p_summ' => $p_summ + $value));
                               }
                            }
                        }
                    }
                 } 
                
             break;
             
             case 'reset_order':
                
                $id = isset($args['id']) ? (integer) $args['id'] : 0;
                $addres_id = isset($args['addres_id']) ? $args['addres_id'] : array();
                $organization_id = isset($args['organization_id']) ? $args['organization_id'] : load::get_org_id();
                $price_fact = isset($args['price_fact']) ? (bool) $args['price_fact'] : true;
                
                if ($id)
                {
                    $mas = load::get_order($id, array('amount', 'price_fact', 'price', 'union', 'name'), 'logists', false);
                    
                    if ($price_fact)
                        $price = ($mas['price_fact']) ? $mas['price_fact'] : $mas['price'];
                    else
                        $price = $mas['price'];
                        
                    $price_fact = $price * $mas['amount'];
                    
                    $term = new term(array('mode' => 'update', 'table' => 'logists', 'id' => $id, 'order_id' => null, 'addres_id' => null, 'price_fact' => $price_fact, 'price' => $price));
                    $logist_obj = new logist(array('mode' => 'update_row', 'id' => $id, 'addres_id' => $addres_id, 'organization_id' => $organization_id));
                    
                    if ($mas['union'])
                    {
                        $union_name = load::get_order($mas['union'], 'name', 'logists');
                        $text = $union_name . ' -'. $mas['name'];
                        $union_term_obj = new term(array('mode' => 'update', 'table' => 'logists', 'id' => $mas['union'], 'name' => $text));     
                        $union_term_notify = new logist(array('mode' => 'update_record', 'field' => 'name', 'id' => $mas['union'], 'text' => $text));
                    }
                }
             
             break;
             
             case 'update_row':
             
                $id = isset($args['id']) ? (integer) $args['id'] : 0;
                $addres_id = isset($args['addres_id']) ? $args['addres_id'] : array();
                $organization_id = isset($args['organization_id']) ? $args['organization_id'] : load::get_org_id();
                $type = isset($args['type']) ? $args['type'] : 0;
                
                $cash = new cash();
                   
                $html = $cash->html2($id, $addres_id, array(8,9,10,11,14,15,16), 'logist', $organization_id, $type);
                
                $notifys = new term(
                            array(
                                'mode' => 'add', 
                                'table' => 'notifys',
                                'text' => $html, 
                                'type_notify_id' => load::get_status('type_notifys', 'update_row_ciba_excel'),
                                'table_name' => 'logists',
                                'record_id' => $id,
                                'organization_id' => $organization_id,
                                'page' => $type,
                                )
                            );
                
                $answer = $id;
               
             break; 
             
             case 'sold':
             
                $id = isset($args['id']) ? (integer) $args['id'] : 0;
                $addres_id = isset($args['addres_id']) ? $args['addres_id'] : array();
                $organization_id = isset($args['organization_id']) ? $args['organization_id'] : load::get_org_id();
                $type_f = isset($args['type']) ? $args['type'] : 0;
                
                if ($id)
                {
                    $mas = load::get_order($id, array('order_id', 'name', 'price', 'amount', 'status_logist_id', 'type', 'addres_id', 'price_sold'), 'logists');
                    $order_id = $mas['order_id'];
                    $name = trim($mas['name']);
                    $price = $mas['price']; 
                    $amount = $mas['amount'];
                    $price_sold = $mas['price_sold'];
                    
                    $sold = load::get_status('status_logists', 'sold');
                    
                    $pass = true;
                    
                    /*if ($pass && !$order_id)
                    {
                        $this->setCode('error');
                                
                        $notifys = new term(
                            array(
                                'mode' => 'add', 
                                'table' => 'notifys',
                                'text' => 'Введите номер заказа!', 
                                'type_notify_id' => load::get_status('type_notifys', 'update_error'),
                                'session' => session_id(),
                                )
                            );
                            
                        $pass = false;
                    }*/
                    
                    if ($pass && $amount < 1)
                    {
                        $this->setCode('error');
                                
                        $notifys = new term(
                            array(
                                'mode' => 'add', 
                                'table' => 'notifys',
                                'text' => 'Нет на складе!', 
                                'type_notify_id' => load::get_status('type_notifys', 'update_error'),
                                'session' => session_id(),
                                )
                            );
                            
                        $pass = false;
                    }
                    
                    if ($pass && !$price_sold)
                    {
                        $this->setCode('error');
                                
                        $notifys = new term(
                            array(
                                'mode' => 'add', 
                                'table' => 'notifys',
                                'text' => 'Введите цену продажи!', 
                                'type_notify_id' => load::get_status('type_notifys', 'update_error'),
                                'session' => session_id(),
                                )
                            );
                            
                        $pass = false;
                    }
                     
                    if ($pass)
                    {                           
                        if ($order_id)
                        {
                            $sql = "SELECT `id` FROM `apparat_to_service_prices` WHERE `name`=:name AND `order_id`=:order_id AND `acs`=1 AND `price`=:price";
                            $stm = pdo::getPdo()->prepare($sql);       
                            $stm->execute(array('name' => $name, 'order_id' => $order_id, 'price' => $price_sold));
                            $service_id = $stm->fetchColumn();
                            
                            if ($service_id)
                            {
                                $sql = "UPDATE `apparat_to_service_prices` SET `amount` = `amount` + 1 WHERE `id`=:id";
                                $stm = pdo::getPdo()->prepare($sql);
                                $stm->execute(array('id' => $service_id));
                            }
                            else
                            {
                                $obj = new term(array('mode' => 'add', 'table' => 'apparat_to_service_prices', 'order_id' => $order_id, 
                                    'name' => $name, 'garantee' => 6, 'price' => $price_sold, 'amount' => 1, 'acs' => 1, 'click' => 0));
                            }
                            
                            $acs = (integer) load::get_order($order_id, 'acs') + $price_sold;                            
                            $order_obj = new term(array('mode' => 'update', 'table' => 'orders', 'id' => $order_id, 'acs' => $acs));
                        }
                        
                        $array = array('status_logist_id' => $sold, 'date' => date('Y-m-d H:i:s', tools::get_time()), 'price' => $price,
                                                        'amount' => 1, 'price_fact' => $price, 'name' => $name, 'type' => $mas['type'], 
                                                                'order_id' => $order_id, 'addres_id' => $mas['addres_id'], 'price_sold' => $price_sold);
                        $term = new term(array_merge(array('mode' => 'add', 'table' => 'logists'), $array));
                        
                        //cash
                        if (!$order_id)
                        {
                            $term_array = array('mode' => 'add', 'table' => 'cashs', 'user_id' => 0, 'summ' => $price_sold,
                                 'status_cash_id' => load::get_status('status_cashs', 'pay'), 'comment' => 'Продажа акссесуаров ('.$name.')', 
                                        'get' => 1020, 'organization_id' => $organization_id, 'type_f' => $type_f);
                                        
                            $cash_obj = new term($term_array);
                                    
                            $cash_id = $cash_obj->getWrapper()->getChildren(0);
                            $cash_obj2 = new cash(array('mode' =>'copy', 'id' => $cash_id, 'addres_id' => $addres_id, 'type' => $type_f));
                        }                            
                        //end cash
                        
                        if ($amount > 1)
                        {
                            $amount = $amount - 1;
                            
                            $logist_term = new term(array('mode' => 'update', 'table' => 'logists', 'id' => $id, 'amount' => $amount));
                            $logist_obj = new logist(array('mode' => 'reset_order', 'id' => $id, 'addres_id' => $addres_id, 'organization_id' => $organization_id, 'price_fact' => false));
                        }
                        else
                        {
                            $logist_term = new term(array('mode' => 'delete', 'table' => 'logists', 'id' => $id));
                            $logist_obj = new logist(array('mode' => 'delete', 'id' => $id));        
                        }
                    }
                }
             
             break;
             
             case 'check_courier':
             
                $m_list = isset($args['m_list']) ? (string) $args['m_list'] : '';
                $organization_id = isset($args['organization_id']) ? $args['organization_id'] : load::get_org_id();
                
                if ($m_list)
                {
                    $this->_check_courier($m_list, $organization_id);
                }
                
             break;
             
             case 'check_go':
             
                $id = isset($args['id']) ? (integer) $args['id'] : 0;
                $value = load::get_order($id, 'go', 'logists');
                
                $logist_obj = new logist(array('mode' => 'm_go', 'id' => $id, 'value' => $value, 'check_go' => true));
                
             break;
             
             case 'copy':
             
                $id = isset($args['id']) ? (integer) $args['id'] : 0;
                $addres_id = isset($args['addres_id']) ? $args['addres_id'] : array();
                $organization_id = isset($args['organization_id']) ? $args['organization_id'] : load::get_org_id();
                $type = isset($args['type']) ? $args['type'] : 0;
                
                if ($id)
                {
                    $array = load::get_order($id, 
                                    array('order_id', 'addres_id', 'type', 'name', 'metro', 'address', 'comment', 'price', 'price_fact', 'metro_id'), 
                                'logists');
                    
                    $term = new term(array_merge(array('mode' => 'add', 'table' => 'logists', 'type_f' => $type), $array));
                    $id = $term->getWrapper()->getChildren(0);  
                    
                    $cash = new cash();   
                    
                    $html = $cash->html2($id, $addres_id, array(8,9,10,11,14,15,16), 'logist', $organization_id, $type); 
                  
                    $notifys = new term(
                            array(
                                'mode' => 'add', 
                                'table' => 'notifys',
                                'text' => $html, 
                                'type_notify_id' => load::get_status('type_notifys', 'add_ciba_excel'),
                                'table_name' => 'logists',
                                'record_id' => $id,
                                'organization_id' => $organization_id,
                                'page' => $type,
                                )
                            );
                }
             break;
             
             case 'delete':
                
                 $id = isset($args['id']) ? (integer) $args['id'] : 0;
                 
                 $obj = new term(array('mode' => 'update', 'table' => 'logists', 'id' => $id, 
                            'delete' => 1, 'delete_user' => load::get_user_id(), 'delete_timestamp' => date('Y-m-d H:i:s', tools::get_time())));
                            
                 $organization_id = isset($args['organization_id']) ? $args['organization_id'] : load::get_org_id();
                 $type = isset($args['type']) ? $args['type'] : 0;
                 
                 $notifys = new term(
                        array(
                            'mode' => 'add', 
                            'table' => 'notifys',
                            'text' => '', 
                            'type_notify_id' => load::get_status('type_notifys', 'delete_ciba_excel'),
                            'table_name' => 'logists',
                            'record_id' => $id,
                            'organization_id' => $organization_id,
                            )
                        );
                
             break;
             
             case 'add_new':
             
                $addres_id = isset($args['addres_id']) ? $args['addres_id'] : array();
                $organization_id = isset($args['organization_id']) ? $args['organization_id'] : load::get_org_id();
                $type = isset($args['type']) ? $args['type'] : 0;
                 
                $obj = new term(array('mode' => 'add', 'table' => 'logists', 'organization_id' => $organization_id, 'type_f' => $type));
                $id = $obj->getWrapper()->getChildren(0);             
             
                $cash = new cash();
                   
                $html = $cash->html2($id, $addres_id, array(8,9,10,11,14,15,16), 'logist', $organization_id, $type);  
                $notifys = new term(
                            array(
                                'mode' => 'add', 
                                'table' => 'notifys',
                                'text' => $html, 
                                'type_notify_id' => load::get_status('type_notifys', 'add_ciba_excel'),
                                'table_name' => 'logists',
                                'record_id' => $id,
                                'organization_id' => $organization_id,
                                'page' => $type,
                                )
                            );
                
                 
                 
             break;
             
             case 'change_date':
             
                $id = isset($args['id']) ? (integer) $args['id'] : 0;
                $date = isset($args['date']) ? (string) $args['date'] : '';
                $field = isset($args['field']) ? (string) $args['field'] : '';
                
                if ($id && in_array($field, array('date', 'deadline')))
                {
                    if (!$date) $date = null;
                        
                    $obj = new term(array('mode' => 'update', 'table' => 'logists', 'id' => $id, $field => $date));
                }
                
             break;
             
             case 'go':
             
                $id = isset($args['id']) ? (integer) $args['id'] : 0;
                $table = isset($args['table']) ? (string) $args['table'] : 'logists';
                
                if ($id)
                {
                    $order_id = load::get_order($id, 'order_id', $table);
                    if ($order_id)
                    {
                        $answer = tools::encode($order_id);     
                    }
                    else
                    {
                        $this->setCode('error');
                    }
                }
                
             break;
             
             case 'm_go':
             
                $id = isset($args['id']) ? (integer) $args['id'] : 0;
                $value = isset($args['value']) ? (integer) $args['value'] : 0;
                $check_go = isset($args['check_go']) ? (integer) $args['check_go'] : false;
                
                if ($id)
                {
                     if (!$check_go) 
                     {
                        $obj_1 = new term(array('mode' => 'update', 'table' => 'logists', 'id' => $id, 'go' => $value));
                     }  
                     
                     $logist_array = load::get_order($id, array('order_id', 'status_logist_id', 'm_list', 'courier'), 'logists');
                     $order_id = $logist_array['order_id'];
                     $end = load::get_order($logist_array['status_logist_id'], 'end', 'status_logists');
                      
                     if ($order_id && $logist_array['status_logist_id'])
                     {
                        $new_status_order_id = load::get_order($order_id, 'new_status_order_id');
                                
                        $name = '';
                        $user_id = $logist_array['courier'];
                        
                        if ($logist_array['courier'])
                        { 
                            $sql = "SELECT `workers`.`name` as `name`, `users`.`name` as `name2`
                                            FROM `users` 
                                    LEFT JOIN `workers` ON `users`.`worker_id` = `workers`.`id`
                                        WHERE `users`.`id`=:user_id";
                            $stm = pdo::getPdo()->prepare($sql);       
                            $stm->execute(array('user_id' => $logist_array['courier']));
                            $name = $stm->fetchAll(\PDO::FETCH_ASSOC);
                            
                            if ($name)
                            {
                                $name = current($name);
                                $name = ' "' . tools::cut_user_name($name['name'], $name['name2']) . '"';
                            }                       
                        }
                        
                        $status_name = mb_strtolower(load::get_order($logist_array['status_logist_id'], 'name', 'status_logists'));
                        
                        $zapchast_array = array('купить', 'обменять', 'вернуть', 'не купил', 'не обменял', 'не вернул', 'купил', 'обменял', 'вернул');
                        $apparat_array = array('отвезти', 'забрать', 'не отвез', 'не забрал', 'отвез', 'забрал'); 
                        
                        if (in_array($status_name, $zapchast_array))                                                
                            $status_name = $status_name. ' запчасть';
                            
                        if (in_array($status_name, $apparat_array))
                            $status_name = $status_name. ' аппарат';
                            
                        $m_list = $logist_array['m_list'] ? ' по МЛ ' . $logist_array['m_list'] : '';
                        
                        $text = '';
                        $sklad = load::get_status('status_logists', 'sklad');
                        
                        if ($logist_array['status_logist_id'] == $sklad)
                        {
                            $text = 'Положил запчасть на склад';    
                        }
                        else
                        {
                            if ($logist_array['m_list'] == 'бк')
                            {
                                $text = tools::mb_ucfirst2(trim($name .' '.$status_name));    
                            }
                            else
                            {    
                                if ($end)
                                {
                                    $text = 'Курьер' .$name .' '.$status_name. $m_list;
                                    
                                    //end_day
                                    if ($user_id)
                                    {
                                        $get_log_obj = new get_log(array('mode' => 'new_notifys', 'user_id' => $user_id));
                                        
                                        $day = date('Y-m-d', tools::get_time());
                                        
                                        $sql = "SELECT `id`, `dinner_time` FROM `calendars` WHERE `day`=:day AND `user_id`=:user_id";
                                        $stm = pdo::getPdo()->prepare($sql);       
                                        $stm->execute(array('day' => $day, 'user_id' => $user_id));
                                        $calendar_array = $stm->fetchAll(\PDO::FETCH_ASSOC);
                                        
                                        if ($calendar_array)
                                        {
                                            $calendar_array = current($calendar_array);
                                            $dinner_time = $calendar_array['dinner_time'];
                                            
                                            $sql = "UPDATE `calendars` SET `work_time` = `work_time` + {$dinner_time}, `dinner_time` = 0 WHERE `id`=:id";
                                            $stm = pdo::getPdo()->prepare($sql);       
                                            $stm->execute(array('id' => $calendar_array['id']));
                                        }                                   
                                    }
                                }
                                else
                                {
                                    if ($value)
                                    {
                                        $text = 'Отправил курьера' .$name .' '.$status_name. $m_list;
                                          
                                        //start_day
                                        if ($user_id)
                                        {
                                            $current_time = tools::get_time();
                                            $day = date('Y-m-d', $current_time);
                                            
                                            $sql = "SELECT `id` FROM `calendars` WHERE `day`=:day AND `user_id`=:user_id";
                                            $stm = pdo::getPdo()->prepare($sql);       
                                            $stm->execute(array('day' => $day, 'user_id' => $user_id));
                                            $calendar_array = $stm->fetchColumn();
                                            
                                            if (!$calendar_array)
                                            {
                                                $get_log_obj = new get_log(array('mode' => 'new_notifys', 'user_id' => $user_id));
                                                $get_log_obj = new get_log(array('mode' => 'new_notifys', 'user_id' => $user_id));
                                            }
                                            else
                                            {
                                                $online_array = array('table' => 'users', 'mode' => 'update', 'id' => $user_id, 'online' => $current_time);
                                                $online = new term($online_array);              
                                            }
                                        }
                                        
                                    }
                                    else
                                    {
                                        if ($check_go == false)
                                        {
                                            $text = 'Отменил курьера';                      
                                        }
                                    }
                                }
                            }
                        } 
                         
                        if ($text)
                        {    
                            $obj_comment = new term(array('mode' => 'add', 'table' => 'chats', 'text' => htmlspecialchars($text), 
                                'order_id' => $order_id, 'publish' => 1, 'new_status_order_id' => $new_status_order_id));
                        }  
                     }
                     
                     $status_logist_id = $logist_array['status_logist_id'];
                     $obj_2 = new logist(array('mode' => 'update_record', 'field' => 'status_logist_id', 'id' => $id, 'text' => $status_logist_id));
                }
                
             break;
             
             case 'ml':
                    
                  $ml = isset($args['ml']) ? (string) $args['ml'] : '';
                  $organization_id = isset($args['organization_id']) ? $args['organization_id'] : load::get_org_id();
                  $type = isset($args['type']) ? $args['type'] : 0;
                  $pass = false;
                  
                  if ($ml)
                  {
                     $sql = "SELECT `orders`.`number`, `address`.`code`, `status_logists`.`name` as `status`,
                                 `logists`.`type`, `logists`.`name`, `metros`.`name` as `metro_name`, `logists`.`address`,
                                            `logists`.`comment`, `logists`.`price`, `workers`.`name` as `w_name`,
                                                `users`.`name` as `w_name2`
                                         FROM `logists`
                            LEFT JOIN `status_logists` ON `status_logists`.`id` = `logists`.`status_logist_id`
                              LEFT JOIN `users` ON `logists`.`courier` = `users`.`id` 
                              LEFT JOIN `workers` ON `users`.`worker_id` = `workers`.`id`
                              LEFT JOIN `orders` ON `orders`.`id` = `logists`.`order_id`
                              LEFT JOIN `address` ON `orders`.`addres_id` = `address`.`id`
                              LEFT JOIN `metros` ON `logists`.`metro_id` = `metros`.`id`
                                    WHERE `m_list`=:m_list AND `logists`.`organization_id`=:organization_id
                                            AND `logists`.`type_f`=:type"; //tools::debug($sql);
                                          
                     $stm = pdo::getPdo()->prepare($sql);      
                     $stm->execute(array('m_list' => $ml, 'organization_id' => $organization_id, 'type' => $type));
                     //tools::debug(array('m_list' => $ml, 'organization_id' => $organization_id));
                     $array = $stm->fetchAll(\PDO::FETCH_ASSOC);
                     
                     //print_r($array);
                     
                     if ($array)
                     {
                        $dompdf = new Dompdf(array('logOutputFile'=> \DOCUMENT_ROOT.'/Dompdf/tmp/log.htm'));
                        
                        $html = '<html><head><meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
                                <style>
                                @page {margin: 36px;}
                                body {font-family:DejaVu Sans; color: #000; font-size: 8px;}
                                table {width: 100%; border-spacing: 0px; border-collapse: collapse;}
                                table td, table th {color: #000; font-size: 8px; padding: 0.5em; vertical-align: middle; border: 1px solid #000; text-align: left;}
                                table.head {width:100%;}      
                                td.first, th.first {width: 80px;}                      
                                table.container td {border: none; vertical-align: top;}
                                table.container table.head td {border: 1px solid #000; vertical-align: middle;}
                                p {margin: 0 0 1em 0;} 
                                h3 {font-weight: bold; text-align:center; margin: 1em 0;}
                                </style></head><body>';
                                
                        $html .= '<table class="container"><tbody><tr><td width="25%">';
                        
                        $html .= '<table class="head"><tbody><tr><td class="first">Дата</td><td>'.date('d.m.y H:i', tools::get_time()).'</td></tr>';
                        
                        $user = current($array);
                        
                        $html .= '<tr><td class="first"><b>Курьер</b></td><td>'.tools::cut_user_name($user['w_name'], $user['w_name2']).'</td></tr>';
                        $html .= '<tr><td class="first">№ МЛ</td><td>'.$ml.'</td></tr></tbody></table>';
                        
                        $html .= '</td><td width="50%"></td><td width="25%">';
                        
                        $html .= '<table class="head"><tbody><tr><td class="first"><b>Итого</b></td><td></td></tr>';                        
                        $html .= '<tr><td class="first">Выдано</td><td></td></tr>';
                        $html .= '<tr><td class="first">Остаток</td><td></td></tr>';
                        $html .= '<tr><td class="first"><b>На руках</b></td><td></td></tr></tbody></table>';
                        
                        $html .= '</td></tr></tbody></table>';
                        
                        
                        $html .= '<table style="margin-top:5em;"><thead>
                                    <tr>
                                         <th class="first">Заказ</th>
                                         <th>Что сделать</th>
                                         <th>Тип</th>
                                         <th>Наименование</th>
                                         <th>Метро</th>
                                         <th>Адрес</th>
                                         <th>Комментарий</th>
                                         <th class="first">Цена</th>
                                </tr></thead><tbody>';
                        
                        foreach ($array as $value)
                        {
                            $html .= '<tr>';
                            $html .= '<td class="first">'.$value['number'].' '.$value['code'].'</td>';
                            $html .= '<td>'.$value['status'].'</td>';
                            $html .= '<td>'.$value['type'].'</td>';
                            $html .= '<td>'.$value['name'].'</td>';
                            $html .= '<td>'.$value['metro_name'].'</td>';
                            $html .= '<td>'.$value['address'].'</td>';
                            $html .= '<td>'.$value['comment'].'</td>';
                            $html .= '<td class="first">'.$value['price'].'</td>';
                            
                            $html .= '</tr>';
                        }
                        
                        $html .= '</tbody></table>'; //echo $html;
                        
                        $dompdf->loadHtml($html);
                        $dompdf->setPaper('A4', 'landscape');
                        $dompdf->render();
                        $output = $dompdf->output(array('compress' => 0));
                        
                        $answer = 'uploads/pdf/ml/'.$ml.'.pdf';
                        tools::file_force_contents(\DOCUMENT_ROOT.$answer, $output);
                        
                        $pass = true;
                     }
                  }
                  
                  if (!$pass)
                  {
                      $this->setCode('error');
                                
                      $notifys = new term(
                            array(
                                'mode' => 'add', 
                                'table' => 'notifys',
                                'text' => 'Маршрутный лист не найден!', 
                                'type_notify_id' => load::get_status('type_notifys', 'update_error'),
                                'session' => session_id(),
                                )
                            );
                  }
             
             break;
             
             case 'block':
             
                $id = isset($args['id']) ? (integer) $args['id'] : 0;
                $block = isset($args['block']) ? (integer) $args['block'] : 0;
                
                $table = isset($args['table']) ? (string) $args['table'] : 'logists';
             
                if ($id)
                {
                    if ($block) 
                    {
                        $block = date('Y-m-d H:i:s', tools::get_time());
                        $user_id = load::get_user_id();
                    
                        if ($user_id)
                        {    
                            $term = new term(array('mode' => 'update', 'table' => $table, 'id' => $id, 'block_user' => $user_id, 'block' => $block));                           
                        }
                    }    
                }
                
             break;
             
             case 'filter':                
                
                $addres_id = isset($args['addres_id']) ? $args['addres_id'] : array();
                $status_logist_id = isset($args['status_logist_id']) ? $args['status_logist_id'] : array();
                
                list($h, $i, $s, $n, $j, $Y) = array(date("H"),date("i"),date("s"),date("n"),date("j"),date("Y"));
                
                $start_date = isset($args['start_date']) ? $args['start_date'] : date('Y-m-d H:i:s', mktime(0, 0, 0, 1, 1, 2017)); 
                $end_date =  isset($args['end_date']) ? $args['end_date'] : date('Y-m-d H:i:s', mktime(23, 59, 59, $n, $j, $Y));
                $organization_id = isset($args['organization_id']) ? $args['organization_id'] : load::get_org_id();
                
                $enum = new enum();
                $enum->setSign(''); 
                            
                $select = new form\select();
                                   
                $filter = array();
                
                $filter[] = "`logists`.`date` >= '{$start_date}'"; 
                $filter[] = "`logists`.`date` <= '{$end_date}'";
                $filter[] = "`logists`.`organization_id` = ".$organization_id;       
                       
                $filter = " WHERE (".implode(") AND (", $filter).")" ;
                 
                $sql = "SELECT `users`.`id`, `workers`.`name` as `name`,
                                        `users`.`name` as `name2`, 
                                            `users`.`not_active` as `not_active`
                                FROM `logists` 
                                  LEFT JOIN `users` ON `users`.`id` = `logists`.`courier`
                                    LEFT JOIN `workers` ON `users`.`worker_id` = `workers`.`id`   
                                       LEFT JOIN `access` ON `access`.`user_id` = `users`.`id`                
                                        {$filter} GROUP BY `users`.`id` ORDER BY `workers`.`name` ASC";
                         
                $users = pdo::getPdo()->query($sql)->fetchAll(\PDO::FETCH_ASSOC);
                
                $t = array();
                $not_active = array();
                
                $last_users_1 = array();
                $last_users_2 = array();
                $include_last_users = array();
                
                foreach ($users as $user)
                {
                    $user['id'] = (integer) $user['id'];
                    $not_active[$user['id']] = $user['not_active'];
                    
                    if (in_array($user['id'], $include_last_users))
                    {
                        $last_users_1[$user['id']] = $user;  
                        continue;
                    }
                    
                    if ($user['not_active'])
                    {
                        $last_users_2[] = $user;
                        continue;
                    }
                    
                    $t[$user['id']] = tools::cut_user_name($user['name'], $user['name2']);
                }
                
                krsort($last_users_1);
                
                foreach (array_merge($last_users_1, $last_users_2) as $user)
                {
                    $t[$user['id']] = tools::cut_user_name($user['name'], $user['name2']);
                }
                
                $users = $t; 
                
                $name = 'courier';
                
                $select->setName($name);
                $select->setValues(array('0' => 'Все') + $t);              
                    
                if (isset($_COOKIE[$name])) 
                {
                    $select->setValue($_COOKIE[$name]);
                    
                    if ($_COOKIE[$name])
                    {
                        if (in_array($_COOKIE[$name], array_keys($users)))
                        {
                            $select->getAttributes()->getClass()->addItems('passed');
                        }
                    }
                }
                
                $options = $select->getChildren('options');
                    
                foreach ($select->getValues() as $key => $value)
                {
                    if (isset($not_active[$key]))
                    {
                        if ($not_active[$key])
                        {
                            $option = $options->getItems($key);
                            $option->getAttributes()->addAttr('data-not_active', 1);
                        }
                    }
                }
                
                $enum->addItems(s_list::write_people('Курьер', $select));
                
                $answer = $enum;
                
             break;
             
             case 'minus':
                
                $id = isset($args['id']) ? (integer) $args['id'] : 0;
                $addres_id = isset($args['addres_id']) ? $args['addres_id'] : array();
                $organization_id = isset($args['organization_id']) ? $args['organization_id'] : load::get_org_id();
                
                if ($id)
                {
                    $term = new term(array('mode' => 'add', 'table' => 'logists', 'organization_id' => $organization_id, 'union' => $id));
                    $id = $term->getWrapper()->getChildren(0);  
                    
                    $cash = new cash();   
                    
                    $html = $cash->html2($id, $addres_id, array(8,9,10,11,14,15,16), 'logist', $organization_id);                    
                    $notifys = new term(
                            array(
                                'mode' => 'add', 
                                'table' => 'notifys',
                                'text' => $html, 
                                'type_notify_id' => load::get_status('type_notifys', 'add_ciba_excel'),
                                'table_name' => 'logists',
                                'record_id' => $id,
                                'organization_id' => $organization_id,
                                )
                            );
                }
                
             break;
             
             case 'number':
             
               $addres_id = isset($args['addres_id']) ? $args['addres_id'] : array();
               $organization_id = isset($args['organization_id']) ? $args['organization_id'] : load::get_org_id();
               
               $value = isset($args['value']) ? (string) $args['value'] : '';
               $id = isset($args['id']) ? (integer) $args['id'] : 0;
               
               $table = isset($args['table']) ? (string) $args['table'] : 'logists';
               
               $pass = false;
                       
               if ($id)
               {
                    $value = trim($value);
                    
                    if ($value)
                    {
                        $sql = "SELECT `orders`.`id` as `id`, `address`.`code` as `code`, `orders`.`number` as `number`, 
                                            `address`.`id` as `addres_id` FROM `orders`
                                        LEFT JOIN `address` ON `orders`.`addres_id` = `address`.`id` 
                                    WHERE `orders`.`number`=:number AND `orders`.`organization_id`=:organization_id
                                            AND `orders`.`addres_id` IN (".implode(',', $addres_id).")";            
                        $stm = pdo::getPdo()->prepare($sql); 
    
                        $stm->execute(array('number' => $value, 'organization_id' => $organization_id));
                        $order_id = $stm->fetchAll(\PDO::FETCH_ASSOC);
                        
                        if ($order_id)
                        {
                            $pass = true;
                            $order_id = current($order_id);
                            
                            //tools::debug($order_id);
                            
                            $term = new term(array('mode' => 'update', 'table' => $table, 'id' => $id, 
                                            'addres_id' => $order_id['addres_id'], 'order_id' => $order_id['id']));
                                            
                            $params = array('field' => 'order_id');
                            $count = log::log_count($table, 'order_id', $id);
                            
                            if ($count > 1)
                            {
                                $params['log_count'] = $count;
                            }                                
                            
                            $notifys = new term(
                                array(
                                    'mode' => 'add', 
                                    'table' => 'notifys',
                                    'text' => $order_id['number'] . ' ' .$order_id['code'], 
                                    'type_notify_id' => load::get_status('type_notifys', 'update_ciba_excel'),
                                    'table_name' => $table,
                                    'record_id' => $id, 
                                    'params' => serialize($params),
                                    'organization_id' => $organization_id,
                                    )
                                );
                        }
                    }
                    else
                    {
                         $term = new term(array('mode' => 'update', 'table' => $table, 'id' => $id, 
                                                'addres_id' => null, 'order_id' => null));
                                                
                         $params = array('field' => 'order_id');
                         $count = log::log_count($table, 'order_id', $id);
                            
                         if ($count > 1)
                         {
                            $params['log_count'] = $count;
                         }                                                 
                                                
                        $notifys = new term(
                                    array(
                                        'mode' => 'add', 
                                        'table' => 'notifys',
                                        'text' => '', 
                                        'type_notify_id' => load::get_status('type_notifys', 'update_ciba_excel'),
                                        'table_name' => $table,
                                        'record_id' => $id, 
                                        'params' => serialize($params),
                                        )
                                    );                                                
                                                
                        //$answer = ''; 
                        $pass = true;                       
                    }
               }
               
               if (!$pass)
               { 
                   $this->setCode('error');
                                
                   $notifys = new term(
                            array(
                                'mode' => 'add', 
                                'table' => 'notifys',
                                'text' => 'Заказ не найден!', 
                                'type_notify_id' => load::get_status('type_notifys', 'update_error'),
                                'session' => session_id(),
                                )
                            );
               }
                
             break;
             
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
                    $start_date = date('Y-m-d H:i:s', mktime(0, 0, 0, 1, 1, 2017));
                    
                if (isset($dop_filter['end_date']))
                {
                    $end_date = $dop_filter['end_date'];
                    unset($dop_filter['end_date']);
                }
                else
                    $end_date = date('Y-m-d H:i:s', mktime(23, 59, 59, $n, $j, $Y));
                                
               $group_code = load::get_group_code();                    
               $is_boss_2 = in_array($group_code, array('moderator', 'director', 'administrator'));                
                
                if ($s_mode)
                {
                    $tbody = array();                    
                    $filter = array();
                    
                    $array = array(1,2,3,4,6,7,8,9);
                    $current_time = tools::get_time();
                    
                    $current_day = mktime(23, 59, 59, $n, $j, $Y);
                    
                    $block_time = 5;
                    
                    $sql = "SELECT `id`, `name`, `color`, `end`, `plus` FROM `status_logists` ORDER BY `weight` ASC";
                    $status_logists = pdo::getPdo()->query($sql)->fetchAll(\PDO::FETCH_ASSOC);  
                    
                    $metro_null = array(0 => 'Не задан');
                    $metros = array();
                    $metro_to_lines = array();
                    
                    if (isset($dop_filter['addres_id']))
                    {
                        $addr_id = $dop_filter['addres_id'];
                        
                        $sql = "SELECT `citi_id` FROM `address` WHERE `id` IN (".implode(',', $addr_id).")";
                        $citis = pdo::getPdo()->query($sql)->fetchAll(\PDO::FETCH_COLUMN);
                        
                        $sql = "SELECT `id`, `name` FROM `metros` WHERE `citi_id` IN (".implode(',', $citis).") ORDER BY `name` ASC";
                        $metros = pdo::getPdo()->query($sql)->fetchAll(\PDO::FETCH_KEY_PAIR);  
                        
                        if ($metros)
                        {                
                            $sql = "SELECT `metro_id`, `line_id` FROM `metro_to_lines` WHERE `metro_id` IN (".implode(',', array_keys($metros)).")";
                            
                            $t = array();
                            $metro_to_lines = pdo::getPdo()->query($sql)->fetchAll(\PDO::FETCH_ASSOC);
                             
                            foreach ($metro_to_lines as $value)
                                $t[$value['metro_id']][] = $value['line_id'];
                                
                            $metro_to_lines = $t;
                        }
                    }   
                      
                    $get = 5; 
                    $sklad = 4;
                    $sold = 24;
                    $sklad_clears = array('m_list', 'deadline', 'address', 'metro', 'courier');
                                       
                    $red_statuses = array();
                    $yellow_statuses = array();
                    $green_statuses = array();
                    $ends = array();
                    
                    $organization_id = $dop_filter['organization_id'];
                    
                    $t = array();
                    $pluses = array();
                    
                    foreach ($status_logists as $value)
                    {
                        $t[$value['id']] = $value['name'];
                        switch ($value['color'])
                        {
                            case 0:
                                $red_statuses[] = $value['id'];
                            break; 
                            case 1:
                                $yellow_statuses[] = $value['id'];
                            break;     
                            case 2:
                                $green_statuses[] = $value['id'];
                            break;                      
                        }
                        
                        if ($value['end']) $ends[] = $value['id'];
                        $pluses[$value['id']] = $value['plus'];
                    }
                    
                    $ex_ends = $ends;
                    //$ex_ends = array(5, 11, 22);   
                        
                    $status_logists = array(0 => 'Не задан') + $t;                    
                    
                    $exclude_groups = $this->_exclude_groups();
                        
                    $addr_id = $dop_filter['addres_id'];
                    
                    $search = '';
                    if (isset($dop_filter['search']))
                    {
                        $search = tools::cut_str($dop_filter['search']);
                        unset($dop_filter['search']);
                    }
                    
                    //unset($dop_filter['organization_id']);
                    
                   $label_courier = false;
                   
                   if (isset($dop_filter['status_logist_id']))
                   {
                       $filter_status = $dop_filter['status_logist_id'];
                       $cr = current($filter_status);
                       
                       if (count($filter_status) == 1 && ($cr == $sold || $cr == $sklad))
                       {
                            $label_courier = true;     
                       }
                    }
                    
                    if ($group_code == 'acceptor') $label_courier = true;                    
                    
                    foreach ($dop_filter as $key => $value)
                    {
                        $dont_use = false;
                                                
                        if (is_array($value))
                        {
                           if ($value)
                           {
                               if ($key == 'status_logist_id')
                               {                           
                                   if (count($value) == 1 && current($value) == 100)
                                   {
                                        $filter[] = "`logists`.`{$key}` != $get OR `logists`.`$key` IS NULL OR `logists`.`$key` = 0";
                                        $dont_use = true;   
                                   }
                               }
                               
                               if (!$dont_use)
                               {
                                    if ($key == 'addres_id' || $key == 'status_logist_id')
                                        $filter[] = "`logists`.`$key` IN (".implode(',', $value).") OR `logists`.`$key` IS NULL OR `logists`.`$key` = 0";
                                    else
                                        $filter[] = "`logists`.`$key` IN (".implode(',', $value).")";  
                               }
                           }                         
                        }
                        else
                        {
                           if (!(($key == 'courier') && $value == 0))
                           {
                                //if (!$dont_courier)
                                //{
                                    $key = "`logists`.`{$key}`";
                                    $filter[] = "$key = $value";                         
                                //}
                           } 
                        }
                    }
                    
                    $filter[] = "((`logists`.`date` >= '{$start_date}' AND `logists`.`date` <= '{$end_date}') OR `logists`.`date` IS NULL OR `logists`.`date` = '0000-00-00 00:00:00')";
                    $filter[] = "(`logists`.`delete` IS NULL OR `logists`.`delete` = 0)";
                    //if (load::get_user_id() != 1)
                        //$filter[] = "`logists`.`id` not in(382,383,384,385)";
                    
                    $filter2 = array();
                        
                    if ($search)
                    {
                        foreach (array('`orders`.`number`', '`logists`.`type`', '`logists`.`name`',
                                '`logists`.`metro`', '`logists`.`address`', '`logists`.`comment`', '`logists`.`price`', '`logists`.`price_fact`',
                                        '`logists`.`m_list`', '`workers`.`name`') as $field)
                        {
                            $filter2[] = "$field LIKE '%{$search}%'";
                        }
                    }         
                    
                    if ($filter2)
                    {
                        $filter2 = "(".implode(") OR (", $filter2).")";
                        $filter[] = $filter2;
                    }  
                   
                    if ($filter)
                        $filter = " WHERE (".implode(") AND (", $filter).")" ;
                    else
                        $filter = '';
                        
                    //if ($dont_courier)
                        //$labels = array('number', 'date', 'status', 'type', 'name', 'metro', 'address', 'comment', 'amount', 'price', 'price_fact', 'price_sold', 'm_list', 'deadline');
                    //else 
                    
                    $labels = array('number', 'date', 'status', 'type', 'name', 'metro', 'address', 'comment', 'amount', 'price', 'price_fact', 'price_sold', 'courier', 'm_list', 'deadline');
                        
                    $textareas = array('number', 'type', 'name', 'address', 'comment', 'amount', 'price', 'price_fact', 'm_list', 'price_sold');
                    
                    if (!$metros)
                    {
                        $textareas[] = 'metro';
                    }
                    
                    $decimal = array('price', 'price_fact', 'amount', 'price_sold');
                    
                    $select = array(
                                "`orders`.`number` as `number`",
                                "`logists`.`date` as `date`",
                                "`status_logists`.`name` as `status`",
                                "`logists`.`type` as `type`",            
                                "`logists`.`name` as `name`",
                                "`metros`.`name` as `metro`",
                                "`logists`.`address` as `address`",
                                "`logists`.`comment` as `comment`",
                                "`logists`.`amount` as `amount`",     
                                "`logists`.`price` as `price`",
                                "`logists`.`price_fact` as `price_fact`",   
                                "`logists`.`price_sold` as `price_sold`",  
                                "`logists`.`courier` as `courier`",
                                "`logists`.`m_list` as `m_list`", 
                                "`logists`.`deadline` as `deadline`",      
                                "`workers`.`name` as `w_name`",
                                "`users`.`name` as `w_name2`",  
                                "`users`.`not_active` as `not_active`",  
                                "`address`.`code` as `code`", 
                                "`logists`.`status_logist_id` as `status_logist_id`",
                                "`logists`.`metro_id` as `metro_id`",  
                                "`status_logists`.`plus` as `plus`",
                                "`logists`.`block` as `block`",
                                "`logists`.`block_user` as `block_user`",    
                                "`logists`.`go` as `go`", 
                                "`logists`.`id` as `id`",                       
                            );
                           
                    $order_field = tools::cut_field($select[$order_column]);                    
                    
                    $f_group = array_merge($red_statuses, $green_statuses, $yellow_statuses);
                    //if (load::get_user_id() == 1) print_r($f_group);
                    
                    if (!$metros)
                    {
                        $select[5] = "`logists`.`metro` as `metro`"; 
                    }
                    
                    $select[] = "(ISNULL({$order_field}) OR {$order_field} LIKE 0 OR {$order_field} = '') as `isnull`";
                    $select[] = "(`status_logist_id` IN (".implode(',', $f_group).")) as `color`";                    
                           
                    $inner = "LEFT JOIN `status_logists` ON `status_logists`.`id` = `logists`.`status_logist_id`
                              LEFT JOIN `users` ON `logists`.`courier` = `users`.`id` 
                              LEFT JOIN `workers` ON `users`.`worker_id` = `workers`.`id`
                              LEFT JOIN `orders` ON `orders`.`id` = `logists`.`order_id`
                              LEFT JOIN `address` ON `logists`.`addres_id` = `address`.`id`
                              LEFT JOIN `metros` ON `logists`.`metro_id` = `metros`.`id`";

                                            
                    $sql = "SELECT ".implode(',', $select)." FROM `logists`  
                                  {$inner}
                                        {$filter} ORDER BY `color` ASC, `isnull` DESC, {$order_field} {$order_dir}, `id` DESC LIMIT {$start},{$length} ";
                                        
                    //if (load::get_user_id() == 1) echo $sql;
                                    
                    $data = $db = pdo::getPdo()->query($sql)->fetchAll(\PDO::FETCH_ASSOC);                    
                    
                    $sql = "SELECT COUNT(*) FROM `logists` {$inner} {$filter}";
                    $all = pdo::getPdo()->query($sql)->fetchColumn();    
                    
                    $i = 0;
                    
                    //all_users
                    $user_ids = array();
                    $db_ids = array();
                    
                    $all_users = array();
                    $all_users_active = array();
                    
                    foreach ($data as $values)
                    {
                        if ($values['courier']) $user_ids[$values['courier']] = true;
                        $db_ids[] = $values['id'];
                    }                     
                 
                    if ($user_ids)
                    {
                        $sql = "SELECT `workers`.`name` as `name`, `users`.`id` as `id`, `users`.`name` as `name2`, 
                                        `users`.`not_active` FROM `users` 
                                LEFT JOIN `workers` ON `users`.`worker_id` = `workers`.`id`
                                    WHERE `users`.`id` IN (".implode(',', array_keys($user_ids)).")
                                                        ORDER BY `workers`.`name` ASC";
                                        
                        $users = pdo::getPdo()->query($sql)->fetchAll(\PDO::FETCH_ASSOC); 
                        
                        $t = array();
                        $t_t = array();  
                        
                        foreach ($users as $user)
                        {
                            $t[$user['id']] = tools::cut_user_name($user['name'], $user['name2']);
                            
                            if ($user['not_active'])
                                $t_t[$user['id']] = false;
                            else
                                $t_t[$user['id']] = true;
                        }
                        
                        $all_users = array(0 => 'Не задан') + $t;
                        $all_users_active = array(0 => true) + $t_t;
                    }
                 
                    //users
                    $sql = "SELECT `workers`.`name` as `name`, `users`.`id` as `id`, `users`.`name` as `name2` 
                                   FROM `access` 
                            LEFT JOIN `users` ON `users`.`id` = `access`.`user_id` 
                            LEFT JOIN `workers` ON `users`.`worker_id` = `workers`.`id`
                                WHERE (`access`.`group_id` IN
                                            (".implode(',', $exclude_groups).") AND 
                                                        `access`.`addres_id` IN (".implode(',', $addr_id).")
                                                            AND (`users`.`not_active`  IS NULL OR `users`.`not_active` = 0)
                                                                AND (`access`.`no_active` IS NULL OR `access`.`no_active` = 0)) OR 
                                                                        (`users`.`id` IN (".implode(',', $this->_user_money($organization_id)).")) 
                                                     ORDER BY `workers`.`name` ASC";
                                    
                    $users = pdo::getPdo()->query($sql)->fetchAll(\PDO::FETCH_ASSOC); 
                    
                    $t = array();
                      
                    foreach ($users as $user)
                    {
                        $t[$user['id']] = tools::cut_user_name($user['name'], $user['name2']);
                    }
                    
                    $users = array(0 => 'Не задан') + $t;
                    $user_id = load::get_user_id();
                    
                    $log_count = array();
                    
                    if ($db_ids)
                    {
                        $this->calculate_log($db_ids, 'logists');    
                    }
                    
                    foreach ($data as $values)
                    {
                        $j = 0;     
                        
                        $dont_courier = ($db[$i]['status_logist_id'] == $sold || $db[$i]['status_logist_id'] == $sklad);
                        
                        foreach ($values as $key => $value)
                        {
                            if ($dont_courier && $key == 'courier') continue;
                            if (!$dont_courier && $key == 'price_sold') continue;
                            
                            if (!in_array($key, $labels)) continue;  
                            
                            $show = true;
                            
                            if ($dont_courier && in_array($key, $sklad_clears))
                            {
                                $value = '';
                                $show = false;
                            }
                                                                                      
                            if ($key == 'number')
                            {
                                 $value = $value . ' ' . $db[$i]['code'];       
                            }
                            
                            if ($key == 'date' || $key == 'deadline')
                            {
                                 $change_date = new node('div');
                                 $change_date->getAttributes()->getClass()->addItems('change_date');
                                 $change_date->getAttributes()->addAttr('data-field', $key);
                                 
                                 if ($value)
                                 {
                                    if ($value != '0000-00-00 00:00:00')
                                        $value = date('d.m.y H:i', strtotime($value));
                                    else
                                        $value = '';                                  
                                 }
                                 
                                 $change_date->addChildren($value);
                                 
                                 $e_sup = $this->_log_count($change_date, $db[$i]['id'], $key, $value);
                                
                                 if (!$e_sup)
                                 {
                                    $value = (string) $change_date;
                                 }
                            }
                            
                            $plus = '';
                            
                            if ($db[$i]['plus'] !== null)
                            {
                                if ($db[$i]['plus'] == 0)
                                {
                                    $plus = 'red';
                                }
                                else
                                {
                                    $plus = 'green';   
                                }
                            }
                            
                            if ($key == 'status')
                            {
                                $status_id = (integer) $db[$i]['status_logist_id'];
                                
                                $select_area = new node('div');
                                $select_area->getAttributes()->getClass()->addItems('select_area');
                                $select_area->getAttributes()->addAttr('data-field', 'status_logist_id');
                                
                                $item = new form\select();
                                $item->setValues($status_logists);
                                $item->setValue($status_id);
                                
                                if (!$status_id)
                                {
                                    $select_area->getAttributes()->getClass()->addItems('dt-red');    
                                }
                                
                                $options = $item->getChildren('options');
                    
                                foreach ($item->getValues() as $key => $value)
                                {
                                    $color = '';
                                    $end = false;
                                    
                                    $option = $options->getItems($key);
                                    
                                    if (in_array($key, $green_statuses))
                                    {
                                       $color = 'dt-green2';
                                    } 
                                    
                                    if (in_array($key, $red_statuses))
                                    {
                                        $color = 'dt-red';
                                    } 
                                    
                                    if (in_array($key, $yellow_statuses))
                                    {
                                        $color = 'dt-yellow';
                                    }
                                    
                                    if (in_array($key, $ex_ends))
                                    {
                                        $end = true;
                                    }
                                    
                                    $plus = '';
                                    
                                    if (isset($pluses[$key]))
                                    {
                                        if ($pluses[$key] !== null)
                                        {
                                            if ($pluses[$key] == 0)
                                            {
                                                $plus = 'red';
                                            }
                                            else
                                            {
                                                $plus = 'green';   
                                            }
                                        }
                                    }
                                    
                                    if ($color)
                                    {
                                        $option->getAttributes()->addAttr('data-color', $color);                                   
                                    }
                                    
                                    if ($end)
                                    {
                                        $option->getAttributes()->addAttr('data-end', 1);     
                                    }
                                    
                                    if ($plus)
                                    {
                                        $option->getAttributes()->addAttr('data-txt_color', $plus);        
                                    }
                                }
                                
                                $item->setName('status_logist_id');
                                
                                $span = new node('span');
                                $span->getAttributes()->getClass()->addItems('s_value');  
                                $span->addChildren(isset($status_logists[$status_id]) ? $status_logists[$status_id] : 'Не задан');
                                
                                $select_area->addChildren($item);
                                $select_area->addChildren($span);
                                
                                /*$e_sup = $this->_log_count($select_area, $db[$i]['id'], 'status_logist_id', $value);
                                
                                if (!$e_sup)
                                {
                                    $value = (string) $select_area;
                                }*/
                                
                                $e_sup = $this->_go_sup($select_area, $status_id, $value, $ends, $db[$i]['go']);
                                
                                if (!$e_sup)
                                {
                                    $value = (string) $select_area;
                                }
                            }
                            
                            if ($key == 'courier' && $show)
                            {
                                $user_id = (integer) $db[$i]['courier'];
                                
                                $select_area = new node('div');
                                $select_area->getAttributes()->getClass()->setItems('select_area');
                                $select_area->getAttributes()->addAttr('data-field', $key);
                                
                                $item = new form\select();
                                $item->setValues($users);
                                $item->setValue($user_id);
                                $item->setName('courier');
                                
                                if (!$user_id)
                                {
                                    $select_area->getAttributes()->getClass()->addItems('dt-red');    
                                }
                                
                                $span = new node('span');
                                $span->getAttributes()->getClass()->addItems('s_value');  
                                $span->addChildren(isset($all_users[$user_id]) ? $all_users[$user_id] : 'Не задан');
                                                                       
                                if (isset($all_users_active[$user_id]))
                                {
                                    if (!$all_users_active[$user_id])
                                    {
                                        $span->getAttributes()->getClass()->setItems(array('red', 'dt_span'));
                                        $fa = new node('i');
                                        $fa->getAttributes()->getClass()->setItems(array('fa', 'fa-trash-o'));
                                        
                                        $span->addChildren($fa);
                                    } 
                                }
                                
                                $select_area->addChildren($item);
                                $select_area->addChildren($span);
                                
                                $e_sup = $this->_log_count($select_area, $db[$i]['id'], $key, $value);
                                
                                if (!$e_sup)
                                {
                                    $value = (string) $select_area;
                                } 
                            }
                           
                            if ($key == 'metro' && $show)
                            {
                                if ($metros)
                                {
                                    $metro_id = (integer) $db[$i]['metro_id'];
                                
                                    $select_area = new node('div');
                                    $select_area->getAttributes()->getClass()->addItems('select_area');
                                    $select_area->getAttributes()->addAttr('data-field', 'metro_id'); 
                                    
                                    if ($metro_id)
                                        $metro_array = array($metro_id => $metros[$metro_id]);
                                    else
                                        $metro_array = $metro_null;           
                                    
                                    $item = new form\select();
                                    $item->setValues($metro_array);
                                    $item->setValue($metro_id);
                                    $item->setName('metro_id');                             
                                    
                                    $span = new node('span');
                                    $span->getAttributes()->getClass()->addItems('s_value');  
                                    $span->addChildren(isset($metros[$metro_id]) ? $metros[$metro_id] : 'Не задан');
                                    
                                    if (!$metro_id)
                                    {
                                        $select_area->getAttributes()->getClass()->addItems('dt-red');    
                                    } 
                                    
                                    if ($metro_id)
                                    {
                                        if (isset($metro_to_lines[$metro_id]))
                                        {
                                            foreach ($metro_to_lines[$metro_id] as $line_id)
                                            {
                                                $subway = new node('span');
                                                $subway->getAttributes()->getClass()->addItems('subway');
                                                $subway->getAttributes()->getClass()->addItems(logist::metro_colour($line_id));
                                                
                                                $select_area->addChildren($subway);
                                            }
                                        }
                                    }                                                                       
                                    
                                    $select_area->addChildren($item);
                                    $select_area->addChildren($span);
                                    
                                    $e_sup = $this->_log_count($select_area, $db[$i]['id'], 'metro_id', $value);
                                    
                                    if (!$e_sup)
                                    {
                                        $value = (string) $select_area;
                                    }
                                }   
                            }
                            
                            if (in_array($key, $textareas))
                            {
                                $textarea = new node('div');
                                $textarea->getAttributes()->getClass()->addItems('textarea');                                
                                $textarea->getAttributes()->addAttr('data-field', $key);
                                 
                                if ($key == 'number')
                                {
                                    $textarea->getAttributes()->getClass()->addItems('number');
                                    $textarea->getAttributes()->setAttr('data-field', 'order_id');
                                }
                                
                                if ($db[$i]['status_logist_id'] == $sklad && ($key == 'number' || $key == 'price_sold' || $key == 'comment')) 
                                {
                                    $textarea->getAttributes()->getClass()->addItems('free');
                                }
                                
                                if ($dont_courier && $key == 'name')
                                {
                                    $textarea->getAttributes()->getClass()->addItems('nowrap');
                                }
                            
                                if (in_array($key, $decimal))
                                {
                                    $textarea->getAttributes()->getClass()->addItems('decimal');    
                                } 
                                
                                if ($key == 'price' || $key == 'price_fact' || $key == 'price_sold')
                                {
                                    if ($value)
                                    {
                                        if ($plus == 'red')
                                        {
                                            $value = '-' . (string) $value;   
                                            $textarea->getAttributes()->addAttr('data-txt_color', 'red');        
                                        }
                                        
                                        if ($plus == 'green')
                                        {
                                            $value = '+' . (string) $value; 
                                            $textarea->getAttributes()->addAttr('data-txt_color', 'green');
                                        }
                                    }                               
                                }
                                        
                                $textarea->addChildren($value);
                                
                                if ($key == 'number') $key = 'order_id';
                                
                                $e_sup = $this->_log_count($textarea, $db[$i]['id'], $key, $value);
                                
                                if (!$e_sup)
                                {
                                    $value = (string) $textarea;
                                }
                            }                     
                            
                            $tbody[$i][] = $value;
                                 
                            $j++;  
                        }
                        
                        if ($dont_courier)
                        {
                            //$fa = '';
                        }
                        else    
                        {
                            $fa = new node('i');
                            $fa->getAttributes()->getClass()->setItems(array('fa', 'fa-copy'));
                            $fa->getAttributes()->addAttr('title', 'Копировать');
                            
                            $tbody[$i][] = (string) $fa;
                        }
                        
                        $fa_go = new node('i');
                        $fa_go->getAttributes()->getClass()->setItems(array('fa', 'fa-arrow-right'));
                        $fa_go->getAttributes()->addAttr('title', 'В карточку');                        
                       
                        $tbody[$i][] = (string) $fa_go; 
                                
                        $super_user = load::is_super_user();
                            
                        $dt_system = false;
                        
                        if ($db[$i]['status_logist_id'] == $sklad)
                        {
                            $fa_minus = new node('i');
                            $fa_minus->getAttributes()->getClass()->setItems(array('fa', 'fa-minus-circle'));
                            $fa_minus->getAttributes()->addAttr('title', 'Разобрать');
                            
                            $tbody[$i][] = (string) $fa_minus;
                            
                            $fa_sklad = new node('i');
                            $fa_sklad->getAttributes()->getClass()->setItems(array('fa', 'fa-plug'));
                            $fa_sklad->getAttributes()->addAttr('title', 'Продать');
                            
                            $tbody[$i][] = (string) $fa_sklad;
                            
                            $dt_system = true;  
                        }
                        else
                        {
                            if ($db[$i]['status_logist_id'] == $sold)
                            {
                                $tbody[$i][] = '';
                                $tbody[$i][] = '';
                                
                                $dt_system = true;
                            }
                            
                            if (!in_array($db[$i]['status_logist_id'], $ex_ends))
                            {
                                if ($is_boss_2)
                                {
                                    $fa_delete = new node('i');
                                    $fa_delete->getAttributes()->getClass()->setItems(array('fa', 'fa-close'));
                                    $fa_delete->getAttributes()->addAttr('title', 'Удалить');
                                    
                                    $tbody[$i][] = (string) $fa_delete;
                                }
                                else
                                {
                                    $tbody[$i][] = '';  
                                }
                            }
                            else
                            {
                                if ($super_user)
                                {
                                    $fa_delete = new node('i');
                                    $fa_delete->getAttributes()->getClass()->setItems(array('fa', 'fa-close'));
                                    $fa_delete->getAttributes()->addAttr('title', 'Удалить');
                                    
                                    $tbody[$i][] = (string) $fa_delete;
                                }
                                else
                                {
                                    $tbody[$i][] = '';  
                                    $dt_system = true;
                                }   
                            }                            
                        }
                        
                        if ($super_user)
                          $dt_system = false;
                        
                        $tbody[$i]['DT_RowData']['logist_id'] = $db[$i]['id'];
                        
                        if (in_array($db[$i]['status_logist_id'], $green_statuses))
                        {
                            $tbody[$i]['DT_RowClass'] = 'dt-green2';
                        } 
                        
                        if (in_array($db[$i]['status_logist_id'], $red_statuses))
                        {
                            $tbody[$i]['DT_RowClass'] = 'dt-red';
                        } 
                        
                        if (in_array($db[$i]['status_logist_id'], $yellow_statuses))
                        {
                            $tbody[$i]['DT_RowClass'] = 'dt-yellow';
                        }
                        
                        if ($dt_system)
                        {
                            if (isset($tbody[$i]['DT_RowClass'])) 
                                $tbody[$i]['DT_RowClass'] .= ' dt-system';
                            else
                                $tbody[$i]['DT_RowClass'] = 'dt-system';
                        }
                        
                        if (strtotime($db[$i]['date']) >= $current_day)
                        {
                            if (isset($tbody[$i]['DT_RowClass'])) 
                                $tbody[$i]['DT_RowClass'] .= ' dt-blind';
                            else
                                $tbody[$i]['DT_RowClass'] = 'dt-blind'; 
                        }
                        
                        if ($db[$i]['block'])
                        {
                            if ($current_time - strtotime($db[$i]['block']) <= $block_time && $db[$i]['block_user'] != $user_id)     
                            {
                                $tbody[$i]['DT_RowClass'] = 'dt-block';
                            }
                        }
                        
                        $i++;  
                    }
                    
                    $a_array = array('draw' => $draw, 'recordsTotal' => $all, 'recordsFiltered' => $all, 'data' => $tbody);
                    
                    if ($label_courier)
                        $a_array['column_name'] = array(11 => 'Цена продажи');
                    else
                        $a_array['column_name'] = array(11 => 'Курьер');
                        
                        
                    $sql = "SELECT COUNT(*) FROM `logists` {$inner} {$filter} AND `status_logist_id`=:status_logist_id";
                    $stm = pdo::getPdo()->prepare($sql); 
                    $stm->execute(array('status_logist_id' => $get));
                    $get_count = (integer) $stm->fetchColumn();
                    //$a_array['summ'] = 'Куплено за период: ' . $get_count;
                    
                    $this->getWrapper()->addChildren($a_array);
                }
                else
                {
                    $thead = array('Заказ', 'Дата', 'Статус', 'Тип', 'Наименование', 'Метро', 'Адрес', 'Комментарий', 'Кол-во', '$', 'Итог $', 'Курьер', 'МЛ', 'Дедлайн', 'no-sort' => '',
                                'no-sort-2' => '', 'no-sort-3' => ''); 
                    
                    $data_table = new shape\datatable();
                    $data_table->setStriped(false);
                    $data_table->getAttributes()->addAttr('data-table', 'logists');
                    $data_table->getAttributes()->getClass()->addItems('ciba_excel');
                    
                    $this->getWrapper()->getAttributes()->setAttr('data-op', 'datatable');
                    $this->getWrapper()->getAttributes()->setAttr('data-obj', 'logist');
                    $this->getWrapper()->getAttributes()->setAttr('id', 'datatable-logists');
                         
                    $data_table->setThead($thead); 
                                          
                    $this->getWrapper()->addChildren($data_table);
                }
                
             break;
        }
        
        $this->getWrapper()->addChildren($answer);
    }
    
    private function _exclude_groups()
    {
        $exclude_groups = array();
        foreach (array('courier', 'administrator') as $value)
            $exclude_groups[] = load::get_status('groups', $value);
            
        return $exclude_groups;
    }
    
    public function check_courier($m_list, $organization_id = 0)
    {
        return $this->_check_courier($m_list, $organization_id);
    }
    
    private function _check_courier($m_list, $organization_id = 0)
    {
        if ($m_list == 'бк') return;
        
        $fix_price = 200;
        if (in_array($organization_id, [751, 1133, 2774, 2830, 2948, 3024, 3097])) $fix_price = 250;
        if ($organization_id == 842) $fix_price = 300;
        
        $sql = "SELECT `price_fact`, `courier`, `order_id`, `metro_id` as `metro`, `date` FROM `logists` WHERE `m_list`=:m_list";       
            
        $stm = pdo::getPdo()->prepare($sql);       
        $stm->execute(array('m_list' => $m_list));
        $summs = $stm->fetchAll(\PDO::FETCH_ASSOC);
        
        $sql = "SELECT COUNT(*) FROM `logists` LEFT JOIN
                    `status_logists` ON `logists`.`status_logist_id` = `status_logists`.`id` 
                            WHERE `m_list`=:m_list AND `status_logists`.`end` = 1";
        $stm = pdo::getPdo()->prepare($sql);       
        $stm->execute(array('m_list' => $m_list));
        $count = $stm->fetchColumn();
        
        $cr_summ = array();
           
        if ($count == count($summs))
        {
            $couriers = array();
            
            $sum_courier = 0; 
            $timestamp = date('Y-m-d H:i:s', tools::get_time());
              
            foreach ($summs as $sum)
            {
                $sum['metro'] = mb_strtolower(trim($sum['metro']));
                $couriers[$sum['metro']][] = $sum['order_id'];
                if ($sum['date']) $timestamp = $sum['date']; 
                if ((integer) $sum['courier'] == 1254) $fix_price = 300;
            }
                
            $count_couriers = count($couriers);
            $sum_courier =  $count_couriers * $fix_price;
              
            foreach ($couriers as $value)
            {
                $c_value = count($value);
                $one = floor($fix_price / $c_value);
                
                foreach ($value as $order_id)
                {    
                    if ($order_id)
                    {
                        if (!isset($cr_summ[$order_id])) $cr_summ[$order_id] = 0;  
                        $cr_summ[$order_id] += $one;
                    }
                } 
            }
            
            $sum['courier'] = (integer) $sum['courier'];
            
            $sql = "INSERT INTO `cr_summs` (`user_id`,`timestamp`,`summ`,`m_list`,`number`,`organization_id`) VALUES 
                            ({$sum['courier']}, '{$timestamp}', {$sum_courier}, '{$m_list}', {$count_couriers}, {$organization_id})
                        ON DUPLICATE KEY UPDATE `summ` = {$sum_courier}, `timestamp` = '{$timestamp}', `user_id` = {$sum['courier']}, 
                                `number` = {$count_couriers}, `organization_id` = {$organization_id}"; //echo $sql;
                         
            pdo::getPdo()->query($sql);                            
                        
            foreach ($cr_summ as $key => $value)
            {
                //echo $key.PHP_EOL;
                $cr_summ = (integer) load::get_order($key, 'cr_summ');
                $z_order_obj = new term(array('mode' =>'update', 'table' => 'orders', 'id' => $key, 'cr_summ' => $cr_summ + $value));    
            }
        }
            
        return $cr_summ;     
    }
    
    public function log_count($item, $id, $key, &$value)
    {
        return $this->_log_count($item, $id, $key, $value);
    }
    
    public function set_log_counts($log_counts)
    {
        $this->_log_counts = $log_counts;    
    }
    
    private function _log_count($item, $id, $key, &$value)
    {
        $e_sup = false;
        
        $log_count = $this->_log_counts;
        
        if (isset($log_count[$id][$key]))
        {
            $label_sup = $log_count[$id][$key];
            
            if ($label_sup >= 1)
            {
                $enum_sup = new enum();
                $enum_sup->setSign('');
                
                $sup = new node('sup');
                $sup->addChildren($label_sup);
                $sup->getAttributes()->getClass()->addItems('log');
                
                $enum_sup->addItems($item);
                $enum_sup->addItems($sup);
       
                $value = (string) $enum_sup;
                
                $e_sup = true;
            }
        }
        
        return $e_sup;
    }
    
    private function _go_sup($item, $status_id, &$value, $ends, $go)
    {
        $e_sup = false;
        
        if (!in_array($status_id, $ends))
        {
             $enum_sup = new enum();
             $enum_sup->setSign(''); 
             
             $sup = new node('sup');
             $sup->getAttributes()->getClass()->setItems(array('go', 'log'));
             
             if (!$go)
             {
                $sup->addChildren($this->_mode_log[0]);
             }
             else
             {
                $sup->addChildren($this->_mode_log[1]);
                $sup->getAttributes()->getClass()->addItems('active');
             }
             
             $enum_sup->addItems($item);
             $enum_sup->addItems($sup);
       
             $value = (string) $enum_sup;
             
             $e_sup = true;
        }
        
        return $e_sup;    
    }
    
    public function calculate_log($db_ids, $table)
    {
        //log_count                    
        $sql = "SELECT count(*) as `count`, `record_name` as `record_name`, `record_id` as `record_id` FROM `logs` WHERE `table_name`=:table AND `record_id` IN (".implode(',', $db_ids).")
                    GROUP BY `record_id`, `record_name`";
        $stm = pdo::getPdo()->prepare($sql); 
        $stm->execute(array('table' => $table));
        $log_count = $stm->fetchAll(\PDO::FETCH_ASSOC);
        
        $t_logs = array();
        
        foreach ($log_count as $count)
        {
            $t_logs[(integer) $count['record_id']][(string) $count['record_name']] = (integer) $count['count'];
        }
        
        $log_count = $t_logs;
        $this->_log_counts = $log_count;
    }
    
    public static function metro_colour($line)
    {
        $accord = array(
            "1" =>"subway-red", 
            "2" => "subway-green",
            "3" => "subway-deepblue",
            "4" => "subway-blue", 
            "5" =>"subway-kolc",
            "6" => "subway-orange", 
            "7" => "subway-purple", 
            "8" => "subway-yellow", 
            "9" => "subway-yellow",
            "10" => "subway-gray", 
            "11" => "subway-ld", 
            "12" => "subway-kah", 
            "13" => "subway-kah", 
            "14" => "subway-but",
            "15" => "subway-monorel",
            "16" => "subway-mck",
            "17" => "subway-red",
            "18" => "subway-deepblue",
            "19" => "subway-green",
            "20" => "subway-orange",
            "21" => "subway-purple",
            "22" => "subway-kolc",
            "23" => "subway-red",
            "24" => "subway-deepblue",
            "25" => "subway-green");
        return isset($accord[$line]) ? $accord[$line] : '';
    }
    
    private function _user_money($organization_id)
    { 
        if ($organization_id == 751) return array(1200, 1201, 1202);
        return array(23);
    }
}

?>