<?php

/**
 * @file
 * Contains \Drupal\filter\Annotation\Filter.
 */

namespace Drupal\filter\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines an filter annotation object.
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
   * The name of the module providing the type.
   *
   * @var string
   */
  public $module;

  /**
   * The human-readable name of the filter.
   *
   * This is used as an administrative summary of what the filter does.
   *
   * @ingroup plugin_translatable
   *
   * @var \Drupal\Core\Annotation\Translation
   */
  public $title;

  /**
   * Additional administrative information about the filter's behavior.
   *
   * @ingroup plugin_translatable
   *
   * @var \Drupal\Core\Annotation\Translation (optional)
   */
  public $description = '';

  /**
   * A default weight for the filter in new text formats.
   *
   * @var int (optional)
   */
  public $weight = 0;

  /**
   * Whether this filter is enabled or disabled by default.
   *
   * @var bool (optional)
   */
  public $status = FALSE;

  /**
   * Specifies whether the filtered text can be cached.
   *
   * Note that setting this to FALSE makes the entire text format not cacheable,
   * which may have an impact on the site's overall performance.
   *
   * @var bool (optional)
   */
  public $cache = TRUE;

  /**
   * The default settings for the filter.
   *
   * @var array (optional)
   */
  public $settings = array();

}
