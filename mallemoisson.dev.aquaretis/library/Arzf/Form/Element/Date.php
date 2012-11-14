<?php
/**
 * OFT Framework
 *
 * @package    Oft_Form
 * @subpackage Form_Element
 */


/**
 * Element de formulaire de type "date".
 *
 * @author Eric Colinet
 * @package    Oft_Form
 * @subpackage Form_Element
 */
class Arzf_Form_Element_Date extends ZendX_JQuery_Form_Element_UiWidget
{
    public $helper = "datePicker";
    
    protected static $_defaultDatePickerOptions = null;
    
    public static function initDatePicker($view)
    {
        // Déjà initialisé ?
        if (self::$_defaultDatePickerOptions!==null) {
            return;
        }
        
        self::$_defaultDatePickerOptions = array(
            'dateFormat' => Oft_Date::getFormat(Oft_Date::JQUERY), //dd/mm/yy',
            'defaultDate' => 0,
            'showOn' => 'both', // 'button'
            'buttonImage' =>
                $view->baseUrlMedia('oft/G0R1/images/calendar.gif'),
            'buttonImageOnly' => true,
            'changeMonth'     => true,
            'changeYear'      => true,
        );
        
        // Inclusion des resources de langue (2 premier caractères)
        $locale = Oft_App::getInstance()->getLocale();
        $locale = substr($locale, 0, 2);
        if (!empty($locale) && $locale!='en') {
            $view->jQuery()
                ->addJavascriptFile($view->baseUrlMedia('jquery/1.4.2/i18n/jquery.ui.datepicker-' . $locale . '.js'));
        }
    }
    
    public static function getDefaultDatePickerOptions()
    {
        return self::$_defaultDatePickerOptions;
    }
    
    public function init()
    {
        self::initDatePicker($this->getView());
        
        $params = array_merge(self::getDefaultDatePickerOptions(), $this->getJQueryParams());
        $this->setJQueryParams($params);
        
        // Validateur par défaut
        $this->addValidator(
            new Zend_Validate_Date(array('format' => Oft_Date::getFormat()))
        );
    }
    
    public function render(Zend_View_Interface $view = null)
    {
        if ($this->getAttrib('disabled')) {
            $this->setJQueryParams(
                array(
                    'disabled' => true,
                    'showOn' => 'focus',
                )
            );
        }
        return parent::render($view);
    }
    
    public function setValue($value)
    {
        $date = new Oft_Date($value);
        return parent::setValue($date->toString());
    }

    public function getSqlValue()
    {
        $value = parent::getValue();
        $date = new Oft_Date($value);
        return $date->toString(Oft_Date::SQL);
    }
}
