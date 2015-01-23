<?php

/**
 * @file
 * Contains \Drupal\Core\Template\TwigEnvironment.
 */

namespace Drupal\Core\Template;

use Drupal\Core\PhpStorage\PhpStorageFactory;

/**
 * A class that defines a Twig environment for Drupal.
 *
 * Instances of this class are used to store the configuration and extensions,
 * and are used to load templates from the file system or other locations.
 *
 * @see core\vendor\twig\twig\lib\Twig\Environment.php
 */
class TwigEnvironment extends \Twig_Environment {
  protected $cache_object = NULL;
  protected $storage = NULL;

  /**
   * Static cache of template classes.
   *
   * @var array
   */
  protected $templateClasses;

  /**
   * Constructs a TwigEnvironment object and stores cache and storage
   * internally.
   *
   * @param string $root
   *   The app root.
   * @param \Twig_LoaderInterface $loader
   *   The Twig loader or loader chain.
   * @param array $options
   *   The options for the Twig environment.
   */
  public function __construct($root, \Twig_LoaderInterface $loader = NULL, $options = array()) {
    // @todo Pass as arguments from the DIC.
    $this->cache_object = \Drupal::cache();

    // Ensure that twig.engine is loaded, given that it is needed to render a
    // template because functions like twig_drupal_escape_filter are called.
    require_once $root . '/core/themes/engines/twig/twig.engine';

    $this->templateClasses = array();

    $options += array(
      // @todo Ensure garbage collection of expired files.
      'cache' => TRUE,
      'debug' => FALSE,
      'auto_reload' => NULL,
    );
    // Ensure autoescaping is always on.
    $options['autoescape'] = TRUE;

    $this->loader = $loader;
    parent::__construct($this->loader, $options);
  }

  /**
   * Checks if the compiled template needs an update.
   */
  protected function isFresh($cache_filename, $name) {
    $cid = 'twig:' . $cache_filename;
    $obj = $this->cache_object->get($cid);
    $mtime = isset($obj->data) ? $obj->data : FALSE;
    return $mtime === FALSE || $this->isTemplateFresh($name, $mtime);
  }

  /**
   * Compile the source and write the compiled template to disk.
   */
  public function updateCompiledTemplate($cache_filename, $name) {
    $source = $this->loader->getSource($name);
    $compiled_source = $this->compileSource($source, $name);
    $this->storage()->save($cache_filename, $compiled_source);
    // Save the last modification time
    $cid = 'twig:' . $cache_filename;
    $this->cache_object->set($cid, REQUEST_TIME);
  }

  /**
   * Implements Twig_Environment::loadTemplate().
   *
   * We need to overwrite this function to integrate with drupal_php_storage().
   *
   * This is a straight copy from loadTemplate() changed to use
   * drupal_php_storage().
   *
   * @param string $name
   *   The template name or the string which should be rendered as template.
   * @param int $index
   *   The index if it is an embedded template.
   *
   * @return \Twig_TemplateInterface
   *   A template instance representing the given template name.
   *
   * @throws \Twig_Error_Loader
   *   When the template cannot be found.
   * @throws \Twig_Error_Syntax
   *   When an error occurred during compilation.
   */
  public function loadTemplate($name, $index = NULL) {
    $cls = $this->getTemplateClass($name, $index);

    if (isset($this->loadedTemplates[$cls])) {
      return $this->loadedTemplates[$cls];
    }

    if (!class_exists($cls, FALSE)) {
      $cache_filename = $this->getCacheFilename($name);

      if ($cache_filename === FALSE) {
        $compiled_source = $this->compileSource($this->loader->getSource($name), $name);
        eval('?' . '>' . $compiled_source);
      }
      else {

        // If autoreload is on, check that the template has not been
        // modified since the last compilation.
        if ($this->isAutoReload() && !$this->isFresh($cache_filename, $name)) {
          $this->updateCompiledTemplate($cache_filename, $name);
        }

        if (!$this->storage()->load($cache_filename)) {
          $this->updateCompiledTemplate($cache_filename, $name);
          $this->storage()->load($cache_filename);
        }
      }
    }

    if (!$this->runtimeInitialized) {
        $this->initRuntime();
    }

    return $this->loadedTemplates[$cls] = new $cls($this);
  }

  /**
   * Gets the PHP code storage object to use for the compiled Twig files.
   *
   * @return \Drupal\Component\PhpStorage\PhpStorageInterface
   */
  protected function storage() {
    if (!isset($this->storage)) {
      $this->storage = PhpStorageFactory::get('twig');
    }
    return $this->storage;
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
  public function getTemplateClass($name, $index = NULL) {
    // We override this method to add caching because it gets called multiple
    // times when the same template is used more than once. For example, a page
    // rendering 50 nodes without any node template overrides will use the same
    // node.html.twig for the output of each node and the same compiled class.
    $cache_index = $name . (NULL === $index ? '' : '_' . $index);
    if (!isset($this->templateClasses[$cache_index])) {
      $this->templateClasses[$cache_index] = $this->templateClassPrefix . hash('sha256', $this->loader->getCacheKey($name)) . (NULL === $index ? '' : '_' . $index);
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
   * @return string
   *   The rendered inline template.
   */
  public function renderInline($template_string, array $context = array()) {
    return $this->loadTemplate($template_string, NULL)->render($context);
  }

}
