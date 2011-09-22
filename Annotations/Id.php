<?php

namespace RedpillLinpro\GamineBundle\Annotations;

class Id extends \Doctrine\Common\Annotations\Annotation
{
    public function getKey()
    {
        return 'id';
    }
}
