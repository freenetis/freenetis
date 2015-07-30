<?php

/* 
 * This file is a part of PHP-HTTP-Auth-server library, released under terms 
 * of GPL-3.0 licence. Copyright (c) 2014, UnArt Slavičín, o.s. All rights 
 * reserved.
 */

namespace phphttpauthserver;

/**
 * The "BasicHttpAuth" class provides implementation of Basic HTTP 
 * Authentication server side mechanism.
 *
 * @author Ondřej Fibich
 */
class BasicHttpAuth extends HttpAuth {
    
    /**
     * Creates HTTP auth handler in given realm with account given by passsed
     * manager.
     * 
     * @param IAccountManager $accountManager Account manager
     * @param string $realmName Realm name
     * @throws \InvalidArgumentException on empty realm name or account manager
     */
    public function __construct(IAccountManager $accountManager, $realmName) {
        parent::__construct($accountManager, $realmName);
    }
    
    /**
     * Performs HTTP Basic auth using server PHP_AUTH_USER and PHP_AUTH_PW
     * variables.
     * 
     * @return HttpAuthResponse response object
     */
    public function auth() {
        $response = new HttpAuthResponse();
        // no login informations send?
        if (!isset($_SERVER['PHP_AUTH_USER'])) {
            $wa = 'Basic realm="' . $this->realm . '"';
            return $response->setPassed(FALSE)
                    ->addHeader('WWW-Authenticate', $wa);
        }
        // prepare
        $username = $_SERVER['PHP_AUTH_USER'];
        $password = $_SERVER['PHP_AUTH_PW'];
        // get user
        $valid_password = $this->accountManager->getUserPassword($username);
        // user not exists?
        if ($valid_password === FALSE) {
            return $response->addError('Wrong Credentials');
        }
        // check password
        if ($password != $valid_password) {
            return $response->addError('Wrong Credentials');
        }
        // auth success
        return $response->setUsername($username);
    }
    
}
