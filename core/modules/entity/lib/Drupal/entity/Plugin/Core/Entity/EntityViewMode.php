<?php

/**
 * @file
 * Contains \Drupal\entity\Plugin\Core\Entity\EntityViewMode.
 */

namespace Drupal\entity\Plugin\Core\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBase;
use Drupal\Core\Entity\Annotation\EntityType;
use Drupal\Core\Annotation\Translation;
use Drupal\entity\EntityViewModeInterface;

/**
 * Defines the view mode configuration entity class.
 *
 * View modes let entities be displayed differently depending on the context.
 * For instance, a node can be displayed differently on its own page ('full'
 * mode), on the home page or taxonomy listings ('teaser' mode), or in an RSS
 * feed ('rss' mode). Modules taking part in the display of the entity (notably
 * the Field API) can adjust their behavior depending on the requested view
 * mode. An additional 'default' view mode is available for all entity types.
 * This view mode is not intended for actual entity display, but holds default
 * display settings. For each available view mode, administrators can configure
 * whether it should use its own set of field display settings, or just
 * replicate the settings of the 'default' view mode, thus reducing the amount
 * of display configurations to keep track of.
 *
 * @see entity_get_view_modes()
 * @see hook_entity_view_mode_info_alter()
 *
 * @EntityType(
 *   id = "view_mode",
 *   label = @Translation("View mode"),
 *   module = "entity",
 *   controllers = {
 *     "storage" = "Drupal\entity\EntityViewModeStorageController"
 *   },
 *   config_prefix = "entity.view_mode",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *     "uuid" = "uuid"
 *   }
 * )
 */
class EntityViewMode extends ConfigEntityBase implements EntityViewModeInterface {

  /**
   * The ID of the view mode.
   *
   * @var string
   */
  public $id;

  /**
   * The UUID of the view mode.
   *
   * @var string
   */
  public $uuid;

  /**
   * The human-readable name of the view mode.
   *
   * @var string
   */
  public $label;

  /**
   * The entity type this view mode is used for.
   *
   * This is not to be confused with EntityViewMode::entityType which is
   * inherited from Entity::entityType and equals 'view_mode' for any view mode
   * entity.
   *
   * @var string
   */
  public $targetEntityType;

  /**
   * Whether or not this view mode has custom settings by default.
   *
   * If FALSE, entities displayed in this view mode will reuse the 'default'
   * display settings by default (e.g. right after the module exposing the view
   * mode is enabled), but administrators can later use the Field UI to apply
   * custom display settings specific to the view mode.
   *
   * @var bool
   */
  public $status = FALSE;

}
