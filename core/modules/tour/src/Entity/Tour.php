<?php

namespace Drupal\tour\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBase;
use Drupal\tour\TipsPluginCollection;
use Drupal\tour\TourInterface;

/**
 * Defines the configured tour entity.
 *
 * @ConfigEntityType(
 *   id = "tour",
 *   label = @Translation("Tour"),
 *   label_collection = @Translation("Tours"),
 *   label_singular = @Translation("tour"),
 *   label_plural = @Translation("tours"),
 *   label_count = @PluralTranslation(
 *     singular = "@count tour",
 *     plural = "@count tours",
 *   ),
 *   handlers = {
 *     "view_builder" = "Drupal\tour\TourViewBuilder",
 *     "access" = "Drupal\tour\TourAccessControlHandler",
 *   },
 *   admin_permission = "administer site configuration",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label"
 *   },
 *   config_export = {
 *     "id",
 *     "label",
 *     "module",
 *     "routes",
 *     "tips",
 *   },
 *   lookup_keys = {
 *     "routes.*.route_name"
 *   }
 * )
 */
class Tour extends ConfigEntityBase implements TourInterface {

  /**
   * The name (plugin ID) of the tour.
   *
   * @var string
   */
  protected $id;

  /**
   * The module which this tour is assigned to.
   *
   * @var string
   */
  protected $module;

  /**
   * The label of the tour.
   *
   * @var string
   */
  protected $label;

  /**
   * The routes on which this tour should be displayed.
   *
   * @var array
   */
  protected $routes = [];

  /**
   * The routes on which this tour should be displayed, keyed by route id.
   *
   * @var array
   */
  protected $keyedRoutes;

  /**
   * Holds the collection of tips that are attached to this tour.
   *
   * @var \Drupal\tour\TipsPluginCollection
   */
  protected $tipsCollection;

  /**
   * The array of plugin config, only used for export and to populate the $tipsCollection.
   *
   * @var array
   */
  protected $tips = [];

  /**
   * {@inheritdoc}
   */
  public function __construct(array $values, $entity_type) {
    parent::__construct($values, $entity_type);

    $this->tipsCollection = new TipsPluginCollection(\Drupal::service('plugin.manager.tour.tip'), $this->tips);
  }

  /**
   * {@inheritdoc}
   */
  public function getRoutes() {
    return $this->routes;
  }

  /**
   * {@inheritdoc}
   */
  public function getTip($id) {
    return $this->tipsCollection->get($id);
  }

  /**
   * {@inheritdoc}
   */
  public function getTips() {
    $tips = [];
    foreach ($this->tips as $id => $tip) {
      $tips[] = $this->getTip($id);
    }
    uasort($tips, function ($a, $b) {
      return $a->getWeight() <=> $b->getWeight();
    });

    \Drupal::moduleHandler()->alter('tour_tips', $tips, $this);
    return array_values($tips);
  }

  /**
   * {@inheritdoc}
   */
  public function getModule() {
    return $this->module;
  }

  /**
   * {@inheritdoc}
   */
  public function hasMatchingRoute($route_name, $route_params) {
    if (!isset($this->keyedRoutes)) {
      $this->keyedRoutes = [];
      foreach ($this->getRoutes() as $route) {
        $this->keyedRoutes[$route['route_name']] = $route['route_params'] ?? [];
      }
    }
    if (!isset($this->keyedRoutes[$route_name])) {
      // We don't know about this route.
      return FALSE;
    }
    if (empty($this->keyedRoutes[$route_name])) {
      // We don't need to worry about route params, the route name is enough.
      return TRUE;
    }
    foreach ($this->keyedRoutes[$route_name] as $key => $value) {
      // If a required param is missing or doesn't match, return FALSE.
      if (empty($route_params[$key]) || $route_params[$key] !== $value) {
        return FALSE;
      }
    }
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function resetKeyedRoutes() {
    unset($this->keyedRoutes);
  }

  /**
   * {@inheritdoc}
   */
  public function calculateDependencies() {
    parent::calculateDependencies();

    foreach ($this->tipsCollection as $instance) {
      $definition = $instance->getPluginDefinition();
      $this->addDependency('module', $definition['provider']);
    }

    $this->addDependency('module', $this->module);
    return $this;
  }

}
