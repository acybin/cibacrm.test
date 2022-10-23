<?

namespace framework\shape\form;

use framework\dom\node;

class input_file extends node
{
    protected $_name = '';
    protected $_multiple = false;
    
    public function __construct($multiple)
    {
        parent::__construct('input', false);
        $this->getAttributes()->addAttr('type', 'file');
        $this->setMultiple($multiple);
    }
    
    public function setName($name)
    {
        parent::setName($name);
        if ($this->_name !== '')
            $this->getAttributes()->setAttr('name', $this->_name);
        else
            $this->getAttributes()->delAttr('name');
    }
    
    public function setMultiple($multiple)
    {
        parent::setMultiple($multiple);
        if ($this->_multiple)
            $this->getAttributes()->setAttr('multiple', 'multiple');
        else
            $this->getAttributes()->delAttr('multiple');
    }
    
    public function __toString()
    {
        if ($this->_multiple) $this->setName($this->getName() . '[]');       
        return parent::__toString();
    }
}

?>