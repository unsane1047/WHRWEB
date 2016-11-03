<?php

namespace AppBundle;

use Symfony\Component\HttpKernel\Bundle\Bundle;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use AppBundle\Security\DependencyInjection\Factory\FormLoginFactory as FLF;

class AppBundle extends Bundle
{
	public function build( ContainerBuilder $container ){
		parent::build( $container );

		$extension = $container->getExtension( 'security' );
		$extension->addSecurityListenerFactory( new FLF() );
	}
}