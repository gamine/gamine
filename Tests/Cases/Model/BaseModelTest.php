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


}
