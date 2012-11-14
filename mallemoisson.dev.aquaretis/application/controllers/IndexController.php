<?php
/**
 * Contrôleur par défaut
 *
 * @package App
 */

/**
 * @package App
 */
class IndexController extends Oft_Controller_Action
{
    /**
     * Règles de validation définies pour le contrôleur
     * @var array
     */
    protected $_validatorRules = array(
        'id' => array('Int')
    );
    
    /**
     * Point d'entrée par défaut de l'application
     */
    public function indexAction ()
    {
		$identity = $this->getApp()->getCurrentIdentity();
		if ($identity->isGuest()) {
			$this->_forward('login', 'user', 'arzf');
		}
        $idParam = $this->_getParam('id', 0);
        $this->view->id = $idParam;
		$this->view->identity = $this->getApp()->getCurrentIdentity();
    }
}

