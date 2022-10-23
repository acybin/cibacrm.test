<?

namespace framework\shape;

use framework\dom\node;
use framework\enum;

class twocolors_table extends node
{
    protected $_thead = array();
    protected $_tbody = array(); 
    protected $_tfoot = '';
    
    public function __construct()
    {
        parent::__construct('table');
        $this->getAttributes()->getClass()->addItems('twocolors');
    }
    
    protected function refresh($property)
    {
        if ($property == '_thead')
        {
            $items = array();
            
            if (!$this->getChildren('thead'))
            {        
                $thead_node = new node('thead');
                $tr_node = new node('tr');
                $thead_enum = new enum();
                $thead_enum->setSign('');
                
                $tr_node->addChildren($thead_enum);
                $thead_node->addChildren($tr_node);
               
                $this->addChildren('thead', $thead_node);
            }
        
            foreach ($this->_thead as $value)
            {
                $th_node = new node('th');
                $th_node->setChildren($value);
                $items[] = $th_node;
            }
            
            $this->getChildren('thead')->getChildren(0)->getChildren(0)->setItems($items);
        }
        
        if ($property == '_tbody')
        {
            $items = array();
            
            if (!$this->getChildren('tbody'))
            {        
                $tbody_node = new node('tbody');
                $tbody_enum = new enum();
                $tbody_enum->setSign('');

                $tbody_node->addChildren($tbody_enum);
               
                $this->addChildren('tbody', $tbody_node);
            }
    
            foreach ($this->_tbody as $value)
            {
                $tr_node = new node('tr');
                $td_nodes = new enum();
                $td_nodes->setSign('');
                
                $tr_node->addChildren($td_nodes);
                
                foreach ($value as $value1)
                {
                    $td_node = new node('td');
                    $td_node->setChildren($value1); 
                    $td_nodes->addItems($td_node);                 
                }
                
                $items[] = $tr_node;
            }
            
            $this->getChildren('tbody')->getChildren(0)->setItems($items);
        }
    }
    
    public function setTfoot($tfoot)
    {
         parent::setTfoot($tfoot);
         $tfoot_node = new node('tfoot');
         $tr_node = new node('tr');
         $th_node = new node('th');
         
         $th_node->getAttributes()->addAttr('colspan', count($this->_thead));
         $th_node->setChildren($this->_tfoot);
         $tr_node->addChildren($th_node);
         $tfoot_node->addChildren($tr_node);
         $this->addChildren($tfoot_node);
    }
}