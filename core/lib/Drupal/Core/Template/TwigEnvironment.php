<?php

/**
 * @file
 * Definition of Drupal\Core\Template\TwigEnvironment.
 */

namespace Drupal\Core\Template;

use Drupal\Component\PhpStorage\PhpStorageFactory;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Extension\ThemeHandlerInterface;

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
   */
  public function __construct(\Twig_LoaderInterface $loader = NULL, $options = array(), ModuleHandlerInterface $module_handler, ThemeHandlerInterface $theme_handler) {
    // @todo Pass as arguments from the DIC.
    $this->cache_object = \Drupal::cache();

    // Set twig path namespace for themes and modules.
    $namespaces = array();
    foreach ($module_handler->getModuleList() as $name => $extension) {
      $namespaces[$name] = $extension->getPath();
    }
    foreach ($theme_handler->listInfo() as $name => $extension) {
      $namespaces[$name] = $extension->getPath();
    }

    foreach ($namespaces as $name => $path) {
      $templatesDirectory = $path . '/templates';
      if (file_exists($templatesDirectory)) {
        $loader->addPath($templatesDirectory, $name);
      }
    }

    $this->templateClasses = array();

    parent::__construct($loader, $options);
  }

  /**
   * Checks if the compiled template needs an update.
   */
  public function needsUpdate($cache_filename, $name) {
     $cid = 'twig:' . $cache_filename;
     $obj = $this->cache_object->get($cid);
     $mtime = isset($obj->data) ? $obj->data : FALSE;
     return $mtime !== FALSE && !$this->isTemplateFresh($name, $mtime);
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
   */
  public function loadTemplate($name, $index = NULL) {
    $cls = $this->getTemplateClass($name, $index);

    if (isset($this->loadedTemplates[$cls])) {
      return $this->loadedTemplates[$cls];
    }

    if (!class_exists($cls, FALSE)) {
      $cache_filename = $this->getCacheFilename($name);

      if ($cache_filename === FALSE) {
        $source = $this->loader->getSource($name);
        $compiled_source = $this->compileSource($source, $name);
        eval('?' . '>' . $compiled_source);
      } else {

        // If autoreload is on, check that the template has not been
        // modified since the last compilation.
        if ($this->isAutoReload() && $this->needsUpdate($cache_filename, $name)) {
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
   * {@inheritdoc}
   */
  public function getTemplateClass($name, $index = null) {
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

}
