<?php

/*
 *  This file is part of open source system FreenetIS
 *  and it is release under GPLv3 licence.
 *
 *  More info about licence can be found:
 *  http://www.gnu.org/licenses/gpl-3.0.html
 *
 *  More info about project can be found:
 *  http://www.freenetis.org/
 */

/**
 * Interface for service classes that defines that each service must have
 * a constructor that provides access to the service factory that was used for
 * injection of this service class instance. Access to service factory is
 * important because it allows to inject other services and then use them.
 *
 * @author Ondřej Fibich <fibich@freenetis.org>
 * @since 1.2
 */
interface IService
{

    /**
     * Constructor that takes service factory that was used for creating
     * of this service.
     *
     * @param ServiceFactory $factory
     */
    public function __construct(ServiceFactory $factory);

}
