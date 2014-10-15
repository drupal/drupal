1.1.2 (July 18, 2013)
-------------------

 * Fixed deep mtime on asset collections
 * `CallablesFilter` now implements `DependencyExtractorInterface`
 * Fixed detection of "partial" children in subfolders in `SassFilter`
 * Restored `PathUtils` for BC

1.1.1 (June 1, 2013)
--------------------

 * Fixed cloning of asset collections
 * Fixed environment var inheritance
 * Replaced `AssetWriter::getCombinations()` for BC, even though we don't use it
 * Added support for `@import-once` to Less filters

1.1.0 (May 15, 2013)
--------------------

 * Added LazyAssetManager::getLastModified() for determining "deep" mtime
 * Added DartFilter
 * Added EmberPrecompile
 * Added GssFilter
 * Added PhpCssEmbedFilter
 * Added RooleFilter
 * Added TypeScriptFilter
 * Added the possibility to configure additional load paths for less and lessphp
 * Added the UglifyCssFilter
 * Fixed the handling of directories in the GlobAsset. #256
 * Added Handlebars support
 * Added Scssphp-compass support
 * Added the CacheBustingWorker
 * Added the UglifyJs2Filter

1.1.0-alpha1 (August 28, 2012)
------------------------------

 * Added pure php css embed filter
 * Added Scssphp support
 * Added support for Google Closure language option
 * Added a way to set a specific ruby path for CompassFilter and SassFilter
 * Ensure uniqueness of temporary files created by the compressor filter. Fixed #61
 * Added Compass option for generated_images_path (for generated Images/Sprites)
 * Added PackerFilter
 * Add the way to contact closure compiler API using curl, if available and allow_url_fopen is off
 * Added filters for JSMin and JSMinPlus
 * Added the UglifyJsFilter
 * Improved the error message in getModifiedTime when a file asset uses an invalid file
 * added support for asset variables:

       Asset variables allow you to pre-compile your assets for a finite set of known
       variable values, and then to simply deliver the correct asset version at runtime.
       For example, this is helpful for assets with language, or browser-specific code.
 * Removed the copy-paste of the Symfony2 Process component and use the original one
 * Added ability to pass variables into lessphp filter
 * Added google closure stylesheets jar filter
 * Added the support of `--bare` for the CoffeeScriptFilter
