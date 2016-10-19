<?php

namespace REJ\XacmlBundle\DependencyInjection;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;
use Symfony\Component\DependencyInjection\Loader;

/**
 * This is the class that loads and manages your bundle configuration.
 *
 * @link http://symfony.com/doc/current/cookbook/bundles/extension.html
 */
class REJXacmlExtension extends Extension
{
    /**
     * {@inheritdoc}
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        $configuration = new Configuration( $container->getParameter( 'kernel.debug' ), $container->getParameter( 'kernel.cache_dir' ) );
        $config = $this->processConfiguration( $configuration, $configs );

        $loader = new Loader\YamlFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));
        $loader->load('services.yml');

		$container->setParameter( 'rej_xacml.debug', $config[ 'debug' ] );
		$container->setParameter( 'rej_xacml.policydir', $config[ 'policydir' ] );
		$container->setParameter( 'rej_xacml.cachedir', $config[ 'cachedir' ] );
		$container->setParameter( 'rej_xacml.combiner', $config[ 'combiner' ] );

		$this->addClassesToCompile( [
			'REJ\\XacmlBundle\\PolicyDecisionPoint',
			'REJ\\XacmlBundle\\PolicyEnforcementPoint',
			'REJ\\XacmlBundle\\Cache\\DecisionCache'
		] );
    }

}
