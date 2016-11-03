<?php

namespace AppBundle\Forms;

use Symfony\Component\Form\AbstractType,
	Symfony\Component\Form\FormBuilderInterface,
	Symfony\Component\Form\Extension\Core\Type\SubmitType,
	Symfony\Component\Form\Extension\Core\Type\EmailType,
	Symfony\Component\Form\Extension\Core\Type\PasswordType,
	Symfony\Component\OptionsResolver\OptionsResolver,
	AppBundle\Forms\Type\reCaptchaType,
	AppBundle\Security\Captcha\CaptchaProvider;

class AuthenticationType extends AbstractType{

	public function buildForm( FormBuilderInterface $builder, array $options ){
		$builder
			->setMethod( 'POST' )
			->add( '_username', EmailType::class, array( 'label' => 'user.username' ) )
			->add( '_password', PasswordType::class, array( 'label' => 'user.password' ) );

		if( isset( $options[ 'captcha' ] ) && $options[ 'captcha' ] === true )
			$builder->add( '_captcha', reCaptchaType::class, array( 'sitekey' => $options[ 'sitekey' ] ) );

		$builder->add( 'login', SubmitType::class, array( 'label' => 'user.login' ) );
	}

	public function configureOptions( OptionsResolver $resolver ){
		$resolver->setDefaults( array(
			'captcha' => false,
			'sitekey' => ''
		) );
	}

	public function getName(){
		return 'authentication';
	}
}