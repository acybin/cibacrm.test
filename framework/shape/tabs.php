<?

namespace framework\shape;

use framework\dom\node;
use framework\enum;

class tabs extends node
{
    protected $_tabs = array();
    protected $_id = '';
    protected $_value = 0;
    protected $_vertical = false;
    
    public function __construct($id = 'tabs')
    {
        parent::__construct('div');
        $this->setId($id);        
        $this->getAttributes()->getClass()->addItems('bar_left');
    }
    
    public function setId($id)
    {
        parent::setId($id); 
        $this->getAttributes()->addAttr('id', $id);
    }
    
    public function setValue($value)
    {
        parent::setValue($value);
        $this->refresh("_tabs");
    }
    
    public function setVertical($vertical)
    {
        parent::setVertical($vertical);
    }
    
    protected function refresh($property)
    {
        if ($property == "_tabs")
        { 
            if (!$this->getChildren('wrapper'))
            {        
                $ul = new ul();
                $scroller_left = new node('div');
                $scroller_right = new node('div');
                
                $wrapper = new node('div');
                $wrapper->getAttributes()->getClass()->addItems('tabs-wrapper');
                
                $scroller_left->getAttributes()->getClass()->setItems(array('tabs-scroller-left', 'tabs-scroller'));
                $scroller_right->getAttributes()->getClass()->setItems(array('tabs-scroller-right', 'tabs-scroller'));
                
                $i_left = new node('i');
                $i_left->getAttributes()->getClass()->setItems(array('fa', 'fa-chevron-left'));
                
                $i_right = new node('i');
                $i_right->getAttributes()->getClass()->setItems(array('fa', 'fa-chevron-right'));
                
                if (!$this->_vertical)
                {
                    $ul->getAttributes()->getClass()->setItems(array('nav', 'nav-tabs', 'bar_tabs'));
                }
                else
                {
                    $ul->getAttributes()->getClass()->setItems(array('nav', 'nav-tabs', 'tabs-left'));
                }
                
                $scroller_left->addChildren($i_left);
                $scroller_right->addChildren($i_right);
                
                $this->addChildren($scroller_left);
                $this->addChildren($scroller_right);
                
                $wrapper->addChildren('ul', $ul);
                $this->addChildren('wrapper', $wrapper);
            } 
            
            if (!$this->getChildren('content'))
            {        
                $content = new node('div');
                $content->getAttributes()->getClass()->addItems('tab-content');
                $this->addChildren('content', $content);
            }
            
            $i = 0;
            $uls = array();
            $panels = array();
            
            foreach ($this->_tabs as $tab)
            {
                $id = $this->_id.'-'.$i;
                $a = new node('a');
                $a->getAttributes()->addAttr('href', '#'.$id);  
                $a->getAttributes()->addAttr('data-toggle', 'tab');    
                $a->addChildren($tab['name']);
                
                $div = new node('div');
                $div->getAttributes()->getClass()->setItems(array('tab-pane', 'fade'));
                $div->getAttributes()->addAttr('id', $id); 
                $div->addChildren($tab['content']);                
                         
                if ($this->_value == $i)
                {
                    $div->getAttributes()->getClass()->addItems('in');
                    $div->getAttributes()->getClass()->addItems('active');
                }
                
                $uls[] = $a;                
                $panels[] = $div;
                $i++;
            }
            
            $this->getChildren('wrapper')->getChildren('ul')->setValues($uls);
            
            $i = 0;
            foreach ($this->getChildren('wrapper')->getChildren('ul')->getChildren('li')->getItems() as $li)
            {
                if ($this->_value == $i)
                    $li->getAttributes()->getClass()->addItems('active');
                    
                $i++;    
            }
            
            $this->getChildren('content')->setChildren($panels);
        }
    }
    
    public function __toString()
    {         
        if ($this->_vertical)
        {
            $div1 = new node('div1');
            $div1->getAttributes()->getClass()->addItems('col-xs-3');
            $div1->addChildren($this->getChildren('wrapper'));
            
            $div2 = new node('div2');
            $div2->getAttributes()->getClass()->addItems('col-xs-9');  
            $div2->addChildren($this->getChildren('content'));    
            
            $this->setChildren(array($div1, $div2));      
        }    
       
        return parent::__toString();
    } 
}

?>