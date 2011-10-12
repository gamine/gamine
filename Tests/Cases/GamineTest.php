<?php

namespace RedpillLinpro\GamineBundle\Tests\Cases;

use RedpillLinpro\GamineBundle\Gamine,
    RedpillLinpro\GamineBundle\Tests\Mocks\Service\MockMongo,
    RedpillLinpro\GamineBundle\Tests\Mocks\Service\MockRest;

class GamineMock extends Gamine
{

    public static $manager_config = array(
        'user' => array(
            'manager' => array(
                'class' => '\RedpillLinpro\GamineBundle\Tests\Mocks\Manager\MockUserManager',
                'arguments' => array(
                    'access_service' => 'mock_mongo',
                )
            ),
            'collection' => 'users',
            'entity' => 'user',
            'model' => array(
                'class' => '\RedpillLinpro\GamineBundle\Tests\Mocks\Model\MockUser'
            )
        ),
        'car' => array(
            'collection' => 'cars',
            'entity' => 'car',
            'model' => array(
                'class' => '\RedpillLinpro\GamineBundle\Tests\Mocks\Model\MockCar'
            )
        ),
        'steering_wheel' => array(
            'collection' => 'steering_wheels',
            'entity' => 'steering_wheel',
            'model' => array(
                'class' => '\RedpillLinpro\GamineBundle\Tests\Mocks\Model\MockSteeringWheel'
            )
        )
    );

    public static $backend_config = array(
        'mock_invalid' => array(
            'class' => '\RedpillLinpro\GamineBundle\Tests\Mocks\Services\Invalid',
            'arguments' => array(
                'first' => 1,
                'second' => 'two'
            )
        ),
        'mock_mongo' => array(
            'class' => '\RedpillLinpro\GamineBundle\Tests\Mocks\Services\MockMongo',
            'arguments' => array(
                'first' => 1,
                'second' => 'two'
            )
        ),
        'mock_rest' => array(
            'class' => '\RedpillLinpro\GamineBundle\Tests\Mocks\Services\MockRest',
            'arguments' => array(
                'first' => 'one',
                'second' => 2
            )
        )
    );

    public function __construct($backends = array(), $managers = array())
    {
        $this->_backendsconfig = self::$backend_config;
        $this->_entityconfigs = self::$manager_config;
    }
    
}

class GamineTest extends \PHPUnit_Framework_TestCase
{

    public function setUp()
    {
        $this->gamine = new Gamine(GamineMock::$backend_config, GamineMock::$manager_config);
    }

    public function testConstruction()
    {
        $this->assertInstanceOf('RedpillLinpro\GamineBundle\Gamine', $this->gamine);
    }

    /**
     * @expectedException UnexpectedValueException
     */
    public function testGetInvalidBackend()
    {
        $mock_invalid = $this->gamine->getBackend('mock_invalid');
    }

    /**
     * @expectedException InvalidArgumentException
     */
    public function testGetNonExistantBackend()
    {
        $mock_nonexistant = $this->gamine->getBackend('mock_danielandreisawesome');
    }

    /**
     * @expectedException RuntimeException
     */
    public function testInitBackendCalledProperly()
    {
        $gamine_mock = $this->getMock('\RedpillLinpro\GamineBundle\Tests\Cases\GamineMock', array('_initBackend'));
        $gamine_mock->expects($this->once())
                    ->method('_initBackend')
                    ->with($this->equalTo('mock_mongo'));

        $gamine_mock->getBackend('mock_mongo');
    }

    public function testGetBackend()
    {
        $mock_mongo = $this->gamine->getBackend('mock_mongo');
        $this->assertInstanceOf(GamineMock::$backend_config['mock_mongo']['class'], $mock_mongo);
        $mock_rest = $this->gamine->getBackend('mock_rest');
        $this->assertInstanceOf(GamineMock::$backend_config['mock_rest']['class'], $mock_rest);
    }

    /**
     * @expectedException RuntimeException
     */
    public function testInitManagerCalledProperly()
    {
        $gamine_mock = $this->getMock('\RedpillLinpro\GamineBundle\Tests\Cases\GamineMock', array('_initManager'));
        $gamine_mock->expects($this->once())
                    ->method('_initManager')
                    ->with($this->equalTo('user'));

        $gamine_mock->getManager('user');
    }

    public function testGetManager()
    {
        $user_manager = $this->gamine->getManager('user');
        $this->assertInstanceOf(GamineMock::$manager_config['user']['manager']['class'], $user_manager);
    }

    public function testInstantiateModel()
    {
        $user = $this->gamine->instantiateModel('user');
        $this->assertInstanceOf(GamineMock::$manager_config['user']['model']['class'], $user);
    }

}
