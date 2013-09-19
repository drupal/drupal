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
use Drupal\Core\Entity\Annotation\EntityType;
use Drupal\Core\Annotation\Translation;

/**
 * Defines the Date Format configuration entity class.
 *
 * @EntityType(
 *   id = "date_format",
 *   label = @Translation("Date format"),
 *   module = "system",
 *   controllers = {
 *     "storage" = "Drupal\Core\Config\Entity\ConfigStorageController",
 *     "access" = "Drupal\system\DateFormatAccessController",
 *     "list" = "Drupal\system\DateFormatListController",
 *     "form" = {
 *       "add" = "Drupal\system\Form\DateFormatAddForm",
 *       "edit" = "Drupal\system\Form\DateFormatEditForm",
 *       "delete" = "Drupal\system\Form\DateFormatDeleteForm"
 *     }
 *   },
 *   config_prefix = "system.date_format",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *     "uuid" = "uuid"
 *   },
 *   links = {
 *     "edit-form" = "admin/config/regional/date-time/formats/manage/{date_format}"
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
   * The date format UUID.
   *
   * @var string
   */
  public $uuid;

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
   * @var array
   */
  protected $locales = array();

  /**
   * {@inheritdoc}
   */
  public function getExportProperties() {
    $properties = parent::getExportProperties();
    $names = array(
      'locked',
      'locales',
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
  public function getLocales() {
    return $this->locales;
  }

  /**
   * {@inheritdoc}
   */
  public function setLocales(array $locales) {
    $this->locales = $locales;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function hasLocales() {
    return !empty($this->locales);
  }

  /**
   * {@inheritdoc}
   */
  public function addLocale($locale) {
    $this->locales[] = $locale;
    $this->locales = array_unique($this->locales);
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
  public function preSave(EntityStorageControllerInterface $storage_controller) {
    parent::preSave($storage_controller);

    if ($this->hasLocales()) {
      $config_factory = \Drupal::service('config.factory');
      $properties = $this->getExportProperties();
      $languages = language_list();
      // Check if the suggested language codes are configured.
      foreach ($this->getLocales() as $langcode) {
        if (isset($languages[$langcode])) {
          $config_factory->get('locale.config.' . $langcode . '.system.date_format.' . $this->id())
            ->setData($properties)
            ->save();
        }
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function postDelete(EntityStorageControllerInterface $storage_controller, array $entities) {
    parent::postDelete($storage_controller, $entities);

    // Clean up the localized entry if required.
    if (\Drupal::moduleHandler()->moduleExists('language')) {
      $languages = language_list();
      foreach ($entities as $entity) {
        $format_id = $entity->id();
        foreach ($languages as $langcode => $data) {
          \Drupal::config("locale.config.$langcode.system.date_format.$format_id")->delete();
        }
      }
    }
  }

}
