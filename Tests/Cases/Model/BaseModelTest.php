<?php

namespace RedpillLinpro\GamineBundle\Tests\Cases\Model;

use RedpillLinpro\GamineBundle\Tests\Mocks\Model\MockModel;

class BaseModelTest extends \PHPUnit_Framework_TestCase
{

    public function testConstruction()
    {
        $model = new MockModel();
        $this->assertTrue($model instanceof \RedpillLinpro\GamineBundle\Model\BaseModel);
    }

    public function testDescribe()
    {
        $expected = array(
            'primary_key' => array('property' => 'mock_id', 'key' => 'id'),
            'properties' => array(
                'mock_id' => array('id' => array('value' => null), 'column' => array('name' => 'id', 'value' => null)),
                'title' => array('column' => array('name' => null, 'value' => null)),
                'names' => array(
                    'column' => array('name' => null, 'value' => null),
                    'extract' => array(
                        'columns' => array('first' => 'firstName', 'last' => 'lastName'),
                        'preserve_items' => false,
                        'value' => null
                    )
                ),
                'subber' => array(
                    'column' => array('name' => null, 'value' => null),
                    'sub_model' => array(
                            'collection' => true,
                            'entity' => 'subber',
                            'identifier' => 'type',
                            'extract_mode' => 'full',
                            'value' => null,
                    )
                ),
                'mother' => array(
                    'column' => array('name' => null, 'value' => null),
                    'relates' => array(
                        'entity' => 'parent',
                        'collection' => true,
                        'relative' => false,
                        'related_by' => 'fk',
                        'value' => null,
                    )
                )
            )
        );
        $result = MockModel::describe();
        $this->assertEquals($expected, $result, 'Description (annotations) of model is wrong');
    }

    public function testPkFunctions()
    {
        $model = new MockModel();
        $result = $model->hasDataArrayIdentifierValue();
        $this->assertFalse($result);
        $model->mock_id = 123;
        $result = $model->hasDataArrayIdentifierValue();
        $this->assertTrue($result);

        $result = $model->getDataArrayIdentifierValue();
        $this->assertEquals(123, $result);

        $model->setDataArrayIdentifierValue(456);
        $this->assertEquals(456, $model->mock_id);
    }

    public function testGetEntityResource()
    {
        $model = new MockModel();
        $expected = 'mockmodel';
        $result = $model->getEntityResource();
        $this->assertEquals($expected, $result);
    }

    public function testFromDataArray()
    {
        $data = array(
            'id' => 123,
            'title' => 'Hello world',
            'names' => array('first' => 'alek', 'last' => 'mor'),
            'bleh' => 'HEH'
        );
        $model = new MockModel();
        $model->fromDataArray($data);

        $this->assertEquals(123, $model->mock_id);
        $this->assertEquals('Hello world', $model->title);
        $this->assertEquals(array(), $model->names);
        $this->assertEquals('alek', $model->firstName);
        $this->assertEquals('mor', $model->lastName);
        $this->assertEquals(null, $model->bleh);
        $this->assertEquals('transient', $model->trans);
        $this->assertEquals(null, $model->subber);
        $this->assertEquals(null, $model->mother);
    }

    public function testToDataArray()
    {
        $model = new MockModel();
        $model->mock_id = 123;
        $model->title = 'Hello world';
        $model->names = array();
        $model->firstName = 'Key';
        $model->lastName = 'West';

        $expected = array(
            'id' => 123,
            'title' => 'Hello world',
            'names' =>array('first' => 'Key', 'last' => 'West'),
            'subber' => array(),
            'mother' => null,
        );
        $result = $model->toDataArray(false);
        $this->assertEquals($expected, $result);
    }

    public function testModifiedData()
    {
        $data = array(
            'id' => 123,
            'title' => 'Hello world',
            'names' => array('first' => 'alek', 'last' => 'mor'),
            'bleh' => 'HEH',
        );
        $model = new MockModel();
        $model->fromDataArray($data);

        $model->title = 'New title';

        $expected = array(
            'title' => array('from' => 'Hello world', 'to' => 'New title'),
            'subber' => array('from' => null, 'to' => array()),
        );
        $result = $model->getModifiedDataArray();
        $this->assertEquals($expected, $result);
    }

    /**
     *  @dataProvider mapExtractAnnotationProvider
     */
    public function testMapExtractAnnotation($mappings, $original_data, $expected, $result_key, $removeUnchanged)
    {
        $model = new MockModel();
        $model->setOriginalData($original_data);
        $model->firstName = 'Kalle';
        $model->lastName = 'Anka';
        $result = array();
        $model->mapExtractAnnotation($mappings, $result, $result_key, $removeUnchanged);
        $this->assertEquals($expected, $result);

    }

    public function mapExtractAnnotationProvider()
    {
        $mappings = array('extract' => array(
                    'columns'        => array('first' => 'firstName', 'last' => 'lastName' ),
                    'preserve_items' => false,
                    'value'          => null));

        $original_data =  array(
                    'names' => array(
                        'first' => 'Donald',
                        'last' => 'Duck'));
        $original_data2 =  array(
                    'names' => array(
                        'first' => 'Kalle',
                        'last' => 'Anka'));
        return array(
            array(
                'mappings' => $mappings,
                'original_data' => $original_data,
                'expected' => array(
                    'names' =>  array('first' => 'Kalle', 'last' => 'Anka' )),
                'property' => 'names',
                'removeUnchanged' => false),
            array(
                'mappings' => $mappings,
                'original_data' => $original_data,
                'expected' => array(
                    'names' =>  array('first' => 'Kalle', 'last' => 'Anka' )),
                'property' => 'names',
                'removeUnchanged' => true),
            array(
                'mappings' => $mappings,
                'original_data' => $original_data2,
                'expected' => array(),
                'property' => 'names',
                'removeUnchanged' => true),
            array(
                'mappings' => $mappings,
                'original_data' => $original_data2,
                'expected' => $original_data2,
                'property' => 'names',
                'removeUnchanged' => false),
        );
    }
}
