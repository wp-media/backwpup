<?php
if ( ! class_exists( 'BackWPup' , false ) )
	die();

if ( ! class_exists( 'Symfony\\Component\\ClassLoader\\UniversalClassLoader' ) )
	include_once __DIR__ . '/Symfony/Component/ClassLoader/UniversalClassLoader.php';

$classLoader = new Symfony\Component\ClassLoader\UniversalClassLoader();
$classLoader->registerNamespaces( array(
									   	'Aws\\Common'  				=> __DIR__,
									   	'Aws\\S3'  					=> __DIR__,
									   	'Guzzle'   					=> __DIR__,
									   	'Symfony\\Component\\EventDispatcher' => __DIR__,
									   	'WindowsAzure' 				=> __DIR__,
										'OpenCloud'					=> __DIR__
								  ) );
$classLoader->register();

// http://www.pear.com/ for WindowsAzure
if ( ! class_exists( 'HTTP_Request2' ) )
	set_include_path( get_include_path() . PATH_SEPARATOR . __DIR__ . '/PEAR/');
