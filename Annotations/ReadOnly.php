<?php

namespace RedpillLinpro\GamineBundle\Annotations;

class ReadOnly extends \Doctrine\Common\Annotations\Annotation
{
    public function getKey()
    {
        return 'readonly';
    }
}
