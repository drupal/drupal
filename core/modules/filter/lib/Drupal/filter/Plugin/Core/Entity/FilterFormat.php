<?php

/**
 * @file
 * Contains \Drupal\filter\Plugin\Core\Entity\FilterFormat.
 */

namespace Drupal\filter\Plugin\Core\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBase;
use Drupal\Core\Entity\Annotation\EntityType;
use Drupal\Core\Annotation\Translation;
use Drupal\filter\FilterFormatInterface;
use Drupal\filter\FilterBag;

/**
 * Represents a text format.
 *
 * @EntityType(
 *   id = "filter_format",
 *   label = @Translation("Text format"),
 *   module = "filter",
 *   controllers = {
 *     "storage" = "Drupal\filter\FilterFormatStorageController"
 *   },
 *   config_prefix = "filter.format",
 *   entity_keys = {
 *     "id" = "format",
 *     "label" = "name",
 *     "uuid" = "uuid",
 *     "status" = "status"
 *   }
 * )
 */
class FilterFormat extends ConfigEntityBase implements FilterFormatInterface {

  /**
   * Unique machine name of the format.
   *
   * @todo Rename to $id.
   *
   * @var string
   */
  public $format;

  /**
   * Unique label of the text format.
   *
   * Since text formats impact a site's security, two formats with the same
   * label but different filter configuration would impose a security risk.
   * Therefore, each text format label must be unique.
   *
   * @todo Rename to $label.
   *
   * @var string
   */
  public $name;

  /**
   * The UUID for this entity.
   *
   * @var string
   */
  public $uuid;

  /**
   * Weight of this format in the text format selector.
   *
   * The first/lowest text format that is accessible for a user is used as
   * default format.
   *
   * @var int
   */
  public $weight = 0;

  /**
   * List of user role IDs to grant access to use this format on initial creation.
   *
   * This property is always empty and unused for existing text formats.
   *
   * Default configuration objects of modules and installation profiles are
   * allowed to specify a list of user role IDs to grant access to.
   *
   * This property only has an effect when a new text format is created and the
   * list is not empty. By default, no user role is allowed to use a new format.
   *
   * @var array
   */
  protected $roles;

  /**
   * Whether processed text of this format can be cached.
   *
   * @var bool
   */
  public $cache = 0;

  /**
   * Configured filters for this text format.
   *
   * An associative array of filters assigned to the text format, keyed by the
   * instance ID of each filter and using the properties:
   * - plugin_id: The plugin ID of the filter plugin instance.
   * - module: The name of the module providing the filter.
   * - status: (optional) A Boolean indicating whether the filter is
   *   enabled in the text format. Defaults to FALSE.
   * - weight: (optional) The weight of the filter in the text format. Defaults
   *   to 0.
   * - settings: (optional) An array of configured settings for the filter.
   *
   * Use FilterFormat::filters() to access the actual filters.
   *
   * @var array
   */
  protected $filters = array();

  /**
   * Holds the collection of filters that are attached to this format.
   *
   * @var \Drupal\filter\FilterBag
   */
  protected $filterBag;

  /**
   * {@inheritdoc}
   */
  public function id() {
    return $this->format;
  }

  /**
   * {@inheritdoc}
   */
  public function filters($instance_id = NULL) {
    if (!isset($this->filterBag)) {
      $this->filterBag = new FilterBag(\Drupal::service('plugin.manager.filter'), $this->filters);
    }
    if (isset($instance_id)) {
      return $this->filterBag->get($instance_id);
    }
    return $this->filterBag;
  }

  /**
   * {@inheritdoc}
   */
  public function setFilterConfig($instance_id, array $configuration) {
    $this->filters[$instance_id] = $configuration;
    if (isset($this->filterBag)) {
      $this->filterBag->setConfig($instance_id, $configuration);
    }
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getExportProperties() {
    $properties = parent::getExportProperties();
    // Sort and export the configuration of all filters.
    $properties['filters'] = $this->filters()->sort()->export();

    return $properties;
  }

  /**
   * {@inheritdoc}
   */
  public function disable() {
    parent::disable();

    // Allow modules to react on text format deletion.
    module_invoke_all('filter_format_disable', $this);

    // Clear the filter cache whenever a text format is disabled.
    filter_formats_reset();
    cache('filter')->deleteTags(array('filter_format' => $this->format));

    return $this;
  }

}
