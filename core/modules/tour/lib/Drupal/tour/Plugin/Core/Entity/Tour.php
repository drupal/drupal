<?php

/**
 * @file
 * Contains \Drupal\tour\Plugin\Core\Entity\Tour.
 */

namespace Drupal\tour\Plugin\Core\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBase;
use Drupal\Component\Annotation\Plugin;
use Drupal\Core\Annotation\Translation;
use Drupal\tour\TipsBag;

/**
 * Defines the configured tour entity.
 *
 * @Plugin(
 *   id = "tour",
 *   label = @Translation("Tour"),
 *   module = "tour",
 *   controller_class = "Drupal\Core\Config\Entity\ConfigStorageController",
 *   render_controller_class = "Drupal\tour\TourRenderController",
 *   config_prefix = "tour.tour",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *     "uuid" = "uuid"
 *   }
 * )
 */
class Tour extends ConfigEntityBase {

  /**
   * The name (plugin ID) of the tour.
   *
   * @var string
   */
  public $id;

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

    $this->tipsBag = new TipsBag(drupal_container()->get('plugin.manager.tour.tip'), $this->tips);
  }

  /**
   * Returns label of tour.
   *
   * @return string
   *   The label of the tour.
   */
  public function getLabel() {
    return $this->label;
  }

  /**
   * The paths that this tour will appear on.
   *
   * @return array
   *   Returns array of paths for the tour.
   */
  public function getPaths() {
    return $this->paths;
  }

  /**
   * Returns tip plugin.
   *
   * @return string
   *   The identifier of the tip.
   */
  public function getTip($id) {
    return $this->tipsBag->get($id);
  }

  /**
   * Returns the tips for this tour.
   *
   * @return array
   *   An array of tip plugins.
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

    drupal_container()->get('module_handler')->alter('tour_tips', $tips, $this);
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
