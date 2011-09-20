<?php

namespace RedpillLinpro\GamineBundle\Annotations;

class Extract extends \Doctrine\Common\Annotations\Annotation
{
    public $columns;
    public $preserve_items = true;
    
    public function hasColumns()
    {
        return (bool) !empty($this->columns);
    }
    
}