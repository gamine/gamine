<?php

namespace RedpillLinpro\GamineBundle\Tests\Mocks;

class GamineMock extends \RedpillLinpro\GamineBundle\Gamine
{

    protected $_backends = array();

    protected $_entityconfigs = array(
        'person' => array(
            'collection' => 'users',
            'entity' => 'user',
            'model' => array(
                'class' => '\Schibsted\AmbassadorBundle\Model\Person'
            )
        ),
        'address' => array(
            'collection' => 'addresses',
            'entity' => 'address',
            'model' => array(
                'class' => '\Schibsted\AmbassadorBundle\Model\Address'
            )
        )
    );

    protected $_managers = array();

    public function __construct($backends = array(), $managers = array())
    {
        $this->_backends = $backends;
        $this->_managers = $managers;
    }

    public function addManager($entity, $manager_object)
    {
        $this->_managers[$entity] = $manager_object;
    }
    public function getManager($entity)
    {
        if (!array_key_exists($entity, $this->_managers))
                return null;
        return $this->_managers[$entity];
    }

    public function getBackend($backend)
    {
        return $this->_backends[$backend];
    }

    public function __call($method, $args)
    {
        return $args;
    }

}
