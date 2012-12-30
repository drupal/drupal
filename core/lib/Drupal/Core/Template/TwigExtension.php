<?php

/**
 * @file
 * Definition of Drupal\Core\Template\TwigExtension.
 *
 * This provides a Twig extension that registers various Drupal specific extensions to Twig.
 *
 * @see \Drupal\Core\CoreBundle
 */

namespace Drupal\Core\Template;

/**
 * A class for providing Twig extensions (specific Twig_NodeVisitors, filters and functions).
 *
 * @see \Drupal\Core\CoreBundle
 */
class TwigExtension extends \Twig_Extension {
  public function getFunctions() {
    // @todo re-add unset => twig_unset if this is really needed
    return array(
      // @todo Remove URL function once http://drupal.org/node/1778610 is resolved.
      'url' => new \Twig_Function_Function('url'),
      // These functions will receive a TwigReference object, if a render array is detected
      'hide' => new TwigReferenceFunction('twig_hide'),
      'render_var' => new TwigReferenceFunction('twig_render_var'),
      'show' => new TwigReferenceFunction('twig_show'),
    );
  }

  public function getFilters() {
    return array(
      't' => new \Twig_Filter_Function('t'),
    );
  }

  public function getNodeVisitors() {
    // The node visitor is needed to wrap all variables with
    // render_var -> twig_render_var() function.
    return array(
      new TwigNodeVisitor(),
    );
  }

  public function getTokenParsers() {
    return array(
      new TwigFunctionTokenParser('hide'),
      new TwigFunctionTokenParser('show'),
    );
  }

  public function getName()
  {
    return 'drupal_core';
  }
}

