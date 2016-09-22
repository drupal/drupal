<?php

namespace Drupal\content_moderation\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBase;
use Drupal\content_moderation\ModerationStateInterface;

/**
 * Defines the Moderation state entity.
 *
 * @ConfigEntityType(
 *   id = "moderation_state",
 *   label = @Translation("Moderation state"),
 *   handlers = {
 *     "access" = "Drupal\content_moderation\ModerationStateAccessControlHandler",
 *     "list_builder" = "Drupal\content_moderation\ModerationStateListBuilder",
 *     "form" = {
 *       "add" = "Drupal\content_moderation\Form\ModerationStateForm",
 *       "edit" = "Drupal\content_moderation\Form\ModerationStateForm",
 *       "delete" = "Drupal\content_moderation\Form\ModerationStateDeleteForm"
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\DefaultHtmlRouteProvider",
 *     },
 *   },
 *   config_prefix = "state",
 *   admin_permission = "administer moderation states",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *     "uuid" = "uuid",
 *     "weight" = "weight",
 *   },
 *   links = {
 *     "add-form" = "/admin/config/workflow/moderation/states/add",
 *     "edit-form" = "/admin/config/workflow/moderation/states/{moderation_state}",
 *     "delete-form" = "/admin/config/workflow/moderation/states/{moderation_state}/delete",
 *     "collection" = "/admin/config/workflow/moderation/states"
 *   },
 *   config_export = {
 *     "id",
 *     "label",
 *     "published",
 *     "default_revision",
 *     "weight",
 *   },
 * )
 */
class ModerationState extends ConfigEntityBase implements ModerationStateInterface {

  /**
   * The Moderation state ID.
   *
   * @var string
   */
  protected $id;

  /**
   * The Moderation state label.
   *
   * @var string
   */
  protected $label;

  /**
   * Whether this state represents a published node.
   *
   * @var bool
   */
  protected $published;

  /**
   * Relative weight of this state.
   *
   * @var int
   */
  protected $weight;

  /**
   * Whether this state represents a default revision of the node.
   *
   * If this is a published state, then this property is ignored.
   *
   * @var bool
   */
  protected $default_revision;

  /**
   * {@inheritdoc}
   */
  public function isPublishedState() {
    return $this->published;
  }

  /**
   * {@inheritdoc}
   */
  public function isDefaultRevisionState() {
    return $this->published || $this->default_revision;
  }

}
