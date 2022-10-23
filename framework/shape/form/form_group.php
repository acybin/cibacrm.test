<?

namespace framework\shape\form;

use framework\dom\node;

class form_group extends node 
{
    public function __construct($class = 'form-item')
    {
        parent::__construct('div');
        $classes = array('col-md-12', 'col-sm-12', 'col-xs-12', 'form-group'); 
        $this->getAttributes()->getClass()->setItems($classes);
    }
}

?>