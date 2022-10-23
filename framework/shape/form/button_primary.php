<?

namespace framework\shape\form;

use framework\dom\node;

class button_primary extends node
{
    protected $_value = '';
    protected $_class = '';
    
    public function __construct($value = 'Закрыть', $class = 'btn-primary')
    {
        parent::__construct('button');
        $this->getAttributes()->getClass()->addItems('btn');
        $this->setValue($value);
        $this->setClass($class);
    }
    
    public function setValue($value)
    {
        parent::setValue($value);
        if ($this->_value !== '')
            $this->addChildren('value', $this->_value);
        else
            $this->delChildren('value');
    }
    
    public function setClass($class)
    {
        parent::setClass($class);
        if ($this->_class !== '')
            $this->getAttributes()->getClass()->setItems('class', $this->_class);
        else
            $this->getAttributes()->getClass()->setItems('class', 'btn-primary');
    }
    
            
}

?>