<?php

/**
 * @file
 * Contains \Drupal\system\Entity\DateFormat.
 */

namespace Drupal\system\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBase;
use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\Entity\EntityStorageControllerInterface;
use Drupal\system\DateFormatInterface;

/**
 * Defines the Date Format configuration entity class.
 *
 * @ConfigEntityType(
 *   id = "date_format",
 *   label = @Translation("Date format"),
 *   controllers = {
 *     "access" = "Drupal\system\DateFormatAccessController",
 *     "list" = "Drupal\system\DateFormatListController",
 *     "form" = {
 *       "add" = "Drupal\system\Form\DateFormatAddForm",
 *       "edit" = "Drupal\system\Form\DateFormatEditForm",
 *       "delete" = "Drupal\system\Form\DateFormatDeleteForm"
 *     }
 *   },
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label"
 *   },
 *   admin_permission = "administer site configuration",
 *   links = {
 *     "delete-form" = "system.date_format_delete",
 *     "edit-form" = "system.date_format_edit"
 *   }
 * )
 */
class DateFormat extends ConfigEntityBase implements DateFormatInterface {

  /**
   * The date format machine name.
   *
   * @var string
   */
  public $id;

  /**
   * The human-readable name of the date format entity.
   *
   * @var string
   */
  public $label;

  /**
   * The date format pattern.
   *
   * @var array
   */
  protected $pattern;

  /**
   * The locked status of this date format.
   *
   * @var bool
   */
  protected $locked = FALSE;

  /**
   * {@inheritdoc}
   */
  public function getExportProperties() {
    $properties = parent::getExportProperties();
    $names = array(
      'locked',
      'pattern',
    );
    foreach ($names as $name) {
      $properties[$name] = $this->get($name);
    }
    return $properties;
  }

  /**
   * {@inheritdoc}
   */
  public function getPattern($type = DrupalDateTime::PHP) {
    return isset($this->pattern[$type]) ? $this->pattern[$type] : '';
  }

  /**
   * {@inheritdoc}
   */
  public function setPattern($pattern, $type = DrupalDateTime::PHP) {
    $this->pattern[$type] = $pattern;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function isLocked() {
    return (bool) $this->locked;
  }

}
