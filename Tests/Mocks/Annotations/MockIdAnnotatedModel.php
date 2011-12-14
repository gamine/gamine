<?php

namespace RedpillLinpro\GamineBundle\Tests\Mocks\Annotations;

class MockIdAnnotatedModel extends \RedpillLinpro\GamineBundle\Model\BaseModel
{

    /**
     * @Id
     * @Column
     */
    public $id;

    public $trans;

}
