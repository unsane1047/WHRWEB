<?php

namespace AppBundle\Security\DependencyInjection\Factory;

use	Symfony\Bundle\SecurityBundle\DependencyInjection\Security\Factory\FormLoginFactory as FLF;
use	Symfony\Component\Config\Definition\Builder\NodeDefinition;
use	Symfony\Component\DependencyInjection\DefinitionDecorator;
use	Symfony\Component\DependencyInjection\ContainerBuilder;
use	Symfony\Component\DependencyInjection\Reference;

class FormLoginFactory extends FLF{

	public function getKey(){
		return 'mod-form-login';
	}

	protected function getListenerId(){
		return 'app.security.authentication.listener.form';
	}

	protected Function createAuthProvider( ContainerBuilder $container, $id, $config, $userProviderId ){
       $provider = 'app.security.authentication.provider.dao.'.$id;
        $container
            ->setDefinition($provider, new DefinitionDecorator('app.security.authentication.provider.dao'))
            ->replaceArgument(0, new Reference($userProviderId))
            ->replaceArgument(1, new Reference('security.user_checker.'.$id))
            ->replaceArgument(2, $id)
        ;

        return $provider;
	}

}