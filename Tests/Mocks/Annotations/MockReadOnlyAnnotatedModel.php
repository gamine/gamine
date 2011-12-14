<?php

namespace RedpillLinpro\GamineBundle\Tests\Mocks\Annotations;

class MockReadOnlyAnnotatedModel extends \RedpillLinpro\GamineBundle\Model\BaseModel
{

    /** @Column */
    public $name;

    /**
     * @ReadOnly
     * @Column
     */
    public $email;

    public $trans;

}
