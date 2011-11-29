<?php

namespace RedpillLinpro\GamineBundle\Annotations;

class SubModel extends \Doctrine\Common\Annotations\Annotation
{
    public $collection = false;
    public $entity;
    public $identifier;
    public $extract_mode = 'full';

    public function getKey()
    {
        return 'sub_model';
    }
}
