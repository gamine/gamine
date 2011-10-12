<?php

namespace RedpillLinpro\GamineBundle\Tests\Cases\Annotation;

use RedpillLinpro\GamineBundle\Tests\Mocks\Annotations\MockReadOnlyAnnotatedModel as MockModel;

class ReadOnlyAnnotationTest extends \PHPUnit_Framework_TestCase
{

    public function testConstruction()
    {
        $model = new MockModel();
        $this->assertTrue($model instanceof \RedpillLinpro\GamineBundle\Tests\Mocks\Annotations\MockReadOnlyAnnotatedModel);
    }

    public function testDescribe()
    {
        $expected = array(
            'properties' => array(
                'name' => array('column' => array('name' => null, 'value' => null)),
                'email' => array('readonly' => array('value' => null), 'column' => array('name' => null, 'value' => null)),
            )
        );
        $result = MockModel::describe();
        $this->assertEquals($expected, $result, 'Model annotation data is wrong');
    }

    public function testAppliedColumns()
    {
        $data = array(
            'name' => 'Daniel André',
            'email' => 'dae@redpill-linpro.com'
        );

        $model = new MockModel();
        $model->fromDataArray($data);
        $this->assertEquals('Daniel André', $model->name, 'Standard column annotation not working properly');
        $this->assertEquals('dae@redpill-linpro.com', $model->email, 'Read-only column annotation not working properly');
    }

    public function testExportColumns()
    {
        $data = array(
            'name' => 'Daniel André',
            'email' => 'dae@redpill-linpro.com'
        );

        $model = new MockModel();
        $model->fromDataArray($data);
        $model->email = 'dae-alternate@redpill-linpro.com';

        $this->assertEmpty($model->getModifiedDataArray(), 'Changed data not empty when only read-only columns has changed');

        $model->name = 'Daniel André Eikeland';

        $expected = array(
            'name' => array(
                'from' => 'Daniel André',
                'to' => 'Daniel André Eikeland')
        );
        $this->assertEquals($expected, $model->getModifiedDataArray(), 'Changed data includes email when it should be a read-only column');
    }

}
