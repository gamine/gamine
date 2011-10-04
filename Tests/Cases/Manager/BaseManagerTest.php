<?php

namespace RedpillLinpro\GamineBundle\Tests\Cases\Manager;

use RedpillLinpro\GamineBundle\Manager\BaseManager;

class BaseManagerMock extends BaseManager {

}

class BaseManagerTest extends \PHPUnit_Framework_TestCase
{

    private $man = null;

    public function testConstruction()
    {
        $gamine_service = null;
        $entity_key = 'Basic';
        $access_service = null;
        $this->man = new BaseManagerMock($gamine_service, $entity_key, $access_service);
        $this->assertTrue($this->man instanceof BaseManager);
    }
}
