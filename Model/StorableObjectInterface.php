<?php

/**
 *
 * @author    Thomas Lundquist <thomasez@redpill-linpro.com>
 * @copyright 2011 Thomas Lundquist
 * @license   http://www.gnu.org/copyleft/lesser.html  LGPL License 2.1
 *
 */

namespace RedpillLinpro\GamineBundle\Model;

interface StorableObjectInterface
{

    public function fromDataArray($data, \RedpillLinpro\GamineBundle\Manager\BaseManager $manager);

    public function toDataArray();

    public static function getClassName();

}
