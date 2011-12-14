<?php

namespace RedpillLinpro\GamineBundle\Tests\Mocks\Annotations;

class MockRelatesAnnotatedModel extends \RedpillLinpro\GamineBundle\Model\BaseModel
{

    /**
     * @Id
     * @Column
     */
    public $id;

    /**
     * @Relates(entity="cars", collection=true)
     */
    public $cars;

    /**
     * @Relates(entity="steering_wheels")
     */
    public $steering_wheel;

}
