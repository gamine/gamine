<?php

/**
 * @author    Daniel André Eikeland <dae@redpill-linpro.com>
 * @copyright 2011 Redpill Linpro AS
 * @license   http://www.gnu.org/copyleft/lesser.html  LGPL License 2.1
 */

namespace RedpillLinpro\GamineBundle;

class Gamine
{
    
    protected $_backends = array();
    protected $_backendsconfig = array();
    
    protected $_managers = array();
    protected $_managersconfig = array();
    
    public function __construct($backends = array(), $managers = array())
    {
        $this->_backendsconfig = $backends;
        $this->_managersconfig = $managers;
    }
    
    protected function _initBackend($backend)
    {
        if (!array_key_exists($backend, $this->_backendsconfig))
            throw new Exception('This backend has not been configured. Please check your services configuration.');
        
        $classname = $this->_backendsconfig[$backend]['class'];
        $this->_backends[$backend] = new $classname($this->_backendsconfig[$backend]['arguments']);
    }
    
    /**
     * Returns a service backend
     * 
     * @param string $backend
     * 
     * @return \RedpillLinpro\GamineBundle\Services\ServiceInterface
     */
    public function getBackend($backend)
    {
        if (!array_key_exists($backend, $this->_backends)) {
            $this->_initBackend($backend);
        }
        return $this->_backends[$backend];
    }

    protected function _initManager($manager)
    {
        if (!array_key_exists($manager, $this->_managersconfig))
            throw new Exception('This manager has not been configured. Please check your services configuration.');
        
        $classname = $this->_managersconfig[$manager]['class'];
        $this->_managers[$manager] = new $classname($this->getBackend($this->_managersconfig[$manager]['access_service']), $this);
    }
    
    /**
     * Returns an object manager
     * 
     * @param string $manager The manager as identified by the identifier in services.yml
     * 
     * @return \RedpillLinpro\GamineBundle\Manager\BaseManager
     */
    public function getManager($manager)
    {
        if (!array_key_exists($manager, $this->_managersconfig))
            throw new Exception('This manager has not been configured. Please check your services configuration.');
        
        if (!array_key_exists($this->_managersconfig[$manager]['class'], $this->_managers)) {
            $this->_initManager($manager);
        }
        return $this->_managers[$manager];
    }
    
    public function getClassManager($class)
    {
        if ($class[0] === '\\') {
            $class = substr($class,1);
        }
        $manager = false;
        foreach ($this->_managersconfig as $managerKey => $config) {
            if ($class === $config['class']) {
                $manager = $managerKey;
                break;
            }
        }
        if ($manager === false) {
            throw new \Exception('No manager found for '.$class);
        }
        if (!array_key_exists($manager, $this->_managers)) {
            $this->_initManager($manager);
            if (!array_key_exists($manager, $this->_managers))
                throw new Exception('No services has been configured for this manager class. Please check your services configuration.');
        }
        return $this->_managers[$manager];
    }
    
}
