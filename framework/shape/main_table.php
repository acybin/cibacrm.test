<?

namespace framework\shape;

use framework\dom\node;

class main_table extends node
{
    protected $_title = '';
    protected $_into = '';
    
    public function __construct()
    {
        parent::__construct('div');
        $this->getAttributes()->getClass()->addItems('main-table');
    }
    
    public function setTitle($title)
    {
         parent::setTitle($title);
         if ($this->_title !== '')
         {
             if (!$this->getChildren('title'))
             {
                $title_node = new node('div');
                $title_node->getAttributes()->getClass()->addItems('divider');
                $title_node->getAttributes()->getClass()->addItems('title');
                
                $span_title_node = new node('span');
                $title_node->addChildren($span_title_node);    
                
                $this->addChildren('title', $title_node);        
             }
             $this->getChildren('title')->getChildren(0)->setChildren($this->_title);
         }
         else
         {
            $this->delChildren('title');
         }
         
    }
    
    public function setInto($into)
    {
         parent::setInto($into);
         if ($this->_into !== '')
         {
             if (!$this->getChildren('into')) 
             {
                $into_node = new node('div');
                $into_node->getAttributes()->getClass()->addItems('into');
                $this->addChildren('into', $into_node);   
             }
             $this->getChildren('into')->setChildren($this->_into);
         }
         else
         {
            $this->delChildren('into');
         }
    }
}

?>