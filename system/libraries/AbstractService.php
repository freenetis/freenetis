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
 * Base implementation of IService interface that is used as base for all other
 * services.
 *
 * @author Ondřej Fibich <fibich@freenetis.org>
 * @since 1.2
 */
abstract class AbstractService implements IService
{
    /**
     * @var ServiceFactory
     */
    protected $factory;

    /**
     * Creates service with reference to factory that was used for its creation.
     *
     * @param ServiceFactory $factory
     * @throws InvalidArgumentException on null factory
     */
    public function __construct(ServiceFactory $factory)
    {
        if ($factory == NULL)
        {
            throw new InvalidArgumentException('null factory');
        }
        $this->factory = $factory;
    }

}
