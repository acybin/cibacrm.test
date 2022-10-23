<?

namespace framework\shape;

use framework\dom\node;
use framework\enum;

class page
{
    private static $_instance = null;
    
    private $_js;
    private $_css; 
    private $_html;
    
    private function __construct()
    {
        $this->_js = new enum();
        $this->_js->setSign(',');
        $this->_css = new enum();
        $this->_css->setSign(',');
        
        $html = new node('html');
        $html->getAttributes()->addAttr('lang', 'RU');
    
        $head_node = new node('head'); 
        $html->addChildren('head', $head_node);        
            
        $body_node = new node('body');
        $html->addChildren('body', $body_node);
        
        $header_node = new node('header');
        $footer_node = new node('footer');
        $main_node = new node('main');
        
        $html->getChildren('body')->setChildren(array('header' => $header_node, 'main' => $main_node, 'footer' => $footer_node));
        
        $this->_html = $html;
    }
    
    private function __clone() 
    {
        
    }
    
    public static function getInstance()
    {
        if (null === self::$_instance) 
        {
            self::$_instance = new page();
        }
        
        return self::$_instance;
    }
    
    public function getHtml()
    {
        return $this->_html;
    }
    
    public function getCss()
    {
        return $this->_css;
    }
    
    public function getJs()
    {
        return $this->_js;
    }
    
    public function getHeader()
    {
        return $this->_html->getChildren('body')->getChildren('header');
    }
    
    public function getFooter()
    {
        return $this->_html->getChildren('body')->getChildren('footer');
    }
    
    public function getMain()
    {
        return $this->_html->getChildren('body')->getChildren('main');
    }
    
    public function __toString()
    {
        if ($this->_css->getItems())
        {
            $css_node = new node('link', false);
            $css_node->getAttributes()->addAttr('rel', 'stylesheet');
            $css_node->getAttributes()->addAttr('type', 'text/css');
            $css_node->getAttributes()->addAttr('href', '/min/f='.$this->_css.'&123456');            
            $this->_html->getChildren('head')->addChildren($css_node);
        }
            
        if ($this->_js->getItems())
        {
            $js_node = new node('script');
            $js_node->getAttributes()->addAttr('type', 'text/javascript');
            $js_node->getAttributes()->addAttr('src', '/min/f='.$this->_js.'&123456'); 
            $this->_html->getChildren('body')->addChildren($js_node);
        }
            
        return '<!DOCTYPE html>' . (string) $this->_html;       
    }
}

?>