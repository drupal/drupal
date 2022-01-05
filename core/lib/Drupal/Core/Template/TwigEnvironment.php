<?php

namespace Drupal\Core\Template;

use Drupal\Component\FrontMatter\Exception\FrontMatterParseException;
use Drupal\Component\FrontMatter\FrontMatter;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\PhpStorage\PhpStorageFactory;
use Drupal\Core\Render\Markup;
use Drupal\Core\Serialization\Yaml;
use Drupal\Core\State\StateInterface;
use Twig\Environment;
use Twig\Error\SyntaxError;
use Twig\Extension\SandboxExtension;
use Twig\Loader\LoaderInterface;
use Twig\Source;

/**
 * A class that defines a Twig environment for Drupal.
 *
 * Instances of this class are used to store the configuration and extensions,
 * and are used to load templates from the file system or other locations.
 */
class TwigEnvironment extends Environment {

  /**
   * Key name of the Twig cache prefix metadata key-value pair in State.
   */
  const CACHE_PREFIX_METADATA_KEY = 'twig_extension_hash_prefix';

  /**
   * The state service.
   *
   * @var \Drupal\Core\State\StateInterface
   */
  protected $state;

  /**
   * Static cache of template classes.
   *
   * @var array
   */
  protected $templateClasses;

  /**
   * The template cache filename prefix.
   *
   * @var string
   */
  protected $twigCachePrefix = '';

  /**
   * Constructs a TwigEnvironment object and stores cache and storage
   * internally.
   *
   * @param string $root
   *   The app root.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache
   *   The cache bin.
   * @param string $twig_extension_hash
   *   The Twig extension hash.
   * @param \Drupal\Core\State\StateInterface $state
   *   The state service.
   * @param \Twig\Loader\LoaderInterface $loader
   *   The Twig loader or loader chain.
   * @param array $options
   *   The options for the Twig environment.
   */
  public function __construct($root, CacheBackendInterface $cache, $twig_extension_hash, StateInterface $state, LoaderInterface $loader, array $options = []) {
    $this->state = $state;

    $this->templateClasses = [];

    $options += [
      // @todo Ensure garbage collection of expired files.
      'cache' => TRUE,
      'debug' => FALSE,
      'auto_reload' => NULL,
    ];
    // Ensure autoescaping is always on.
    $options['autoescape'] = 'html';
    if ($options['cache'] === TRUE) {
      $current = $state->get(static::CACHE_PREFIX_METADATA_KEY, ['twig_extension_hash' => '']);
      if ($current['twig_extension_hash'] !== $twig_extension_hash || empty($current['twig_cache_prefix'])) {
        $current = [
          'twig_extension_hash' => $twig_extension_hash,
          // Generate a new prefix which invalidates any existing cached files.
          'twig_cache_prefix' => uniqid(),

        ];
        $state->set(static::CACHE_PREFIX_METADATA_KEY, $current);
      }
      $this->twigCachePrefix = $current['twig_cache_prefix'];

      $options['cache'] = new TwigPhpStorageCache($cache, $this->twigCachePrefix);
    }

    $this->setLoader($loader);
    parent::__construct($this->getLoader(), $options);
    $policy = new TwigSandboxPolicy();
    $sandbox = new SandboxExtension($policy, TRUE);
    $this->addExtension($sandbox);
  }

  /**
   * {@inheritdoc}
   */
  public function compileSource(Source $source): string {
    // Note: always use \Drupal\Core\Serialization\Yaml here instead of the
    // "serializer.yaml" service. This allows the core serializer to utilize
    // core related functionality which isn't available as the standalone
    // component based serializer.
    $frontMatter = FrontMatter::create($source->getCode(), Yaml::class);

    // Reconstruct the source if there is front matter data detected. Prepend
    // the source with {% line \d+ %} to inform Twig that the source code
    // actually starts on a different line past the front matter data. This is
    // particularly useful when used in error reporting.
    try {
      if (($line = $frontMatter->getLine()) > 1) {
        $content = "{% line $line %}" . $frontMatter->getContent();
        $source = new Source($content, $source->getName(), $source->getPath());
      }
    }
    catch (FrontMatterParseException $exception) {
      // Convert parse exception into a syntax exception for Twig and append
      // the path/name of the source to help further identify where it occurred.
      $message = sprintf($exception->getMessage() . ' in %s', $source->getPath() ?: $source->getName());
      throw new SyntaxError($message, $exception->getSourceLine(), $source, $exception);
    }

    return parent::compileSource($source);
  }

  /**
   * Invalidates all compiled Twig templates.
   *
   * @see \drupal_flush_all_caches
   */
  public function invalidate() {
    PhpStorageFactory::get('twig')->deleteAll();
    $this->templateClasses = [];
    $this->state->delete(static::CACHE_PREFIX_METADATA_KEY);
  }

  /**
   * Get the cache prefixed used by \Drupal\Core\Template\TwigPhpStorageCache.
   *
   * @return string
   *   The file cache prefix, or empty string if the cache is disabled.
   */
  public function getTwigCachePrefix() {
    return $this->twigCachePrefix;
  }

  /**
   * Retrieves metadata associated with a template.
   *
   * @param string $name
   *   The name for which to calculate the template class name.
   *
   * @return array
   *   The template metadata, if any.
   *
   * @throws \Twig\Error\LoaderError
   * @throws \Twig\Error\SyntaxError
   */
  public function getTemplateMetadata(string $name): array {
    $loader = $this->getLoader();
    $source = $loader->getSourceContext($name);

    // Note: always use \Drupal\Core\Serialization\Yaml here instead of the
    // "serializer.yaml" service. This allows the core serializer to utilize
    // core related functionality which isn't available as the standalone
    // component based serializer.
    try {
      return FrontMatter::create($source->getCode(), Yaml::class)->getData();
    }
    catch (FrontMatterParseException $exception) {
      // Convert parse exception into a syntax exception for Twig and append
      // the path/name of the source to help further identify where it occurred.
      $message = sprintf($exception->getMessage() . ' in %s', $source->getPath() ?: $source->getName());
      throw new SyntaxError($message, $exception->getSourceLine(), $source, $exception);
    }
  }

  /**
   * Gets the template class associated with the given string.
   *
   * @param string $name
   *   The name for which to calculate the template class name.
   * @param int $index
   *   The index if it is an embedded template.
   *
   * @return string
   *   The template class name.
   */
  public function getTemplateClass(string $name, int $index = NULL): string {
    // We override this method to add caching because it gets called multiple
    // times when the same template is used more than once. For example, a page
    // rendering 50 nodes without any node template overrides will use the same
    // node.html.twig for the output of each node and the same compiled class.
    $cache_index = $name . (NULL === $index ? '' : '_' . $index);
    if (!isset($this->templateClasses[$cache_index])) {
      $this->templateClasses[$cache_index] = parent::getTemplateClass($name, $index);
    }
    return $this->templateClasses[$cache_index];
  }

  /**
   * Renders a twig string directly.
   *
   * Warning: You should use the render element 'inline_template' together with
   * the #template attribute instead of this method directly.
   * On top of that you have to ensure that the template string is not dynamic
   * but just an ordinary static php string, because there may be installations
   * using read-only PHPStorage that want to generate all possible twig
   * templates as part of a build step. So it is important that an automated
   * script can find the templates and extract them. This is only possible if
   * the template is a regular string.
   *
   * @param string $template_string
   *   The template string to render with placeholders.
   * @param array $context
   *   An array of parameters to pass to the template.
   *
   * @return \Drupal\Component\Render\MarkupInterface|string
   *   The rendered inline template as a Markup object.
   *
   * @see \Drupal\Core\Template\Loader\StringLoader::exists()
   */
  public function renderInline($template_string, array $context = []) {
    // Prefix all inline templates with a special comment.
    $template_string = '{# inline_template_start #}' . $template_string;
    return Markup::create($this->createTemplate($template_string)->render($context));
  }

}
