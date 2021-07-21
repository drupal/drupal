<?php

namespace Drupal\field;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\EntityDisplayRepositoryInterface;
use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Rebuilds all form and view modes for a passed entity bundle.
 *
 * @see field_field_config_insert()
 *
 * @internal
 */
class EntityDisplayRebuilder implements ContainerInjectionInterface {

  /**
   * The field storage config storage.
   *
   * @var \Drupal\Core\Entity\EntityTypeManager
   */
  protected $entityTypeManager;

  /**
   * The display repository.
   *
   * @var \Drupal\Core\Entity\EntityDisplayRepository
   */
  protected $entityDisplayRepository;

  /**
   * The entity type bundle info.
   *
   * @var \Drupal\Core\Entity\EntityTypeBundleInfoInterface
   */
  protected $entityTypeBundleInfo;

  /**
   * Constructs a new EntityDisplayRebuilder.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity manager.
   * @param \Drupal\Core\Entity\EntityDisplayRepositoryInterface $entity_display_repository
   *   The entity display repository.
   * @param \Drupal\Core\Entity\EntityTypeBundleInfoInterface $entity_type_bundle_info
   *   The entity type bundle info.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, EntityDisplayRepositoryInterface $entity_display_repository, EntityTypeBundleInfoInterface $entity_type_bundle_info) {
    $this->entityTypeManager = $entity_type_manager;
    $this->entityDisplayRepository = $entity_display_repository;
    $this->entityTypeBundleInfo = $entity_type_bundle_info;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('entity_display.repository'),
      $container->get('entity_type.bundle.info')
    );
  }

  /**
   * Rebuild displays for single Entity Type.
   *
   * @param string $entity_type_id
   *   The entity type machine name.
   * @param string $bundle
   *   The bundle we need to rebuild.
   */
  public function rebuildEntityTypeDisplays($entity_type_id, $bundle) {
    // Get the displays.
    $view_modes = $this->entityDisplayRepository->getViewModeOptions($entity_type_id);
    $form_modes = $this->entityDisplayRepository->getFormModeOptions($entity_type_id);

    // Save view mode displays.
    $view_mode_ids = array_map(function ($view_mode) use ($entity_type_id, $bundle) {
      return "$entity_type_id.$bundle.$view_mode";
    }, array_keys($view_modes));
    foreach ($this->entityTypeManager->getStorage('entity_view_display')->loadMultiple($view_mode_ids) as $display) {
      $display->save();
    }
    // Save form mode displays.
    $form_mode_ids = array_map(function ($form_mode) use ($entity_type_id, $bundle) {
      return "$entity_type_id.$bundle.$form_mode";
    }, array_keys($form_modes));
    foreach ($this->entityTypeManager->getStorage('entity_form_display')->loadMultiple($form_mode_ids) as $display) {
      $display->save();
    }
  }

}
