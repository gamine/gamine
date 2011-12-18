<?php

/**
 *
 * @author    Thomas Lundquist <thomasez@redpill-linpro.com>
 * @copyright 2011 Thomas Lundquist
 * @license   http://www.gnu.org/copyleft/lesser.html  LGPL License 2.1
 *
 */

namespace RedpillLinpro\GamineBundle\Services;

class SimpleMongo implements ServiceInterface
{

    private $mongo;
    private $mongodb;

    public function __construct($options = array())
    {
        $conn_string = 'mongodb://';
        if ($options['dbusername'] != '') {
            $conn_string .= $options['dbusername'];
                $conn_string .= ':'.$options['dbpassword'];
            $conn_string .= '@';
        }
        $conn_string .= $options['dbhost'];
        $conn_string .= (array_key_exists('dbport', $options)) ? ':' . $options['dbport'] : '';
        $this->mongo = new \Mongo($conn_string);
        $this->mongodb = $this->mongo->selectDB($options['dbname']);
    }

    public function call($resource, $method = 'GET', $data = array())
    {
        return $this->findAll($resource, $data);
    }

    public function save($data, $collection = null)
    {
        if (is_object($data)) {
            $data = $data->toDataArray();
        }

        if (!$collection) {
            throw new \InvalidArgumentException("Got no collection to save the data");
        }
        $mongo_collection = $this->mongodb->$collection;

        if (isset($data['id']))
        {
            $mongo_id = new \MongoId($data['id']);
            // Back
            unset($data['id']);
            $mongo_collection->update(array('_id' => $mongo_id), $data);
            // and Forth
            $data['id'] = $mongo_id->{'$id'};
        }
        else
        {
            $mongo_collection->insert($data);
            $data['id'] = $data['_id']->{'$id'};
            unset($data['_id']);
        }

        if (isset($data['id'])) {
            $id = new \MongoId($data['id']);
            unset($data['id']);
            $mongo_collection->update(array('_id' => $id), $data);
        } else {
            $mongo_collection->insert($data);
        }
        if (isset($data['_id'])) {
            $data['id'] = $data['_id'];
            unset($data['_id']);
        }
        return $data;
    }

    public function remove($data, $collection = null)
    {
        if (!$collection) {
            throw new \InvalidArgumentException("Got no collection to delete");
        }

        if (is_object($data)) {
            $id = $data->getId();
        } else {
            $id = $data;
        }

        $mongo_collection = $this->mongodb->$collection;

        $mid = new \MongoId($id);
        return $mongo_collection->remove(array('_id' => $mid),
                array('justOne' => true));
    }

    public function findAll($collection, $params = array())
    {
        $limit = 0;
        $sort = array();
        if (array_key_exists('limit', $params)) {
            $limit = $params['limit'];
            unset($params['limit']);
        }
        if (array_key_exists('sort', $params)) {
            $sort = $params['sort'];
            unset($params['sort']);
        }
        $retarr = array();
        $results = $this->mongodb->$collection->find($params)->limit($limit)->sort($sort);

        foreach (iterator_to_array($results) as $data) {
            $data['id'] = $data['_id'];
            unset($data['_id']);
            $retarr[] = $data;
        }
        return $retarr;
    }

    public function findOneById($collection, $id, $params = array())
    {
        $data = $this->mongodb->$collection->findOne(
           array('_id' => new \MongoId($id)));
        if(!$data) return null;
        $data['id'] = $data['_id']->{'$id'};
        unset($data['_id']);
        return $data;
    }

    public function findOneByKeyVal($collection, $key, $val, $params = array())
    {
        $data = $this->mongodb->$collection->findOne(array($key => $val));
        $data['id'] = $data['_id'];
        unset($data['_id']);
        return $data;
    }

    public function findByKeyVal($collection, $key, $val, $params = array())
    {
        $retarr = array();

        // PHPs Mongodb thingie has an issue with numbers, it quotes them
        // unless it is explocitly typecasted or manipulated in math context.
        if (is_numeric($val)) {
            $val = $val * 1;
        }

        $cursor = $this->mongodb->$collection->find(array($key => $val));
        // Since I am cooking rigth from php.net I'll use while here:
        while ($cursor->hasNext()) {
            $data = $cursor->getNext();
            $retarr[] = $data;
        }
        return $retarr;
    }

}
