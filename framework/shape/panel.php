<?

namespace framework\shape;

use framework\dom\node;

class panel extends node
{
    protected $_title = '';
    protected $_into = '';
    protected $_chevron = false;
    
    public function __construct()
    {
        parent::__construct('div');
        $this->getAttributes()->getClass()->addItems('panel');
    }
    
    public function setTitle($title)
    {
         parent::setTitle($title);
         if ($this->_title !== '')
         {
             if (!$this->getChildren('title'))
             {
                $title_node = new node('div');
                $title_node->getAttributes()->getClass()->addItems('panel-heading');
                
                $span_title_node = new node('h4');
                $span_title_node->getAttributes()->getClass()->addItems('panel-title');
                $title_node->addChildren($span_title_node);
                
                if ($this->_chevron)
                {
                    $i = new node('i');
                    $i->getAttributes()->getClass()->setItems(array('fa', 'fa-chevron-down'));
                    $title_node->addChildren($i);
                }
                
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
                $into_node->getAttributes()->getClass()->addItems('panel-body');
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