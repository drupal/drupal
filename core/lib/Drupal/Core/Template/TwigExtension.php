<?php

namespace Drupal\Core\Template;

use Drupal\Component\Utility\Html;
use Drupal\Component\Render\MarkupInterface;
use Drupal\Core\Cache\CacheableDependencyInterface;
use Drupal\Core\Datetime\DateFormatterInterface;
use Drupal\Core\Render\AttachmentsInterface;
use Drupal\Core\Render\BubbleableMetadata;
use Drupal\Core\Render\Markup;
use Drupal\Core\Render\RenderableInterface;
use Drupal\Core\Render\RendererInterface;
use Drupal\Core\Routing\UrlGeneratorInterface;
use Drupal\Core\Theme\ThemeManagerInterface;
use Drupal\Core\Url;

/**
 * A class providing Drupal Twig extensions.
 *
 * This provides a Twig extension that registers various Drupal-specific
 * extensions to Twig, specifically Twig functions, filter, and node visitors.
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
   * @var \Drupal\Core\Datetime\DateFormatterInterface
   */
  protected $dateFormatter;

  /**
   * Constructs \Drupal\Core\Template\TwigExtension.
   *
   * @param \Drupal\Core\Render\RendererInterface $renderer
   *   The renderer.
   * @param \Drupal\Core\Routing\UrlGeneratorInterface $url_generator
   *   The URL generator.
   * @param \Drupal\Core\Theme\ThemeManagerInterface $theme_manager
   *   The theme manager.
   * @param \Drupal\Core\Datetime\DateFormatterInterface $date_formatter
   *   The date formatter.
   */
  public function __construct(RendererInterface $renderer, UrlGeneratorInterface $url_generator, ThemeManagerInterface $theme_manager, DateFormatterInterface $date_formatter) {
    $this->renderer = $renderer;
    $this->urlGenerator = $url_generator;
    $this->themeManager = $theme_manager;
    $this->dateFormatter = $date_formatter;
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
   *
   * @deprecated in Drupal 8.3.x-dev, will be removed before Drupal 9.0.0.
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
   *
   * @deprecated in Drupal 8.3.x-dev, will be removed before Drupal 9.0.0.
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
   *
   * @deprecated in Drupal 8.3.x-dev, will be removed before Drupal 9.0.0.
   */
  public function setDateFormatter(DateFormatterInterface $date_formatter) {
    $this->dateFormatter = $date_formatter;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getFunctions() {
    return [
      // This function will receive a renderable array, if an array is detected.
      new \Twig_SimpleFunction('render_var', [$this, 'renderVar']),
      // The url and path function are defined in close parallel to those found
      // in \Symfony\Bridge\Twig\Extension\RoutingExtension
      new \Twig_SimpleFunction('url', [$this, 'getUrl'], ['is_safe_callback' => [$this, 'isUrlGenerationSafe']]),
      new \Twig_SimpleFunction('path', [$this, 'getPath'], ['is_safe_callback' => [$this, 'isUrlGenerationSafe']]),
      new \Twig_SimpleFunction('link', [$this, 'getLink']),
      new \Twig_SimpleFunction('file_url', function ($uri) {
        return file_url_transform_relative(file_create_url($uri));
      }),
      new \Twig_SimpleFunction('attach_library', [$this, 'attachLibrary']),
      new \Twig_SimpleFunction('active_theme_path', [$this, 'getActiveThemePath']),
      new \Twig_SimpleFunction('active_theme', [$this, 'getActiveTheme']),
      new \Twig_SimpleFunction('create_attribute', [$this, 'createAttribute']),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFilters() {
    return [
      // Translation filters.
      new \Twig_SimpleFilter('t', 't', ['is_safe' => ['html']]),
      new \Twig_SimpleFilter('trans', 't', ['is_safe' => ['html']]),
      // The "raw" filter is not detectable when parsing "trans" tags. To detect
      // which prefix must be used for translation (@, !, %), we must clone the
      // "raw" filter and give it identifiable names. These filters should only
      // be used in "trans" tags.
      // @see TwigNodeTrans::compileString()
      new \Twig_SimpleFilter('placeholder', [$this, 'escapePlaceholder'], ['is_safe' => ['html'], 'needs_environment' => TRUE]),

      // Replace twig's escape filter with our own.
      new \Twig_SimpleFilter('drupal_escape', [$this, 'escapeFilter'], ['needs_environment' => TRUE, 'is_safe_callback' => 'twig_escape_filter_is_safe']),

      // Implements safe joining.
      // @todo Make that the default for |join? Upstream issue:
      //   https://github.com/fabpot/Twig/issues/1420
      new \Twig_SimpleFilter('safe_join', [$this, 'safeJoin'], ['needs_environment' => TRUE, 'is_safe' => ['html']]),

      // Array filters.
      new \Twig_SimpleFilter('without', 'twig_without'),

      // CSS class and ID filters.
      new \Twig_SimpleFilter('clean_class', '\Drupal\Component\Utility\Html::getClass'),
      new \Twig_SimpleFilter('clean_id', '\Drupal\Component\Utility\Html::getId'),
      // This filter will render a renderable array to use the string results.
      new \Twig_SimpleFilter('render', [$this, 'renderVar']),
      new \Twig_SimpleFilter('format_date', [$this->dateFormatter, 'format']),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getNodeVisitors() {
    // The node visitor is needed to wrap all variables with
    // render_var -> TwigExtension->renderVar() function.
    return [
      new TwigNodeVisitor(),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getTokenParsers() {
    return [
      new TwigTransTokenParser(),
    ];
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
   *
   * @return string
   *   The generated URL path (relative URL) for the given route.
   *
   * @see \Drupal\Core\Routing\UrlGeneratorInterface::generateFromRoute()
   */
  public function getPath($name, $parameters = [], $options = []) {
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
  public function getUrl($name, $parameters = [], $options = []) {
    // Generate URL.
    $options['absolute'] = TRUE;
    $generated_url = $this->urlGenerator->generateFromRoute($name, $parameters, $options, TRUE);

    // Return as render array, so we can bubble the bubbleable metadata.
    $build = ['#markup' => $generated_url->getGeneratedUrl()];
    $generated_url->applyTo($build);
    return $build;
  }

  /**
   * Gets a rendered link from a url object.
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
    // The twig extension should not modify the original URL object, this
    // ensures consistent rendering.
    // @see https://www.drupal.org/node/2842399
    $url = clone $url;
    if ($attributes) {
      if ($attributes instanceof Attribute) {
        $attributes = $attributes->toArray();
      }
      $url->mergeOptions(['attributes' => $attributes]);
    }
    // The text has been processed by twig already, convert it to a safe object
    // for the render system.
    if ($text instanceof \Twig_Markup) {
      $text = Markup::create($text);
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
   * Thus, the only character within a URL that must be escaped in HTML is the
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
      return ['html'];
    }

    return [];
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
   * @throws \Exception
   *   When $arg is passed as an object which does not implement __toString(),
   *   RenderableInterface or toString().
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

    $this->bubbleArgMetadata($arg);

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
      // You can't throw exceptions in the magic PHP __toString() methods, see
      // http://php.net/manual/language.oop5.magic.php#object.tostring so
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
      if ($autoescape && $return instanceof MarkupInterface) {
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
   * Bubbles Twig template argument's cacheability & attachment metadata.
   *
   * For example: a generated link or generated URL object is passed as a Twig
   * template argument, and its bubbleable metadata must be bubbled.
   *
   * @see \Drupal\Core\GeneratedLink
   * @see \Drupal\Core\GeneratedUrl
   *
   * @param mixed $arg
   *   A Twig template argument that is about to be printed.
   *
   * @see \Drupal\Core\Theme\ThemeManager::render()
   * @see \Drupal\Core\Render\RendererInterface::render()
   */
  protected function bubbleArgMetadata($arg) {
    // If it's a renderable, then it'll be up to the generated render array it
    // returns to contain the necessary cacheability & attachment metadata. If
    // it doesn't implement CacheableDependencyInterface or AttachmentsInterface
    // then there is nothing to do here.
    if ($arg instanceof RenderableInterface || !($arg instanceof CacheableDependencyInterface || $arg instanceof AttachmentsInterface)) {
      return;
    }

    $arg_bubbleable = [];
    BubbleableMetadata::createFromObject($arg)
      ->applyTo($arg_bubbleable);

    $this->renderer->render($arg_bubbleable);
  }

  /**
   * Wrapper around render() for twig printed output.
   *
   * If an object is passed which does not implement __toString(),
   * RenderableInterface or toString() then an exception is thrown;
   * Other objects are casted to string. However in the case that the
   * object is an instance of a Twig_Markup object it is returned directly
   * to support auto escaping.
   *
   * If an array is passed it is rendered via render() and scalar values are
   * returned directly.
   *
   * @param mixed $arg
   *   String, Object or Render Array.
   *
   * @throws \Exception
   *   When $arg is passed as an object which does not implement __toString(),
   *   RenderableInterface or toString().
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
      $this->bubbleArgMetadata($arg);
      if ($arg instanceof RenderableInterface) {
        $arg = $arg->toRenderable();
      }
      elseif (method_exists($arg, '__toString')) {
        return (string) $arg;
      }
      // You can't throw exceptions in the magic PHP __toString() methods, see
      // http://php.net/manual/language.oop5.magic.php#object.tostring so
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
   * @param mixed[]|\Traversable|null $value
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
      $value = iterator_to_array($value, FALSE);
    }

    return implode($glue, array_map(function($item) use ($env) {
      // If $item is not marked safe then it will be escaped.
      return $this->escapeFilter($env, $item, 'html', NULL, TRUE);
    }, (array) $value));
  }

  /**
   * Creates an Attribute object.
   *
   * @param array $attributes
   *   (optional) An associative array of key-value pairs to be converted to
   *   HTML attributes.
   *
   * @return \Drupal\Core\Template\Attribute
   *   An attributes object that has the given attributes.
   */
  public function createAttribute(array $attributes = []) {
    return new Attribute($attributes);
  }

}
