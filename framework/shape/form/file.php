<?

namespace framework\shape\form;

use framework\dom\node;

class file extends node
{
    public function __construct()
    {
        parent::__construct('div');
        $this->getAttributes()->getClass()->setItems(array('form', 'form-horizontal', 'form-label-left'));
        
        $div = new node('div');
        $div->getAttributes()->getClass()->setItems(array('col-md-12', 'col-sm-12', 'col-xs-12', 'form-group'));
        
        $label_node = new node('label');
        $label_node->getAttributes()->getClass()->setItems(array('control-label', 'col-md-3', 'col-sm-3', 'col-xs-12'));
        $label_node->addChildren('Файл');
        
        $div_wrapper = new node('div');
        $div_wrapper->getAttributes()->getClass()->setItems(array('col-md-9', 'col-sm-9', 'col-xs-12'));
        
        $form = new form();
        $form->getAttributes()->getClass()->addItems('dropzone');
        $form->getAttributes()->addAttr('action', '/admin/');
        $form->getAttributes()->addAttr('id', 'upload');
        
        $hidden = new hidden();
        $hidden->setName('op');
        $hidden->setValue('upload');                
        $form->addChildren($hidden);
        
        $div_wrapper->addChildren($form);
        $div->addChildren($label_node);
        $div->addChildren($div_wrapper);
        
        $this->addChildren($div);
    }
}

?>