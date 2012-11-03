<?php

/**
 * @file
 * Definition of Drupal\Core\Template\TwigFactory.
 *
 * This provides a factory class to construct Twig_Environment objects and use
 * them in combination with the Drupal Injection Container.
 *
 * @see \Drupal\Core\CoreBundle
 */

namespace Drupal\Core\Template;

/**
 * A class for constructing Twig_Environment objects.
 *
 * This is used for constructing and configuring a system wide Twig_Environment
 * object that is integrated with the Drupal Injection Container.
 *
 * @see \Drupal\Core\CoreBundle
 */
class TwigFactory {
  /**
  * Returns a fully initialized Twig_Environment object.
  *
  * This constructs and configures a Twig_Environment. It also adds Drupal
  * specific Twig_NodeVisitors, filters and functions.
  *
  * To retrieve the system wide Twig_Environment object you should use:
  * @code
  *   $twig = drupal_container()->get('twig');
  * @endcode
  * This will retrieve the Twig_Environment object from the DIC.
  *
  * @return Twig_Environment
  *   The fully initialized Twig_Environment object.
  *
  * @see twig_render
  * @see TwigNodeVisitor
  * @see TwigReference
  * @see TwigReferenceFunction
  */
  public static function get() {
    // @todo Maybe we will have our own loader later.
    $loader = new \Twig_Loader_Filesystem(DRUPAL_ROOT);
    $twig = new TwigEnvironment($loader, array(
        // This is saved / loaded via drupal_php_storage().
        // All files can be refreshed by clearing caches.
        // @todo ensure garbage collection of expired files.
        'cache' => TRUE,
        'base_template_class' => 'Drupal\Core\Template\TwigTemplate',
        // @todo Remove in followup issue
        // @see http://drupal.org/node/1712444.
        'autoescape' => FALSE,
        // @todo Remove in followup issue
        // @see http://drupal.org/node/1806538.
        'strict_variables' => FALSE,
        // @todo Maybe make debug mode dependent on "production mode" setting.
        'debug' => TRUE,
        // @todo Make auto reload mode dependent on "production mode" setting.
        'auto_reload' => FALSE,
    ));

    // The node visitor is needed to wrap all variables with
    // render -> twig_render() function.
    $twig->addNodeVisitor(new TwigNodeVisitor());
    $twig->addTokenParser(new TwigFunctionTokenParser('hide'));
    $twig->addTokenParser(new TwigFunctionTokenParser('show'));

    // @todo Figure out what to do about debugging functions.
    // @see http://drupal.org/node/1804998
    $twig->addExtension(new \Twig_Extension_Debug());

    $reference_functions = array(
      'hide' => 'twig_hide',
      'render' => 'twig_render',
      'show' => 'twig_show',
      // @todo re-add unset => twig_unset if this is really needed
    );
    $filters = array(
      't' => 't'
    );

    // These functions will receive a TwigReference object, if a render array is detected
    foreach ($reference_functions as $function => $php_function) {
      $twig->addFunction($function, new TwigReferenceFunction($php_function));
    }

    foreach ($filters as $filter => $php_function) {
      $twig->addFilter($filter, new \Twig_Filter_Function($php_function));
    }

    // @todo Remove URL function once http://drupal.org/node/1778610 is resolved.
    $twig->addFunction('url', new \Twig_Function_Function('url'));
    return $twig;
  }
}
