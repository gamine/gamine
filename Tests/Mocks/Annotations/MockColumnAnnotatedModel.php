<?php

namespace RedpillLinpro\GamineBundle\Tests\Mocks\Annotations;

class MockColumnAnnotatedModel extends \RedpillLinpro\GamineBundle\Model\BaseModel
{

    /** @Column */
    public $title;

    /**
     * @Column(name="realname")
     */
    public $my_real_name;

    public $trans;

}
