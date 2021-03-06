<?php

class LoginController extends Zend_Controller_Action
{
	public function indexAction()
    {
        $this->view->form = $this->getForm();
    }

	public function preDispatch()
    {
        if (Zend_Auth::getInstance()->hasIdentity()) {
            // If the user is logged in, we don't want to show the login form;
            // however, the logout action should still be available
            if ('logout' != $this->getRequest()->getActionName()) {
                $this->_helper->redirector('index', 'index');
            }
        } else {
            // If they aren't, they can't logout, so that action should
            // redirect to the login form
            if ('logout' == $this->getRequest()->getActionName()) {
                $this->_helper->redirector('index');
            }
        }
    }

	public function getForm()
    {
        return new Users_Form_Login(array(
            'action' => '/login/process',
            'method' => 'post',
        ));
    }

    public function getAuthAdapter(array $params)
    {
    	$db = $this->_getParam('db');
    	
        // Leaving this to the developer...
        // Makes the assumption that the constructor takes an array of
        // parameters which it then uses as credentials to verify identity.
        // Our form, of course, will just pass the parameters 'username'
        // and 'password'.
        $adapter = new Zend_Auth_Adapter_DbTable(
        	$db,
            'users',
            'username',
            'password',
        	'MD5(?)'
        );
        
        $adapter->setIdentity($params['username'])
        	->setCredential($params['password']);
        
        return $adapter;
    }

	public function processAction()
    {
        $request = $this->getRequest();

        // Check if we have a POST request
        if (!$request->isPost()) {
            return $this->_helper->redirector('index');
        }

        // Get our form and validate it
        $form = $this->getForm();
        if (!$form->isValid($request->getPost())) {
            // Invalid entries
            $this->view->form = $form;
            return $this->render('index'); // re-render the login form
        }

        // Get our authentication adapter and check credentials
        $adapter = $this->getAuthAdapter($form->getValues());
        $auth    = Zend_Auth::getInstance();
        $result  = $auth->authenticate($adapter);
        if (!$result->isValid()) {
            // Invalid credentials
            $form->setDescription('Invalid credentials provided');
            $this->view->form = $form;
            return $this->render('index'); // re-render the login form
        }

        // get user information from database as store it in auth adapter for persistance use
        $storage = $auth->getStorage();
        $username = $storage->read();
        $Users = new Users_Model_Users();
        $userInfo = $Users->getUserInfo($username);

        $info['id'] = $userInfo->id;
        $info['username'] = $userInfo->username;
        $info['real_name'] = $userInfo->real_name;
        $info['roles'] = $userInfo->roles;
        $storage->write($info);

        // We're authenticated! Redirect to the home page
        $this->_helper->redirector('index', 'index');
    }
    
    public function logoutAction()
    {
        Zend_Auth::getInstance()->clearIdentity();
        $this->_helper->redirector('index', 'index'); // back to homepage
    }
    
    public function subscribeAction()
    {
    	$this->_helper->layout->disableLayout();
    	$request = $this->getRequest();

    	$Users = new Users_Model_Users();
    	$Users->subscribeEmail($request->getParam('email'));
    	
    	$this->view->email = $request->getParam('email');
    }
    
}