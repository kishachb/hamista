#!/usr/bin/env node
/**
 * Generates hamista-core's icon data files and credits from npm packages.
 *
 * Writes:
 *   wp-content/plugins/hamista-core/includes/support/icons/lucide.php  name => inner SVG markup
 *   wp-content/plugins/hamista-core/includes/support/icons/brands.php  slug => path d
 *   wp-content/plugins/hamista-core/assets/icons/CREDITS.md            licences and versions
 *
 * Usage (the packages are NOT dependencies of the repository):
 *   npm install --prefix /tmp/icons-src lucide-static simple-icons
 *   node tools/icons/generate-icons.mjs --src /tmp/icons-src
 *
 * Options:
 *   --src <dir>   Prefix that contains node_modules/lucide-static and node_modules/simple-icons (required).
 *   --core <dir>  hamista-core directory (default: wp-content/plugins/hamista-core).
 *
 * Node 18+, no dependencies.
 */

import { existsSync, mkdirSync, readFileSync, writeFileSync } from 'node:fs';
import { dirname, join, resolve } from 'node:path';
import { fileURLToPath } from 'node:url';

/** Curated Lucide icons. Renamed icons keep their old name through lucide-static's alias files. */
const LUCIDE = [
	'arrow-left', 'arrow-right', 'arrow-up', 'arrow-up-right', 'check', 'check-circle', 'x', 'x-circle', 'plus', 'minus',
	'menu', 'search', 'sun', 'moon', 'monitor', 'user', 'users', 'shopping-cart', 'shopping-bag', 'key', 'download',
	'upload', 'bell', 'ticket', 'file', 'file-text', 'files', 'code', 'code-xml', 'palette', 'pen-tool', 'briefcase',
	'share-2', 'cpu', 'puzzle', 'layout-template', 'sparkles', 'rocket', 'shield', 'shield-check', 'zap', 'globe', 'mail',
	'phone', 'map-pin', 'clock', 'star', 'chevron-left', 'chevron-right', 'chevron-down', 'chevron-up', 'external-link',
	'copy', 'eye', 'heart', 'message-circle', 'messages-square', 'settings', 'log-out', 'house', 'layout-grid', 'list',
	'filter', 'refresh-cw', 'trash-2', 'pencil', 'calendar', 'credit-card', 'receipt', 'package', 'layers', 'trending-up',
	'award', 'target', 'compass', 'lightbulb', 'megaphone', 'book-open', 'headphones', 'lock', 'info', 'alert-triangle',
	'circle-help', 'loader', 'paperclip', 'send', 'image', 'play', 'quote', 'gauge', 'wallet', 'store', 'badge-check',
	'box', 'database', 'server', 'smartphone', 'monitor-smartphone', 'gift',
];

/** Brand icons from Simple Icons (slugs). Missing ones are skipped and reported. */
const BRANDS = [ 'instagram', 'telegram', 'whatsapp', 'linkedin', 'x', 'youtube', 'github', 'dribbble', 'behance', 'pinterest', 'facebook', 'aparat' ];

/** Elements and attributes allowed in generated Lucide markup (matches Html::svg_kses()). */
const ALLOWED_TAGS = new Set( [ 'path', 'circle', 'ellipse', 'line', 'polyline', 'polygon', 'rect' ] );
const ALLOWED_ATTRS = new Set( [ 'd', 'cx', 'cy', 'r', 'rx', 'ry', 'x', 'y', 'x1', 'y1', 'x2', 'y2', 'width', 'height', 'points', 'fill', 'stroke' ] );
const PAINT_ATTRS = new Set( [ 'fill', 'stroke' ] );
const PAINT_VALUES = new Set( [ 'currentColor', 'none' ] );

function parseArgs( argv ) {
	const args = {};
	for ( let i = 0; i < argv.length; i++ ) {
		if ( argv[ i ].startsWith( '--' ) ) {
			args[ argv[ i ].slice( 2 ) ] = argv[ i + 1 ];
			i++;
		}
	}
	return args;
}

function fail( message ) {
	console.error( `generate-icons: ${ message }` );
	process.exit( 1 );
}

function packageVersion( dir ) {
	return JSON.parse( readFileSync( join( dir, 'package.json' ), 'utf8' ) ).version;
}

/** Inner markup of a lucide-static SVG, validated against the allow-list. */
function lucideInner( dir, name ) {
	const file = join( dir, 'icons', `${ name }.svg` );
	if ( ! existsSync( file ) ) {
		return null;
	}
	const svg = readFileSync( file, 'utf8' ).replace( /<!--[\s\S]*?-->/g, '' );
	const match = svg.match( /<svg[^>]*>([\s\S]*?)<\/svg>/ );
	if ( ! match ) {
		fail( `${ file } has no <svg> element` );
	}
	const elements = match[ 1 ].split( '\n' ).map( ( line ) => line.trim() ).filter( Boolean );
	for ( const element of elements ) {
		const tag = element.match( /^<([a-z]+)\s(.*?)\s*\/>$/ );
		if ( ! tag || ! ALLOWED_TAGS.has( tag[ 1 ] ) ) {
			fail( `${ name }: unexpected markup ${ element }` );
		}
		for ( const [ , attr, value ] of tag[ 2 ].matchAll( /([a-z0-9-]+)="([^"]*)"/g ) ) {
			const badPaint = PAINT_ATTRS.has( attr ) && ! PAINT_VALUES.has( value );
			if ( ! ALLOWED_ATTRS.has( attr ) || badPaint || /[<>&'\\]/.test( value ) ) {
				fail( `${ name }: unexpected attribute ${ attr }="${ value }"` );
			}
		}
	}
	return elements.map( ( element ) => element.replace( /\s*\/>$/, '/>' ) ).join( '' );
}

/** Path data of a Simple Icons SVG. */
function brandPath( dir, slug ) {
	const file = join( dir, 'icons', `${ slug }.svg` );
	if ( ! existsSync( file ) ) {
		return null;
	}
	const match = readFileSync( file, 'utf8' ).match( /<path d="([^"]+)"\s*\/>/ );
	if ( ! match || ! /^[MmLlHhVvCcSsQqTtAaZz0-9.,\s-]+$/.test( match[ 1 ] ) ) {
		fail( `${ slug }: unexpected path data` );
	}
	return match[ 1 ];
}

function phpString( value ) {
	return `'${ value.replace( /\\/g, '\\\\' ).replace( /'/g, "\\'" ) }'`;
}

function phpArrayFile( description, entries ) {
	const width = Math.max( ...entries.map( ( [ key ] ) => phpString( key ).length ) );
	const lines = entries.map( ( [ key, value ] ) => `\t${ phpString( key ).padEnd( width ) } => ${ phpString( value ) },` );
	return [
		'<?php',
		'/**',
		...description.map( ( line ) => ( line ? ` * ${ line }` : ' *' ) ),
		' *',
		' * Generated by tools/icons/generate-icons.mjs. Do not edit by hand.',
		' *',
		' * @package Hamista\\Core',
		' */',
		'',
		"defined( 'ABSPATH' ) || exit;",
		'',
		'return [',
		...lines,
		'];',
		'',
	].join( '\n' );
}

const args = parseArgs( process.argv.slice( 2 ) );
if ( ! args.src ) {
	fail( 'missing --src <dir> (an npm prefix with lucide-static and simple-icons installed)' );
}

const repo = resolve( dirname( fileURLToPath( import.meta.url ) ), '..', '..' );
const core = resolve( args.core ?? join( repo, 'wp-content/plugins/hamista-core' ) );
const lucideDir = join( resolve( args.src ), 'node_modules/lucide-static' );
const simpleDir = join( resolve( args.src ), 'node_modules/simple-icons' );
for ( const dir of [ lucideDir, simpleDir ] ) {
	if ( ! existsSync( join( dir, 'package.json' ) ) ) {
		fail( `${ dir } not found; run npm install --prefix ${ args.src } lucide-static simple-icons` );
	}
}
const lucideVersion = packageVersion( lucideDir );
const simpleVersion = packageVersion( simpleDir );

const lucide = [];
const missingLucide = [];
for ( const name of LUCIDE ) {
	const inner = lucideInner( lucideDir, name );
	if ( null === inner ) {
		missingLucide.push( name );
	} else {
		lucide.push( [ name, inner ] );
	}
}
if ( missingLucide.length ) {
	fail( `Lucide icons not found (pick the closest current name): ${ missingLucide.join( ', ' ) }` );
}

const brands = [];
const skippedBrands = [];
for ( const slug of BRANDS ) {
	const d = brandPath( simpleDir, slug );
	if ( null === d ) {
		skippedBrands.push( slug );
	} else {
		brands.push( [ slug, d ] );
	}
}

lucide.sort( ( a, b ) => a[ 0 ].localeCompare( b[ 0 ] ) );
brands.sort( ( a, b ) => a[ 0 ].localeCompare( b[ 0 ] ) );

const iconsDir = join( core, 'includes/support/icons' );
const creditsDir = join( core, 'assets/icons' );
mkdirSync( iconsDir, { recursive: true } );
mkdirSync( creditsDir, { recursive: true } );

writeFileSync(
	join( iconsDir, 'lucide.php' ),
	phpArrayFile(
		[
			'Lucide icons: name => inner SVG markup (24×24 viewBox, 2px round stroke).',
			'',
			`Source: lucide-static ${ lucideVersion } (ISC; Feather-derived icons MIT). See assets/icons/CREDITS.md.`,
		],
		lucide
	)
);

writeFileSync(
	join( iconsDir, 'brands.php' ),
	phpArrayFile(
		[
			'Brand icons: Simple Icons slug => path data (24×24 viewBox, filled).',
			'',
			`Source: simple-icons ${ simpleVersion } (CC0-1.0). Brand marks are trademarks of their owners. See assets/icons/CREDITS.md.`,
		],
		brands
	)
);

const lucideLicense = readFileSync( join( lucideDir, 'LICENSE' ), 'utf8' ).trim();
writeFileSync(
	join( creditsDir, 'CREDITS.md' ),
	[
		'# Icon credits',
		'',
		'Generated by `tools/icons/generate-icons.mjs`. The icon data lives in `includes/support/icons/`.',
		'',
		'## Lucide',
		'',
		`- Source: [lucide-static](https://lucide.dev) ${ lucideVersion }`,
		'- Licence: ISC. Icons derived from Feather are also under the MIT licence (both notices below).',
		`- Icons: ${ lucide.map( ( [ name ] ) => name ).join( ', ' ) }`,
		'',
		'```text',
		lucideLicense,
		'```',
		'',
		'## Simple Icons',
		'',
		`- Source: [simple-icons](https://simpleicons.org) ${ simpleVersion }`,
		'- Licence: CC0-1.0 (public domain dedication).',
		`- Icons: ${ brands.map( ( [ slug ] ) => slug ).join( ', ' ) }`,
		...( skippedBrands.length ? [ `- Not available in Simple Icons, so not included: ${ skippedBrands.join( ', ' ) }` ] : [] ),
		'',
		'Brand icons are trademarks of their respective owners. Their use does not imply endorsement;',
		"follow each brand's guidelines (see the Simple Icons DISCLAIMER).",
		'',
	].join( '\n' )
);

console.log( `lucide-static ${ lucideVersion }: ${ lucide.length } icons` );
console.log( `simple-icons ${ simpleVersion }: ${ brands.length } brands` );
if ( skippedBrands.length ) {
	console.log( `skipped (not in Simple Icons): ${ skippedBrands.join( ', ' ) }` );
}
