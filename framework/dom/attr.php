<?

namespace framework\dom;

use framework\enum;

class attr extends \framework\main 
{
    protected $_class;
    protected $_attr = array();
    
    public function __construct()
    {
       $this->_class = new enum();
       $this->_attr["class"] = $this->_class;  
    }
    
    public function __toString()
    {
        if (!$this->_attr) return '';
        
        $str = '';
        foreach ($this->_attr as $key => $value)
        {
            if ((string) $value != '')
                $str .= $key.'="'.$value.'" ';
        }
        if ($str) $str = ' '.mb_substr($str, 0, -1);
        return $str;
    }
}

?>