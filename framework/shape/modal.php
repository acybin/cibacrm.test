<?

namespace framework\shape;

use framework\dom\node;
use framework\shape\form as form;

class modal extends node
{
    protected $_title = '';
    protected $_body = '';
    protected $_footer = array();
    protected $_id = '';
    protected $_bclose = true;
    protected $_buttonclose = false;
    protected $_showfooter = false;
    
    public function __construct($id)
    {
        parent::__construct('div');
        $classes = array('modal', 'fade');
        $this->getAttributes()->getClass()->setItems($classes);
        
        $modal_dialog = new node('div');
        $classes = array('modal-dialog', 'modal-lg');
        $modal_dialog->getAttributes()->getClass()->setItems($classes);
        
        $modal_content = new node('div');
        $modal_content->getAttributes()->getClass()->addItems('modal-content');
        
        $this->addChildren($modal_dialog);
        $modal_dialog->addChildren($modal_content);
        
        $modal_header = new node('div');
        $modal_header->getAttributes()->getClass()->addItems('modal-header');
                
        $h4_node = new node('h4');
        $h4_node->getAttributes()->getClass()->addItems('modal-title');
        $modal_header->addChildren($h4_node);
                
        $this->getChildren(0)->getChildren(0)->addChildren('title', $modal_header);        
        
        $modal_body = new node('div');
        $modal_body->getAttributes()->getClass()->addItems('modal-body');
                
        $this->getChildren(0)->getChildren(0)->addChildren('body', $modal_body);  
        
        $modal_footer = new node('div');
        $modal_footer->getAttributes()->getClass()->addItems('modal-footer');
        
        $this->getChildren(0)->getChildren(0)->addChildren('footer', $modal_footer);
        
        $this->setId($id);
    }
    
    public function setBclose($bclose)    
    {
         parent::setBclose($bclose);  
    }
    
    public function setButtonclose($buttonclose)
    {
        parent::setButtonclose($buttonclose);
          
        if ($this->_buttonclose)
        {
            $h4_node = $this->getChildren(0)->getChildren(0)->getChildren('title')->getChildren(0);
            
            $button = new node('button');
            $button->getAttributes()->addAttr('type', 'button');
            $button->getAttributes()->getClass()->addItems('close');
            $button->getAttributes()->addAttr('data-dismiss', 'modal');
            
            $span = new node('span');
            $span->getAttributes()->addAttr('aria-hidden', 'true');
            $span->addChildren('×');
            
            $button->addChildren($span);
            
            $this->getChildren(0)->getChildren(0)->getChildren('title')->setChildren(array($button, $h4_node));
        }
    }
    
    public function setTitle($title)
    {
         parent::setTitle($title);
           
         if ($this->_buttonclose)
            $this->getChildren(0)->getChildren(0)->getChildren('title')->getChildren(1)->setChildren($this->_title);
         else
            $this->getChildren(0)->getChildren(0)->getChildren('title')->getChildren(0)->setChildren($this->_title);              
    }
    
    public function setBody($body)
    {
         parent::setBody($body);
         $this->getChildren(0)->getChildren(0)->getChildren('body')->setChildren($this->_body);        
    } 
    
    public function setFooter($footer)
    {
         parent::setFooter($footer);
   
         $button_primary = new form\button_primary($this->_footer['name']);
         if (isset($this->_footer['action']))
            $button_primary->getAttributes()->addAttr('onclick', $this->_footer['action']);
         $this->getChildren(0)->getChildren(0)->getChildren('footer')->addChildren('button_primary', $button_primary); 
    }
    
    public function setId($id)
    {
        parent::setId($id); 
        $this->getAttributes()->addAttr('id', $id);
    }
    
    public function __toString()
    {         
        if ($this->_bclose)
        {
            $button_close = new form\button_close();
            if ($this->getChildren(0)->getChildren(0)->getChildren('footer'))
            {
                $this->getChildren(0)->getChildren(0)->getChildren('footer')->addChildren('button_close', $button_close);
            }
        }
        
        if ($this->_buttonclose && !$this->_showfooter)
        {
            $this->getChildren(0)->getChildren(0)->delChildren('footer');    
        }    
       
        return parent::__toString();
    }
} 

?>  
    