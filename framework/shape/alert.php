<?

namespace framework\shape;

use framework\dom\node;

class alert extends node
{
    protected $_text = '';
    
    public function __construct($text = '', $class = 'danger')
    {
        parent::__construct('div');
        $classes = array('alert', 'alert-'.$class, 'alert-dismissible', 'fade', 'in');        
        $this->getAttributes()->getClass()->setItems($classes);
        $this->getAttributes()->addAttr('role', 'alert');
        
        $button = new node('button');
        $button->getAttributes()->getClass()->addItems('close');
        $button->getAttributes()->addAttr('data-dismiss', 'alert');
        
        $span = new node('span');
        $span->addChildren('×');
                    
        $button->addChildren($span);
        $this->addChildren($button);
        
        $this->setText($text);
    }
    
    public function setText($text)
    {
        parent::setText($text);
        if ($this->_text !== '')
            $this->addChildren('text', $this->_text);
        else
            $this->delChildren('text');
    }
}

?>