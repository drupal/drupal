<?php

/**
 * @file
 * Contains \Drupal\filter\Entity\FilterFormat.
 */

namespace Drupal\filter\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBase;
use Drupal\Core\Entity\Annotation\EntityType;
use Drupal\Core\Annotation\Translation;
use Drupal\Core\Entity\EntityStorageControllerInterface;
use Drupal\filter\FilterFormatInterface;
use Drupal\filter\FilterBag;

/**
 * Represents a text format.
 *
 * @EntityType(
 *   id = "filter_format",
 *   label = @Translation("Text format"),
 *   controllers = {
 *     "form" = {
 *       "add" = "Drupal\filter\FilterFormatAddFormController",
 *       "edit" = "Drupal\filter\FilterFormatEditFormController",
 *       "disable" = "Drupal\filter\Form\FilterDisableForm"
 *     },
 *     "list" = "Drupal\filter\FilterFormatListController",
 *     "access" = "Drupal\filter\FilterFormatAccessController",
 *     "storage" = "Drupal\Core\Config\Entity\ConfigStorageController"
 *   },
 *   config_prefix = "filter.format",
 *   entity_keys = {
 *     "id" = "format",
 *     "label" = "name",
 *     "uuid" = "uuid",
 *     "weight" = "weight",
 *     "status" = "status"
 *   },
 *   links = {
 *     "edit-form" = "admin/config/content/formats/manage/{filter_format}"
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
  public $cache = FALSE;

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
      $this->filterBag->setConfiguration($instance_id, $configuration);
    }
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getExportProperties() {
    $properties = parent::getExportProperties();
    // Sort and export the configuration of all filters.
    $properties['filters'] = $this->filters()->sort()->getConfiguration();

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

  /**
   * {@inheritdoc}
   */
  public function preSave(EntityStorageControllerInterface $storage_controller) {
    parent::preSave($storage_controller);

    $this->name = trim($this->label());

    // @todo Do not save disabled filters whose properties are identical to
    //   all default properties.

    // Determine whether the format can be cached.
    // @todo This is a derived/computed definition, not configuration.
    $this->cache = TRUE;
    foreach ($this->filters()->getAll() as $filter) {
      if ($filter->status && !$filter->cache) {
        $this->cache = FALSE;
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function postSave(EntityStorageControllerInterface $storage_controller, $update = TRUE) {
    parent::postSave($storage_controller, $update);

    // Clear the static caches of filter_formats() and others.
    filter_formats_reset();

    if ($update) {
      // Clear the filter cache whenever a text format is updated.
      cache('filter')->deleteTags(array('filter_format' => $this->id()));
    }
    else {
      // Default configuration of modules and installation profiles is allowed
      // to specify a list of user roles to grant access to for the new format;
      // apply the defined user role permissions when a new format is inserted
      // and has a non-empty $roles property.
      // Note: user_role_change_permissions() triggers a call chain back into
      // filter_permission() and lastly filter_formats(), so its cache must be
      // reset upfront.
      if (($roles = $this->get('roles')) && $permission = $this->getPermissionName()) {
        foreach (user_roles() as $rid => $name) {
          $enabled = in_array($rid, $roles, TRUE);
          user_role_change_permissions($rid, array($permission => $enabled));
        }
      }
    }
  }

  /**
   * Returns if this format is the fallback format.
   *
   * The fallback format can never be disabled. It must always be available.
   *
   * @return bool
   *   TRUE if this format is the fallback format, FALSE otherwise.
   */
  public function isFallbackFormat() {
    $fallback_format = \Drupal::config('filter.settings')->get('fallback_format');
    return $this->id() == $fallback_format;
  }

  /**
   * {@inheritdoc}
   */
  public function getPermissionName() {
    return !$this->isFallbackFormat() ? 'use text format ' . $this->id() : FALSE;
  }

}
