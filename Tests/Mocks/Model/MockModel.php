<?php

namespace RedpillLinpro\GamineBundle\Tests\Mocks\Model;

class MockModel extends \RedpillLinpro\GamineBundle\Model\BaseModel
{

    /**
     * @Id
     * @Column(name="id")
     */
    public $mock_id;

    /** @Column */
    public $title;

    public $trans = "transient";

    /**
     * @Column
     * @Extract(columns={"first"="firstName", "last"="lastName"}, preserve_items=false)
     */
    public $names;
    public $firstName;
    public $lastName;

    public $bleh;
    
    /**
     * @Column
     * @SubModel(entity="subber", collection=true, identifier="type")
     */
    public $subber;

    /**
     * @Column
     * @Relates(entity="parent", collection=true, relative=false, related_by="fk")
     */
    public $mother;
}
