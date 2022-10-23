<?

namespace framework\shape;

use framework\dom\node;
use framework\enum;

class ul extends node
{
    protected $_values = array();
    
    public function __construct($tag = 'ul')
    {
        parent::__construct($tag);
    }
    
    protected function setTag($tag)
    {
        if ($tag == 'ol' ||$tag == 'ul')
        {
            parent::setTag($tag);
        }         
    }
    
    protected function refresh($property)
    {
        if ($property == "_values")
        {
            $li = array();
           
            if (!$this->getChildren('li'))
            {        
                $li_enum = new enum();
                $li_enum->setSign('');
                $this->addChildren('li', $li_enum);
            }           
 
            foreach ($this->_values as $value)
            {
                $li_node = new node('li');                
                $li_node->addChildren($value);               
                $li[] = $li_node;
            }
            
            $this->getChildren('li')->setItems($li);
        }
    } 
}


?>