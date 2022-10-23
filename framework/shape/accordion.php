<?

namespace framework\shape;

use framework\dom\node;
use framework\enum;

class accordion extends node
{
    protected $_panels = array();
    protected $_id = '';
    protected $_value = -1;
    
    public function __construct($id = 'accordion')
    {
        parent::__construct('div');
        $this->getAttributes()->getClass()->addItems('accordion');
        $this->setId($id);
    }
    
    public function setId($id)
    {
        parent::setId($id); 
        $this->getAttributes()->addAttr('id', $id);
    }
    
    public function setValue($value)
    {
        parent::setValue($value);
        $this->refresh("_panels");
    }
    
    protected function refresh($property)
    {
        if ($property == "_panels")
        {
            $panels = array();
           
            if (!$this->getChildren('panels'))
            {        
                $panel_enum = new enum();
                $panel_enum->setSign('');
                $this->addChildren('panels', $panel_enum);
            }           
            
            $i = 0;
            foreach ($this->_panels as $panel)
            {
                $title = $panel->getChildren('title');
                $title->setTag('a');
                                
                $id = $this->_id.'-'.$i;
                $title->getAttributes()->addAttr('data-toggle', 'collapse');
                $title->getAttributes()->addAttr('data-parent', '#'.$this->_id);
                $title->getAttributes()->addAttr('href', '#'.$id);                 
                
                $into = $panel->getInto();
                
                $div = new node('div');
                $div->getAttributes()->getClass()->setItems(array('panel-collapse', 'collapse'));
                $div->getAttributes()->addAttr('id', $id);                 
                
                $into_node = new node('div');
                $into_node->getAttributes()->getClass()->addItems('panel-body');
                $into_node->addChildren($into);
                
                if ($this->_value == $i)
                {
                    if ($panel->getChevron())
                    {
                        $panel->getChildren('title')->getChildren(1)->getAttributes()->getClass()->setItems(array('fa', 'fa-chevron-up'));                        
                    } 
                    $div->getAttributes()->getClass()->addItems('in');
                }
                else
                {
                    $title->getAttributes()->getClass()->addItems('collapsed');
                }
                
                $div->addChildren($into_node);
                
                $panel->setChildren('into', $div);                                
                
                $panels[] = $panel;
                $i++;
            }
            
            $this->getChildren('panels')->setItems($panels);
        }
    } 
}

?>