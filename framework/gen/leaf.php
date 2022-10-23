<?

namespace framework\gen;

class leaf extends \framework\main 
{
    protected $_variants = array();
    protected $_next;
    protected $_current = 0;
    
    public function getNext()
    {
        return $this->_next;
    }
    
    public function setNext($next)
    {
        $this->_next = $next;
    }
}

?>