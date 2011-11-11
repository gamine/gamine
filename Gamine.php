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

    static protected $_cache_directory;

    protected $_backends = array();
    protected $_backendsconfig = array();

    protected $_datasource_meta = array();

    protected $_managers = array();
    protected $_entityconfigs = array();
    protected $_cache_enabled = false;

    public static function setCacheDirectory($cache_directory)
    {
        self::$_cache_directory = $cache_directory;
    }

    protected function initCache()
    {

        if (!file_exists(self::$_cache_directory)) {
            mkdir(self::$_cache_directory);
        }
    }

    public static function getCacheDirectory()
    {
        return self::$_cache_directory;
    }

    public function describeClass($class)
    {
        $return_array = null;
        if (self::$_cache_directory) {
            $filename = self::getCacheDirectory() . str_replace("\\", '_', $class) . '_description.cache.php';
            if (file_exists($filename)) {
                $return_array = unserialize(file_get_contents($filename));
            }
        }
        if($return_array === null) {
            $reflection_class = new \ReflectionClass($class);
            $reader = new \Doctrine\Common\Annotations\AnnotationReader(new \Doctrine\Common\Cache\ArrayCache());
            $reader->setEnableParsePhpImports(true);
            $reader->setDefaultAnnotationNamespace('RedpillLinpro\\GamineBundle\\Annotations\\');
            $return_array = array();
            foreach ($reflection_class->getProperties() as $property) {
                $is_id = false;
                $annotations = $reader->getPropertyAnnotations($property);
                foreach ($annotations as $annotation) {
                    if (!method_exists($annotation, 'getKey')) continue;
                    switch ($annotation->getKey()) {
                        case 'id' :
                            $return_array['primary_key']['property'] = $property->name;
                            $return_array['primary_key']['key'] = $property->name;
                            $is_id = true;
                            break;
                    }
                    $return_array['properties'][$property->name][$annotation->getKey()] = (array) $annotation;
                }
                if ($is_id && isset($return_array['properties'][$property->name]['column']['name'])) {
                    $return_array['primary_key']['key'] = $return_array['properties'][$property->name]['column']['name'];
                }
            }
            if (self::$_cache_directory) file_put_contents($filename, serialize($return_array));
        }
        return $return_array;
    }

    public function __construct($backends = array(), $managers = array(), $cache_enabled = false)
    {
        $this->_backendsconfig = $backends;
        $this->_entityconfigs = $managers;

        if($cache_enabled){
            self::initCache();
        }else{
            self::$_cache_directory = null;
        }


    }

    protected function _initBackend($backend)
    {
        if (!array_key_exists($backend, $this->_backendsconfig))
            throw new \InvalidArgumentException('This backend has not been configured. Please check your services configuration.');

        $classname = $this->_backendsconfig[$backend]['class'];

        if (!class_exists($classname))
            throw new \UnexpectedValueException("The class this backend depends on ({$classname}) does not exist in this scope. Please check your services configuration.");

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

        if (!array_key_exists($backend, $this->_backends))
            throw new \RuntimeException("This backend ({$backend}) did not instantiate properly. Please check your services configuration.");

        return $this->_backends[$backend];
    }

    protected function _initManager($entity)
    {
        if (!array_key_exists($entity, $this->_entityconfigs))
            throw new \InvalidArgumentException('This manager has not been configured. Please check your services configuration.');

        $classname = $this->_entityconfigs[$entity]['manager']['class'];

        if (!class_exists($classname))
            throw new \UnexpectedValueException("The class this manager depends on ({$classname}) does not exist in this scope. Please check your services configuration.");

        $entity_config = $this->_entityconfigs[$entity]['manager']['arguments'];
        $options = isset($entity_config['cache_service']) ? array('cache_service' => $entity_config['cache_service']) : null;

        $this->_managers[$entity] = new $classname($this, $entity, $this->getBackend($entity_config['access_service']), $options);
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

        if (!array_key_exists($entity, $this->_managers))
            throw new \RuntimeException("This manager for this entity ({$entity}) did not instantiate properly. Please check your services configuration.");
                
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
            throw new \Exception('Missing collection configuration. Please check your services configuration.');

        return $this->_entityconfigs[$entity]['collection'];
    }

    public function getEntityResource($entity)
    {
        if (!isset($this->_entityconfigs[$entity]['entity']))
            throw new \Exception('Missing entity configuration. Please check your services configuration.');

        return $this->_entityconfigs[$entity]['entity'];
    }

    public function getModelClassname($entity)
    {
        if (!isset($this->_entityconfigs[$entity]['model']['class']))
            throw new \Exception('Missing model class configuration. Please check your services configuration.');

        return $this->_entityconfigs[$entity]['model']['class'];
    }

    protected function _populateDataSourceMetaInformation($entity)
    {
        if (!array_key_exists($entity, $this->_datasource_meta)) {
            $classname = $this->getModelClassname($entity);
            $this->_datasource_meta[$entity] = self::describeClass($classname);
        }
    }

    public function getMappedProperties($entity)
    {
        $this->_populateDataSourceMetaInformation($entity);
        return $this->_datasource_meta[$entity]['properties'];
    }

    public function getMappedProperty($entity,$property)
    {
        $this->_populateDataSourceMetaInformation($entity);
        return $this->_datasource_meta[$entity]['properties'][$property];
    }

    public function getPrimaryKeyProperty($entity)
    {
        $this->_populateDataSourceMetaInformation($entity);
        if (!array_key_exists('primary_key', $this->_datasource_meta[$entity]))
            throw new \Exception('Missing @Id on '. $entity);

        return $this->_datasource_meta[$entity]['primary_key']['property'];

    }
}
