<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Glory\Bundle\OAuthBundle\Model;

/**
 *
 * @author ForeverGlory
 */
interface UserOAuthInterface
{

    public function hasOAuth($owner);

    public function addOAuth($oauth);

    public function removeOAuth($oauth);

    public function getOAuths();
}
