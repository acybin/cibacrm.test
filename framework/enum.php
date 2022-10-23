<?

namespace framework;

class enum extends main
{
    protected $_items = array();
    protected $_sign = ' '; 
    
    public function __toString()
    {
        if (!$this->_items) return '';
        foreach ($this->_items as $key => $value)
            $this->_items[$key] = $value;
                                        
        return implode($this->_sign, $this->_items);
    }
}

?>