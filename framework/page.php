<?

namespace framework;

class page extends main
{
    private static $_page = null;
    protected $_h1 = '';
    protected $_title = '';
    protected $_description = '';
    protected $_js = array();
    protected $_css = array(); 
    protected $_cssline = array(); 
    protected $_template = '';
    
    protected $_header = true;
    protected $_footer = true;
    
    private function __construct()
    {
     
    }
    
    private function __clone() 
    {
        
    }
    
    public static function getPage()
    {
        if (self::$_page === null) 
        {
           self::$_page = new page;
        }
        
       return self::$_page;
    }
}

?>