<?php

namespace Drupal\Core\Template;

use Drupal\Component\Utility\Html;
use Drupal\Component\Render\MarkupInterface;
use Drupal\Core\Cache\CacheableDependencyInterface;
use Drupal\Core\Datetime\DateFormatterInterface;
use Drupal\Core\File\FileUrlGeneratorInterface;
use Drupal\Core\Render\AttachmentsInterface;
use Drupal\Core\Render\BubbleableMetadata;
use Drupal\Core\Render\Markup;
use Drupal\Core\Render\RenderableInterface;
use Drupal\Core\Render\RendererInterface;
use Drupal\Core\Routing\UrlGeneratorInterface;
use Drupal\Core\Theme\ThemeManagerInterface;
use Drupal\Core\Url;
use Twig\Environment;
use Twig\Extension\AbstractExtension;
use Twig\Markup as TwigMarkup;
use Twig\Node\Expression\ArrayExpression;
use Twig\Node\Expression\ConstantExpression;
use Twig\Node\Node;
use Twig\TwigFilter;
use Twig\TwigFunction;
use Twig\Runtime\EscaperRuntime;

/**
 * A class providing Drupal Twig extensions.
 *
 * This provides a Twig extension that registers various Drupal-specific
 * extensions to Twig, specifically Twig functions, filter, and node visitors.
 *
 * @see \Drupal\Core\CoreServiceProvider
 */
class TwigExtension extends AbstractExtension {

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
   * The file URL generator.
   *
   * @var \Drupal\Core\File\FileUrlGeneratorInterface
   */
  protected $fileUrlGenerator;

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
   * @param \Drupal\Core\File\FileUrlGeneratorInterface $file_url_generator
   *   The file URL generator.
   */
  public function __construct(RendererInterface $renderer, UrlGeneratorInterface $url_generator, ThemeManagerInterface $theme_manager, DateFormatterInterface $date_formatter, FileUrlGeneratorInterface $file_url_generator) {
    $this->renderer = $renderer;
    $this->urlGenerator = $url_generator;
    $this->themeManager = $theme_manager;
    $this->dateFormatter = $date_formatter;
    $this->fileUrlGenerator = $file_url_generator;
  }

  /**
   * {@inheritdoc}
   */
  public function getFunctions() {
    return [
      // This function will receive a renderable array, if an array is detected.
      new TwigFunction('render_var', [$this, 'renderVar']),
      // The URL and path function are defined in close parallel to those found
      // in \Symfony\Bridge\Twig\Extension\RoutingExtension
      new TwigFunction('url', [$this, 'getUrl'], ['is_safe_callback' => [$this, 'isUrlGenerationSafe']]),
      new TwigFunction('path', [$this, 'getPath'], ['is_safe_callback' => [$this, 'isUrlGenerationSafe']]),
      new TwigFunction('link', [$this, 'getLink']),
      new TwigFunction('file_url', [$this, 'getFileUrl']),
      new TwigFunction('attach_library', [$this, 'attachLibrary']),
      new TwigFunction('active_theme_path', [$this, 'getActiveThemePath']),
      new TwigFunction('active_theme', [$this, 'getActiveTheme']),
      new TwigFunction('create_attribute', [$this, 'createAttribute']),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFilters() {
    return [
      // Translation filters.
      new TwigFilter('t', 't', ['is_safe' => ['html']]),
      new TwigFilter('trans', 't', ['is_safe' => ['html']]),
      // The "raw" filter is not detectable when parsing "trans" tags. To detect
      // which prefix must be used for translation (@, !, %), we must clone the
      // "raw" filter and give it identifiable names. These filters should only
      // be used in "trans" tags.
      // @see TwigNodeTrans::compileString()
      new TwigFilter('placeholder', [$this, 'escapePlaceholder'], ['is_safe' => ['html'], 'needs_environment' => TRUE]),

      // Replace twig's escape filter with our own.
      new TwigFilter('drupal_escape', [$this, 'escapeFilter'], ['needs_environment' => TRUE, 'is_safe_callback' => 'twig_escape_filter_is_safe']),

      // Implements safe joining.
      // @todo Make that the default for |join? Upstream issue:
      //   https://github.com/fabpot/Twig/issues/1420
      new TwigFilter('safe_join', [$this, 'safeJoin'], ['needs_environment' => TRUE, 'is_safe' => ['html']]),

      // Array filters.
      new TwigFilter('without', [$this, 'withoutFilter']),

      // CSS class and ID filters.
      new TwigFilter('clean_class', '\Drupal\Component\Utility\Html::getClass'),
      new TwigFilter('clean_id', '\Drupal\Component\Utility\Html::getId'),
      new TwigFilter('clean_unique_id', '\Drupal\Component\Utility\Html::getUniqueId'),
      new TwigFilter('add_class', [$this, 'addClass']),
      new TwigFilter('set_attribute', [$this, 'setAttribute']),
      // This filter will render a renderable array to use the string results.
      new TwigFilter('render', [$this, 'renderVar']),
      new TwigFilter('format_date', [$this->dateFormatter, 'format']),
      // Add new theme hook suggestions directly from a Twig template.
      new TwigFilter('add_suggestion', [$this, 'suggestThemeHook']),
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
      new TwigNodeVisitorCheckDeprecations(),
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
   *   (optional) An associative array of route parameters names and values.
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
    assert($this->urlGenerator instanceof UrlGeneratorInterface, "The URL generator hasn't been set up. Any configuration YAML file with a service directive dealing with the Twig configuration can cause this, most likely found in a recently installed or changed module.");

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
   * @return array
   *   A render array with generated absolute URL for the given route.
   *
   * @todo Add an option for scheme-relative URLs.
   */
  public function getUrl($name, $parameters = [], $options = []) {
    assert($this->urlGenerator instanceof UrlGeneratorInterface, "The URL generator hasn't been set up. Any configuration YAML file with a service directive dealing with the Twig configuration can cause this, most likely found in a recently installed or changed module.");

    // Generate URL.
    $options['absolute'] = TRUE;
    $generated_url = $this->urlGenerator->generateFromRoute($name, $parameters, $options, TRUE);

    // Return as render array, so we can bubble the bubbleable metadata.
    $build = ['#markup' => $generated_url->getGeneratedUrl()];
    $generated_url->applyTo($build);
    return $build;
  }

  /**
   * Gets a rendered link from a URL object.
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
    assert(is_string($url) || $url instanceof Url, '$url must be a string or object of type \Drupal\Core\Url');
    assert(is_array($attributes) || $attributes instanceof Attribute, '$attributes, if set, must be an array or object of type \Drupal\Core\Template\Attribute');

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
    if ($text instanceof TwigMarkup) {
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
   * Gets the file URL.
   *
   * @param string|null $uri
   *   The file URI.
   *
   * @return string
   *   The file URL.
   */
  public function getFileUrl(?string $uri): string {
    if (is_null($uri)) {
      return '';
    }
    return $this->fileUrlGenerator->generateString($uri);
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
   * @param \Twig\Node\Node $args_node
   *   The arguments of the path/url functions.
   *
   * @return array
   *   An array with the contexts the URL is safe
   */
  public function isUrlGenerationSafe(Node $args_node) {
    // Support named arguments.
    $parameter_node = $args_node->hasNode('parameters') ? $args_node->getNode('parameters') : ($args_node->hasNode(1) ? $args_node->getNode(1) : NULL);

    if (!isset($parameter_node) || $parameter_node instanceof ArrayExpression && count($parameter_node) <= 2 &&
        (!$parameter_node->hasNode(1) || $parameter_node->getNode(1) instanceof ConstantExpression)) {
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
    assert(is_string($library), 'Argument must be a string.');

    // Use Renderer::render() on a temporary render array to get additional
    // bubbleable metadata on the render stack.
    $template_attached = ['#attached' => ['library' => [$library]]];
    $this->renderer->render($template_attached);
  }

  /**
   * Provides a placeholder wrapper around ::escapeFilter.
   *
   * @param \Twig\Environment $env
   *   A Twig Environment instance.
   * @param mixed $string
   *   The value to be escaped.
   *
   * @return string|null
   *   The escaped, rendered output, or NULL if there is no valid output.
   */
  public function escapePlaceholder(Environment $env, $string) {
    $return = $this->escapeFilter($env, $string);

    return $return ? '<em class="placeholder">' . $return . '</em>' : NULL;
  }

  /**
   * Overrides twig_escape_filter().
   *
   * Replacement function for Twig's escape filter.
   *
   * @param \Twig\Environment $env
   *   A Twig Environment instance.
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
   */
  public function escapeFilter(Environment $env, $arg, $strategy = 'html', $charset = NULL, $autoescape = FALSE) {
    // Check for a numeric zero int or float.
    if ($arg === 0 || $arg === 0.0) {
      return 0;
    }

    // Return early for NULL and empty arrays.
    if ($arg == NULL) {
      return NULL;
    }

    $this->bubbleArgMetadata($arg);

    // Keep \Twig\Markup objects intact to support autoescaping.
    if ($autoescape && ($arg instanceof TwigMarkup || $arg instanceof MarkupInterface)) {
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
      return $env->getRuntime(EscaperRuntime::class)->escape($arg, $strategy, $charset, $autoescape);
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
   * object is an instance of a \Twig\Markup object it is returned directly
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
   *   The rendered output or a \Twig\Markup object.
   *
   * @see render
   * @see TwigNodeVisitor
   */
  public function renderVar($arg) {
    // Check for a numeric zero int or float.
    if ($arg === 0 || $arg === 0.0) {
      return 0;
    }

    // Return early for NULL, empty arrays, empty strings and FALSE booleans.
    // @todo https://www.drupal.org/project/drupal/issues/3240093 Determine if
    //   this behavior is correct or should be deprecated.
    if ($arg == NULL) {
      return '';
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
   * @param \Twig\Environment $env
   *   A Twig Environment instance.
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
  public function safeJoin(Environment $env, $value, $glue = '') {
    if ($value instanceof \Traversable) {
      $value = iterator_to_array($value, FALSE);
    }

    return implode($glue, array_map(function ($item) use ($env) {
      // If $item is not marked safe then it will be escaped.
      return $this->escapeFilter($env, $item, 'html', NULL, TRUE);
    }, (array) $value));
  }

  /**
   * Creates an Attribute object.
   *
   * @param Attribute|array $attributes
   *   (optional) An existing attribute object or an associative array of
   *   key-value pairs to be converted to HTML attributes.
   *
   * @return \Drupal\Core\Template\Attribute
   *   An attributes object that has the given attributes.
   */
  public function createAttribute(Attribute|array $attributes = []) {
    if (\is_array($attributes)) {
      return new Attribute($attributes);
    }
    return $attributes;
  }

  /**
   * Removes child elements from a copy of the original array.
   *
   * Creates a copy of the renderable array and removes child elements by key
   * specified through filter's arguments. The copy can be printed without these
   * elements. The original renderable array is still available and can be used
   * to print child elements in their entirety in the twig template.
   *
   * @param array|object $element
   *   The parent renderable array to exclude the child items.
   * @param string[]|string ...
   *   The string keys of $element to prevent printing. Arguments can include
   *   string keys directly, or arrays of string keys to hide.
   *
   * @return array
   *   The filtered renderable array.
   */
  public function withoutFilter($element) {
    if ($element instanceof \ArrayAccess) {
      $filtered_element = clone $element;
    }
    else {
      $filtered_element = $element;
    }
    $args = func_get_args();
    unset($args[0]);
    // Since the remaining arguments can be a mix of arrays and strings, we use
    // some native PHP iterator classes to allow us to recursively iterate over
    // everything in a single pass.
    $iterator = new \RecursiveIteratorIterator(new \RecursiveArrayIterator($args));
    foreach ($iterator as $key) {
      unset($filtered_element[$key]);
    }
    return $filtered_element;
  }

  /**
   * Adds a theme suggestion to the element.
   *
   * @param array|null $element
   *   A theme element render array.
   * @param string|\Stringable $suggestion
   *   The theme suggestion part to append to the existing theme hook(s).
   *
   * @return array|null
   *   The element with the full theme suggestion added as the highest priority.
   */
  public function suggestThemeHook(?array $element, string|\Stringable $suggestion): ?array {
    // Make sure we have a valid theme element render array.
    if (empty($element['#theme'])) {
      // Throw assertion for render arrays that contain more than just metadata
      // (e.g., don't assert on empty field content).
      assert(array_diff_key($element ?? [], [
        '#cache' => TRUE,
        '#weight' => TRUE,
        '#attached' => TRUE,
      ]) === [], 'Invalid target for the "|add_suggestion" Twig filter; element does not have a "#theme" key.');
      return $element;
    }

    // Replace dashes with underscores to support suggestions that match the
    // target template name rather than the underlying theme hook.
    $suggestion = str_replace('-', '_', $suggestion);

    // Transform the theme hook to a format that supports multiple suggestions.
    if (!is_iterable($element['#theme'])) {
      $element['#theme'] = [$element['#theme']];
    }

    // Add _new_ suggestions for each existing theme hook. Simply modifying the
    // existing items (appending to each theme hook instead of adding new ones)
    // would cause the original hooks to be unavailable as fallbacks.
    //
    // Start with the lowest priority theme hook.
    foreach (array_reverse($element['#theme']) as $theme_hook) {
      // Add new suggestions to the front (highest priority).
      array_unshift($element['#theme'], $theme_hook . '__' . $suggestion);
    }

    // Reset the "#printed" flag to make sure the content gets rendered with the
    // new suggestion in place.
    unset($element['#printed']);

    // Add a cache key to prevent using render cache from before the suggestion
    // was added. If there are no cache keys already set, don't add one, as that
    // would enable caching on this element where there wasn't any before.
    if (isset($element['#cache']['keys'])) {
      $element['#cache']['keys'][] = $suggestion;
    }

    return $element;
  }

  /**
   * Triggers a deprecation error if a variable is deprecated.
   *
   * @param array $context
   *   A Twig context array.
   * @param array $used_variables
   *   The names of the variables used in a template.
   *
   * @see \Drupal\Core\Template\TwigNodeCheckDeprecations
   */
  public function checkDeprecations(array $context, array $used_variables): void {
    if (!isset($context['deprecations'])) {
      return;
    }

    foreach ($used_variables as $name) {
      if (isset($context['deprecations'][$name]) && \array_key_exists($name, $context)) {
        @trigger_error($context['deprecations'][$name], E_USER_DEPRECATED);
      }
    }
  }

  /**
   * Adds a value into the class attributes of a given element.
   *
   * Assumes element is an array.
   *
   * @param array $element
   *   A render element.
   * @param string[]|string ...$classes
   *   The class(es) to add to the element. Arguments can include string keys
   *   directly, or arrays of string keys.
   *
   * @return array
   *   The element with the given class(es) in attributes.
   */
  public function addClass(array $element, ...$classes): array {
    $attributes = new Attribute($element['#attributes'] ?? []);
    $attributes->addClass(...$classes);
    $element['#attributes'] = $attributes->toArray();

    // Make sure element gets rendered again.
    unset($element['#printed']);

    return $element;
  }

  /**
   * Sets an attribute on a given element.
   *
   * Assumes the element is an array.
   *
   * @param array $element
   *   A render element.
   * @param string $name
   *   The attribute name.
   * @param mixed $value
   *   (optional) The attribute value.
   *
   * @return array
   *   The element with the given sanitized attribute's value.
   */
  public function setAttribute(array $element, string $name, mixed $value = NULL): array {
    $element['#attributes'] = AttributeHelper::mergeCollections(
      $element['#attributes'] ?? [],
      new Attribute([$name => $value])
    );

    // Make sure element gets rendered again.
    unset($element['#printed']);

    return $element;
  }

}
