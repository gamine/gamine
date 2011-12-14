<?php

/**
 *
 * @author    Thomas Lundquist <thomasez@redpill-linpro.com>
 * @copyright 2011 Thomas Lundquist
 * @license   http://www.gnu.org/copyleft/lesser.html  LGPL License 2.1
 *
 */

namespace RedpillLinpro\GamineBundle\Model;

abstract class BaseModel implements StorableObjectInterface
{

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

    protected $_validation_message;
    protected $_validation_errors;

    public static function describe()
    {
        return \RedpillLinpro\GamineBundle\Gamine::describeClass(get_called_class());
    }

    /**
     * Called from the manager, populates the object with data from a response
     * Also passes the manager in, so a reference to it can be statically cached
     * for later calls (lazy-loading / auto-retrieving), etc.
     *
     * @param array $data
     */
    public function fromDataArray($data, $set_original_data = true)
    {
        if ($set_original_data) {
            $this->_original_data = $data;
        }
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
    public function toDataArray($removeUnchanged = true)
    {
        return $this->_extractToDataArray($removeUnchanged);
    }

    /**
     * This function returns an array of properties that have been modified from
     * its original value when this object was retrieved
     *
     * @return array
     */
    public function getModifiedDataArray()
    {
        $new_data = $this->_extractToDataArray(false);
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
        if ($this->_gamineservice && method_exists($this->_gamineservice, 'getPrimaryKeyProperty'))
            $primary_key_property = $this->_gamineservice->getPrimaryKeyProperty($this->entity_key);
        else {
            $description = \RedpillLinpro\GamineBundle\Gamine::describeClass(get_called_class());
            $primary_key_property = $description['primary_key']['property'];
        }

        return $this->{$primary_key_property};
    }

    /**
     * Checks to see if this object has an Id value, or if it is a new object
     * 
     * @return boolean
     */
    public function hasDataArrayIdentifierValue()
    {
        return (bool) $this->getDataArrayIdentifierValue();
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
        if ($this->_gamineservice && method_exists($this->_gamineservice, 'getPrimaryKeyProperty'))
            $primary_key_property = $this->_gamineservice->getPrimaryKeyProperty($this->entity_key);
        else {
            $description = \RedpillLinpro\GamineBundle\Gamine::describeClass(get_called_class());
            $primary_key_property = $description['primary_key']['property'];
        }
        $this->{$primary_key_property} = $identifier_value;
    }

    /**
     * Some entities' location url needs to be prefixed by an already existing
     * url with information about a container id or parent id, unknown to this
     * entity. Setting the resource location prefix will put this entity's own
     * resource location under a containing resource
     * 
     * Ex: if this entity's resource location is "orders", and you set the 
     * prefix to "user/11", the resulting unique resource location for this 
     * entity will be "user/11/orders", whereas without a prefix, it will be
     * just "orders" (always relative from the service's own defined root url)
     * 
     * @param string $rlp 
     */
    public function setResourceLocationPrefix($rlp)
    {
        $this->_resource_location_prefix = $rlp;
    }

    public function getResourceLocationPrefix()
    {
        return $this->_resource_location_prefix;
    }

    /**
     * Returns the unique resource location for this entity, used when saving this
     * entity via the manager, or when retrieving related objects
     *
     * @return string
     */
    protected function _getResourceLocation()
    {
        if (!$this->hasDataArrayIdentifierValue())
            throw new \Exception("This object does not have a unique resource location yet. Make sure it is being managed.");
            
        if ($this->_resource_location === null) {
            $entity = $this->getEntityResource();
            $id = $this->getDataArrayIdentifierValue();
            $this->_resource_location = "/$entity/$id";
        }
        return $this->_resource_location_prefix . $this->_resource_location;
    }

    /**
     * Returns the string representation of the resource, be it db or api endpoint.
     * Will use entity_key on gamine service if set, will generate from model class name if not
     *
     * @return string
     */
    public function getEntityResource()
    {
        if (!$this->entity_key) {
            $classarr = explode('\\', get_called_class());
            $class = array_pop($classarr);
            $this->entity_key = strtolower($class);
        }
        if ($this->_gamineservice)
            return $this->_gamineservice->getEntityResource($this->entity_key);
        else
            return $this->entity_key;
    }

    /**
     * Return an action resource for this entity, usually related to this 
     * entity's resource location
     * 
     * Ex: "{entity_resource}/resetpassword"
     * or: "{entity_resource}/credit/{amount}", where the amount can be passed
     * in the $params array
     * 
     * @param string $routename
     * @param array $params Key => value for any resource location parameters
     * 
     * @return string
     */
    protected function getResourceByRoutename($routename, $params = array())
    {
        $resource = $this->_gamineservice->getManager($this->entity_key)->getResourceRoute($routename);
        foreach ($params as $key => $value) {
            $resource = str_replace("{:{$key}}", $value, $resource);
        }
        return $resource;
    }

    /**
     * Perform an api get request via a resource route, used for calling actions
     * where the return value isn't really of importance. The call is made as
     * a GET request.
     * 
     * @see self::getResourceByRoutename
     * 
     * @param string $routename
     * @param array $params
     * 
     * @return mixed Whatever is returned from the api endpoint
     */
    protected function _apiCall($routename, $params = array())
    {
        return $this->_apiGet($routename, $params);
    }

    /**
     * Perform an api GET request to a resource route, used for calling actions
     * on an entity where the endpoint return value is being checked in the
     * following code.
     * 
     * @see self::getResourceByRoutename
     * 
     * @param string $routename
     * @param array $params
     * 
     * @return mixed Whatever is returned from the api endpoint
     */
    protected function _apiGet($routename, $params = array())
    {
        $resource_route = $this->getResourceByRoutename($routename, $params);
        $resource_route = (substr($resource_route, 0, 1) == "/") ? $resource_route : $this->_getResourceLocation() . '/' . $resource_route;
        return $this->_gamineservice->getManager($this->entity_key)->getAccessService()->call($resource_route);
    }

    /**
     * Perform an api POST request to a resource route, used for sending values
     * to the endpoint resource.
     * 
     * @see self::getResourceByRoutename
     * 
     * @param string $routename
     * @param array $params
     * 
     * @return mixed Whatever is returned from the api endpoint
     */
    protected function _apiSet($routename, $params = array(), $post_params = array())
    {
        $resource_route = $this->getResourceByRoutename($routename, $params);
        $resource_route = (substr($resource_route, 0, 1) == "/") ? $resource_route : $this->_getResourceLocation() . '/' . $resource_route;
        return $this->_gamineservice->getManager($this->entity_key)->getAccessService()->call($resource_route, 'POST', $post_params);
    }

    /**
     * Performs a DELETE request on a resource location. Used to delete a 
     * resource located at the specified endpoint location.
     * 
     * @see self::getResourceByRoutename
     * 
     * @param string $routename
     * @param array $params
     * 
     * @return mixed Whatever is returned from the api endpoint
     */
    protected function _apiUnset($routename, $params = array(), $post_params = array())
    {
        $resource_route = $this->getResourceByRoutename($routename, $params);
        $resource_route = (substr($resource_route, 0, 1) == "/") ? $resource_route : $this->_getResourceLocation() . '/' . $resource_route;
        return $this->_gamineservice->getManager($this->entity_key)->getAccessService()->call($resource_route, 'DELETE', $post_params);
    }

    private function __populateSubModel(array $mapping, array $value = array())
    {
        if (empty($value)) return null;
        $model = $this->_gamineservice->instantiateModel($mapping['entity']);
        $model->fromDataArray($value);
        return $model;
    }

    /**
     * Apply result on entity property through the mappings
     *
     * @param string $property
     * @param array $mappings
     * @param array $result
     */
    protected function _applyDataArrayProperty($property, $mappings, &$result)
    {
        // Also, ignore this property if there are no valid annotation mappings
        // meaning we will use the object's own default value for that property
        if (!is_array($mappings)) return;

        // Figure out which key in the result array is the one we're looking for
        $result_key = (isset($mappings['column']['name']) && $mappings['column']['name']) ? $mappings['column']['name'] : $property;

        // Ignore this property if there is no matching $key in the result array,
        // meaning we will use the object's own default value for that property
        if (!array_key_exists($result_key, $result)) return;

        // Check to see if this property has an "Extract" annotation, in which
        // case we will take data from the result array and extract it into 
        // other properties as well as the original property
        if (array_key_exists('extract', $mappings)) {
            
            // Find all the columns (corresponds to an array key inside the 
            // targetted $result[$key], and map this to properties
            foreach ($mappings['extract']['columns'] as $column => $extract_to_property) {
                
                // Ignore this column key if it is not being provided in the result set
                if (!array_key_exists($column, $result[$result_key])) continue;
                
                $this->{$extract_to_property} = $result[$result_key][$column];
                
                // If the preserve_items key is false, pick items *out* of the
                // array when we extract it onto other properties
                if (!$mappings['extract']['preserve_items']) 
                    unset($result[$result_key][$column]);
            }
            
            // Always preserve the original data in the property where the
            // annotation is placed
            $this->{$property} = $result[$result_key];
            return;
        }

        // Check to see if this property has a "Submodel" annotation, in which
        // case we will extract the property value into an instantiated object
        if (array_key_exists('sub_model', $mappings)) {
            $value = $result[$result_key];
            
            // If the submodel is a collection of objects, we need to 
            // instantiate more than one submodel entity
            if ($mappings['sub_model']['collection']) {
                $submodels = array();
                
                // Use the specified identifier as an array key, or none if not
                // provided
                $key = (array_key_exists('identifier', $mappings['sub_model'])) ? $mappings['sub_model']['identifier'] : null;
                if ($value) {
                    foreach ($value as $subkey => $one) {
                        $submodel = $this->__populateSubModel($mappings['sub_model'], $one);
                        if ($key) {
                            $submodels[$one[$key]] = $submodel;
                        } else {
                            $submodels[$subkey] = $submodel;
                        }
                    }
                }
                $this->{$property} = $submodels ?: $value;
            } else {
                $this->{$property} = $this->__populateSubModel($mappings['sub_model'], $value);
            }
            return;
        }

        $this->{$property} = $result[$result_key];
    }

    /**
     * Populate a property that is supposed to contain an entity or collection
     * of entities if it is not already populated
     *
     * @param mixed $property
     */
    protected function _populateRelatedObject($property, $params = array())
    {
        // Don't populate the related object if it already is
        if (is_array($this->$property) || is_object($this->$property)) return;

        $mappings = $this->_gamineservice->getMappedProperty($this->entity_key, $property);

        $primary_key = $this->_gamineservice->getPrimaryKeyProperty($this->entity_key);

        $final_resource_location = '';
        if ($mappings['relates']['relative']) {
            $final_resource_location .= $this->_getResourceLocation() . '/';
        } else {
            $fkey = $mappings['relates']['related_by'];
            $params[$fkey] = $this->getDataArrayIdentifierValue();
        }

        if ($mappings['relates']['collection']) {
            $final_resource_location .= $this->_gamineservice->getCollectionResource($mappings['relates']['entity']);
        } else {
            $entity_path = $this->_gamineservice->getEntityResource($mappings['relates']['entity']);
            if (strpos($entity_path, '{:id}')) {
                 $final_resource_location .= str_ireplace('{:id}', $this->getDataArrayIdentifierValue(), $entity_path);
            } elseif ($mappings['relates']['related_by']) {
                 $final_resource_location .= $entity_path . '/' . $params[$fkey];
            } else {
                 $final_resource_location .= $entity_path;
            }
        }
        $data = $this->_gamineservice->getManager($mappings['relates']['entity'])->getAccessService()->call($final_resource_location, 'GET', $params, false);
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

    protected function _extractDataArrayProperty($property, array $mappings, &$result, $removeUnchanged = true)
    {
        if (!array_key_exists('column', $mappings) || array_key_exists('readonly', $mappings)) return;
        $result_key = (isset($mappings['column']['name'])) ? $mappings['column']['name'] : $property;

        if (array_key_exists('extract', $mappings)) {
            $result[$result_key] = array();
            foreach ($mappings['extract']['columns'] as $extracted_result_key => $extracted_property) {
                if ($removeUnchanged && $this->_original_data[$result_key][$extracted_result_key] !== $this->{$extracted_property})
                    $result[$result_key][$extracted_result_key] = $this->{$extracted_property};
                elseif (!$removeUnchanged)
                    $result[$result_key][$extracted_result_key] = $this->{$extracted_property};
            }
            return;
        }
        if (array_key_exists('sub_model', $mappings)) {
            if ($mappings['sub_model']['collection']) {
                $result[$result_key] = array();
                if (is_array($this->{$property})) {
                    foreach ($this->{$property} as $k => $sub_model) {
                        $sub_model->injectGamineService($this->_gamineservice, $mappings['sub_model']['entity']);
                        $result[$result_key][$k] = $sub_model->toDataArray(false);
                    }
                    if (empty($result[$result_key])) {
                        $result[$result_key] = null;
                    } else {
                        if ($mappings['sub_model']['extract_mode'] == 'min') {
                            $pk = $this->_gamineservice->getPrimaryKeyProperty($mappings['sub_model']['entity']);
                            foreach ($result[$result_key] as $k => $res) {
                                $diff = (array_key_exists($k, $this->_original_data)) ? array_diff_assoc($this->_original_data[$result_key][$k], $res) : $res;
                                if (!count($diff)) {
                                    $result[$result_key][$k] = array($pk => $res[$pk]);
                                } else {
                                    if ($res[$pk]) {
                                        $diff[$pk] = $res[$pk];
                                    } else {
                                        unset($diff[$pk]);
                                    }

                                    $result[$result_key][$k] = $diff;
                                }
                            }
                        }
                    }
                }
            } else {
                $sub_model->injectGamineService($this->_gamineservice, $mappings['sub_model']['entity']);
                $result[$result_key] = $this->{$property}->toDataArray();
                if ($removeUnchanged && empty($result[$result_key])) unset($result[$result_key]);
            }
            return;
        }

        if (!$removeUnchanged || !isset($this->_original_data[$result_key]) || $this->{$property} !== $this->_original_data[$result_key])
            $result[$result_key] = (is_object($this->{$property}) && $this->{$property} instanceof \RedpillLinpro\GamineBundle\Model\StorableObjectInterface) ? $this->{$property}->getDataArrayIdentifierValue() : $this->{$property};
    }

    /**
     * Convert the result arrays from VGS client to collection of Entities
     *
     * @param array $resultset array of responses from VGS_Client
     * @return array
     */
    protected function _dataArrayMap($result)
    {
        if ($this->_gamineservice)
            $mapped_properties = $this->_gamineservice->getMappedProperties($this->entity_key);
        else {
            $description = \RedpillLinpro\GamineBundle\Gamine::describeClass(get_called_class());
            $mapped_properties = $description['properties'];
        }

        if (array_key_exists('primary_key', $mapped_properties) && !array_key_exists($result[$mapped_properties['primary_key']['key']]))
            throw new \Exception('The result data returned from the endpoint is not formatted correctly (Could not find primary key in returned data)');

        foreach ($mapped_properties as $property => $mappings) {
            $this->_applyDataArrayProperty($property, $mappings, $result);
        }
    }

    protected function _extractToDataArray($removeUnchanged = true)
    {
        $result = array();

        if ($this->_gamineservice){
            $mapped_properties = $this->_gamineservice->getMappedProperties($this->entity_key);
        }else {
            $description = \RedpillLinpro\GamineBundle\Gamine::describeClass(get_called_class());
            $mapped_properties = $description['properties'];
        }

        foreach ($mapped_properties as $property => $mappings) {
            if (array_key_exists('relates', $mappings) && !array_key_exists('column', $mappings)) continue;
            $this->_extractDataArrayProperty($property, $mappings, $result, $removeUnchanged);
        }
        return $result;
    }

    public function setValidationErrors($message, $errors = array())
    {
        $this->_validation_message = $message;
        $this->_validation_errors = $errors;
    }

    public function getValidationMessage()
    {
        return $this->_validation_message;
    }

    public function getValidationErrors()
    {
        return $this->_validation_errors;
    }
}
