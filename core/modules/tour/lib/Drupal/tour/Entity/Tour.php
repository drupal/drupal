<?php

/**
 * @file
 * Contains \Drupal\tour\Entity\Tour.
 */

namespace Drupal\tour\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBase;
use Drupal\Core\Entity\Annotation\EntityType;
use Drupal\Core\Annotation\Translation;
use Drupal\tour\TipsBag;
use Drupal\tour\TourInterface;

/**
 * Defines the configured tour entity.
 *
 * @EntityType(
 *   id = "tour",
 *   label = @Translation("Tour"),
 *   module = "tour",
 *   controllers = {
 *     "storage" = "Drupal\Core\Config\Entity\ConfigStorageController",
 *     "view_builder" = "Drupal\tour\TourViewBuilder"
 *   },
 *   config_prefix = "tour.tour",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *     "uuid" = "uuid"
 *   }
 * )
 */
class Tour extends ConfigEntityBase implements TourInterface {

  /**
   * The name (plugin ID) of the tour.
   *
   * @var string
   */
  public $id;

  /**
   * The module which this tour is assigned to.
   *
   * @var string
   */
  public $module;

  /**
   * The label of the tour.
   *
   * @var string
   */
  public $label;

  /**
   * The paths in which this tip can be displayed.
   *
   * @var array
   */
  protected $paths = array();

  /**
   * Holds the collection of tips that are attached to this tour.
   *
   * @var \Drupal\tour\TipsBag
   */
  protected $tipsBag;

  /**
   * The array of plugin config, only used for export and to populate the $tipsBag.
   *
   * @var array
   */
  protected $tips = array();

  /**
   * Overrides \Drupal\Core\Config\Entity\ConfigEntityBase::__construct();
   */
  public function __construct(array $values, $entity_type) {
    parent::__construct($values, $entity_type);

    $this->tipsBag = new TipsBag(\Drupal::service('plugin.manager.tour.tip'), $this->tips);
  }

  /**
   * {@inheritdoc}
   */
  public function getPaths() {
    return $this->paths;
  }

  /**
   * {@inheritdoc}
   */
  public function getTip($id) {
    return $this->tipsBag->get($id);
  }

  /**
   * {@inheritdoc}
   */
  public function getTips() {
    $tips = array();
    foreach ($this->tips as $id => $tip) {
      $tips[] = $this->getTip($id);
    }
    uasort($tips, function ($a, $b) {
      if ($a->getWeight() == $b->getWeight()) {
        return 0;
      }
      return ($a->getWeight() < $b->getWeight()) ? -1 : 1;
    });

    \Drupal::moduleHandler()->alter('tour_tips', $tips, $this);
    return array_values($tips);
  }

  /**
   * Overrides \Drupal\Core\Config\Entity\ConfigEntityBase::getExportProperties();
   */
  public function getExportProperties() {
    $properties = parent::getExportProperties();
    $names = array(
      'paths',
      'tips',
    );
    foreach ($names as $name) {
      $properties[$name] = $this->get($name);
    }
    return $properties;
  }

}
