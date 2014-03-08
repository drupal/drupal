<?php

/**
 * @file
 * Contains \Drupal\entity\EntityDisplayModeBase.
 */

namespace Drupal\entity;

use Drupal\Core\Config\Entity\ConfigEntityBase;

/**
 * Base class for config entity types that hold settings for form and view modes.
 */
abstract class EntityDisplayModeBase extends ConfigEntityBase implements EntityDisplayModeInterface {

  /**
   * The ID of the form or view mode.
   *
   * @var string
   */
  public $id;

  /**
   * The human-readable name of the form or view mode.
   *
   * @var string
   */
  public $label;

  /**
   * The entity type this form or view mode is used for.
   *
   * This is not to be confused with EntityDisplayModeBase::$entityType which is
   * inherited from Entity::$entityType.
   *
   * @var string
   */
  public $targetEntityType;

  /**
   * Whether or not this form or view mode has custom settings by default.
   *
   * If FALSE, entities displayed in this mode will reuse the 'default' display
   * settings by default (e.g. right after the module exposing the form or view
   * mode is enabled), but administrators can later use the Field UI to apply
   * custom display settings specific to the form or view mode.
   *
   * @var bool
   */
  public $status = TRUE;

  /**
   * Whether or not the rendered output of this view mode is cached by default.
   *
   * @var bool
   */
  public $cache = TRUE;

  /**
   * {@inheritdoc}
   */
  public static function sort($a, $b) {
    // Sort by the type of entity the view mode is used for.
    $a_type = $a->getTargetType();
    $b_type = $b->getTargetType();
    $type_order = strnatcasecmp($a_type, $b_type);
    return $type_order != 0 ? $type_order : parent::sort($a, $b);
  }

  /**
   * {@inheritdoc}
   */
  public function getTargetType() {
    return $this->targetEntityType;
  }

}
