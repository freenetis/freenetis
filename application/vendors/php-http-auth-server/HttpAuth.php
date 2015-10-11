<?php

/* 
 * This file is a part of PHP-HTTP-Auth-server library, released under terms 
 * of GPL-3.0 licence. Copyright (c) 2014, UnArt Slavičín, o.s. All rights 
 * reserved.
 */

namespace phphttpauthserver;

/**
 * The "HttpAuth" abstract class defines requirements for all kinds of server
 * side HTTP auth mechanisms and allows their easy creation using factory
 * pattern.
 *
 * @author Ondřej Fibich
 */
abstract class HttpAuth {
    
    /**
     * Library version.
     */
    const VERSION = '0.1.1';

    /**
     * Available HttpAuth types that implements HttpAuth.
     *
     * @var array
     */
    private static $types = array(
        'basic'     => 'BasicHttpAuth',
        'digest'    => 'DigestHttpAuth'
    );
    
    /**
     * Factory method for creating available implementations of HttpAuth.
     * 
     * @param string $authType String type (e.g. basic, digest)
     * @param IAccountManager $accountManager Account manager
     * @param string $realmName Auth realm name
     * @return HttpAuth
     * @throws \InvalidArgumentException on unknown auth type, empty realm name
     *      or invalid account manager
     */
    public static final function factory($authType, $accountManager,
            $realmName) {
        // unknown?
        if (!array_key_exists($authType, self::$types)) {
            throw new \InvalidArgumentException('unknown type: ' . $authType);
        }
        // invalid account?
        if (empty($accountManager) || 
                !($accountManager instanceof IAccountManager)) {
            throw new \InvalidArgumentException('invalid account type');
        }
        // load class and create its instance with mandatory arguments
        $class_name = __NAMESPACE__ . '\\' . self::$types[$authType];
        return new $class_name($accountManager, $realmName);
    }
    
    /**
     * Realm name
     *
     * @var string
     */
    protected $realm;
    
    /**
     * Account manager for getting informations about users.
     * 
     * @var IAccountManager
     */
    protected $accountManager;

    /**
     * Creates HTTP auth handler in given realm with account given by passsed
     * manager.
     * 
     * @param IAccountManager $accountManager Account manager
     * @param string $realmName Realm name
     * @throws InvalidArgumentException on empty realm name or account manager
     */
    public function __construct(IAccountManager $accountManager, $realmName) {
        if (empty($realmName)) {
            throw new \InvalidArgumentException('empty realm name not allowed');
        }
        if (empty($accountManager)) {
            throw new \InvalidArgumentException('empty account manager not allowed');
        }
        $this->realm = $realmName;
        $this->accountManager = $accountManager;
    }
    
    /**
     * Performs HTTP auth using server prefetched data and server values.
     * 
     * @return HttpAuthResponse response object with response for auth
     */
    public abstract function auth();
    
}
