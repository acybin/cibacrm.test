<?

namespace framework\shape\form;

use framework\dom\node;

class button extends node
{
    protected $_name = '';
    protected $_value = '';
    
    public function __construct()
    {
        parent::__construct('input', false);
        $this->getAttributes()->addAttr('type', 'button');
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
        if ($this->_value !== '')
            $this->getAttributes()->setAttr('value', $this->_value);
        else
            $this->getAttributes()->delAttr('value');
    }
}

?>