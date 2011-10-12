<?php

namespace RedpillLinpro\GamineBundle\Tests\Mocks\Annotations;

class MockIdAnnotatedModelAlternate extends \RedpillLinpro\GamineBundle\Model\BaseModel
{

    /**
     * @Id
     * @Column(name="id")
     */
    public $primaryId;

    public $trans;

}
