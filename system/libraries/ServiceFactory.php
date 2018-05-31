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
 * Factory class for service layer class that provides access to service
 * class instances and manage live cycle of these instances.
 *
 * @author Ondřej Fibich <fibich@freenetis.org>
 * @since 1.2
 */
class ServiceFactory
{
    /**
     * Namespace that contains all service classes. It must ends with \.
     */
    const SERVICE_NS = '\\freenetis\\service\\';

    /**
     * Pool of service instances.
     *
     * @var array
     */
    private $instances_pool = array();

    /**
     * Inject (provide) service instance with given class suffix.
     *
     * @param string $class_suffix class name with namespace relative
     *      to namespace given by string constant SERVICE_NS
     * @return IService service instance
     */
    private function inject($class_suffix)
    {
        // trim invalid name space separators
        $t_class_suffix = trim($class_suffix, '\\');
        // already initilized?
        if (!array_key_exists($t_class_suffix, $this->instances_pool))
        {
            // load service class - this part will be removed when FreenetIS
            // class loader will be able to load classes by their namespace
            // and name
            $file_name = str_replace('\\', DIRECTORY_SEPARATOR, $t_class_suffix);
            require_once APPPATH . '/services/' . $file_name . EXT;
            // create service instance with passed factory
            $class = self::SERVICE_NS . $t_class_suffix;
            $this->instances_pool[$t_class_suffix] = new $class($this);
        }
        // provide service instance
        return $this->instances_pool[$t_class_suffix];
    }

    /* ************************************************************************\
     * Services available to inject.
    \* ************************************************************************/

    /**
     * @return \freenetis\service\core\AclService
     */
    public function injectCoreAcl()
    {
        return $this->inject('core\AclService');
    }

    /**
     * @return \freenetis\service\core\DatabaseService
     */
    public function injectCoreDatabase()
    {
        return $this->inject('core\DatabaseService');
    }

    /**
     * @return \freenetis\service\core\DatabaseInitService
     */
    public function injectCoreDatabaseInit()
    {
        return $this->inject('core\DatabaseInitService');
    }

    /**
     * @return \freenetis\service\core\SetupService
     */
    public function injectCoreSetup()
    {
        return $this->inject('core\SetupService');
    }

    /**
     * @return \freenetis\service\member\ExpirationCalcService
     */
    public function injectMemberExpirationCalc()
    {
        return $this->inject('member\ExpirationCalcService');
    }

}
