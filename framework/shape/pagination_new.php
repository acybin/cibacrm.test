<?

namespace framework\shape;

use framework\dom\node;
use framework\shape\ul;

class pagination_new extends node
{
    protected $_page = 0;
    protected $_pages = 0;
    
    public function __construct()
    {
        parent::__construct('div');
        $this->getAttributes()->getClass()->setItems(array('col-md-12', 'col-sm-12', 'col-xs-12', 'form-group', 'page'));
        
        $div = new node('div');        
        $div->getAttributes()->getClass()->addItems('btn-group');
        
        $a_left_node = new node('a');
        $a_right_node = new node('a');
        
        $a_left_node->getAttributes()->addAttr('href', '#');
        $a_right_node->getAttributes()->addAttr('href', '#');
        
        $a_left_node->getAttributes()->getClass()->setItems(array('btn', 'btn-default'));
        $a_right_node->getAttributes()->getClass()->setItems(array('btn', 'btn-default'));
        
        $a_left_node->getAttributes()->addAttr('id', 'pagination-left');
        $a_right_node->getAttributes()->addAttr('id', 'pagination-right');
        
        $i_left = new node('i');
        $i_left->getAttributes()->getClass()->setItems(array('fa', 'fa-caret-left'));
        
        $i_right = new node('i');
        $i_right->getAttributes()->getClass()->setItems(array('fa', 'fa-caret-right'));
        
        $a_left_node->addChildren($i_left);
        $a_right_node->addChildren($i_right);
        
        $div->setChildren(array('left' => $a_left_node, 'buttons' => new node(''), 'right' => $a_right_node));
        $this->setChildren(array('group' => $div));         
    }
    
    public function setPage($page)
    {
         parent::setPage($page);
         $this->_restruct();
    }
    
    public function setPages($pages)
    {
         parent::setPages($pages);
         $this->_restruct();
    }

    private function _restruct()
    {
        $pages = $this->_pages;
        $page = $this->_page;
        $values = array();
         
        if ($pages > 5)
        {
             $values[] = $this->_drawNav(1, $page);
             if ($page > 3 && $page < ($pages-2))
             {
                 $values[] = $this->_drawNav($page-1, $page);
                 $values[] = $this->_drawNav($page, $page);
                 $values[] = $this->_drawNav($page+1, $page);
             }
             if ($page <= 3)
             {
                 $values[] = $this->_drawNav(2, $page);
                 $values[] = $this->_drawNav(3, $page);
                 $values[] = $this->_drawNav(4, $page);
             }
             if ($page >= ($pages-2))
             {
                 $values[] = $this->_drawNav($pages-3, $page);
                 $values[] = $this->_drawNav($pages-2, $page);
                 $values[] = $this->_drawNav($pages-1, $page);
             }
             $values[] = $this->_drawNav($pages, $page);
        }
        else
        {
            for ($i=1; $i<=$pages; $i++)
            {
                $values[] = $this->_drawNav($i, $page);
            }
        }
              
        $this->getChildren('group')->getChildren('buttons')->setChildren($values);
    }
    
    private function _drawNav($i)
    {
        $a_node = new node('a');
        $a_node->getAttributes()->getClass()->setItems(array('btn', 'btn-default'));
        $a_node->getAttributes()->addAttr('href', '#');
        $a_node->getAttributes()->addAttr('data-number', $i);
        
        if ($i == $this->_page)
        {
            $a_node->getAttributes()->getClass()->addItems('btn-dark');
            $a_node->getAttributes()->getClass()->addItems('active');
        }
        
        $a_node->setChildren($i);
           
        return $a_node;
    }
}

?>