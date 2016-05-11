<?php

/**
 * (c) ForeverGlory <http://foreverglory.me/>
 * 
 * For the full copyright and license information, please view the LICENSE
 */

namespace Glory\Bundle\OAuthBundle\OAuth;

use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;
use HWI\Bundle\OAuthBundle\OAuth\ResourceOwnerInterface as OwnerInterface;

/**
 * Description of OwnerMap
 *
 * @author ForeverGlory
 */
class OwnerMap implements ContainerAwareInterface
{

    use ContainerAwareTrait;

    protected $owners = [];

    public function addOwner(OwnerInterface $owner)
    {
        $this->owners[$owner->getName()] = $owner;
    }

    public function hasOwner($name)
    {
        return array_key_exists($name, $this->owners);
    }

    /**
     * @param type $name
     * @return OwnerInterface
     */
    public function getOwner($name)
    {
        return $this->hasOwner($name) ? $this->owners[$name] : null;
    }

    public function getOwners()
    {
        return $this->owners;
    }

}