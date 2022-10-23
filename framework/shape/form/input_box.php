<?

namespace framework\shape\form;

use framework\dom\node;

class input_box extends node
{
    protected $_class = '';    
    
    protected $_name = '';
    protected $_error = '';
    protected $_holder = '';
    protected $_value = '';
    protected $_readonly = false;
    protected $_placeholder = '';
    
    public function __construct($class = '')
    {
        parent::__construct('input', false);
        $this->getAttributes()->addAttr('type', 'text');
        $this->setClass($class);
    }
    
    public function setClass($class)
    {
       switch ($class)
       {
            case 'phone': case 'decimal': case 'email': case 'date':
                parent::setClass($class);
                $this->getAttributes()->getClass()->addItems($this->_class);
            break;
       }
    }
    
    public function setName($name)
    {
         parent::setName($name);
         if ($this->_name !== '')
            $this->getAttributes()->setAttr('name', $this->_name);
         else
            $this->getAttributes()->delAttr('name');
    }
    
    public function setError($error)
    {
        parent::setError($error);
        if ($this->_error !== '')
        {
            $this->getAttributes()->setAttr('data-error', $this->_error);
            $this->getAttributes()->getClass()->setItems('required', 'required');
        }
        else
        {
            $this->getAttributes()->delAttr('data-error');
            $this->getAttributes()->getClass()->delItems('required');
        }
    }
    
    public function setHolder($holder)
    {
         parent::setHolder($holder);
         if ($this->_holder !== '')
            $this->getAttributes()->setAttr('data-holder', $this->_holder);
         else
            $this->getAttributes()->delAttr('data-holder');
    }
    
    public function setPlaceholder($placeholder)
    {
        parent::setPlaceholder($placeholder);
        if ($this->_placeholder !== '')
            $this->getAttributes()->setAttr('placeholder', $this->_placeholder);
        else
            $this->getAttributes()->delAttr('placeholder');
    }
    
    public function setValue($value)
    {
        parent::setValue($value);
        if ($this->_value !== '')
            $this->getAttributes()->setAttr('value', $this->_value);
        else
            $this->getAttributes()->delAttr('value');
    }
    
    public function setReadonly($readonly)
    {
         parent::setReadonly($readonly);
         if ($this->_readonly === true) 
            $this->getAttributes()->setAttr('readonly', 'readonly');
         else
            $this->getAttributes()->delAttr('readonly');
    }
}

?>