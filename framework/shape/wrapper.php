<?

namespace framework\shape;

use framework\dom\node;
use framework\enum;

class wrapper extends node
{
    protected $_op = '';
    protected $_rel;
    
    public function __construct($op)
    {
        parent::__construct('div');
        $this->getAttributes()->getClass()->addItems('wrapper');
        
        $this->_rel = new enum();
        $this->_rel->setSign(',');
        $this->getAttributes()->addAttr('data-rel', $this->_rel);
        $this->setOp($op);       
    }
    
    public function setOp($op)
    {
        if ($op !== '')
        {
            parent::setOp($op);
            $this->getAttributes()->setAttr('data-op', $this->_op);
        }
    }
}

?>