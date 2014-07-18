<?php

/**
 * @file
 * Contains \Drupal\Core\Template\TwigExtension.
 *
 * This provides a Twig extension that registers various Drupal specific
 * extensions to Twig.
 *
 * @see \Drupal\Core\CoreServiceProvider
 */

namespace Drupal\Core\Template;

/**
 * A class providing Drupal Twig extensions.
 *
 * Specifically Twig functions, filter and node visitors.
 *
 * @see \Drupal\Core\CoreServiceProvider
 */
class TwigExtension extends \Twig_Extension {

  /**
   * {@inheritdoc}
   */
  public function getFunctions() {
    return array(
      new \Twig_SimpleFunction('url', 'url'),
      // This function will receive a renderable array, if an array is detected.
      new \Twig_SimpleFunction('render_var', 'twig_render_var'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFilters() {
    return array(
      // Translation filters.
      new \Twig_SimpleFilter('t', 't', array('is_safe' => array('html'))),
      new \Twig_SimpleFilter('trans', 't', array('is_safe' => array('html'))),
      // The "raw" filter is not detectable when parsing "trans" tags. To detect
      // which prefix must be used for translation (@, !, %), we must clone the
      // "raw" filter and give it identifiable names. These filters should only
      // be used in "trans" tags.
      // @see TwigNodeTrans::compileString()
      new \Twig_SimpleFilter('passthrough', 'twig_raw_filter', array('is_safe' => array('html'))),
      new \Twig_SimpleFilter('placeholder', 'twig_raw_filter', array('is_safe' => array('html'))),

      // Replace twig's escape filter with our own.
      new \Twig_SimpleFilter('drupal_escape', 'twig_drupal_escape_filter', array('needs_environment' => true, 'is_safe_callback' => 'twig_escape_filter_is_safe')),

      // Implements safe joining.
      // @todo Make that the default for |join? Upstream issue:
      //   https://github.com/fabpot/Twig/issues/1420
      new \Twig_SimpleFilter('safe_join', 'twig_drupal_join_filter', array('is_safe' => array('html'))),

      // Array filters.
      new \Twig_SimpleFilter('without', 'twig_without'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getNodeVisitors() {
    // The node visitor is needed to wrap all variables with
    // render_var -> twig_render_var() function.
    return array(
      new TwigNodeVisitor(),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getTokenParsers() {
    return array(
      new TwigTransTokenParser(),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getName() {
    return 'drupal_core';
  }

}
