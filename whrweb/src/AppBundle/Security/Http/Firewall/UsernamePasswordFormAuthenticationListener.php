<?php
//this class is here just to simplify things with the custom FormLoginFactory, do not remove it
namespace AppBundle\Security\Http\Firewall;

use Symfony\Component\Security\Http\Firewall\UsernamePasswordFormAuthenticationListener as UPFAL;

class UsernamePasswordFormAuthenticationListener extends UPFAL{}