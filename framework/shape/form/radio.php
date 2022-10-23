<?

namespace framework\shape\form;

use framework\dom\node;

class radio extends node
{
    protected $_name = '';
    protected $_value = 0;
    
    public function __construct()
    {
        parent::__construct('input', false);
        $this->getAttributes()->addAttr('type', 'radio');
    }
    
    public function setName($name)
    {
        parent::setName($name);
        if ($this->_name !== '')
            $this->getAttributes()->setAttr('name', $this->_name);
        else
            $this->getAttributes()->delAttr('name');
    }
    
    public function setValue($value)
    {
        parent::setValue($value);
        //if ($this->_value)
            $this->getAttributes()->setAttr('value', $this->_value);
        //else
            //$this->getAttributes()->delAttr('value');
    }
}

?>