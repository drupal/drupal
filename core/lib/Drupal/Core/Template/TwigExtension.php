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

use Drupal\Component\Utility\Html;
use Drupal\Component\Utility\SafeMarkup;
use Drupal\Component\Render\MarkupInterface;
use Drupal\Core\Datetime\DateFormatter;
use Drupal\Core\Render\RenderableInterface;
use Drupal\Core\Render\RendererInterface;
use Drupal\Core\Routing\UrlGeneratorInterface;
use Drupal\Core\Theme\ThemeManagerInterface;
use Drupal\Core\Url;

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
   * The renderer.
   *
   * @var \Drupal\Core\Render\RendererInterface
   */
  protected $renderer;

  /**
   * The theme manager.
   *
   * @var \Drupal\Core\Theme\ThemeManagerInterface
   */
  protected $themeManager;

  /**
   * The date formatter.
   *
   * @var \Drupal\Core\Datetime\DateFormatter
   */
  protected $dateFormatter;

  /**
   * Constructs \Drupal\Core\Template\TwigExtension.
   *
   * @param \Drupal\Core\Render\RendererInterface $renderer
   *   The renderer.
   */
  public function __construct(RendererInterface $renderer) {
    $this->renderer = $renderer;
  }

  /**
   * Sets the URL generator.
   *
   * @param \Drupal\Core\Routing\UrlGeneratorInterface $url_generator
   *   The URL generator.
   *
   * @return $this
   *
   * @deprecated in Drupal 8.0.x-dev, will be removed before Drupal 9.0.0.
   *   Use \Drupal\Core\Template\TwigExtension::setUrlGenerator().
   */
  public function setGenerators(UrlGeneratorInterface $url_generator) {
    return $this->setUrlGenerator($url_generator);
  }

  /**
   * Sets the URL generator.
   *
   * @param \Drupal\Core\Routing\UrlGeneratorInterface $url_generator
   *   The URL generator.
   *
   * @return $this
   */
  public function setUrlGenerator(UrlGeneratorInterface $url_generator) {
    $this->urlGenerator = $url_generator;
    return $this;
  }

  /**
   * Sets the theme manager.
   *
   * @param \Drupal\Core\Theme\ThemeManagerInterface $theme_manager
   *   The theme manager.
   *
   * @return $this
   */
  public function setThemeManager(ThemeManagerInterface $theme_manager) {
    $this->themeManager = $theme_manager;
    return $this;
  }

  /**
   * Sets the date formatter.
   *
   * @param \Drupal\Core\Datetime\DateFormatter $date_formatter
   *   The date formatter.
   *
   * @return $this
   */
  public function setDateFormatter(DateFormatter $date_formatter) {
    $this->dateFormatter = $date_formatter;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getFunctions() {
    return [
      // This function will receive a renderable array, if an array is detected.
      new \Twig_SimpleFunction('render_var', array($this, 'renderVar')),
      // The url and path function are defined in close parallel to those found
      // in \Symfony\Bridge\Twig\Extension\RoutingExtension
      new \Twig_SimpleFunction('url', array($this, 'getUrl'), array('is_safe_callback' => array($this, 'isUrlGenerationSafe'))),
      new \Twig_SimpleFunction('path', array($this, 'getPath'), array('is_safe_callback' => array($this, 'isUrlGenerationSafe'))),
      new \Twig_SimpleFunction('link', array($this, 'getLink')),
      new \Twig_SimpleFunction('file_url', function ($uri) {
        return file_url_transform_relative(file_create_url($uri));
      }),
      new \Twig_SimpleFunction('attach_library', [$this, 'attachLibrary']),
      new \Twig_SimpleFunction('active_theme_path', [$this, 'getActiveThemePath']),
      new \Twig_SimpleFunction('active_theme', [$this, 'getActiveTheme']),
    ];
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
      new \Twig_SimpleFilter('placeholder', [$this, 'escapePlaceholder'], array('is_safe' => array('html'), 'needs_environment' => TRUE)),

      // Replace twig's escape filter with our own.
      new \Twig_SimpleFilter('drupal_escape', [$this, 'escapeFilter'], array('needs_environment' => true, 'is_safe_callback' => 'twig_escape_filter_is_safe')),

      // Implements safe joining.
      // @todo Make that the default for |join? Upstream issue:
      //   https://github.com/fabpot/Twig/issues/1420
      new \Twig_SimpleFilter('safe_join', [$this, 'safeJoin'], ['needs_environment' => true, 'is_safe' => ['html']]),

      // Array filters.
      new \Twig_SimpleFilter('without', 'twig_without'),

      // CSS class and ID filters.
      new \Twig_SimpleFilter('clean_class', '\Drupal\Component\Utility\Html::getClass'),
      new \Twig_SimpleFilter('clean_id', '\Drupal\Component\Utility\Html::getId'),
      // This filter will render a renderable array to use the string results.
      new \Twig_SimpleFilter('render', array($this, 'renderVar')),
      new \Twig_SimpleFilter('format_date', array($this->dateFormatter, 'format')),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getNodeVisitors() {
    // The node visitor is needed to wrap all variables with
    // render_var -> TwigExtension->renderVar() function.
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
    // Generate URL.
    $options['absolute'] = TRUE;
    $generated_url = $this->urlGenerator->generateFromRoute($name, $parameters, $options, TRUE);

    // Return as render array, so we can bubble the bubbleable metadata.
    $build = ['#markup' => $generated_url->getGeneratedUrl()];
    $generated_url->applyTo($build);
    return $build;
  }

  /**
   * Gets a rendered link from an url object.
   *
   * @param string $text
   *   The link text for the anchor tag as a translated string.
   * @param \Drupal\Core\Url|string $url
   *   The URL object or string used for the link.
   * @param array|\Drupal\Core\Template\Attribute $attributes
   *   An optional array or Attribute object of link attributes.
   *
   * @return array
   *   A render array representing a link to the given URL.
   */
  public function getLink($text, $url, $attributes = []) {
    if (!$url instanceof Url) {
      $url = Url::fromUri($url);
    }
    if ($attributes) {
      if ($attributes instanceof Attribute) {
        $attributes = $attributes->toArray();
      }
      if ($existing_attributes = $url->getOption('attributes')) {
        $attributes = array_merge($existing_attributes, $attributes);
      }
      $url->setOption('attributes', $attributes);
    }
    $build = [
      '#type' => 'link',
      '#title' => $text,
      '#url' => $url,
    ];
    return $build;
  }

  /**
   * Gets the name of the active theme.
   *
   * @return string
   *   The name of the active theme.
   */
  public function getActiveTheme() {
    return $this->themeManager->getActiveTheme()->getName();
  }

  /**
   * Gets the path of the active theme.
   *
   * @return string
   *   The path to the active theme.
   */
  public function getActiveThemePath() {
    return $this->themeManager->getActiveTheme()->getPath();
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
   * constant parameter given. For instance, we know beforehand this will not
   * need to be escaped:
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

  /**
   * Attaches an asset library to the template, and hence to the response.
   *
   * Allows Twig templates to attach asset libraries using
   * @code
   * {{ attach_library('extension/library_name') }}
   * @endcode
   *
   * @param string $library
   *   An asset library.
   */
  public function attachLibrary($library) {
    // Use Renderer::render() on a temporary render array to get additional
    // bubbleable metadata on the render stack.
    $template_attached = ['#attached' => ['library' => [$library]]];
    $this->renderer->render($template_attached);
  }

  /**
   * Provides a placeholder wrapper around ::escapeFilter.
   *
   * @param \Twig_Environment $env
   *   A Twig_Environment instance.
   * @param mixed $string
   *   The value to be escaped.
   *
   * @return string|null
   *   The escaped, rendered output, or NULL if there is no valid output.
   */
  public function escapePlaceholder($env, $string) {
    return '<em class="placeholder">' . $this->escapeFilter($env, $string) . '</em>';
  }

  /**
   * Overrides twig_escape_filter().
   *
   * Replacement function for Twig's escape filter.
   *
   * Note: This function should be kept in sync with
   * theme_render_and_autoescape().
   *
   * @param \Twig_Environment $env
   *   A Twig_Environment instance.
   * @param mixed $arg
   *   The value to be escaped.
   * @param string $strategy
   *   The escaping strategy. Defaults to 'html'.
   * @param string $charset
   *   The charset.
   * @param bool $autoescape
   *   Whether the function is called by the auto-escaping feature (TRUE) or by
   *   the developer (FALSE).
   *
   * @return string|null
   *   The escaped, rendered output, or NULL if there is no valid output.
   *
   * @todo Refactor this to keep it in sync with theme_render_and_autoescape()
   *   in https://www.drupal.org/node/2575065
   */
  public function escapeFilter(\Twig_Environment $env, $arg, $strategy = 'html', $charset = NULL, $autoescape = FALSE) {
    // Check for a numeric zero int or float.
    if ($arg === 0 || $arg === 0.0) {
      return 0;
    }

    // Return early for NULL and empty arrays.
    if ($arg == NULL) {
      return NULL;
    }

    // Keep Twig_Markup objects intact to support autoescaping.
    if ($autoescape && ($arg instanceof \Twig_Markup || $arg instanceof MarkupInterface)) {
      return $arg;
    }

    $return = NULL;

    if (is_scalar($arg)) {
      $return = (string) $arg;
    }
    elseif (is_object($arg)) {
      if ($arg instanceof RenderableInterface) {
        $arg = $arg->toRenderable();
      }
      elseif (method_exists($arg, '__toString')) {
        $return = (string) $arg;
      }
      // You can't throw exceptions in the magic PHP __toString methods, see
      // http://php.net/manual/en/language.oop5.magic.php#object.tostring so
      // we also support a toString method.
      elseif (method_exists($arg, 'toString')) {
        $return = $arg->toString();
      }
      else {
        throw new \Exception('Object of type ' . get_class($arg) . ' cannot be printed.');
      }
    }

    // We have a string or an object converted to a string: Autoescape it!
    if (isset($return)) {
      if ($autoescape && SafeMarkup::isSafe($return, $strategy)) {
        return $return;
      }
      // Drupal only supports the HTML escaping strategy, so provide a
      // fallback for other strategies.
      if ($strategy == 'html') {
        return Html::escape($return);
      }
      return twig_escape_filter($env, $return, $strategy, $charset, $autoescape);
    }

    // This is a normal render array, which is safe by definition, with
    // special simple cases already handled.

    // Early return if this element was pre-rendered (no need to re-render).
    if (isset($arg['#printed']) && $arg['#printed'] == TRUE && isset($arg['#markup']) && strlen($arg['#markup']) > 0) {
      return $arg['#markup'];
    }
    $arg['#printed'] = FALSE;
    return $this->renderer->render($arg);
  }

  /**
   * Wrapper around render() for twig printed output.
   *
   * If an object is passed that has no __toString method an exception is thrown;
   * other objects are casted to string. However in the case that the object is an
   * instance of a Twig_Markup object it is returned directly to support auto
   * escaping.
   *
   * If an array is passed it is rendered via render() and scalar values are
   * returned directly.
   *
   * @param mixed $arg
   *   String, Object or Render Array.
   *
   * @return mixed
   *   The rendered output or an Twig_Markup object.
   *
   * @see render
   * @see TwigNodeVisitor
   */
  public function renderVar($arg) {
    // Check for a numeric zero int or float.
    if ($arg === 0 || $arg === 0.0) {
      return 0;
    }

    // Return early for NULL and empty arrays.
    if ($arg == NULL) {
      return NULL;
    }

    // Optimize for scalars as it is likely they come from the escape filter.
    if (is_scalar($arg)) {
      return $arg;
    }

    if (is_object($arg)) {
      if ($arg instanceof RenderableInterface) {
        $arg = $arg->toRenderable();
      }
      elseif (method_exists($arg, '__toString')) {
        return (string) $arg;
      }
      // You can't throw exceptions in the magic PHP __toString methods, see
      // http://php.net/manual/en/language.oop5.magic.php#object.tostring so
      // we also support a toString method.
      elseif (method_exists($arg, 'toString')) {
        return $arg->toString();
      }
      else {
        throw new \Exception('Object of type ' . get_class($arg) . ' cannot be printed.');
      }
    }

    // This is a render array, with special simple cases already handled.
    // Early return if this element was pre-rendered (no need to re-render).
    if (isset($arg['#printed']) && $arg['#printed'] == TRUE && isset($arg['#markup']) && strlen($arg['#markup']) > 0) {
      return $arg['#markup'];
    }
    $arg['#printed'] = FALSE;
    return $this->renderer->render($arg);
  }

  /**
   * Joins several strings together safely.
   *
   * @param \Twig_Environment $env
   *   A Twig_Environment instance.
   * @param mixed[]|\Traversable|NULL $value
   *   The pieces to join.
   * @param string $glue
   *   The delimiter with which to join the string. Defaults to an empty string.
   *   This value is expected to be safe for output and user provided data
   *   should never be used as a glue.
   *
   * @return string
   *   The strings joined together.
   */
  public function safeJoin(\Twig_Environment $env, $value, $glue = '') {
    if ($value instanceof \Traversable) {
      $value = iterator_to_array($value, false);
    }

    return implode($glue, array_map(function($item) use ($env) {
      // If $item is not marked safe then it will be escaped.
      return $this->escapeFilter($env, $item, 'html', NULL, TRUE);
    }, (array) $value));
  }

}
