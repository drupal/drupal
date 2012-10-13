In order to use the Assetic OOP API you must first understand the two central
concepts of Assetic: assets and filters.

### What is an Asset?

As asset is an object that has content and metadata which can be loaded and
dumped. Your assets will probably fall into three categories: Javascripts,
stylesheets and images. Most assets will be loaded from files in your
filesystem, but they can also be loaded via HTTP, a database, from a string,
or virtually anything else. All that an asset has to do is fulfill Assetic's
basic asset interface.

### What is a Filter?

A filter is an object that acts upon an asset's content when that asset is
loaded and/or dumped. Similar to assets, a filter can do virtually anything,
as long as it implements Assetic's filter interface. 

Here is a list of some of the tools that can be applied to assets using a
filter:

 * CoffeeScript
 * CssEmbed
 * CssMin
 * Google Closure Compiler
 * jpegoptim
 * jpegtran
 * Less
 * LessPHP
 * optipng
 * Packager
 * pngout
 * SASS
 * Sprockets (version 1)
 * Stylus
 * YUI Compressor

### Using Assets and Filters

You need to start by creating an asset object. This will probably mean
instantiating a `FileAsset` instance, which takes a filesystem path as its
first argument:

    $asset = new Assetic\Asset\FileAsset('/path/to/main.css');

Once you have an asset you can begin adding filters to it by calling
`ensureFilter()`. For example, you can add a filter that applies the YUI
Compressor to the contents of the asset:

    $yui = new Assetic\Filter\Yui\CssCompressorFilter('/path/to/yui.jar');
    $asset->ensureFilter($yui);

Once you've added as many filters as you'd like you can output the finished
asset to the browser:

    header('Content-Type: text/css');
    echo $asset->dump();

### Asset Collections

It is a good idea to combine assets of the same type into a single file to
avoid unnecessary HTTP requests. You can do this in Assetic using the
`AssetCollection` class. This class is just like any other asset in Assetic's
eyes as it implements the asset interface, but under the hood it allows you to
combine multiple assets into one.

    use Assetic\Asset\AssetCollection;

    $asset = new AssetCollection(array(
        new FileAsset('/path/to/js/jquery.js'),
        new FileAsset('/path/to/js/jquery.plugin.js'),
        new FileAsset('/path/to/js/application.js'),
    ));

### Nested Asset Collections

The collection class implements the asset interface and all assets passed into
a collection must implement the same interface, which means you can easily
nest collections within one another:

    use Assetic\Asset\AssetCollection;
    use Assetic\Asset\GlobAsset;
    use Assetic\Asset\HttpAsset;

    $asset = new AssetCollection(array(
        new HttpAsset('http://example.com/jquery.min.js'),
        new GlobAsset('/path/to/js/*'),
    ));

The `HttpAsset` class is a special asset class that loads a file over HTTP;
`GlobAsset` is a special asset collection class that loads files based on a
filesystem glob -- both implement the asset interface.

This concept of nesting asset collection become even more powerful when you
start applying different sets of filters to each collection. Imagine some of
your application's stylesheets are written in SASS, while some are written in
vanilla CSS. You can combine all of these into one seamless CSS asset:

    use Assetic\Asset\AssetCollection;
    use Assetic\Asset\GlobAsset;
    use Assetic\Filter\SassFilter;
    use Assetic\Filter\Yui\CssCompressorFilter;

    $css = new AssetCollection(array(
        new GlobAsset('/path/to/sass/*.sass', array(new SassFilter())),
        new GlobAsset('/path/to/css/*.css'),
    ), array(
        new YuiCompressorFilter('/path/to/yuicompressor.jar'),
    ));

You'll notice I've also applied the YUI compressor filter to the combined
asset so all CSS will be minified.

### Iterating over an Asset Collection

Once you have an asset collection you can iterate over it like you would a
plain old PHP array:

    echo "Source paths:\n";
    foreach ($collection as $asset) {
        echo ' - '.$asset->getSourcePath()."\n";
    }

The asset collection iterates recursively, which means you will only see the
"leaf" assets during iteration. Iteration also includes a smart filter which
ensures you only see each asset once, even if the same asset has been included
multiple times.

Next: [Defining Assets "On The Fly"](define.md)
