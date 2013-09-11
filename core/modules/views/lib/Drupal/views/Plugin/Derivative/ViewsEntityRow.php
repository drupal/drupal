<?php

/**
 * @file
 * Contains \Drupal\views\Plugin\Derivative\ViewsEntityRow.
 */

namespace Drupal\views\Plugin\Derivative;

use Drupal\Core\Plugin\Discovery\ContainerDerivativeInterface;
use Drupal\Core\Entity\EntityManager;
use Drupal\views\ViewsData;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides views row plugin definitions for all non-special entity types.
 *
 * @ingroup views_row_plugins
 *
 * @see \Drupal\views\Plugin\views\row\EntityRow
 */
class ViewsEntityRow implements ContainerDerivativeInterface {

  /**
   * Stores all entity row plugin information.
   *
   * @var array
   */
  protected $derivatives = array();

  /**
   * The base plugin ID that the derivative is for.
   *
   * @var string
   */
  protected $basePluginId;

  /**
   * The entity manager.
   *
   * @var \Drupal\Core\Entity\EntityManager
   */
  protected $entityManager;

  /**
   * The views data service.
   *
   * @var \Drupal\views\ViewsData
   */
  protected $viewsData;

  /**
   * Constructs a ViewsEntityRow object.
   *
   * @param string $base_plugin_id
   *   The base plugin ID.
   * @param \Drupal\Core\Entity\EntityManager $entity_manager
   *   The entity manager.
   * @param \Drupal\views\ViewsData $views_data
   *   The views data service.
   */
  public function __construct($base_plugin_id, EntityManager $entity_manager, ViewsData $views_data) {
    $this->basePluginId = $base_plugin_id;
    $this->entityManager = $entity_manager;
    $this->viewsData = $views_data;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, $base_plugin_id) {
    return new static(
      $base_plugin_id,
      $container->get('entity.manager'),
      $container->get('views.views_data')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getDerivativeDefinition($derivative_id, array $base_plugin_definition) {
    if (!empty($this->derivatives) && !empty($this->derivatives[$derivative_id])) {
      return $this->derivatives[$derivative_id];
    }
    $this->getDerivativeDefinitions($base_plugin_definition);
    return $this->derivatives[$derivative_id];
  }

  /**
   * {@inheritdoc}
   */
  public function getDerivativeDefinitions(array $base_plugin_definition) {
    foreach ($this->entityManager->getDefinitions() as $entity_type => $entity_info) {
      // Just add support for entity types which have a views integration.
      if (isset($entity_info['base_table']) && $this->viewsData->get($entity_info['base_table']) && $this->entityManager->hasController($entity_type, 'render')) {
        $this->derivatives[$entity_type] = array(
          'id' => 'entity:' . $entity_type,
          'provider' => 'views',
          'title' => $entity_info['label'],
          'help' => t('Display the @label', array('@label' => $entity_info['label'])),
          'base' => array($entity_info['base_table']),
          'entity_type' => $entity_type,
          'display_types' => array('normal'),
          'class' => $base_plugin_definition['class'],
        );
      }
    }

    return $this->derivatives;
  }

}
