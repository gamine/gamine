<?php

namespace RedpillLinpro\GamineBundle\Exceptions;

class ValidationError extends \Exception
{

    protected $code;
    protected $message;
    protected $container;

    public function __construct($message = null, $code = null, $container = array()) {
        $this->code = $code;
        $this->message = $message;
        $this->container = $container;
    }

    public function getMeta()
    {
        return $this->__getContainerPiece('meta');
    }

    public function getError()
    {
        return $this->__getContainerPiece('error');
    }

    public function getDebug()
    {
        return $this->__getContainerPiece('debug');
    }

    public function getData()
    {
        return $this->__getContainerPiece('data');
    }

    public function __getContainerPiece($piece)
    {
        if ($this->container && is_array($this->container) && isset($this->container[$piece])) {
            return $this->container[$piece];
        }
    }

}
