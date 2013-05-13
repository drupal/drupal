1.1.0
-----

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
