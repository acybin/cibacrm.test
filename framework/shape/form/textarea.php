<?

namespace framework\shape\form;

use framework\dom\node;

class textarea extends input_box
{
    public function __construct()
    {
        node::__construct('textarea');
    }
    
    public function setValue($value)
    {
        parent::setValue($value);
        if ($this->_value !== '')
            $this->addChildren('value', $value);
        else
            $this->delChildren('value');
    }
    
    public function setClass($class)
    {
        return false;
    }
}

?>