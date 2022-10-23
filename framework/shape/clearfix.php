<?

namespace framework\shape;

use framework\dom\node;

class clearfix extends node
{
    public function __construct()
    {
        parent::__construct('div');
        $this->getAttributes()->getClass()->addItems('clearfix');
    }
}

?>