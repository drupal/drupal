<?php

/**
 * @file
 * Contains \Drupal\Core\Datetime\Entity\DateFormat.
 */

namespace Drupal\Core\Datetime\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBase;
use Drupal\Core\Config\Entity\ConfigEntityInterface;
use Drupal\Core\Datetime\DateFormatInterface;

/**
 * Defines the Date Format configuration entity class.
 *
 * @ConfigEntityType(
 *   id = "date_format",
 *   label = @Translation("Date format"),
 *   handlers = {
 *     "access" = "Drupal\system\DateFormatAccessControlHandler",
 *   },
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label"
 *   },
 *   admin_permission = "administer site configuration",
 *   list_cache_tags = { "rendered" }
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
  public function getPattern() {
    return $this->pattern;
  }

  /**
   * {@inheritdoc}
   */
  public function setPattern($pattern) {
    $this->pattern = $pattern;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function isLocked() {
    return (bool) $this->locked;
  }

  /**
   * {@inheritdoc}
   */
  public static function sort(ConfigEntityInterface $a, ConfigEntityInterface $b) {
    if ($a->isLocked() == $b->isLocked()) {
      $a_label = $a->label();
      $b_label = $b->label();
      return strnatcasecmp($a_label, $b_label);
    }
    return $a->isLocked() ? 1 : -1;
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheTag() {
    return ['rendered'];
  }

}
