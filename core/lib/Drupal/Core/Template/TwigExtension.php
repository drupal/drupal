<?php

/**
 * @file
 * Definition of Drupal\Core\Template\TwigExtension.
 *
 * This provides a Twig extension that registers various Drupal specific extensions to Twig.
 *
 * @see \Drupal\Core\CoreServiceProvider
 */

namespace Drupal\Core\Template;

/**
 * A class for providing Twig extensions (specific Twig_NodeVisitors, filters and functions).
 *
 * @see \Drupal\Core\CoreServiceProvider
 */
class TwigExtension extends \Twig_Extension {
  public function getFunctions() {
    // @todo re-add unset => twig_unset if this is really needed
    return array(
      // @todo Remove URL function once http://drupal.org/node/1778610 is resolved.
      new \Twig_SimpleFunction('url', 'url'),
      // This function will receive a renderable array, if an array is detected.
      new \Twig_SimpleFunction('render_var', 'twig_render_var'),
    );
  }

  public function getFilters() {
    return array(
      new \Twig_SimpleFilter('t', 't'),
      new \Twig_SimpleFilter('trans', 't'),
      // The "raw" filter is not detectable when parsing "trans" tags. To detect
      // which prefix must be used for translation (@, !, %), we must clone the
      // "raw" filter and give it identifiable names. These filters should only
      // be used in "trans" tags.
      // @see TwigNodeTrans::compileString()
      new \Twig_SimpleFilter('passthrough', 'twig_raw_filter'),
      new \Twig_SimpleFilter('placeholder', 'twig_raw_filter'),
      new \Twig_SimpleFilter('without', 'twig_without'),
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
      new TwigTransTokenParser(),
    );
  }

  public function getName()
  {
    return 'drupal_core';
  }
}

