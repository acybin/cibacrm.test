<?

namespace framework\ajax;

use framework\shape as shape;

abstract class ajax
{
    protected $_wrapper;
    protected $_code = 'success';
   
    public function __construct($op)
    {
        $this->_wrapper = new shape\wrapper($op);
    }
    
    public function getWrapper()
    {
       return $this->_wrapper;     
    }
    
    public function getCode()
    {
        return $this->_code;
    }
    
    public function setCode($code)
    {
        $this->_code = (string) $code;
    }
    
    public function __toString()
    {
        return (string) $this->_wrapper;    
    } 
}