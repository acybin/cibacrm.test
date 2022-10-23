<?

namespace framework\shape\form;

use framework\dom\node;

class pass_box extends input_box
{
    protected $_auto = true;
    
    public function __construct($auto = true)
    {
        node::__construct('input', false);
        $this->getAttributes()->addAttr('type', 'password');
        $this->setAuto($auto);
    }
    
    public function setAuto($auto)
    {
        parent::setAuto($auto);
        if ($this->_auto === true) 
             $this->getAttributes()->setAttr('autocomplete', 'new-password');
        else
             $this->getAttributes()->delAttr('autocomplete');
        
    }
    
    public function setClass($class)
    {
        return false;
    }
}

?>