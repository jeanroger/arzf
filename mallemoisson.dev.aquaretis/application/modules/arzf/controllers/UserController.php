<?php
/**
 */


/**
 * Contr�lleur de gestion de l'authentification.
 *
 */
class Arzf_UserController extends Oft_UserController
{
    /**
     * Action de connection
     */
    public function loginAction()
    {
        // Titre sp�cifique � l'�cran de connexion
        $appOptions = $this->getApp()->getOption('app');
        if ($appOptions && isset($appOptions['auth_title']) && $appOptions['auth_title']) {
            $this->_setTitle($appOptions['auth_title']);
        }
        
        $this->_setSubTitle("Connexion");
        
        $auth = Oft_App::getInstance()->getAuth();
        
        $form = $auth->getForm();
        
        // Authentification directe
        if ($form===null) {
            if ($auth->authenticate()) {
                $this->_successRedirect();
            } else {
                // Erreur d'authentification
                $flashMessage = 'L\'authentification � �chou�e';
                $authMessages = $auth->getMessages();
                if (count($authMessages)) {
                    $flashMessage .= ' : '
                        . implode(', ', $authMessages);
                }
                $this->_addMessage($flashMessage, 'err');
            }
            return;
        }
        
		$this->_helper->layout->setLayout('is_guest');
        $this->view->form = $form;

        if ($this->getRequest()->isPost()) {
            if ($this->view->form->isValid($this->getRequest()->getPost()) ) {
                $values = $this->view->form->getValues();

                $result = $auth->authenticate($values);

                if ($result) {
                    // Ok
                    $this->_successRedirect();
                } else {
                    // Erreur d'authentification
                    $flashMessage = 'L\'authentification � �chou�e';
                    $authMessages = $auth->getMessages();
                    if (count($authMessages)) {
                        $flashMessage .= ' : '
                            . implode(', ', $authMessages);
                    }
                    $this->_addMessage($flashMessage, 'err');
                }
            } else {
                // Erreur de saisie
                $this->_addMessage('Saisie non valide', 'warn');
            }
        }
    }

}

