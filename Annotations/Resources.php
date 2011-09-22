<?php

namespace RedpillLinpro\GamineBundle\Annotations;

class Resources extends \Doctrine\Common\Annotations\Annotation
{
    public $entity;
    public $collection;

    public function getKey()
    {
        return 'resources';
    }
}
