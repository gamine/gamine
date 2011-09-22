<?php

/**
 *
 * @author    Thomas Lundquist <thomasez@redpill-linpro.com>
 * @copyright 2011 Thomas Lundquist
 * @license   http://www.gnu.org/copyleft/lesser.html  LGPL License 2.1
 *
 */

namespace RedpillLinpro\GamineBundle\Model;

abstract class BaseModelAnnotation implements StorableObjectInterface
{

    /**
     * @var \Doctrine\Common\Annotations\AnnotationReader
     */
    protected static $_reader = null;

    /**
     * @var \RedpillLinpro\GamineBundle\Gamine
     */
    protected $_gamineservice = null;

    protected $entity_key = null;

    /**
     * The original data that was passed to this entity via the data mapper
     *
     * @var array
     */
    protected $_original_data = array();

    protected $_resource_location = null;
    protected $_resource_location_prefix = null;

    public static function describe()
    {
        $reflection_class = new \ReflectionClass(get_called_class());
        $reader = new \Doctrine\Common\Annotations\AnnotationReader(new \Doctrine\Common\Cache\ArrayCache());
        $reader->setEnableParsePhpImports(true);
        $reader->setDefaultAnnotationNamespace('RedpillLinpro\\GamineBundle\\Annotations\\');
        $return_array = array();
        foreach ($reflection_class->getProperties() as $property) {
            $annotations = $reader->getPropertyAnnotations($property);
            foreach ($annotations as $annotation) {
                switch ($annotation->getKey()) {
                    case 'id' :
                        $return_array['primary_key'] = $property->name;
                        break;
                    default:
                        $return_array['properties'][$property->name][$annotation->getKey()] = (array) $annotation;
                }
            }
        }
        return $return_array;
    }

    /**
     * Called from the manager, populates the object with data from a response
     * Also passes the manager in, so a reference to it can be statically cached
     * for later calls (lazy-loading / auto-retrieving), etc.
     *
     * @param array $data
     */
    public function fromDataArray($data)
    {
        $this->_original_data = $data;
        $this->_dataArrayMap($data);
    }

    public function injectGamineService(\RedpillLinpro\GamineBundle\Gamine $gamine_service, $entity_key)
    {
        $this->_gamineservice = $gamine_service;
        $this->entity_key = $entity_key;
    }

    /**
     * The manager calls this method to retrieve an array representation of the
     * object data, based on the structure defined in the object's annotations
     *
     * @return array
     */
    public function toDataArray()
    {
        return $this->_extractToDataArray();
    }

    /**
     * This function returns an array of properties that have been modified from
     * its original value when this object was retrieved
     *
     * @return array
     */
    public function getModifiedDataArray()
    {
        $new_data = $this->_extractToDataArray();
        $diff_data = array();
        foreach ($new_data as $field => $value) {
            if (!array_key_exists($field, $this->_original_data) && $value === null) continue;

            if (!array_key_exists($field, $this->_original_data) || $this->_original_data[$field] != $value) {
                $orig_value = (array_key_exists($field, $this->_original_data)) ? $this->_original_data[$field] : null;
                $diff_data[$field] = array('from' => $orig_value, 'to' => $value);
            }
        }
        return $diff_data;
    }

    /**
     * Returns the unique identifier value for this object, usually the value
     * of an $id property, $<objecttype>Id or similar
     *
     * @return mixed
     */
    public function getDataArrayIdentifierValue()
    {
        $primary_key_property = $this->_gamineservice->getPrimaryKey($this->entity_key);
        return $this->{$primary_key_property};
    }

    public function hasDataArrayIdentifierValue()
    {
        return $this->_gamineservice->getPrimaryKey($this->entity_key) !== null;
    }

    /**
     * Set the unique identifier value for this object
     *
     * This method is used by the manager to set the identifier value to the
     * value retrieved from the remote call after storing this object
     *
     * @param mixed $identifier_value
     */
    public function setDataArrayIdentifierValue($identifier_value)
    {
        $primary_key_property = $this->_gamineservice->getPrimaryKey($this->entity_key);
        $this->{$primary_key_property} = $identifier_value;
    }

    public function setResourceLocationPrefix($rlp)
    {
        $this->_resource_location_prefix = $rlp;
    }

    /**
     * Returns the resource location for this object, used when saving this
     * object via the manager
     *
     * @return string
     */
    protected function _getResourceLocation()
    {
        if ($this->_resource_location === null) {
            $primary_key = $this->_gamineservice->getPrimaryKey($this->entity_key);
            $entity = $this->_gamineservice->getEntityResource($this->entity_key);
            $id = $this->{$primary_key};
            $this->_resource_location = "/$entity/$id";
        }
        return $this->_resource_location_prefix . $this->_resource_location;
    }

    protected function getResourceByRoutename($routename, $params = array())
    {
        $resource = $this->_entitymanager->getResourceRoute($routename);
        foreach ($params as $key => $value) {
            $resource = str_replace("{:{$key}}", $value, $resource);
        }
        return $resource;
    }

    protected function _apiCall($routename, $params = array())
    {
        return $this->_apiGet($routename, $params);
    }

    protected function _apiGet($routename, $params = array())
    {
        $resource_route = $this->getResourceByRoutename($routename, $params);
        $resource_route = (substr($resource_route, 0, 1) == "/") ? $resource_route : $this->_getResourceLocation() . '/' . $resource_route;
        dd($resource_route);
        return $this->_entitymanager->getAccessService()->call($resource_route);
    }

    protected function _apiSet($routename, $params = array(), $post_params = array())
    {
        $resource_route = $this->getResourceByRoutename($routename, $params);
        $resource_route = (substr($resource_route, 0, 1) == "/") ? $resource_route : $this->_getResourceLocation() . '/' . $resource_route;

        return $this->_entitymanager->getAccessService()->call($resource_route, 'POST', $post_params);
    }

    protected function _apiUnset($routename, $params = array(), $post_params = array())
    {
        $resource_route = $this->getResourceByRoutename($routename, $params);
        $resource_route = (substr($resource_route, 0, 1) == "/") ? $resource_route : $this->_getResourceLocation() . '/' . $resource_route;

        return $this->_entitymanager->getAccessService()->call($resource_route, 'DELETE', $post_params);
    }

    private function __populateSubModel(array $mapping, array $value = array())
    {
        if (empty($value)) return null;
        $model = $this->_gamineservice->instantiateModel($mapping['entity']);
        $model->fromDataArray($value);
        return $model;
    }

    protected function _applyDataArrayProperty($property, $mappings, $result, $extracted = null)
    {
        $result_key = (isset($mappings['column']['name']) && $mappings['column']['name']) ? $mappings['column']['name'] : $property;

        if (!array_key_exists($result_key, $result)) return;

        // Extract
        if (is_array($mappings) && array_key_exists('extract', $mappings)) {
            foreach ($mappings['extract']['columns'] as $column => $extract_to_property) {
                if (array_key_exists($column, $result[$result_key])) {
                    $this->{$extract_to_property} = $result[$result_key][$column];
                    if (!$mappings['extract']['preserve_items']) {
                      //  unset($result[$result_key][$column]);
                    }
                }
            }
            if ($mappings['extract']['preserve_items'])
                $this->{$property} = $result[$result_key];
            return;
        }

        // Sub model
        if (is_array($mappings) && array_key_exists('sub_model', $mappings)) {
            $value = $result[$result_key];
            if ($mappings['sub_model']['collection']) {
                $sub = array();
                $key = $mappings['sub_model']['identifier'];
                foreach ($value as $one) {
                    $sub[$one[$key]] = $this->__populateSubModel($mappings['sub_model'], $one);
                }
                $this->{$property} = $sub;
            } else {
                $this->{$property} = $this->__populateSubModel($mappings['sub_model'], $value);
            }
            return;
        }

        // Relates
        if (is_array($mappings) && array_key_exists('relates', $mappings)) {
           // dd($property, $mappings, $result);
            if ($mappings['relates']['entity']) {
                $related_manager = $this->_gamineservice->getManager($mappings['relates']['entity']);
            } else {
                $related_manager = null;
            }
            $this->_mapRelationData($property, $result[$result_key], $mappings['relates']);
            return;
        }

        $this->{$property} = $result[$result_key];
    }

    protected function _populateRelatedObject($property)
    {
        if (is_array($this->$property) || is_object($this->$property)) return;

        $mappings = $this->_gamineservice->getMappedProperty($this->entity_key, $property);

        $primary_key = $this->_gamineservice->getPrimaryKey($this->entity_key);

        $final_resource_location = '';
        if ($mappings['relates']['relative']) {
            $final_resource_location .= $this->_getResourceLocation();
        }

        if ($mappings['relates']['collection']) {
            $final_resource_location .= '/'.$this->_gamineservice->getCollectionResource($mappings['relates']['entity']);
        } else {
            // @todo 'related_by' annotation?
            $final_resource_location .= '/'.$this->_gamineservice->getEntityResource($mappings['relates']['entity']) . '/'. $this->{$mappings['relates']['related_by']};
        }
        $data = $this->_gamineservice->getManager($this->entity_key)->getAccessService()->call($final_resource_location, 'GET', array());
        $this->_mapRelationData($property, $data, $mappings['relates']);
    }

    protected function _mapRelationData($property, $data, $mappings)
    {
        if ($mappings['collection']) {
            $models = array();
            foreach ($data as $single_result) {
                $related_model = $this->_gamineservice->instantiateModel($mappings['entity']);
                $related_model->fromDataArray($single_result);
                $related_model->setResourceLocationPrefix($this->_getResourceLocation() . "/");

                if ($related_model->hasDataArrayIdentifierValue()) {
                    $models[(string) $related_model->getDataArrayIdentifierValue()] = $related_model;
                } else {
                    $models[] = $object;
                }
            }
            $this->$property = $models;
        } else {
            $related_model = $this->_gamineservice->instantiateModel($mappings['entity']);
            $related_model->fromDataArray($data);
            $related_model->setResourceLocationPrefix($this->_getResourceLocation() . "/");
            $this->$property = $related_model;
        }
    }

    protected function _extractDataArrayProperty($property, &$result, $extracted = null)
    {
        $column_annotation = ($extracted !== null) ? null : $this->_entitymanager->getAnnotationsReader()->getPropertyAnnotation($property, 'RedpillLinpro\\GamineBundle\\Annotations\\Column');

        if ($column_annotation !== null || $extracted !== null) {
            if ($extracted !== null) {
                $result[$extracted] = $this->$property;
            } else {
                $name = ($column_annotation->name) ? $column_annotation->name : $property->name;
                if ($extract_annotation = $this->_entitymanager->getAnnotationsReader()->getPropertyAnnotation($property, 'RedpillLinpro\\GamineBundle\\Annotations\\Extract')) {
                    $return_value = array();
                    foreach ($extract_annotation->columns as $column => $extract_from_property) {
                        $this->_extractDataArrayProperty($extract_from_property, $return_value, $column);
                    }
                    $result[$name] = $return_value;
                } else {
                    $result[$name] = $this->{$property->name};
                }
            }
        }
    }

    /**
     * Convert the result arrays from VGS client to collection of Entities
     *
     * @param array $resultset array of responses from VGS_Client
     * @return array
     */
    protected function _dataArrayMap($result)
    {
        foreach ($this->_gamineservice->getMappedProperties($this->entity_key) as $property => $mappings) {
            $this->_applyDataArrayProperty($property, $mappings, $result);
        }
    }

    protected function _extractToDataArray()
    {
        $result = array();

        foreach ($this->_entitymanager->getReflectedClass()->getProperties() as $property) {
            $relates_annotation = $this->getRelatesAnnotation($property);
            $column_annotation = $this->getColumnAnnotation($property);
            if ($relates_annotation && $mappings['manager']) {
                $c_name = ($column_annotation->name) ? $column_annotation->name : $property->name;
                if (array_key_exists($c_name, $this->_original_data)) {
                    unset($this->_original_data[$c_name]);
                }
                continue;
            }
            $this->_extractDataArrayProperty($property, $result);
        }

        return $result;
    }

    static function getFormSetup()
    {
        return static::$model_setup;
    }

    static function getClassName()
    {
        return 'user';
    }

}

