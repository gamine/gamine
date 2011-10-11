<?php

namespace RedpillLinpro\GamineBundle\Tests\Cases\Model;

use RedpillLinpro\GamineBundle\Tests\Mocks\Annotations\MockIdAnnotatedModel as MockModel;
use RedpillLinpro\GamineBundle\Tests\Mocks\Annotations\MockIdAnnotatedModelAlternate as MockModelAlternate;

class IdAnnotationTest extends \PHPUnit_Framework_TestCase
{

    public function testConstruction()
    {
        $model = new MockModel();
        $model_alternate = new MockModelAlternate();
        $this->assertTrue($model instanceof \RedpillLinpro\GamineBundle\Tests\Mocks\Annotations\MockIdAnnotatedModel);
        $this->assertTrue($model_alternate instanceof \RedpillLinpro\GamineBundle\Tests\Mocks\Annotations\MockIdAnnotatedModelAlternate);
    }

    public function testDescribeRegular()
    {
        $expected = array(
            'primary_key' => array('property' => 'id', 'key' => 'id'),
            'properties' => array(
                'id' => array('id' => array('value' => null), 'column' => array('name' => null, 'value' => null)),
            )
        );
        $result = MockModel::describe();
        $this->assertEquals($expected, $result, 'Model annotation data is wrong');
    }

    public function testDescribeAlternate()
    {
        $expected = array(
            'primary_key' => array('property' => 'primaryId', 'key' => 'id'),
            'properties' => array(
                'primaryId' => array('id' => array('value' => null), 'column' => array('name' => 'id', 'value' => null)),
            )
        );
        $result = MockModelAlternate::describe();
        $this->assertEquals($expected, $result, 'Alternate model annotation data is wrong');
    }

    public function testApplyRegularId()
    {
        $data = array(
            'id' => 1,
            );

        $model1 = new MockModel();
        $model1->fromDataArray($data);
        $this->assertEquals(1, $model1->id, 'Numeric id annotation not working properly');

        $data = array(
            'id' => 'somethingsomething1somethingsomething',
            );

        $model2 = new MockModel();
        $model2->fromDataArray($data);
        $this->assertEquals('somethingsomething1somethingsomething', $model2->id, 'String id annotation not working properly');

        return array($model1, $model2);
    }

    /**
     * @depends testApplyRegularId
     */
    public function testExtractRegularId($models)
    {
        list ($model1, $model2) = $models;
        $this->assertEquals(1, $model1->getDataArrayIdentifierValue(), 'Regular numeric id extration not working properly');
        $this->assertEquals('somethingsomething1somethingsomething', $model2->getDataArrayIdentifierValue(), 'Regular string id extration not working properly');
    }

    public function testApplyAlternateId()
    {
        $data = array(
            'id' => 1,
            'primaryId' => 2
            );

        $model = new MockModelAlternate();
        $model->fromDataArray($data);
        $this->assertEquals(1, $model->primaryId, 'Alternate id annotation not working properly');

        return $model;
    }

    /**
     * @depends testApplyAlternateId
     */
    public function testExtractAlternateId($model)
    {
        $this->assertEquals(1, $model->getDataArrayIdentifierValue(), 'Alternate id extration not working properly');
    }

}
