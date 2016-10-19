<?php

namespace REJ\XacmlBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

/**
 * This is the class that validates and merges configuration from your app/config files.
 *
 * To learn more see {@link http://symfony.com/doc/current/cookbook/bundles/configuration.html}
 */
class Configuration implements ConfigurationInterface
{
	private $debug;
	private $cacheDir;

	public function __construct( $debug, $cacheDir )
	{
		$this->debug = (bool) $debug;
		$this->cacheDir = (string) $cacheDir;
	}

    /**
     * {@inheritdoc}
     */
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder();
        $rootNode = $treeBuilder->root('rej_xacml');

        $rootNode
			->children()
				->scalarNode( 'policydir' )
					->defaultValue( '' )
				->end()
				->scalarNode( 'cachedir' )
					->defaultValue( $this->cacheDir )
				->end()
				->scalarNode( 'combiner' )
					->defaultValue( 'urn:oasis:names:tc:xacml:3.0:policy-combining-algorithm:deny-overrides' )
				->end()
				->booleanNode( 'debug' )
					->defaultValue( $this->debug )
				->end()
			->end()
		->end();


        return $treeBuilder;
    }
}
