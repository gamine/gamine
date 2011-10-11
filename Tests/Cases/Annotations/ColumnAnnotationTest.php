<?php

namespace RedpillLinpro\GamineBundle\Tests\Cases\Annotation;

use RedpillLinpro\GamineBundle\Tests\Mocks\Annotations\MockColumnAnnotatedModel as MockModel;

class ColumnAnnotationTest extends \PHPUnit_Framework_TestCase
{

    public function testConstruction()
    {
        $model = new MockModel();
        $this->assertTrue($model instanceof \RedpillLinpro\GamineBundle\Tests\Mocks\Annotations\MockColumnAnnotatedModel);
    }

    public function testDescribe()
    {
        $expected = array(
            'properties' => array(
                'title' => array('column' => array('name' => null, 'value' => null)),
                'my_real_name' => array('column' => array('name' => 'realname', 'value' => null)),
            )
        );
        $result = MockModel::describe();
        $this->assertEquals($expected, $result, 'Model annotation data is wrong');
    }

    public function testAppliedColumns()
    {
        $data = array(
            'title' => 'My title',
            'realname' => 'My Real Name'
            );

        $model = new MockModel();
        $model->fromDataArray($data);
        $this->assertEquals('My title', $model->title, 'Standard column annotation not working properly');
        $this->assertEquals('My Real Name', $model->my_real_name, 'Column annotation with alternate name not working properly');
    }

    public function testMissingRegularColumn()
    {
        $data = array(
            'title' => '',
            'realname' => 'My Real Name'
            );

        $model = new MockModel();
        $model->fromDataArray($data);
        $this->assertEquals('', $model->title, 'Standard column annotation not working properly when omitted');
        $this->assertEquals('My Real Name', $model->my_real_name, 'Column annotation with alternate name not working properly when omitted');
    }

    public function testMissingAlternateNameColumn()
    {
        $data = array(
            'title' => 'My title',
            'realname' => ''
            );

        $model = new MockModel();
        $model->fromDataArray($data);
        $this->assertEquals('My title', $model->title, 'Standard column annotation not working properly when omitted');
        $this->assertEquals('', $model->my_real_name, 'Column annotation with alternate name not working properly when omitted');
    }

}
