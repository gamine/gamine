<?php

namespace RedpillLinpro\GamineBundle\Annotations;

class Relates extends \Doctrine\Common\Annotations\Annotation
{
    public $entity;
    public $collection = false;
    public $relative = true;
    public $related_by;

    public function getKey()
    {
        return 'relates';
    }
}
