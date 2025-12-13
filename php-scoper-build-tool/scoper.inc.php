<?php
declare(strict_types=1);

use Symfony\Component\Finder\Finder;


/** @var Symfony\Component\Finder\Finder $finder */
$finder = Isolated\Symfony\Component\Finder\Finder::class;


function getWpExcludedSymbols( string $fileName ): array {
	$filePath = __DIR__ . '/vendor/sniccowp/php-scoper-wordpress-excludes/generated/' . $fileName;

	return json_decode(
		file_get_contents( $filePath ),
		true,
	);
}


$wpConstants = getWpExcludedSymbols( 'exclude-wordpress-constants.json' );
$wpClasses   = getWpExcludedSymbols( 'exclude-wordpress-classes.json' );
$wpFunctions = getWpExcludedSymbols( 'exclude-wordpress-functions.json' );


return array(
	// your unique namespace prefix
	'prefix'                  => 'Niztech\NiztechYoutubeDependency',
	'output-dir' => './niztech-youtube/vendor',

	'finders'                 => array(
		// Scan your vendor directory (except WP ones)
		Finder::create()
				->files()
				->exclude(
					array(
						'bin',
						'doc',
						'test',
						'test_old',
						'tests',
						'Tests',
						'vendor-bin',
					)
				)
				->in( __DIR__ . '/niztech-youtube-vendor-pre-process' )
				->notName( '/LICENSE|.*\\.md|.*\\.dist|Makefile|composer\\.json|composer\\.lock/' ),

		// Optionally include your plugin code (usually not needed)
	),

	'exclude-files'           => array(),
	'exclude-namespaces'      => array(),
	'patchers'                => array(),

	'expose-namespaces'       => array(
		// If you want: expose your own plugin namespace
		'Niztech\NiztechYoutube',
	),

	'expose-global-classes'   => true,
	'expose-global-constants' => true,
	'expose-global-functions' => true,

	'expose-classes'          => array(),
	'expose-constants'        => array(),
	'expose-functions'        => array(),

	'exclude-classes'         => $wpClasses,
	'exclude-constants'       => $wpConstants,
	'exclude-functions'       => $wpFunctions,
);
