<?php

namespace RedpillLinpro\GamineBundle\Tests\Cases\Annotation;

use RedpillLinpro\GamineBundle\Tests\Mocks\Annotations\MockRelatesAnnotatedModel as MockModel;

class RelatesAnnotationTest extends \PHPUnit_Framework_TestCase
{

    public function testConstruction()
    {
        $model = new MockModel();
        $this->assertTrue($model instanceof \RedpillLinpro\GamineBundle\Tests\Mocks\Annotations\MockRelatesAnnotatedModel);
    }

    public function testDescribe()
    {
        $expected = array(
            'primary_key' => array('property' => 'id', 'key' => 'id'),
            'properties' => array(
                'id' => array('id' => array('value' => null), 'column' => array('name' => null, 'value' => null)),
                'cars' => array('relates' => array('value' => null, 'entity' => 'cars', 'collection' => true, 'relative' => true, 'related_by' => null)),
                'steering_wheel' => array('relates' => array('value' => null, 'entity' => 'steering_wheels', 'collection' => false, 'relative' => true, 'related_by' => null)),
            )
        );
        $result = MockModel::describe();
        $this->assertEquals($expected, $result, 'Model annotation data is wrong');
    }

    public function testRetrieveRelatedObject()
    {
        $gamine = $this->getMock('\RedpillLinpro\GamineBundle\Gamine');
    }

}
