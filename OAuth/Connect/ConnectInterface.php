<?php

/*
 * This file is part of the current project.
 * 
 * (c) ForeverGlory <http://foreverglory.me/>
 * 
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Glory\Bundle\OAuthBundle\OAuth\Connect;

use Glory\Bundle\OAuthBundle\Model\OAuthInterface;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * ConnectInterface
 * 
 * @author ForeverGlory <foreverglory@qq.com>
 */
interface ConnectInterface
{

    public function connect(OAuthInterface $oauth, UserInterface $user = null);

    public function unConnect(OAuthInterface $oauth);
}
