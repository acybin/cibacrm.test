<?

namespace framework\shape;

use framework\dom\node;
use framework\enum;

class menu extends node
{
    public function __construct()
    {
        parent::__construct('menu');
        $ul_node = new ul('ul');
        $this->setChildren(array('ul' => $ul_node)); 
    }
}