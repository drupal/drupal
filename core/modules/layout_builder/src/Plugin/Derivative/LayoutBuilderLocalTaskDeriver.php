<?php

namespace Drupal\layout_builder\Plugin\Derivative;

use Drupal\Component\Plugin\Derivative\DeriverBase;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Plugin\Discovery\ContainerDeriverInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\layout_builder\Plugin\SectionStorage\SectionStorageLocalTaskProviderInterface;
use Drupal\layout_builder\SectionStorage\SectionStorageManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides local task definitions for the layout builder user interface.
 *
 * @todo Remove this in https://www.drupal.org/project/drupal/issues/2936655.
 *
 * @internal
 *   Plugin derivers are internal.
 */
class LayoutBuilderLocalTaskDeriver extends DeriverBase implements ContainerDeriverInterface {

  use StringTranslationTrait;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The section storage manager.
   *
   * @var \Drupal\layout_builder\SectionStorage\SectionStorageManagerInterface
   */
  protected $sectionStorageManager;

  /**
   * Constructs a new LayoutBuilderLocalTaskDeriver.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\layout_builder\SectionStorage\SectionStorageManagerInterface $section_storage_manager
   *   The section storage manager.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, SectionStorageManagerInterface $section_storage_manager) {
    $this->entityTypeManager = $entity_type_manager;
    $this->sectionStorageManager = $section_storage_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, $base_plugin_id) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('plugin.manager.layout_builder.section_storage')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getDerivativeDefinitions($base_plugin_definition) {
    foreach ($this->sectionStorageManager->getDefinitions() as $plugin_id => $definition) {
      $section_storage = $this->sectionStorageManager->loadEmpty($plugin_id);
      if ($section_storage instanceof SectionStorageLocalTaskProviderInterface) {
        $this->derivatives += $section_storage->buildLocalTasks($base_plugin_definition);
      }
    }
    return $this->derivatives;
  }

}
