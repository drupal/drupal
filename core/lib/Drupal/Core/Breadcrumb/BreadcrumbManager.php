<?php

/**
 * @file
 * Contains \Drupal\Core\Breadcrumb\BreadcrumbManager.
 */

namespace Drupal\Core\Breadcrumb;

use Drupal\Component\Utility\String;
use Drupal\Core\Extension\ModuleHandlerInterface;

/**
 * Provides a breadcrumb manager.
 *
 * Holds an array of path processor objects and uses them to sequentially process
 * a path, in order of processor priority.
 */
class BreadcrumbManager implements BreadcrumbBuilderInterface {

  /**
   * The module handler to invoke the alter hook.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * Holds arrays of breadcrumb builders, keyed by priority.
   *
   * @var array
   */
  protected $builders = array();

  /**
   * Holds the array of breadcrumb builders sorted by priority.
   *
   * Set to NULL if the array needs to be re-calculated.
   *
   * @var array|null
   */
  protected $sortedBuilders;

  /**
   * Constructs a \Drupal\Core\Breadcrumb\BreadcrumbManager object.
   *
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   */
  public function __construct(ModuleHandlerInterface $module_handler) {
    $this->moduleHandler = $module_handler;
  }

  /**
   * Adds another breadcrumb builder.
   *
   * @param \Drupal\Core\Breadcrumb\BreadcrumbBuilderInterface $builder
   *   The breadcrumb builder to add.
   * @param int $priority
   *   Priority of the breadcrumb builder.
   */
  public function addBuilder(BreadcrumbBuilderInterface $builder, $priority) {
    $this->builders[$priority][] = $builder;
    // Force the builders to be re-sorted.
    $this->sortedBuilders = NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function applies(array $attributes) {
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function build(array $attributes) {
    $breadcrumb = array();
    $context = array('builder' => NULL);
    // Call the build method of registered breadcrumb builders,
    // until one of them returns an array.
    foreach ($this->getSortedBuilders() as $builder) {
      if (!$builder->applies($attributes)) {
        // The builder does not apply, so we continue with the other builders.
        continue;
      }

      $build = $builder->build($attributes);

      if (is_array($build)) {
        // The builder returned an array of breadcrumb links.
        $breadcrumb = $build;
        $context['builder'] = $builder;
        break;
      }
      else {
        throw new \UnexpectedValueException(String::format('Invalid breadcrumb returned by !class::build().', array('!class' => get_class($builder))));
      }
    }
    // Allow modules to alter the breadcrumb.
    $this->moduleHandler->alter('system_breadcrumb', $breadcrumb, $attributes, $context);
    // Fall back to an empty breadcrumb.
    return $breadcrumb;
  }

  /**
   * Returns the sorted array of breadcrumb builders.
   *
   * @return array
   *   An array of breadcrumb builder objects.
   */
  protected function getSortedBuilders() {
    if (!isset($this->sortedBuilders)) {
      // Sort the builders according to priority.
      krsort($this->builders);
      // Merge nested builders from $this->builders into $this->sortedBuilders.
      $this->sortedBuilders = array();
      foreach ($this->builders as $builders) {
        $this->sortedBuilders = array_merge($this->sortedBuilders, $builders);
      }
    }
    return $this->sortedBuilders;
  }

}
