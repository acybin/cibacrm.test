<?

namespace framework\shape\form;

use framework\dom\node;

class button_close extends node
{
    protected $_value = '';
    
    public function __construct($value = 'Закрыть')
    {
        parent::__construct('button');
        $this->getAttributes()->getClass()->addItems('btn');
        $this->getAttributes()->getClass()->addItems('btn-default');
        $this->getAttributes()->addAttr('data-dismiss', 'modal');
        $this->setValue($value);
    }
    
    public function setValue($value)
    {
        parent::setValue($value);
        if ($this->_value !== '')
            $this->addChildren('value', $this->_value);
        else
            $this->delChildren('value');
    }
}

?>