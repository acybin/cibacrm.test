<?

namespace framework\shape\form;

use framework\dom\node;

class checkbox extends node
{
    protected $_name = '';
    protected $_value = false;
    protected $_readonly = false;
    protected $_vmode = '';
    
    public function __construct()
    {
        parent::__construct('input', false);
        $this->getAttributes()->addAttr('type', 'checkbox');
        $this->getAttributes()->setAttr('value', '1');
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
        if ($this->_value === true)
            $this->getAttributes()->setAttr('checked', 'checked');
        else
            $this->getAttributes()->delAttr('checked');

    }
    
    public function setVmode($vmode)
    {
        parent::setVmode($vmode);
        if ($this->_vmode !== '')
            $this->getAttributes()->setAttr('value', $this->_vmode);
        else
            $this->getAttributes()->delAttr('value');
    }
    
    public function __toString()
    {
        $str = '';
        
        if ($this->_readonly && $this->_name)
        {
            $name = $this->getAttributes()->getAttr('name');
            $this->getAttributes()->setAttr('name', $name.'-checkbox');
            $this->getAttributes()->setAttr('disabled', 'disabled');
             
            $hidden = new hidden();
            $hidden->setName($this->_name);
            if ($this->_value) $hidden->setValue($this->_value);
            
            $str .= $hidden;
        }
        
        $str = parent::__toString() . $str;       
       
        return $str;
    }
}

?>