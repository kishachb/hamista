#!/usr/bin/env node
/**
 * HAMISTA asset pipeline (Node 22, ESM).
 *
 *   node tools/build-assets.mjs               fonts + CSS bundles + minify every package + size report
 *   node tools/build-assets.mjs --fonts-only  copy the web fonts into the theme only
 *
 * 1. Fonts:   Vazirmatn-NL and Inter (latin) variable woff2 + OFL licences -> theme assets/fonts/.
 * 2. Bundles: wp-content/themes/hamista/assets/css/src/bundles.json maps an output path (relative to
 *             the theme's assets/css/) to an ordered list of partials (globs allowed in the file name).
 *             Each bundle is concatenated with a banner per part, url() paths are rewritten for the
 *             output location, and a .min.css sibling is written. Core's ui.css fallback is one bundle.
 * 3. Minify:  every assets/** /*.css (not *.min.css, not under /src/) and assets/** /*.js (not *.min.js)
 *             of the theme and of every wp-content/plugins/hamista-* package gets a .min sibling.
 * 4. Report:  raw + gzip size of the theme's main.min.css / main.min.js against their budgets (warn only).
 *
 * Exits non-zero on any CSS/JS syntax error or missing input.
 */
import { readFile, writeFile, readdir, mkdir, copyFile } from 'node:fs/promises';
import { existsSync } from 'node:fs';
import path from 'node:path';
import { fileURLToPath } from 'node:url';
import { gzipSync } from 'node:zlib';
import { transform as transformCss, Features } from 'lightningcss';
import { transform as transformJs } from 'esbuild';

const ROOT = path.resolve( path.dirname( fileURLToPath( import.meta.url ) ), '..' );
const THEME = path.join( ROOT, 'wp-content/themes/hamista' );
const PLUGINS = path.join( ROOT, 'wp-content/plugins' );
const THEME_CSS = path.join( THEME, 'assets/css' );
const CSS_SRC = path.join( THEME_CSS, 'src' );
const MODULES = path.join( ROOT, 'node_modules' );

// lightningcss encodes browser versions as (major << 16) | (minor << 8) | patch.
const version = ( major, minor = 0 ) => ( major << 16 ) | ( minor << 8 );
const CSS_TARGETS = {
	chrome: version( 100 ),
	edge: version( 100 ),
	firefox: version( 100 ),
	safari: version( 15 ),
	ios_saf: version( 15 ),
};
const JS_TARGET = 'es2019';

const FONTS = [
	{
		from: 'vazirmatn/misc/Non-Latin/fonts/webfonts/Vazirmatn-NL[wght].woff2',
		to: 'vazirmatn-nl-var.woff2',
	},
	{ from: 'vazirmatn/OFL.txt', to: 'vazirmatn-OFL.txt' },
	{ from: '@fontsource-variable/inter/files/inter-latin-wght-normal.woff2', to: 'inter-latin-var.woff2' },
	{ from: '@fontsource-variable/inter/LICENSE', to: 'inter-OFL.txt' },
];

const BUDGETS = [
	{ file: path.join( THEME_CSS, 'main.min.css' ), budget: 30 * 1024 },
	{ file: path.join( THEME, 'assets/js/main.min.js' ), budget: 6 * 1024 },
];

let errors = 0;
const rel = ( file ) => path.relative( ROOT, file ).split( path.sep ).join( '/' );
const error = ( message ) => {
	errors++;
	console.error( `ERROR  ${ message }` );
};
const warn = ( message ) => console.warn( `WARN   ${ message }` );
const kb = ( bytes ) => `${ ( bytes / 1024 ).toFixed( 1 ) } KB`;

/* ------------------------------------------------------------------ fonts */

async function copyFonts() {
	const target = path.join( THEME, 'assets/fonts' );
	await mkdir( target, { recursive: true } );
	for ( const font of FONTS ) {
		const source = path.join( MODULES, font.from );
		if ( ! existsSync( source ) ) {
			error( `font source missing: node_modules/${ font.from } (run npm install)` );
			continue;
		}
		await copyFile( source, path.join( target, font.to ) );
	}
	console.log( `fonts  ${ FONTS.length } files -> ${ rel( target ) }/` );
}

/* ---------------------------------------------------------------- bundles */

/** Expands a bundle entry. Wildcards (* and ?) are allowed in the file name only; matches are sorted. */
async function expandEntry( entry ) {
	const dir = path.posix.dirname( entry );
	const base = path.posix.basename( entry );
	if ( /[*?]/.test( dir ) ) {
		throw new Error( `wildcards are only supported in file names: ${ entry }` );
	}
	if ( ! /[*?]/.test( base ) ) {
		return [ entry ];
	}
	const pattern = new RegExp(
		'^' + base.replace( /[.+^${}()|[\]\\]/g, '\\$&' ).replace( /\*/g, '[^/]*' ).replace( /\?/g, '[^/]' ) + '$'
	);
	const names = await readdir( path.join( CSS_SRC, dir ) ).catch( () => [] );
	const matches = names.filter( ( name ) => pattern.test( name ) ).sort();
	if ( ! matches.length ) {
		throw new Error( `no partial matches ${ entry }` );
	}
	return matches.map( ( name ) => ( dir === '.' ? name : `${ dir }/${ name }` ) );
}

/** Rewrites relative url() references from the partial's directory to the output's directory. */
function rewriteUrls( css, fromDir, toDir, partial ) {
	return css.replace( /url\(\s*(['"]?)([^'")]+)\1\s*\)/g, ( match, quote, raw ) => {
		const url = raw.trim();
		if ( /^(?:data:|[a-z][a-z0-9+.-]*:|\/|#)/i.test( url ) ) {
			return match;
		}
		const [ file, suffix = '' ] = url.split( /(?=[?#])/ );
		const absolute = path.resolve( fromDir, file );
		if ( ! existsSync( absolute ) ) {
			warn( `${ partial }: url(${ url }) does not resolve to a file` );
		}
		const relative = path.relative( toDir, absolute ).split( path.sep ).join( '/' );
		return `url(${ quote }${ relative }${ suffix }${ quote })`;
	} );
}

function minifyCss( code, filename ) {
	const result = transformCss( {
		filename,
		code: Buffer.from( code ),
		minify: true,
		targets: CSS_TARGETS,
		// No light-dark() is used, so skip the polyfill variables lightningcss adds next to color-scheme.
		exclude: Features.LightDark,
		errorRecovery: false,
	} );
	for ( const warning of result.warnings ) {
		warn( `${ filename }:${ warning.loc?.line ?? '?' } ${ warning.message }` );
	}
	return result.code.toString();
}

async function buildBundles() {
	const outputs = new Set();
	const manifestFile = path.join( CSS_SRC, 'bundles.json' );
	if ( ! existsSync( manifestFile ) ) {
		error( `missing ${ rel( manifestFile ) }` );
		return outputs;
	}
	let manifest;
	try {
		manifest = JSON.parse( await readFile( manifestFile, 'utf8' ) );
	} catch ( e ) {
		error( `${ rel( manifestFile ) }: ${ e.message }` );
		return outputs;
	}

	for ( const [ output, entries ] of Object.entries( manifest ) ) {
		const outFile = path.resolve( THEME_CSS, output );
		const outDir = path.dirname( outFile );
		try {
			const parts = [];
			for ( const entry of entries ) {
				parts.push( ...( await expandEntry( entry ) ) );
			}
			// A plain comment (not /*!) so the minified sibling drops it.
			const chunks = [
				`/* Generated by tools/build-assets.mjs from wp-content/themes/hamista/assets/css/src/ (${ parts.length } partials). Do not edit; edit the partials and run "npm run build". */`,
			];
			for ( const part of parts ) {
				const partFile = path.join( CSS_SRC, part );
				const css = await readFile( partFile, 'utf8' );
				chunks.push( `/* source: ${ part } */\n${ rewriteUrls( css, path.dirname( partFile ), outDir, part ).trim() }` );
			}
			const css = chunks.join( '\n\n' ) + '\n';
			const minFile = outFile.replace( /\.css$/, '.min.css' );
			const minified = minifyCss( css, rel( outFile ) );
			await mkdir( outDir, { recursive: true } );
			await writeFile( outFile, css );
			await writeFile( minFile, minified );
			outputs.add( outFile );
			console.log( `bundle ${ rel( outFile ) } (${ parts.length } partials) + .min.css` );
		} catch ( e ) {
			error( `bundle ${ output }: ${ e.message }${ e.loc ? ` (line ${ e.loc.line })` : '' }` );
		}
	}
	return outputs;
}

/* ----------------------------------------------------------------- minify */

async function walk( dir, files = [] ) {
	const entries = await readdir( dir, { withFileTypes: true } ).catch( () => [] );
	for ( const entry of entries ) {
		const full = path.join( dir, entry.name );
		if ( entry.isDirectory() ) {
			if ( entry.name !== 'node_modules' && entry.name !== 'vendor' ) {
				await walk( full, files );
			}
		} else {
			files.push( full );
		}
	}
	return files;
}

async function packageDirs() {
	const dirs = existsSync( THEME ) ? [ THEME ] : [];
	const plugins = await readdir( PLUGINS, { withFileTypes: true } ).catch( () => [] );
	for ( const entry of plugins ) {
		if ( entry.isDirectory() && entry.name.startsWith( 'hamista-' ) ) {
			dirs.push( path.join( PLUGINS, entry.name ) );
		}
	}
	return dirs;
}

async function minifyPackages( skip ) {
	let count = 0;
	for ( const pkg of await packageDirs() ) {
		const files = await walk( path.join( pkg, 'assets' ) );
		for ( const file of files ) {
			const posix = file.split( path.sep ).join( '/' );
			const isCss = file.endsWith( '.css' ) && ! file.endsWith( '.min.css' ) && ! posix.includes( '/src/' );
			const isJs = file.endsWith( '.js' ) && ! file.endsWith( '.min.js' );
			if ( ( ! isCss && ! isJs ) || skip.has( file ) ) {
				continue;
			}
			try {
				const code = await readFile( file, 'utf8' );
				if ( isCss ) {
					await writeFile( file.replace( /\.css$/, '.min.css' ), minifyCss( code, rel( file ) ) );
				} else {
					const result = await transformJs( code, {
						loader: 'js',
						minify: true,
						target: JS_TARGET,
						sourcefile: rel( file ),
					} );
					for ( const warning of result.warnings ) {
						warn( `${ rel( file ) }: ${ warning.text }` );
					}
					await writeFile( file.replace( /\.js$/, '.min.js' ), result.code );
				}
				count++;
			} catch ( e ) {
				const detail = e.errors?.length
					? e.errors.map( ( x ) => `${ x.text } (line ${ x.location?.line ?? '?' })` ).join( '; ' )
					: `${ e.message }${ e.loc ? ` (line ${ e.loc.line })` : '' }`;
				error( `${ rel( file ) }: ${ detail }` );
			}
		}
	}
	console.log( `minify ${ count } files across the theme and hamista-* plugins` );
}

/* ----------------------------------------------------------------- report */

async function sizeReport() {
	const rows = [];
	for ( const { file, budget } of BUDGETS ) {
		if ( ! existsSync( file ) ) {
			warn( `${ rel( file ) } not found; no size report` );
			continue;
		}
		const data = await readFile( file );
		const gzip = gzipSync( data, { level: 9 } ).length;
		rows.push( [ path.basename( file ), kb( data.length ), kb( gzip ), kb( budget ), data.length > budget ? 'OVER BUDGET' : 'ok' ] );
		if ( data.length > budget ) {
			warn( `${ rel( file ) } is ${ kb( data.length ) }, over its ${ kb( budget ) } budget` );
		}
	}
	if ( ! rows.length ) {
		return;
	}
	const header = [ 'file', 'raw', 'gzip', 'budget', 'status' ];
	const widths = header.map( ( h, i ) => Math.max( h.length, ...rows.map( ( r ) => r[ i ].length ) ) + 2 );
	const line = ( cells ) => '  ' + cells.map( ( c, i ) => c.padEnd( widths[ i ] ) ).join( '' ).trimEnd();
	console.log( '\nTheme size report (raw = minified bytes, gzip level 9)' );
	console.log( line( header ) );
	rows.forEach( ( r ) => console.log( line( r ) ) );
}

/* ------------------------------------------------------------------- main */

const fontsOnly = process.argv.includes( '--fonts-only' );
await copyFonts();
if ( ! fontsOnly ) {
	const bundled = await buildBundles();
	await minifyPackages( bundled );
	await sizeReport();
}
if ( errors ) {
	console.error( `\nBuild failed with ${ errors } error(s).` );
	process.exitCode = 1;
}
