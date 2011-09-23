<?php
/**
 * @author    Daniel André Eikeland <dae@redpill-linpro.com>
 * @copyright 2011 Redpill Linpro AS
 * @license   http://www.gnu.org/copyleft/lesser.html  LGPL License 2.1
 */

namespace RedpillLinpro\GamineBundle;

use \Exception;

class Gamine
{

    protected $_backends = array();
    protected $_backendsconfig = array();

    protected $_datasource_meta = array();

    protected $_managers = array();
    protected $_entityconfigs = array();

    public function __construct($backends = array(), $managers = array())
    {
        $this->_backendsconfig = $backends;
        $this->_entityconfigs = $managers;

        /** @todo look for cached meta data, populate if there **/
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

    protected function _initManager($entity)
    {
        if (!array_key_exists($entity, $this->_entityconfigs))
            throw new Exception('This manager has not been configured. Please check your services configuration.');

        $classname = $this->_entityconfigs[$entity]['manager']['class'];
        $this->_managers[$entity] = new $classname($this, $entity, $this->getBackend($this->_entityconfigs[$entity]['manager']['arguments']['access_service']));
    }

    /**
     * Returns an object manager
     *
     * @param string $entity The manager as identified by the identifier in services.yml
     *
     * @return \RedpillLinpro\GamineBundle\Manager\BaseManager
     */
    public function getManager($entity)
    {
        if (!array_key_exists($entity, $this->_managers)) {
            $this->_initManager($entity);
        }
        return $this->_managers[$entity];
    }


    public function instantiateModel($entity)
    {
        $classname = $this->_entityconfigs[$entity]['model']['class'];

        $object = new $classname();
        $object->injectGamineService($this, $entity);
        return $object;
    }

    public function getCollectionResource($entity)
    {
        if (!isset($this->_entityconfigs[$entity]['collection']))
            throw new Exception('Missing collection configuration. Please check your services configuration.');

        return $this->_entityconfigs[$entity]['collection'];
    }

    public function getEntityResource($entity)
    {
        if (!isset($this->_entityconfigs[$entity]['entity']))
            throw new Exception('Missing entity configuration. Please check your services configuration.');

        return $this->_entityconfigs[$entity]['entity'];
    }

    public function getModelClassname($entity)
    {
        if (!isset($this->_entityconfigs[$entity]['model']['class']))
            throw new Exception('Missing model class configuration. Please check your services configuration.');

        return $this->_entityconfigs[$entity]['model']['class'];
    }

    public function getMappedProperties($entity)
    {
        if (!array_key_exists($entity, $this->_datasource_meta)) {
            $classname = $this->getModelClassname($entity);
            $this->_datasource_meta[$entity] = $classname::describe();
        }

        return $this->_datasource_meta[$entity]['properties'];
    }

    public function getMappedProperty($entity,$property)
    {
        if (!array_key_exists($entity, $this->_datasource_meta)) {
            $classname = $this->getModelClassname($entity);
            $this->_datasource_meta[$entity] = $classname::describe();
        }

        return $this->_datasource_meta[$entity]['properties'][$property];
    }

    public function getPrimaryKeyProperty($entity)
    {
        if (!array_key_exists($entity, $this->_datasource_meta)) {
            $classname = $this->getModelClassname($entity);
            $this->_datasource_meta[$entity] = $classname::describe();
        }
        if (!array_key_exists('primary_key', $this->_datasource_meta[$entity]))
            throw new \Exception('Missing @Id on '. $entity);

        return $this->_datasource_meta[$entity]['primary_key']['property'];

    }
}
