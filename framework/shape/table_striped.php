<?

namespace framework\shape;

use framework\dom\node;
use framework\enum;

class table_striped extends node
{
    protected $_thead = array();
    protected $_tbody = array(); 
    protected $_tfoot = '';
    protected $_tfoots = array();
    protected $_width = array();
    protected $_fth = false;
    
    public function __construct()
    {
        parent::__construct('table');
        $this->getAttributes()->getClass()->setItems(array('table', 'table-striped'));
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
                
                if ($this->_width)
                    $tr_node->getAttributes()->getClass()->addItems('row');
                
                $thead_enum = new enum();
                $thead_enum->setSign('');
                
                $tr_node->addChildren($thead_enum);
                $thead_node->addChildren($tr_node);
               
                $this->addChildren('thead', $thead_node);
            }
        
            foreach ($this->_thead as $key => $value)
            {
                $th_node = new node('th');
                $th_node->setChildren($value);
                
                if ($this->_width)
                    $th_node->getAttributes()->getClass()->addItems($this->_width[$key]);
                    
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
                
                if ($this->_width)
                    $tr_node->getAttributes()->getClass()->addItems('row');                
                
                $td_nodes = new enum();
                $td_nodes->setSign('');
                
                $tr_node->addChildren($td_nodes);
                
                $i = 0;
                
                if (isset($value['DT_RowData']))
                {
                    foreach ((array) $value['DT_RowData'] as $data_key => $data_value)
                    {
                        $tr_node->getAttributes()->addAttr('data-' . $data_key, $data_value);
                    }
                }
                
                foreach ($value as $key => $value1)
                {
                    if ($this->_fth && !$i)
                        $td_node = new node('th');
                    else
                        $td_node = new node('td');
                    
                    if ($this->_width)
                        $td_node->getAttributes()->getClass()->addItems($this->_width[$key]);
                    
                    if ((string) $key != 'DT_RowData')
                    {
                        $td_node->setChildren($value1); 
                        $td_nodes->addItems($td_node);  
                    }
                    
                    $i++;               
                }
                
                $items[] = $tr_node;
               
                //print_r($items);
            }
            
            $this->getChildren('tbody')->getChildren(0)->setItems($items);
        }
        
       if ($property == '_tfoots')
       {
            $items = array();
            
            if (!$this->getChildren('tfoot'))
            {        
                $tfoot_node = new node('tfoot');
                $tr_node = new node('tr');
                
                if ($this->_width)
                    $tr_node->getAttributes()->getClass()->addItems('row');
                
                $tfoot_enum = new enum();
                $tfoot_enum->setSign('');
                
                $tr_node->addChildren($tfoot_enum);
                $tfoot_node->addChildren($tr_node);
               
                $this->addChildren('tfoot', $tfoot_node);
            }
        
            foreach ($this->_tfoots as $key => $value)
            {
                $td_node = new node('td');
                $td_node->setChildren($value);
                
                if ($this->_width)
                    $td_node->getAttributes()->getClass()->addItems($this->_width[$key]);
                    
                $items[] = $td_node;
            }
            
            $this->getChildren('tfoot')->getChildren(0)->getChildren(0)->setItems($items);
        }
    }
    
    public function setTfoot($tfoot)
    {
         parent::setTfoot($tfoot);
         $tfoot_node = new node('tfoot');
         $tr_node = new node('tr');
         $th_node = new node('th');
         
         if ($this->_width)
            $tr_node->getAttributes()->getClass()->addItems('row');
         
         $th_node->getAttributes()->addAttr('colspan', $this->_thead ? count($this->_thead) : count($this->_tbody[0]));
         
         if ($this->_width)
            $th_node->getAttributes()->getClass()->addItems('col-sm-12');
         
         $th_node->setChildren($this->_tfoot);
         $tr_node->addChildren($th_node);
         $tfoot_node->addChildren($tr_node);
         $this->addChildren($tfoot_node);
    }
}

?>