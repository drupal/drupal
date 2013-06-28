/**
 * This is a Drupal-optimized build of CKEditor.
 *
 * At the time of writing, this build is identical to the "standard" build of
 * CKEditor, includes all languages, excludes the "placeholder" and includes the
 * "sourcedialog" plugin.
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
		'image' : 1,
		'indent' : 1,
		'link' : 1,
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
		'dialog' : 1,
		'dialogui' : 1,
		'menu' : 1,
		'floatpanel' : 1,
		'panel' : 1,
		'button' : 1,
		'popup' : 1,
		'richcombo' : 1,
		'listblock' : 1,
		'fakeobjects' : 1,
		'justify' : 1,
		'showblocks' : 1,
		'showborders' : 1,
		'tableresize' : 1,
		'sharedspace' : 1,
		'sourcedialog' : 1
		// @todo D8: CKEditor Widgets is not available in 4.1 RC, and we're not yet
		// using this, so it's commented out for now. However, it will be readded in
		// the nearby future.
		// 'widget' : 1,
		// 'widgetblockquote' : 1,
		// 'widgetcaption' : 1,
		// 'widgettime' : 1,
		// 'widgetvideo' : 1
	},
	languages : {
		'af' : 1,
		'ar' : 1,
		'eu' : 1,
		'bn' : 1,
		'bs' : 1,
		'bg' : 1,
		'ca' : 1,
		'zh-cn' : 1,
		'zh' : 1,
		'hr' : 1,
		'cs' : 1,
		'da' : 1,
		'nl' : 1,
		'en' : 1,
		'en-au' : 1,
		'en-ca' : 1,
		'en-gb' : 1,
		'eo' : 1,
		'et' : 1,
		'fo' : 1,
		'fi' : 1,
		'fr' : 1,
		'fr-ca' : 1,
		'gl' : 1,
		'ka' : 1,
		'de' : 1,
		'el' : 1,
		'gu' : 1,
		'he' : 1,
		'hi' : 1,
		'hu' : 1,
		'is' : 1,
		'it' : 1,
		'ja' : 1,
		'km' : 1,
		'ko' : 1,
		'ku' : 1,
		'lv' : 1,
		'lt' : 1,
		'mk' : 1,
		'ms' : 1,
		'mn' : 1,
		'no' : 1,
		'nb' : 1,
		'fa' : 1,
		'pl' : 1,
		'pt-br' : 1,
		'pt' : 1,
		'ro' : 1,
		'ru' : 1,
		'sr' : 1,
		'sr-latn' : 1,
		'sk' : 1,
		'sl' : 1,
		'es' : 1,
		'sv' : 1,
		'th' : 1,
		'tr' : 1,
		'ug' : 1,
		'uk' : 1,
		'vi' : 1,
		'cy' : 1
	}
};
