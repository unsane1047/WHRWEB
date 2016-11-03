<?php

namespace AppBundle\Forms\Type;

use Symfony\Component\Form\AbstractType,
	Symfony\Component\Form\FormView,
	Symfony\Component\Form\FormInterface,
	Symfony\Component\OptionsResolver\OptionsResolver,
	AppBundle\Security\Captcha\CaptchaProvider;

class reCaptchaType extends AbstractType{

    public function buildView( FormView $view, FormInterface $form, array $options ){
        parent::buildView( $view, $form, $options );

        $view->vars = array_merge( $view->vars, array(
            'sitekey'	=> $options['sitekey'],
			'theme'		=> $options['theme'],
			'type'		=> $options['type'],
			'size'		=> $options['size'],
			'tabindex'	=> $options['tabindex'],
			'callback'	=> $options['callback'],
			'expired_callback' => $options['expired_callback']
        ) );
    }

	public function configureOptions( OptionsResolver $resolver ){
		$resolver->setDefaults( array(
			'compound' => false,
			'language' => NULL,
			'mapped' => false,
			'required' => true,
			'trim' => false,
			'label' => false,
			'sitekey' => '',
			'theme' => 'light',
			'type' => 'image',
			'size' => 'normal',
			'tabindex' => 0,
			'callback' => NULL,
			'expired_callback' => NULL
		) );
	}

	public function getBlockPrefix(){
		return 'captcha';
	}
}