<?

namespace framework;

class bank extends main
{
    private static $_bank = null;
    protected $_array = array(); 
    
    private function __construct()
    {
     
    }
    
    private function __clone() 
    {
        
    }
    
    public static function getBank()
    {
        if (self::$_bank === null) 
        {
           self::$_bank = new bank;
        }
        
       return self::$_bank;
    }
}

?>