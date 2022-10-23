<?

namespace framework\gen;

class tree
{
    private $_leaf = null; //текущий лист дерева
    private $_start_leaf = null; // верхушка дерева
    
    public function __construct($path)
    {
        srand($path);
    }
    
    public function addLeaf($leaf)
    {
        if ($this->_leaf)
        {
            $this->_leaf->setNext($leaf); 
        }
        else
        {
            $this->_start_leaf = $leaf; 
        }
           
        $leaf->setCurrent(rand(0, count($leaf->getVariants())-1));
        $this->_leaf = $leaf;
    }
    
    public function browse()
    {
        if (!$this->_start_leaf) return;
        
        $words = array();
        $leaf = $this->_start_leaf;
      
        do 
        {
            $word = $leaf->getVariants($leaf->getCurrent());
            if ($word)
                $words[] = $word;
        } while (($leaf = $leaf->getNext()) != null);
        
        return implode(' ', $words);      
    }
    
    public function insertAfter($number, $new_leaf)
    {
        if (!$this->_start_leaf || $number < 0) return;

        $new_leaf->setCurrent(rand(0, count($new_leaf->getVariants())-1));
        
        $leaf = $this->_start_leaf;
        $i = 0;
        do 
        {
            if ($i == $number) break;
            $i++;
        } while (($leaf = $leaf->getNext()) != null);
        
        if (!$leaf) return;
        
        $t = $leaf->getNext();
        $leaf->setNext($new_leaf);
        $new_leaf->setNext($t); 
    }
}

?>