<?

namespace framework\shape\form;

use framework\dom\node;

class form_item extends node 
{
    protected $_label = '';
    protected $_class = '';
    
    public function __construct($class = 'form-item')
    {
        parent::__construct('div');
        $this->setClass($class);
    }
    
    public function setLabel($label, $label_class = 'inline')
    {
         parent::setLabel($label);
         if ($this->_label !== '')
         {
            if (!$this->getChildren('label'))
            {
                $label_node = new node('label');
                $label_node->getAttributes()->getClass()->addItems($label_class);
                $this->addChildren('label', $label_node);      
            }
            $this->getChildren('label')->setChildren($this->_label);
         }
         else
         {
            $this->delChildren('label');
         }
    }
    
    public function setClass($class)
    {
        if ($class)
        {
            parent::setClass($class);
            $this->getAttributes()->getClass()->addItems($this->_class);
        }
    }
}

?>