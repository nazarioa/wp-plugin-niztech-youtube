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
	'output-dir'              => './../dist/niztech-youtube',

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
				->in( __DIR__ . '/../build/niztech-youtube--before-prefix' )
				->notName( '/LICENSE|.*\\.md|.*\\.dist|Makefile|composer\\.json|composer\\.lock/' ),

		// Optionally include your plugin code (usually not needed)
	),

	'exclude-files'           => array(),
	'exclude-namespaces'      => array(),
	'patchers'                => array(
		static function ( string $filePath, string $prefix, string $content ): string {
			$altered_content = $content;
			if ( str_ends_with( $filePath, 'class-niztech-youtube.php' ) ) {
				$altered_content = str_replace(
					'new Google\Client(',
					'new ' . $prefix . '\Google\Client(',
					$altered_content
				);

				$altered_content = str_replace(
					'new Google\Service\YouTube(',
					'new ' . $prefix . '\Google\Service\YouTube(',
					$altered_content
				);

				$altered_content = preg_replace(
					// For some reason str_replace makes tab and new line characters appear when they should not
					array( '/\\\n/', '/\\\t+/' ),
					array( ' ', '' ),
					$altered_content
				);
			}

			return $altered_content;
		},
	),

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
