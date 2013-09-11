/**
 * This is a Drupal-optimized build of CKEditor.
 *
 * You may re-use it at any time at http://ckeditor.com/builder to build
 * CKEditor again. Alternatively, use the "build.sh" script to build it locally.
 * If you do so, be sure to pass it the "-s" flag. So: "sh build.sh -s".
 *
 * NOTE:
 *    This file is not used by CKEditor, you may remove it.
 *    Changing this file will not change your CKEditor configuration.
 */

var CKBUILDER_CONFIG = {
	skin: 'moono',
	ignore: [
		// CKEditor repository structure: unrelated to the usage of CKEditor itself.
		'dev',
		'.mailmap',
		'.gitignore',
		'.gitattributes',
		// Parts of CKEditor that we consciously don't ship with Drupal.
		'adapters',
		'README.md',
		'config.js',
		'contents.css',
		'styles.js',
		'samples',
		'skins/moono/readme.md'
	],
	plugins : {
		'about' : 1,
		'a11yhelp' : 1,
		'basicstyles' : 1,
		'blockquote' : 1,
		'clipboard' : 1,
		'contextmenu' : 1,
		'resize' : 1,
		'toolbar' : 1,
		'elementspath' : 1,
		'enterkey' : 1,
		'entities' : 1,
		'filebrowser' : 1,
		'floatingspace' : 1,
		'format' : 1,
		'htmlwriter' : 1,
		'horizontalrule' : 1,
		'wysiwygarea' : 1,
		'indent' : 1,
		'indentlist' : 1,
		'list' : 1,
		'magicline' : 1,
		'maximize' : 1,
		'pastetext' : 1,
		'pastefromword' : 1,
		'removeformat' : 1,
		'sourcearea' : 1,
		'specialchar' : 1,
		'stylescombo' : 1,
		'tab' : 1,
		'table' : 1,
		'tabletools' : 1,
		'undo' : 1,
		'justify' : 1,
		'showblocks' : 1,
		'showborders' : 1,
		'tableresize' : 1,
		'sharedspace' : 1,
		'sourcedialog' : 1,
		'widget' : 1
	}
};
