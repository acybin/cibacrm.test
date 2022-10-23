<?

namespace framework;

class main
{
    public function __call($name, $arguments) 
    {
        $action = mb_substr($name, 0, 3);
        $property = '_' . mb_strtolower(mb_substr($name, 3));
        $pass = false;
        
        if (property_exists($this, $property))
        {
            $count = count($arguments);
            if ($count == 0)
            {
                if ($action == 'get')
                    return $this->{$property};
            }
            
            if ($count == 1)
            {
                if ($action == 'set')
                {
                    settype($arguments[0], gettype($this->$property));
                    $this->{$property} = $arguments[0];
                    $this->refresh($property);
                    $pass = true;
                }
                
                if (is_array($this->{$property}))
                {
                    if ($action == 'get')
                    {
                        if (isset($this->{$property}[$arguments[0]]))
                            return $this->{$property}[$arguments[0]];
                    }                
                    
                    if ($action == 'add')
                    {
                        $this->{$property}[] = $arguments[0];
                        $this->refresh($property);
                        $pass = true;
                    }                
                    
                    if ($action == 'del' && isset($this->{$property}[$arguments[0]]))
                    {
                        unset($this->{$property}[$arguments[0]]);
                        $this->refresh($property);
                        $pass = true;
                    }
                } 
            }
            
            if ($count == 2)
            {
                if (is_array($this->{$property}))
                {
                    if ($action == 'set' || $action == 'add')
                    {
                        $this->{$property}[$arguments[0]] = $arguments[1];
                        $this->refresh($property);
                        $pass = true;
                    }
                }
            }
        }
        //if (!$pass) echo 'Неверный вызов метода '.$name.' или аргументы.';
        return $pass;
    }
    
    protected function refresh($property)
    {
        
    }
}

?>