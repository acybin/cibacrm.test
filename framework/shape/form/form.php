<?

namespace framework\shape\form;

use framework\pdo;
use framework\dom\node;
use framework\tools;
use framework\load;
use framework\ajax\lid\lid;

class form extends node 
{
    protected $_message = '';
    protected $_submit = '';
    
    public function __construct()
    {
        parent::__construct('form');
        $this->getAttributes()->getClass()->addItems('form');
    }
    
    public function setMessage($message)
    {
        parent::setMessage($message);
        if (!$this->getChildren('message'))
        { 
            $p_node = new node('p');
            $p_node->getAttributes()->getClass()->addItems('error-message');
            $this->addChildren('message', $p_node);   
        }
        $this->getChildren('message')->setChildren($this->_message);
    }
    
    public function fillValuesBase($args, $types = array(), $table = '', $mode = '')
    {
        if (isset($args['id']) && isset($args['table']))
        {
            $table = $args['table'];
            $id =  $args['id'];
            
            $sql = "SELECT * FROM `{$table}` WHERE `id`=:id";
            $stm = pdo::getPdo()->prepare($sql);
            $stm->execute(array('id' => $id));
            $this->fillValues(current($stm->fetchAll(\PDO::FETCH_ASSOC)), $types, $table, $mode);          
        }
    }
    
    public function fillValues($values, $types = array(), $table = '', $mode = '')
    {
        $childrens = $this->_recursion($this->getChildren());
        $values = (array) $values;
        $user_id = load::get_user_id();
        
        foreach ($values as $key => $value)
        {
            foreach ($childrens as $children)
            {
                if ($children->getName() === $key)
                {
                    $def = true;
                    if ($types && $value)
                    {
                       if (isset($types[$key]))
                       {
                           switch ($types[$key])
                           {
                               //case 9:
                                    //$value = date('d.m.Y H:i', strtotime($value));
                               //break;
                           }
                       }
                       
                    }
                    if ($def) 
                    {
                        if (in_array(load::get_org_id(), lid::getStaticExclude()))
                        {
                            if (($table == 'orders' && ($key == 'phone_name4' || $key == 'contact_phone' || $key == 'delivery_phone') && $mode != 13) ||
                                ($table == 'phones' && $key == 'name' && $mode != 303))
                            {
                                if (load::get_group_code() != 'director' && !in_array($user_id, [1, 1296, 4519, 4550, 4702]))
                                {
                                    if ($value)
                                    {
                                        for ($j=1; $j<mb_strlen($value)-4; $j++)
                                        {
                                           $value[$j] = '*';  
                                        }     
                                    }
                                       
                                    $children->getAttributes()->getClass()->addItems('stars'); 
                                } 
                            }
                        }
                        
                        if ($table == 'organizations' && $mode == 400 && ($key == 'phone' || $key == 'uis'))
                        {
                            $sql = "SELECT `modr` FROM `organizations` WHERE `id`=:id";
                            $stm = pdo::getPdo()->prepare($sql);         
                            $stm->execute(array('id' => $values['id']));
                            $modr = $stm->fetchColumn();                    
                            
                            $user_id = load::get_user_id();
                            
                            if ($modr)
                            {
                                $sql = "SELECT `groups`.`code` FROM `access`
                                            LEFT JOIN `groups` ON `access`.`group_id` = `groups`.`id`
                                                    WHERE (`access`.`addres_id` IS NULL OR `access`.`addres_id` = 0) AND `user_id`=:user_id";
                                $stm = pdo::getPdo()->prepare($sql);         
                                $stm->execute(array('user_id' => $user_id));                     
                                $group_code = $stm->fetchColumn();                            
                                
                                if (($group_code == 'resaler' || $group_code == 'saler') && ($modr != $user_id))
                                {
                                    if ($value)
                                    {
                                        for ($j=1; $j<mb_strlen($value)-4; $j++)
                                        {
                                           $value[$j] = '*';  
                                        }     
                                    }
                                       
                                    $children->getAttributes()->getClass()->addItems('stars'); 
                                }
                            }                                                   
                        }                        
                        
                        $children->setValue((string) htmlspecialchars($value));
                        
                        if ($value != '')
                            $children->getAttributes()->getClass()->addItems('passed');
                    }
                    
                    break;
                }
            }
        }
    }
    
    private function _recursion($childrens)
    {
        $arr = array();
        foreach ($childrens as $children)
        {
            if (is_a($children, 'framework\dom\node'))
            {
                $arr[] = $children;
                if ($children->getChildren()) 
                    $arr = array_merge($arr, $this->_recursion($children->getChildren()));
            }     
        }
        return $arr;
    }
    
    public function setSubmit($submit)
    {
         parent::setSubmit($submit);
         if ($this->_submit !== '')
         {
             if (!$this->getChildren('button-item'))
             { 
                $button_form_item = new form_item('form-block');
                $button = new button();
                $button_form_item->addChildren($button);
                $this->addChildren('button-item', $button_form_item);
              }
              $this->getChildren('button-item')->getChildren(0)->setValue($this->_submit);        
         }
         else
         {
            $this->delChildren('button-item');
         }
    }
}

?>