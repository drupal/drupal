Defining Assets "On The Fly"
----------------------------

The second approach to using Assetic involves defining your application's
assets "on the fly" in your templates, instead of in an isolated PHP file.
Using this approach, your PHP template would look something like this:

    <script src="<?php echo assetic_javascripts('js/*', 'yui_js') ?>"></script>

This call to `assetic_javascripts()` serves a dual purpose. It will be read by
the Assetic "formula loader" which will extract an asset "formula" that can be
used to build, dump and output the asset. It will also be executed when the
template is rendered, at which time the path to the output asset is output.

Assetic includes the following templating helper functions:

 * `assetic_image()`
 * `assetic_javascripts()`
 * `assetic_stylesheets()`

Defining assets on the fly is a much more sophisticated technique and
therefore relies on services to do the heavy lifting. The main one being the
asset factory.

### Asset Factory

The asset factory knows how to create asset objects using only arrays and
scalar values as input. This is the same string syntax used by the `assetic_*`
template helper functions.

    use Assetic\Factory\AssetFactory;

    $factory = new AssetFactory('/path/to/web');
    $js = $factory->createAsset(array(
        'js/jquery.js',
        'js/jquery.plugin.js',
        'js/application.js',
    ));

### Filter Manager

You can also apply filters to asset created by the factory. To do this you
must setup a `FilterManager`, which organizes filters by a name.

    use Assetic\FilterManager;
    use Assetic\Filter\GoogleClosure\ApiFilter as ClosureFilter;

    $fm = new FilterManager();
    $fm->set('closure', new ClosureFilter());
    $factory->setFilterManager($fm);

    $js = $factory->createAsset('js/*', 'closure');

This code creates an instance of the Google Closure Compiler filter and
assigns it the name `closure` using a filter manager. This filter manager is
then injected into the asset factory, making the filter available as `closure`
when creating assets.

### Debug Mode

The asset factory also introduces the concept of a debug mode. This mode
allows you to omit certain filters from assets the factory creates depending
on whether it is enabled or not.

For example, the YUI Compressor is awesome, but it is only appropriate in a
production environment as it is very difficult to debug minified Javascript.

    use Asset\Factory\AssetFactory;

    $factory = new AssetFactory('/path/to/web', true); // debug mode is on
    $factory->setFilterManager($fm);
    $js = $factory->createAsset('js/*', '?closure');

By prefixing the `closure` filter's name with a question mark, we are telling
the factory this filter is optional and should only be applied with debug mode
is off.

### Asset Manager and Asset References

The asset factory provides another special string syntax that allows you to
reference assets you defined elsewhere. These are called "asset references"
and involve an asset manager which, similar to the filter manager, organizes
assets by name.

    use Assetic\AssetManager;
    use Assetic\Asset\FileAsset;
    use Assetic\Factory\AssetFactory;

    $am = new AssetManager();
    $am->set('jquery', new FileAsset('/path/to/jquery.js'));

    $factory = new AssetFactory('/path/to/web');
    $factory->setAssetManager($am);

    $js = $factory->createAsset(array(
        '@jquery',
        'js/application.js',
    ));

### Extracting Assets from Templates

Once you've defined a set of assets in your templates you must use the
"formula loader" service to extract these asset definitions.

    use Assetic\Factory\Loader\FunctionCallsFormulaLoader;
    use Assetic\Factory\Resource\FileResource;

    $loader = new FunctionCallsFormulaLoader($factory);
    $formulae = $loader->load(new FileResource('/path/to/template.php'));

These asset formulae aren't much use by themselves. They each include just
enough information for the asset factory to create the intended asset object.
In order for these to be useful they must be wrapped in the special
`LazyAssetManager`.

### The Lazy Asset Manager

This service is a composition of the asset factory and one or more formula
loaders. It acts as the glue between these services behind the scenes, but can
be used just like a normal asset manager on the surface.

    use Assetic\Asset\FileAsset;
    use Assetic\Factory\LazyAssetManager;
    use Assetic\Factory\Loader\FunctionCallsFormulaLoader;
    use Assetic\Factory\Resource\DirectoryResource;

    $am = new LazyAssetManager($factory);
    $am->set('jquery', new FileAsset('/path/to/jquery.js'));
    $am->setLoader('php', new FunctionCallsFormulaLoader($factory));
    $am->addResource(new DirectoryResource('/path/to/templates', '/\.php$/'), 'php');

### Asset Writer

Finally, once you've create an asset manager that knows about every asset
you've defined in your templates, you must use an asset writer to actually
create the files your templates are going to be referencing.

    use Assetic\AssetWriter;

    $writer = new AssetWriter('/path/to/web');
    $writer->writeManagerAssets($am);

After running this script, all of the assets in your asset manager will be
loaded into memory, filtered with their configured filters and dumped to your
web directory as static files, ready to be served.
