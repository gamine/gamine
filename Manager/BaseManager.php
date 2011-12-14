<?php
/**
 *
 * @author    Thomas Lundquist <thomasez@redpill-linpro.com>
 * @copyright 2011 Thomas Lundquist
 * @license   http://www.gnu.org/copyleft/lesser.html  LGPL License 2.1
 *
 */

namespace RedpillLinpro\GamineBundle\Manager;

use \Exception;
use \RedpillLinpro\GamineBundle\Exceptions\ValidationError;

abstract class BaseManager
{
    /**
     * @var \RedpillLinpro\GamineBundle\Gamine
     */
    protected $gamine_service;

    /**
     * @var \RedpillLinpro\GamineBundle\Services\ServiceInterface
     */
    protected $access_service;

    /**
     * The entity key this manager is instantiated as - matches available
     * entity keys in the gamine entity config
     * 
     * @var string
     */
    protected $entity_key;

    protected $collection_resource;
    protected $entity_resource;

    protected $model;
    protected $_id_property = null;
    protected $_id_column = null;
    protected $_data_array_identifiable = null;

    public function __construct($gamine_service, $entity_key, $access_service)
    {
        $this->access_service = $access_service;
        $this->gamine_service = $gamine_service;
        $this->entity_key = $entity_key;
    }

    /**
     * Get a reflection class object valid for this static class, so we don't
     * have to instantiate a new one for each instance with the overhead that
     * comes with it
     *
     * @return \ReflectionClass
     */
    public function getReflectedClass()
    {
        if ($this->_reflectedclass === null) {
            $this->_reflectedclass = new \ReflectionClass($this->model);
        }
        return $this->_reflectedclass;
    }

    /**
     * This method is called internally from the class. It reads through the
     * annotated properties to find which columns and resultset array keys is
     * defined as the identifier columns
     *
     * This is needed for auto-populating object's id value for new objects, as
     * well as being able to return a proper array representation of the object
     * to the manager for storage.
     */
    protected function _populateAnnotatedIdValues()
    {
        if ($this->_data_array_identifiable === null) {
            foreach ($this->getReflectedClass($this->model)->getProperties() as $property) {
                if ($id_annotation = $this->getIdAnnotation($property)) {
                    if (!$column_annotation = $this->getColumnAnnotation($property))
                        throw new Exception('You must set the Id annotation on a property annotated with @Column');

                    $this->_id_column = ($column_annotation->name) ? $column_annotation->name : $property->name;
                    $this->_id_property = $property->name;
                    $this->_data_array_identifiable = true;
                    break;
                }
            }
            if ($this->_data_array_identifiable === null)
                $this->_data_array_identifiable = false;
        }
    }

    /**
     * Returns the identifier column, used by the manager when finding which
     * data array column to use as the identifier value
     *
     * @return string
     */
    public function getDataArrayIdentifierColumn()
    {
        $this->_populateAnnotatedIdValues();
        return $this->_id_column;
    }

    /**
     * Returns the identifier property, used by the entity when finding which
     * property to use as the identifier value
     *
     * @return string
     */
    public function getDataArrayIdentifierProperty()
    {
        $this->_populateAnnotatedIdValues();
        return $this->_id_property;
    }

    public function hasDataArrayIdentifierProperty()
    {
        return (bool) $this->_data_array_identifiable;
    }

    public function getResourceRoute($routename)
    {
        if (!array_key_exists($routename, static::$resource_routes))
            throw new Exception('This route does not exist in the static array property $resource_routes on this manager');

        return static::$resource_routes[$routename];
    }


    /**
     * @return \RedpillLinpro\GamineBundle\Services\ServiceInterface
     */
    public function getAccessService()
    {
        return $this->access_service;
    }

    /**
     * @return \RedpillLinpro\GamineBundle\Gamine
     */
    public function getGamineService()
    {
        return $this->gamine_service;
    }

    public function getCollectionResource()
    {
        return $this->getGamineService()->getCollectionResource($this->entity_key);
    }

    public function getEntityResource()
    {
        return $this->getGamineService()->getEntityResource($this->entity_key);
    }

    public function getModelClassname()
    {
        return $this->getGamineService()->getModelClassname($this->entity_key);
    }

    public function getInstantiatedModel()
    {
        return $this->getGamineService()->instantiateModel($this->entity_key);
    }

    public function findAll($params = array())
    {
        $objects = array();
        $res = $this->access_service->findAll($this->getCollectionResource(), $params);
        if (!is_array($res)) return null;
        foreach ($res as $o) {
            $object = $this->getInstantiatedModel();
            $object->fromDataArray($o);
            $objects[] = $object;
        }

        return $objects;
    }

    public function findOneById($id, $params = array())
    {
        $resource = $this->getEntityResource();
        $data = $this->access_service->findOneById(
                $resource, $id, $params);

        if (!$data) {
            return null;
        }
        $object = $this->gamine_service->instantiateModel($this->entity_key);
        $object->fromDataArray($data);

        return $object;
    }

    public function findByKeyVal($key, $val, $params = array())
    {
        $objects = array();

        foreach ($this->access_service->findByKeyVal(
                $this->getCollectionResource(), $key, $val, $params) as $o) {
            $object = $this->getInstantiatedModel();
            $object->fromDataArray($o);
            $objects[] = $object;
        }

        return $objects;
    }

    public function save(\RedpillLinpro\GamineBundle\Model\BaseModel $object)
    {
        $classname = $this->getModelClassname();
        if (!$object instanceof $classname) {
            throw new \InvalidArgumentException('This is not an object I can save, it must be of the same classname defined in this manager');
        }

        $object->injectGamineService($this->gamine_service, $this->entity_key);
        $is_new = !$object->hasDataArrayIdentifierValue();

        $do_continue = true;
        $result = false;

        if (method_exists($object, 'beforeSave')) {
            $do_continue = $object->beforeSave();
        }
        if ($do_continue && method_exists($this, 'beforeSave')) {
            $do_continue = $this->beforeSave($object);
        }

        if ($do_continue !== false) {
            // Save can do both insert and update with MongoDB.
            try {
                $new_data = $this->access_service->save($object, $this->getEntityResource());
                $result = is_array($new_data);
            } catch (\VGS_Client_Exception $e) {
                $result = false;
            } catch (ValidationError $e) {
                $result = false;
                $error = $e->getError();
                $object->setValidationErrors($error['description'], $e->getData());
            }
            if ($result) {
                $object->fromDataArray($new_data, false);
            }

            if (method_exists($object, 'afterSave')) {
                $result = $object->afterSave($is_new, $result);
            }
            if (method_exists($this, 'afterSave')) {
                $result = $this->afterSave($object, $is_new, $result);
            }
        }

        return $result;
    }

    public function remove($object)
    {

        $classname = $this->getModelClassname();
        if (!$object instanceof $classname) {
            throw new \InvalidArgumentException('This is not an object I can delete, it must be of the same classname defined in this manager');
        }

        if (!$object->getDataArrayIdentifierValue()) {
            throw new \InvalidArgumentException('This is not an object I can delete since it does not have a entity identifier value');
        }

        if (method_exists($this, 'beforeRemove')) {
            $this->beforeRemove($object);
        }

        // Save can do both insert and update with MongoDB.
        $status = $this->access_service->remove($object->getDataArrayIdentifierValue(), $this->getEntityResource());

        if (method_exists($this, 'afterRemove')) {
            $status = $this->afterRemove($object, $status);
        }

        return $status;
    }

    protected function _getPayload($result) {
        if (is_array($result) && isset($result['data'])) {
            return $result['data'];
        }
        return $result;
    }
}
