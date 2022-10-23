<?

namespace framework\shape;

use framework\dom\node;

class x_panel extends node
{
    protected $_title = '';
    protected $_into = '';
    protected $_collapse = false;
    protected $_cls = false;
    protected $_calendar = false;
    
    public function __construct()
    {
        parent::__construct('div');
        $this->getAttributes()->getClass()->addItems('x_panel');
    }
    
    public function setTitle($title)
    {
         parent::setTitle($title);
         if ($this->_title !== '')
         {
             if (!$this->getChildren('title'))
             {
                $title_node = new node('div');
                $title_node->getAttributes()->getClass()->addItems('x_title');
                
                $span_title_node = new node('h2');
                $title_node->addChildren($span_title_node);

                if ($this->_collapse || $this->_cls)
                {
                    $ul = new ul();
                    $ul->getAttributes()->getClass()->setItems(array('nav', 'navbar-right', 'panel_toolbox'));
                    
                    $a_array = array();
                    
                    if ($this->_collapse)
                    {
                        $a = new node('a');
                        $a->getAttributes()->getClass()->addItems('collapse-link');
                        
                        $i = new node('i');
                        $i->getAttributes()->getClass()->setItems(array('fa', 'fa-chevron-up')); 
                        $a->addChildren($i);
                        $a_array[] = $a;
                    }
                    
                    if ($this->_cls)
                    {
                        $a = new node('a');
                        $a->getAttributes()->getClass()->addItems('close-link');
                        
                        $i = new node('i');
                        $i->getAttributes()->getClass()->setItems(array('fa', 'fa-close')); 
                        $a->addChildren($i);
                        $a_array[] = $a;
                    }
                    
                    $ul->setValues($a_array);
                    $title_node->addChildren($ul);
                    
                    $this->getAttributes()->addAttr('style', 'height: auto;');
                }
                
                if ($this->_calendar)
                {
                    $title_node->addChildren('<div class="pull-right range_picker"><i class="glyphicon glyphicon-calendar fa fa-calendar"></i> <span></span> <b class="caret"></b></div>');
                }
                               
                $clearfix = new clearfix();   
                $title_node->addChildren($clearfix); 
                
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
                $into_node->getAttributes()->getClass()->addItems('x_content');
                
                if ($this->_collapse)
                {
                    $into_node->getAttributes()->addAttr('style', 'display: none;');
                }
                
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