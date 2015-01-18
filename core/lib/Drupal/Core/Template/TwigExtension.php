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

use Drupal\Core\Routing\UrlGeneratorInterface;
use Drupal\Core\Url;
use Drupal\Core\Utility\LinkGeneratorInterface;

/**
 * A class providing Drupal Twig extensions.
 *
 * Specifically Twig functions, filter and node visitors.
 *
 * @see \Drupal\Core\CoreServiceProvider
 */
class TwigExtension extends \Twig_Extension {

  /**
   * The URL generator.
   *
   * @var \Drupal\Core\Routing\UrlGeneratorInterface
   */
  protected $urlGenerator;

  /**
   * The link generator.
   *
   * @var \Drupal\Core\Utility\LinkGeneratorInterface
   */
  protected $linkGenerator;

  /**
   * Constructs \Drupal\Core\Template\TwigExtension.
   *
   * @param \Drupal\Core\Routing\UrlGeneratorInterface $url_generator
   *   The URL generator.
   */
  public function setGenerators(UrlGeneratorInterface $url_generator) {
    $this->urlGenerator = $url_generator;
    return $this;
  }

  /**
   * Sets the link generator.
   *
   * @param \Drupal\Core\Utility\LinkGeneratorInterface $link_generator
   *   The link generator.
   *
   * @return $this
   */
  public function setLinkGenerator(LinkGeneratorInterface $link_generator) {
    $this->linkGenerator = $link_generator;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getFunctions() {
    return array(
      // This function will receive a renderable array, if an array is detected.
      new \Twig_SimpleFunction('render_var', 'twig_render_var'),
      // The url and path function are defined in close parallel to those found
      // in \Symfony\Bridge\Twig\Extension\RoutingExtension
      new \Twig_SimpleFunction('url', array($this, 'getUrl'), array('is_safe_callback' => array($this, 'isUrlGenerationSafe'))),
      new \Twig_SimpleFunction('path', array($this, 'getPath'), array('is_safe_callback' => array($this, 'isUrlGenerationSafe'))),
      new \Twig_SimpleFunction('url_from_path', array($this, 'getUrlFromPath'), array('is_safe_callback' => array($this, 'isUrlGenerationSafe'))),
      new \Twig_SimpleFunction('link', array($this, 'getLink')),
      new \Twig_SimpleFunction('file_url', 'file_create_url'),
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

      // CSS class and ID filters.
      new \Twig_SimpleFilter('clean_class', '\Drupal\Component\Utility\Html::getClass'),
      new \Twig_SimpleFilter('clean_id', '\Drupal\Component\Utility\Html::getId'),
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

  /**
   * Generates a URL path given a route name and parameters.
   *
   * @param $name
   *   The name of the route.
   * @param array $parameters
   *   An associative array of route parameters names and values.
   * @param array $options
   *   (optional) An associative array of additional options. The 'absolute'
   *   option is forced to be FALSE.
   *   @see \Drupal\Core\Routing\UrlGeneratorInterface::generateFromRoute().
   *
   * @return string
   *   The generated URL path (relative URL) for the given route.
   */
  public function getPath($name, $parameters = array(), $options = array()) {
    $options['absolute'] = FALSE;
    return $this->urlGenerator->generateFromRoute($name, $parameters, $options);
  }

  /**
   * Generates an absolute URL given a route name and parameters.
   *
   * @param $name
   *   The name of the route.
   * @param array $parameters
   *   An associative array of route parameter names and values.
   * @param array $options
   *   (optional) An associative array of additional options. The 'absolute'
   *   option is forced to be TRUE.
   *
   * @return string
   *   The generated absolute URL for the given route.
   *
   * @todo Add an option for scheme-relative URLs.
   */
  public function getUrl($name, $parameters = array(), $options = array()) {
    $options['absolute'] = TRUE;
    return $this->urlGenerator->generateFromRoute($name, $parameters, $options);
  }

  /**
   * Generates an absolute URL given a path.
   *
   * @param string $path
   *   The path.
   * @param array $options
   *   (optional) An associative array of additional options. The 'absolute'
   *   option is forced to be TRUE.
   *
   * @return string
   *   The generated absolute URL for the given path.
   */
  public function getUrlFromPath($path, $options = array()) {
    $options['absolute'] = TRUE;
    return $this->urlGenerator->generateFromPath($path, $options);
  }

  /**
   * Gets a rendered link from an url object.
   *
   * @param string $text
   *   The link text for the anchor tag as a translated string.
   * @param \Drupal\Core\Url|string $url
   *   The URL object or string used for the link.
   *
   * @return string
   *   An HTML string containing a link to the given url.
   */
  public function getLink($text, $url) {
    if (!$url instanceof Url) {
      $url = Url::fromUri($url);
    }
    return $this->linkGenerator->generate($text, $url);
  }

  /**
   * Determines at compile time whether the generated URL will be safe.
   *
   * Saves the unneeded automatic escaping for performance reasons.
   *
   * The URL generation process percent encodes non-alphanumeric characters.
   * Thus, the only character within an URL that must be escaped in HTML is the
   * ampersand ("&") which separates query params. Thus we cannot mark
   * the generated URL as always safe, but only when we are sure there won't be
   * multiple query params. This is the case when there are none or only one
   * constant parameter given. E.g. we know beforehand this will not need to
   * be escaped:
   * - path('route')
   * - path('route', {'param': 'value'})
   * But the following may need to be escaped:
   * - path('route', var)
   * - path('route', {'param': ['val1', 'val2'] }) // a sub-array
   * - path('route', {'param1': 'value1', 'param2': 'value2'})
   * If param1 and param2 reference placeholders in the route, it would not
   * need to be escaped, but we don't know that in advance.
   *
   * @param \Twig_Node $args_node
   *   The arguments of the path/url functions.
   *
   * @return array
   *   An array with the contexts the URL is safe
   */
  public function isUrlGenerationSafe(\Twig_Node $args_node) {
    // Support named arguments.
    $parameter_node = $args_node->hasNode('parameters') ? $args_node->getNode('parameters') : ($args_node->hasNode(1) ? $args_node->getNode(1) : NULL);

    if (!isset($parameter_node) || $parameter_node instanceof \Twig_Node_Expression_Array && count($parameter_node) <= 2 &&
        (!$parameter_node->hasNode(1) || $parameter_node->getNode(1) instanceof \Twig_Node_Expression_Constant)) {
      return array('html');
    }

    return array();
  }

}
