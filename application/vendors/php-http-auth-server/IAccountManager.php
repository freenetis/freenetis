<?php

/* 
 * This file is a part of PHP-HTTP-Auth-server library, released under terms 
 * of GPL-3.0 licence. Copyright (c) 2014, UnArt Slavičín, o.s. All rights 
 * reserved.
 */

namespace phphttpauthserver;

/**
 * Account manager provides user accounts information required to auth.
 * It is a bridge between storage of user accounts and auth module which
 * is important in order to maintain low coupling GRASP paradigm.
 * 
 * @author Ondřej Fibich
 */
interface IAccountManager {
    
    /**
     * Gets password of user with given username.
     * 
     * @return string|boolean user password or its hash or FALSE if no user
     *        with given username exists or some error occured
     */
    public function getUserPassword($username);
    
}
