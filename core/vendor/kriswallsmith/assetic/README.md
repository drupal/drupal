# Assetic [![Build Status](https://travis-ci.org/kriswallsmith/assetic.png?branch=master)](https://travis-ci.org/kriswallsmith/assetic) ![project status](http://stillmaintained.com/kriswallsmith/assetic.png) #

Assetic is an asset management framework for PHP.

``` php
<?php

use Assetic\Asset\AssetCollection;
use Assetic\Asset\FileAsset;
use Assetic\Asset\GlobAsset;

$js = new AssetCollection(array(
    new GlobAsset('/path/to/js/*'),
    new FileAsset('/path/to/another.js'),
));

// the code is merged when the asset is dumped
echo $js->dump();
```

Assets
------

An Assetic asset is something with filterable content that can be loaded and
dumped. An asset also includes metadata, some of which can be manipulated and
some of which is immutable.

| **Property** | **Accessor**    | **Mutator**   |
|--------------|-----------------|---------------|
| content      | getContent      | setContent    |
| mtime        | getLastModified | n/a           |
| source root  | getSourceRoot   | n/a           |
| source path  | getSourcePath   | n/a           |
| target path  | getTargetPath   | setTargetPath |

Filters
-------

Filters can be applied to manipulate assets.

``` php
<?php

use Assetic\Asset\AssetCollection;
use Assetic\Asset\FileAsset;
use Assetic\Asset\GlobAsset;
use Assetic\Filter\LessFilter;
use Assetic\Filter\Yui;

$css = new AssetCollection(array(
    new FileAsset('/path/to/src/styles.less', array(new LessFilter())),
    new GlobAsset('/path/to/css/*'),
), array(
    new Yui\CssCompressorFilter('/path/to/yuicompressor.jar'),
));

// this will echo CSS compiled by LESS and compressed by YUI
echo $css->dump();
```

The filters applied to the collection will cascade to each asset leaf if you
iterate over it.

``` php
<?php

foreach ($css as $leaf) {
    // each leaf is compressed by YUI
    echo $leaf->dump();
}
```

The core provides the following filters in the `Assetic\Filter` namespace:

 * `CoffeeScriptFilter`: compiles CoffeeScript into Javascript
 * `CompassFilter`: Compass CSS authoring framework
 * `CssEmbedFilter`: embeds image data in your stylesheets
 * `CssImportFilter`: inlines imported stylesheets
 * `CssMinFilter`: minifies CSS
 * `CssRewriteFilter`: fixes relative URLs in CSS assets when moving to a new URL
 * `DartFilter`: compiles Javascript using dart2js
 * `GoogleClosure\CompilerApiFilter`: compiles Javascript using the Google Closure Compiler API
 * `GoogleClosure\CompilerJarFilter`: compiles Javascript using the Google Closure Compiler JAR
 * `GssFilter`: compliles CSS using the Google Closure Stylesheets Compiler
 * `HandlebarsFilter`: compiles Handlebars templates into Javascript
 * `JpegoptimFilter`: optimize your JPEGs
 * `JpegtranFilter`: optimize your JPEGs
 * `JSMinFilter`: minifies Javascript
 * `JSMinPlusFilter`: minifies Javascript
 * `LessFilter`: parses LESS into CSS (using less.js with node.js)
 * `LessphpFilter`: parses LESS into CSS (using lessphp)
 * `OptiPngFilter`: optimize your PNGs
 * `PackagerFilter`: parses Javascript for packager tags
 * `PackerFilter`: compresses Javascript using Dean Edwards's Packer
 * `PhpCssEmbedFilter`: embeds image data in your stylesheet
 * `PngoutFilter`: optimize your PNGs
 * `Sass\SassFilter`: parses SASS into CSS
 * `Sass\ScssFilter`: parses SCSS into CSS
 * `ScssphpFilter`: parses SCSS using scssphp
 * `SprocketsFilter`: Sprockets Javascript dependency management
 * `StylusFilter`: parses STYL into CSS
 * `TypeScriptFilter`: parses TypeScript into Javascript
 * `UglifyCssFilter`: minifies CSS
 * `UglifyJs2Filter`: minifies Javascript
 * `UglifyJsFilter`: minifies Javascript
 * `Yui\CssCompressorFilter`: compresses CSS using the YUI compressor
 * `Yui\JsCompressorFilter`: compresses Javascript using the YUI compressor

Asset Manager
-------------

An asset manager is provided for organizing assets.

``` php
<?php

use Assetic\AssetManager;
use Assetic\Asset\FileAsset;
use Assetic\Asset\GlobAsset;

$am = new AssetManager();
$am->set('jquery', new FileAsset('/path/to/jquery.js'));
$am->set('base_css', new GlobAsset('/path/to/css/*'));
```

The asset manager can also be used to reference assets to avoid duplication.

``` php
<?php

use Assetic\Asset\AssetCollection;
use Assetic\Asset\AssetReference;
use Assetic\Asset\FileAsset;

$am->set('my_plugin', new AssetCollection(array(
    new AssetReference($am, 'jquery'),
    new FileAsset('/path/to/jquery.plugin.js'),
)));
```

Filter Manager
--------------

A filter manager is also provided for organizing filters.

``` php
<?php

use Assetic\FilterManager;
use Assetic\Filter\Sass\SassFilter;
use Assetic\Filter\Yui;

$fm = new FilterManager();
$fm->set('sass', new SassFilter('/path/to/parser/sass'));
$fm->set('yui_css', new Yui\CssCompressorFilter('/path/to/yuicompressor.jar'));
```

Asset Factory
-------------

If you'd rather not create all these objects by hand, you can use the asset
factory, which will do most of the work for you.

``` php
<?php

use Assetic\Factory\AssetFactory;

$factory = new AssetFactory('/path/to/asset/directory/');
$factory->setAssetManager($am);
$factory->setFilterManager($fm);
$factory->setDebug(true);

$css = $factory->createAsset(array(
    '@reset',         // load the asset manager's "reset" asset
    'css/src/*.scss', // load every scss files from "/path/to/asset/directory/css/src/"
), array(
    'scss',           // filter through the filter manager's "scss" filter
    '?yui_css',       // don't use this filter in debug mode
));

echo $css->dump();
```

Prefixing a filter name with a question mark, as `yui_css` is here, will cause
that filter to be omitted when the factory is in debug mode.

Caching
-------

A simple caching mechanism is provided to avoid unnecessary work.

``` php
<?php

use Assetic\Asset\AssetCache;
use Assetic\Asset\FileAsset;
use Assetic\Cache\FilesystemCache;
use Assetic\Filter\Yui;

$yui = new Yui\JsCompressorFilter('/path/to/yuicompressor.jar');
$js = new AssetCache(
    new FileAsset('/path/to/some.js', array($yui)),
    new FilesystemCache('/path/to/cache')
);

// the YUI compressor will only run on the first call
$js->dump();
$js->dump();
$js->dump();
```

Cache Busting
-------------

You can use the CacheBustingWorker to provide unique names.

Two strategies are provided: CacheBustingWorker::STRATEGY_CONTENT (content based), CacheBustingWorker::STRATEGY_MODIFICATION (modification time based)

``` php
<?php

use Assetic\Factory\AssetFactory;
use Assetic\Factory\Worker\CacheBustingWorker;

$factory = new AssetFactory('/path/to/asset/directory/');
$factory->setAssetManager($am);
$factory->setFilterManager($fm);
$factory->setDebug(true);
$factory->addWorker(new CacheBustingWorker(CacheBustingWorker::STRATEGY_CONTENT));

$css = $factory->createAsset(array(
    '@reset',         // load the asset manager's "reset" asset
    'css/src/*.scss', // load every scss files from "/path/to/asset/directory/css/src/"
), array(
    'scss',           // filter through the filter manager's "scss" filter
    '?yui_css',       // don't use this filter in debug mode
));

echo $css->dump();
```

Static Assets
-------------

Alternatively you can just write filtered assets to your web directory and be
done with it.

``` php
<?php

use Assetic\AssetWriter;

$writer = new AssetWriter('/path/to/web');
$writer->writeManagerAssets($am);
```

Twig
----

To use the Assetic [Twig][3] extension you must register it to your Twig
environment:

``` php
<?php

$twig->addExtension(new AsseticExtension($factory, $debug));
```

Once in place, the extension exposes a stylesheets and a javascripts tag with a syntax similar
to what the asset factory uses:

``` html+jinja
{% stylesheets '/path/to/sass/main.sass' filter='sass,?yui_css' output='css/all.css' %}
    <link href="{{ asset_url }}" type="text/css" rel="stylesheet" />
{% endstylesheets %}
```

This example will render one `link` element on the page that includes a URL
where the filtered asset can be found.

When the extension is in debug mode, this same tag will render multiple `link`
elements, one for each asset referenced by the `css/src/*.sass` glob. The
specified filters will still be applied, unless they are marked as optional
using the `?` prefix.

This behavior can also be triggered by setting a `debug` attribute on the tag:

``` html+jinja
{% stylesheets 'css/*' debug=true %} ... {% stylesheets %}
```

These assets need to be written to the web directory so these URLs don't
return 404 errors.

``` php
<?php

use Assetic\AssetWriter;
use Assetic\Extension\Twig\TwigFormulaLoader;
use Assetic\Extension\Twig\TwigResource;
use Assetic\Factory\LazyAssetManager;

$am = new LazyAssetManager($factory);

// enable loading assets from twig templates
$am->setLoader('twig', new TwigFormulaLoader($twig));

// loop through all your templates
foreach ($templates as $template) {
    $resource = new TwigResource($twigLoader, $template);
    $am->addResource($resource, 'twig');
}

$writer = new AssetWriter('/path/to/web');
$writer->writeManagerAssets($am);
```

---

Assetic is based on the Python [webassets][1] library (available on
[GitHub][2]).

[1]: http://elsdoerfer.name/docs/webassets
[2]: https://github.com/miracle2k/webassets
[3]: http://twig.sensiolabs.org
