<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of Form
 *
 * @author RD
 */
class Arzf_Form extends Zend_Form
{
    public function __construct($options = null)
    {
        // JQuery
        $this->addPrefixPath(
            'ZendX_JQuery_Form_Decorator',
            'ZendX/JQuery/Form/Decorator', 'decorator'
        )->addPrefixPath(
            'ZendX_JQuery_Form_Element',
            'ZendX/JQuery/Form/Element', 'element'
        )->addElementPrefixPath(
            'ZendX_JQuery_Form_Decorator',
            'ZendX/JQuery/Form/Decorator', 'decorator'
        )->addDisplayGroupPrefixPath(
            'ZendX_JQuery_Form_Decorator',
            'ZendX/JQuery/Form/Decorator'
        );

        // Arzf
        $this->addPrefixPath(
            'Arzf_Form_Element',
            'Arzf/Form/Element',
            'element'
        );
        
        $this->addElementPrefixPath(
            'Arzf_Validate',
            'Arzf/Validate',
            'validate'
        );

        parent::__construct($options);
        
        // Default class
        $this->setAttrib('class', 'Arzf-form');
        $dec = $this->getDecorator('HtmlTag');
        if ($dec) {
            $dec->setOption('class', 'Arzf-form');
        }
    }
}

?>
