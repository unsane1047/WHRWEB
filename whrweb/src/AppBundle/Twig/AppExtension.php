<?php
// src/AppBundle/Twig/AppExtension.php

namespace AppBundle\Twig;

use \Michelf\MarkdownExtra,
	\Michelf\Markdown,
	\cogpowered\FineDiff\Diff,
	\cogpowered\FineDiff\Granularity\Paragraph,
	\cogpowered\FineDiff\Granularity\Sentence,
	\cogpowered\FineDiff\Granularity\Word,
	\cogpowered\FineDiff\Granularity\Character,
	\cogpowered\FineDiff\Render\Text
	\cogpowered\FineDiff\Render\Html
	\Masterminds\HTML5,
	AppBundle\Libraries\AppDateTime,
	Symfony\Bundle\FrameworkBundle\Routing\Router;

class AppExtension extends \Twig_Extension{
	private $markdown_parser;
	private $router;

	public function __construct( Router $r ){
		$this->markdown_parser = new MarkdownExtra;
		$this->markdown_parser->empty_element_suffix = " />";
		$this->markdown_parser->tab_width = 4;
		$this->router = $r;
	}

	public function getFilters(){
		return [
			new \Twig_SimpleFilter( 'markdown', [ $this, 'markdownFilter' ] ),
			new \Twig_SimpleFilter( 'diff', [ $this, 'diffFilter' ] ),
			new \Twig_SimpleFilter( 'parsefulldom', [ $this, 'parseFullDOMFilter' ] ),
			new \Twig_SimpleFilter( 'parsedomfragment', [ $this, 'parseDOMFragmentFilter' ] ),
			new \Twig_SimpleFilter( 'serializeDOM', [ $this, 'serializeDOMFilter' ] ),
			new \Twig_SimpleFilter( 'capitalize', [ $this, 'capitalizeFilter' ] ),
			new \Twig_SimpleFilter( 'ordinalize', [ $this, 'ordinalizeFilter' ] ),
			new \Twig_SimpleFilter( 'asdate', [ $this, 'dateFilter' ] ),
			new \Twig_SimpleFilter( 'asduration', [ $this, 'durationFilter' ] ),
			new \Twig_SimpleFilter( 'asphonenumber', [ $this, 'phonenumberFilter' ] ),
			new \Twig_SimpleFilter( 'uploadurl', [ $this, 'uploadUrlFilter' ] ),
		];
	}

	public function dateFilter( $string, $format = AppDateTime::W3C, $timezone ){
		$date = new AppDateTime( $string, $timezone );
		if( $date === NULL )
			return NULL;
		$date->setFormat( $format );
		return $date->__toString();
	}

	public function durationFilter( $string, $format = '%2$02d:%3$02d:%4$02d', $highestUsed = 'h' ){
		return AppDateTime::duration( $string, $format, $highestUsed );
	}

	public function markdownFilter( $string, $allow_html = false, $allow_entities = false, $footnote_prefix = '' ){
		$this->markdown_parser->no_markup = !$allow_html;
		$this->markdown_parser->no_entities = !$allow_entities;
		$this->markdown_parser->fn_id_prefix = $footnote_prefix;
		return $this->markdown_parser->transform( $string );
	}

	public function diffFilter( $string1, $string2 = '', $g = 2, $format = "Html" ){
		$granularity = [
			'Paragraph',
			'Sentence',
			'Word',
			'Character'
		];
		switch( $format ){
			default:
				$format = 'Html';
			break;

			case 'Text':
			case 'text':
				$format = 'Text';
			break;
		}
		$g = max( min( $g, 3 ), 0 );
		$granularity = $granularity[ $g ];
		$granularity = new $granularity;
		$diff = new Diff( $granularity );
		$string1 = mb_convert_encoding( $string1, 'HTML-ENTITIES' );
		$string2 = mb_convert_encoding( $string2, 'HTML-ENTITIES' );
		$opcode = $diff->getOpcodes( $string1, $string2 );
		$render = new $text;
		return $render->process( $string1, $opcode );
	}

	public function parseFullDOMFilter( $string, $format = 'HTML5' ){
		switch( $format ){
			default:
				$parser = new HTML5();
				$dom = $parser->loadHTML( $string );
			break;
			case 'XML':
				$parser = new \DOMDocument;
				$dom = $parser->loadXML( $string );
			break;
		}
		return $dom;
	}

	public function parseDOMFragmentFilter( $string, $format = 'HTML5' ){
		$parser = new HTML5();
		return $parser->loadHTMLFragment( $string );
	}

	public function serializeDOMFilter( $dom, $format = 'HTML5' ){
		switch( $format ){
			default:
				$parser = new HTML5();
				$string = $parser->saveHTML( $dom );
			break;
			case 'XML':
				$string = $dom->saveXML();
			break;
		}
		return $string;
	}

	public function ordinalizeFilter( $string ){
		if( in_array( ( $string % 100 ), range( 11,13 ) ) )
			return $string . 'th';

		switch( ( $number % 10 ) ){
			case 1:
				return $string . 'st';
			case 2:
				return $string . 'nd';
			case 3:
				return $string . 'rd';
			default:
				return $string . 'th';
		}
	}

	public function capitalizeFilter( $string, $type ){
		if( $type === 'first' )
			return ( mb_strtoupper( mb_substr( $string, 0, 1 ) ) . mb_substr( $string, 1 ) );
		return mb_convert_case( $string, MB_CASE_TITLE );
	}

	 #this should probably be updated to use a table of rules based
	 #on locale and format according to that locale's customs
	public function phonenumberFilter( $string, $extension = '' ){
		$offset = 0;
		$formatted = '';

		if( empty( $string ) )
			return '';

		if( $string[$offset] === '+' ){
			$formatted .= '+';
			$offset++;
			if( substr( $string, $offset, 2 ) === '44' ){ #special formatting for British phone numbers because I know the rules and there is a weirdness with 0 on the city code
				if( substr( $string, $offset + 2, 1 ) === '0' )
					$offset++;
				$formatted .= '44-';
				$formatted .= substr( $string, $offset + 2, 2 ) . '-' . substr( $string, $offset + 4, 4 ) . '-' . substr( $string, $offset + 8 );
			}
			else
				$formatted .= substr( $string, $offset, 3 ) . '.' . substr( $string, $offset + 3 );
		}else{ #format NANPA numbers as usual
			$formatted .= '+1-';
			if( $string[$offset] === 1 )
				$offset++;
			$formatted .= substr( $string, $offset, 3 ) . '-' . substr( $string, $offset + 3, 3 ) . '-' . substr( $string, $offset + 6 );
		}

		if( !empty( $extension ) )
			$formatted .= '#' . $extension;

		return $formatted;
	}

	public function uploadUrlFilter( $path, array $args = [] ){
		$pathVars = array_merge( [ 'path' => $path ], $args );
		return $this->router->generate( 'filecontroller', $pathVars );
	}

	public function getName(){
		return 'app_extension';
	}

}