<?

namespace framework\ajax\term;

use framework\ajax as ajax;
use framework\pdo;
use framework\tools;
use framework\load;
use framework\notify;
use framework\log;

class term extends ajax\ajax
{
    public function __construct($args)
    {
        parent::__construct('term');

//        if ($args['table'] == 'calls') {
//            $query = pdo::getPdo()->prepare('INSERT INTO `sr_logs` (`log`) VALUES (?)');
//            $query->execute([json_encode(['args' => $args, 'debug' => debug_backtrace()])]);
//        }

        $responce = isset($_POST['responce']) ? $_POST['responce'] : false;
        $table = isset($args['table']) ? $args['table'] : '';
        $mode = isset($args['mode']) ? $args['mode'] : 'add';
        $answer = '';
        
        $use_log = false;
        $log_tables = log::get_log_tables();         
        if (in_array($table, $log_tables)) $use_log = true;
        
        if ($table)
        {
            unset($args['table']);
            
            $hook_obj = null;
                        
            if (file_exists(\DOCUMENT_ROOT.'framework/ajax/edit_table/hooks/'.$table.'.php'))
            {
                $obj_str = 'framework\\ajax\\edit_table\\hooks\\'.$table;                        
                $hook_obj = new $obj_str($args);
            }
            
            $pass = true;
            
            switch ($mode)
            {
                case 'add':
                
                    unset($args['mode']);                    
                    
                    if (method_exists($hook_obj, 'beforeAdd'))
                        $pass = $hook_obj->beforeAdd($args);
                            
                    if ($pass)
                    {
                          try 
                          {
                            $sql = "INSERT INTO `{$table}` SET ".pdo::prepare($args);
                            $stm = pdo::getPdo()->prepare($sql);  
                            $stm->execute($args);
                          }
                          catch (\PDOException $e) 
                          {
                            if (load::get_user_id())
                            {
                                $notifys1 = new term(
                                    array(
                                        'mode' => 'add', 
                                        'table' => 'notifys',
                                        'text' => 'Ошибка при добавлении'.$e->getMessage(), 
                                        'type_notify_id' => load::get_status('type_notifys', 'add_error'),
                                        'organization_id' => load::get_org_id(),
                                        'session' => session_id(),
                                        )
                                    );
                            }
                             
                             $notifys2 = new term(
                                array(
                                    'mode' => 'add', 
                                    'table' => 'notifys',
                                    'text' => 'Ошибка при добавлении (term, '.$table.') '.$e->getMessage(), 
                                    'type_notify_id' => load::get_status('type_notifys', 'admin_error'), 
                                    )
                                );   
                             $pass = false;
                          }
                    }
                      
                    if ($pass)
                    {
                          $answer = $args['last_id'] = pdo::getPdo()->lastInsertId();                
            
                          if (method_exists($hook_obj, 'afterAdd'))
                              $hook_obj->afterAdd($args);
                              
                          if ($use_log) log::log($args, $table);
                    }
                
                break;
                
                case 'update':
                    
                     if (method_exists($hook_obj, 'beforeUpdate'))
                        $pass = $hook_obj->beforeUpdate($args);
                     
                     unset($args['mode'], $args['v_mode']);  
                                          
                     if ($pass)
                     {
                         try 
                         {
                            $sql = "UPDATE `{$table}` SET ".pdo::prepare($args)." WHERE `id`=:id";
                            $stm = pdo::getPdo()->prepare($sql);
                            $stm->execute($args);
                         }
                         catch (\PDOException $e) 
                         {
                            if (load::get_user_id())
                            {
                                $notifys1 = new term(
                                    array(
                                        'mode' => 'add', 
                                        'table' => 'notifys',
                                        'text' => 'Ошибка при обновлении', 
                                        'type_notify_id' => load::get_status('type_notifys', 'add_error'),
                                        'organization_id' => load::get_org_id(),
                                        'session' => session_id(),
                                        )
                                    );
                             }
                             
                             $notifys2 = new term(
                                array(
                                    'mode' => 'add', 
                                    'table' => 'notifys',
                                    'text' => 'Ошибка при обновлении (term, '.$table.') '.$e->getMessage(), 
                                    'type_notify_id' => load::get_status('type_notifys', 'admin_error'), 
                                    )
                                ); 
                             
                             $pass = false;
                         }
                     }
                         
                     if ($pass)
                     {                       
                         $answer = $args['id'];
                         
                         if (method_exists($hook_obj, 'afterUpdate'))
                            $hook_obj->afterUpdate($args);  
                            
                         if ($use_log) log::log($args, $table);                            
                     }
                    
                break;
                
                case 'delete':
                
                   unset($args['mode']);   
                    
                    if (method_exists($hook_obj, 'beforeDelete'))
                        $pass = $hook_obj->beforeDelete($args);
                        
                    if ($pass)
                    {
                        try 
                        {
                            $sql = "DELETE FROM `{$table}` WHERE `id`=:id";
                            $stm = pdo::getPdo()->prepare($sql);
                            $stm->execute(array('id' => $args['id']));
                        }
                        catch (\PDOException $e) 
                        {
                            if (load::get_user_id())
                            {
                                $notifys1 = new term(
                                    array(
                                        'mode' => 'add', 
                                        'table' => 'notifys',
                                        'text' => 'Ошибка при удалении', 
                                        'type_notify_id' => load::get_status('type_notifys', 'add_error'),
                                        'organization_id' => load::get_org_id(),
                                        'session' => session_id(),
                                        )
                                    );
                             }
                             
                             $notifys2 = new term(
                                array(
                                    'mode' => 'add', 
                                    'table' => 'notifys',
                                    'text' => 'Ошибка при удалении (term, '.$table.') '.$e->getMessage(), 
                                    'type_notify_id' => load::get_status('type_notifys', 'admin_error'), 
                                    )
                                ); 
                             
                             $pass = false;
                        }                        
                    }
                    
                    if ($pass)
                    {   
                        $answer = $args['id'];
                        
                        if (method_exists($hook_obj, 'afterDelete'))
                            $hook_obj->afterDelete($args);
                    } 
                
                break;
           }           
        } 

        $this->getWrapper()->addChildren($answer);
    }
}

?>