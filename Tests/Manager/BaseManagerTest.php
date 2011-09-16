<?php

namespace RedpillLinpro\GamineBundle\Tests\Manager;

use RedpillLinpro\GamineBundle\Manager\BaseManager;

class BaseManagerMock extends BaseManager {
    
}

class BaseManagerTest extends \PHPUnit_Framework_TestCase
{
    public function testConstruction()
    {
        $man = new BaseManagerMock(null, new \RedpillLinpro\GamineBundle\Gamine());
        d($man);
    }
}
