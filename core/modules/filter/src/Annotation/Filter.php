<?php

namespace Drupal\filter\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines a filter annotation object.
 *
 * Plugin Namespace: Plugin\Filter
 *
 * For a working example, see \Drupal\filter\Plugin\Filter\FilterHtml
 *
 * @see \Drupal\filter\FilterPluginManager
 * @see \Drupal\filter\Plugin\FilterInterface
 * @see \Drupal\filter\Plugin\FilterBase
 * @see plugin_api
 *
 * @Annotation
 */
class Filter extends Plugin {

  /**
   * The plugin ID.
   *
   * @var string
   */
  public $id;

  /**
   * The name of the provider that owns the filter.
   *
   * @var string
   */
  public $provider;

  /**
   * The human-readable name of the filter.
   *
   * This is used as an administrative summary of what the filter does.
   *
   * @var \Drupal\Core\Annotation\Translation
   *
   * @ingroup plugin_translatable
   */
  public $title;

  /**
   * Additional administrative information about the filter's behavior.
   *
   * This property is optional and it does not need to be declared.
   *
   * @var \Drupal\Core\Annotation\Translation
   *
   * @ingroup plugin_translatable
   */
  public $description = '';

  /**
   * A default weight for the filter in new text formats.
   *
   * This property is optional and it does not need to be declared.
   *
   * @var int
   */
  public $weight = 0;

  /**
   * Whether this filter is enabled or disabled by default.
   *
   * This property is optional and it does not need to be declared.
   *
   * @var bool
   */
  public $status = FALSE;

  /**
   * The default settings for the filter.
   *
   * This property is optional and it does not need to be declared.
   *
   * @var array
   */
  public $settings = [];

}
