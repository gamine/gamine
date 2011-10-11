<?php

namespace RedpillLinpro\GamineBundle\Tests\Mocks\Model;

class MockCar extends \RedpillLinpro\GamineBundle\Model\BaseModel
{

    /**
     * @Id
     * @Column(name="id")
     */
    public $mock_id;

    /**
     * @Column
     */
    public $make;

}
