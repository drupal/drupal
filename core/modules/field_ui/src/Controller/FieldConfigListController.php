<?php

namespace Drupal\field_ui\Controller;

use Drupal\Core\Entity\Controller\EntityListController;
use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines a controller to list field instances.
 */
class FieldConfigListController extends EntityListController {

  /**
   * The entity type bundle info service.
   *
   * @var \Drupal\Core\Entity\EntityTypeBundleInfoInterface
   */
  protected $entityTypeBundleInfo;

  /**
   * Creates a FieldConfigListController instance.
   *
   * @param \Drupal\Core\Entity\EntityTypeBundleInfoInterface $entity_type_bundle_info
   *   The entity type bundle info service.
   */
  public function __construct(EntityTypeBundleInfoInterface $entity_type_bundle_info) {
    $this->entityTypeBundleInfo = $entity_type_bundle_info;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.bundle.info')
    );
  }

  /**
   * Shows the 'Manage fields' page.
   *
   * @param string $entity_type_id
   *   The entity type.
   * @param string $bundle
   *   The entity bundle.
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The current route match.
   *
   * @return array
   *   A render array as expected by
   *   \Drupal\Core\Render\RendererInterface::render().
   */
  public function listing($entity_type_id = NULL, $bundle = NULL, RouteMatchInterface $route_match = NULL) {
    $build = $this->entityTypeManager()->getListBuilder('field_config')->render($entity_type_id, $bundle);

    $build['#title'] = $this->title($entity_type_id, $bundle);

    return $build;
  }

  /**
   * Provides the title for the 'Manage fields' page.
   *
   * @return string
   *   The title.
   */
  protected function title($entity_type_id, $bundle) {
    $bundle_info = $this->entityTypeBundleInfo->getBundleInfo($entity_type_id);

    return $this->t('Manage fields: @bundle-label', [
      '@bundle-label' => $bundle_info[$bundle]['label'],
    ]);
  }

}
