/**
 * This is a Drupal-optimized build of CKEditor.
 *
 * You may re-use it at any time at http://ckeditor.com/builder to build
 * CKEditor again. Alternatively, use the "build.sh" script to build it locally.
 * If you do so, be sure to pass it the "-s" flag. So: "sh build.sh -s".
 *
 * If you are developing or debugging CKEditor plugins, you may want to work
 * against an unoptimized (unminified) CKEditor build. To do so, you have two
 * options:
 * 1. Upload build-config.js to http://ckeditor.com/builder and choose the
 *    "Source (Big N'Slow)" option when downloading.
 * 2. Use the "build.sh" script to build it locally, with one additional flag:
 *    "sh build.sh -s --leave-js-unminified".
 * Then, replace this directory (core/assets/vendor/ckeditor) with your build.
 *
 * NOTE:
 *    This file is not used by CKEditor, you may remove it.
 *    Changing this file will not change your CKEditor configuration.
 */

/* exported CKBUILDER_CONFIG */

var CKBUILDER_CONFIG = {
	skin: 'moono-lisa',
	ignore: [
		// CKEditor repository structure: unrelated to the usage of CKEditor itself.
		'bender.js',
		'.bender',
		'bender-err.log',
		'bender-out.log',
		'dev',
		'docs',
		'.DS_Store',
		'.editorconfig',
		'.gitignore',
		'.gitattributes',
		'gruntfile.js',
		'.idea',
		'.jscsrc',
		'.jshintignore',
		'.jshintrc',
		'less',
		'.mailmap',
		'node_modules',
		'package.json',
		'README.md',
		'tests',
		// Parts of CKEditor that we consciously don't ship with Drupal.
		'adapters',
		'config.js',
		'contents.css',
		'styles.js',
		'samples',
		'skins/moono-lisa/readme.md'
	],
	plugins: {
		a11yhelp: 1,
		about: 1,
		autogrow: 1,
		basicstyles: 1,
		blockquote: 1,
		clipboard: 1,
		contextmenu: 1,
		elementspath: 1,
		enterkey: 1,
		entities: 1,
		filebrowser: 1,
		floatingspace: 1,
		format: 1,
		horizontalrule: 1,
		htmlwriter: 1,
		image2: 1,
		indent: 1,
		indentlist: 1,
		justify: 1,
		language: 1,
		list: 1,
		magicline: 1,
		maximize: 1,
		pastefromword: 1,
		pastetext: 1,
		removeformat: 1,
		sharedspace: 1,
		showblocks: 1,
		showborders: 1,
		sourcearea: 1,
		sourcedialog: 1,
		specialchar: 1,
		stylescombo: 1,
		tab: 1,
		table: 1,
		tableresize: 1,
		tabletools: 1,
		toolbar: 1,
		undo: 1,
		widget: 1,
		wysiwygarea: 1
	}
};
