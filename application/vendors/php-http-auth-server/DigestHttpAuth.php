<?php

/* 
 * This file is a part of PHP-HTTP-Auth-server library, released under terms 
 * of GPL-3.0 licence. Copyright (c) 2014, UnArt Slavičín, o.s. All rights 
 * reserved.
 */

namespace phphttpauthserver;

/**
 * The "DigestHttpAuth" class provides implementation of Digest HTTP 
 * Authentication server side mechanism.
 * 
 * This implementation requires mod_auth_digest to be enabled on server.
 * 
 * This implementation is based on http://php.net/manual/en/features.http-auth.php.
 *
 * @author Ondřej Fibich
 */
class DigestHttpAuth extends HttpAuth {
    
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
     * Get nonce - protection again replay attacks.
     * 
     * @return string
     */
    protected function generateNonce() {
        return uniqid();
    }
    
    /**
     * Get opaque - server identificator.
     * 
     * @return string
     */
    protected function getOpaque() {
        return md5($this->realm);
    }

    /**
     * Get digest auth HTTP header from php server variables.
     * 
     * @return string|null header or null
     */
    private static function getAuthDigestHeader() {
        if (isset($_SERVER['PHP_AUTH_DIGEST']))    {
            return $_SERVER['PHP_AUTH_DIGEST'];
        } else if (isset($_SERVER['HTTP_AUTHORIZATION'])) {
            $httpAuth = $_SERVER['HTTP_AUTHORIZATION'];
            if (strncasecmp($httpAuth, 'digest', 6) === 0) {
                return substr($httpAuth, 7);
            }
        }
        return NULL;
    }
    
    /**
     * Performs HTTP Digest auth using server PHP_AUTH_DIGEST and REQUEST_METHOD
     * variables.
     * 
     * @return HttpAuthResponse response object
     */
    public function auth() {
        $httpDigest = self::getAuthDigestHeader();
        $requestMethod = $_SERVER['REQUEST_METHOD'];
        $response = new HttpAuthResponse();
        // no digest header sended
        if (empty($httpDigest)) {
            $wa = sprintf('Digest realm="%s",qop="auth",nonce="%s",opaque="%s"',
                    $this->realm, $this->generateNonce(), $this->getOpaque());
            return $response->setPassed(FALSE)
                    ->addHeader('WWW-Authenticate', $wa);
        }
        // no request method?
        if (empty($requestMethod)) {
            return $response->addError('Request method empty');
        }
        // analyze the PHP_AUTH_DIGEST variable
        try {
            $data = self::httpDigestParse($httpDigest);
        } catch (\InvalidArgumentException $ex) {
            return $response->addError('Invalid HTTP Auth Digest header: ' 
                    . $ex->getMessage());
        }
        // get user
        $userPassword = $this->accountManager->getUserPassword(
                $data['username']);
        // user not exists?
        if ($userPassword === FALSE) {
            return $response->addError('Wrong Credentials');
        }
        // generate the valid response
        $validResponse = $this->calculateValidResponse(
                $data, $requestMethod, $userPassword);
        // check client response
        if ($data['response'] != $validResponse) {
            echo $requestMethod . ' ' . $data['response'] . '!=' . $validResponse . "\n";
            return $response->addError('Wrong Credentials');
        }
        // auth success
        return $response->setUsername($data['username']);
    }
    
    /**
     * Calculate valid response that should be received from client.
     * 
     * @param array $data
     * @param string $requestMethod
     * @param string $password
     * @return string
     */
    private function calculateValidResponse($data, $requestMethod, $password) {
        $A1 = md5($data['username'] . ':' . $this->realm . ':' . $password);
        $A2 = md5($requestMethod . ':' . $data['uri']);
        return md5($A1 . ':' . $data['nonce'] . ':' . $data['nc']
                . ':' . $data['cnonce'] . ':' . $data['qop'] . ':' . $A2);
    }
    
    /**
     * Parses HTTP auth header sended by client.
     * 
     * @param string $headerStrValue 
     * @return array parsed client data asassociative array with key: nonce, 
     *        nc, cnonce, qop, username, uri, response
     * @throws \InvalidArgumentException if not all required fields were provided
     */
    private static function httpDigestParse($headerStrValue) {
        // protect against missing data
        $neededParts = array(
            'nonce' => 1, 'nc' => 1, 'cnonce' => 1, 'qop' => 1, 'username' => 1,
            'uri' => 1, 'response' => 1
        );
        $data = array();
        $keys = implode('|', array_keys($neededParts));

        $matches = array();
        preg_match_all('@(' . $keys . ')=(?:([\'"])([^\2]+?)\2|([^\s,]+))@',
                $headerStrValue, $matches, PREG_SET_ORDER);

        foreach ($matches as $m) {
            $data[$m[1]] = $m[3] ? $m[3] : $m[4];
            unset($neededParts[$m[1]]);
        }

        if ($neededParts) {
            $npStr = implode(', ', array_keys($neededParts));
            throw new \InvalidArgumentException('Missing fields: ' . $npStr);
        }
        
        return $data;
    }

}
