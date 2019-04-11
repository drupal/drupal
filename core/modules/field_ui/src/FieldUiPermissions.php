<?php

namespace Drupal\field_ui;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\DependencyInjection\DeprecatedServicePropertyTrait;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides dynamic permissions of the field_ui module.
 */
class FieldUiPermissions implements ContainerInjectionInterface {

  use StringTranslationTrait;
  use DeprecatedServicePropertyTrait;

  /**
   * {@inheritdoc}
   */
  protected $deprecatedProperties = ['entityManager' => 'entity.manager'];

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructs a new FieldUiPermissions instance.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager) {
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static($container->get('entity_type.manager'));
  }

  /**
   * Returns an array of field UI permissions.
   *
   * @return array
   */
  public function fieldPermissions() {
    $permissions = [];

    foreach ($this->entityTypeManager->getDefinitions() as $entity_type_id => $entity_type) {
      if ($entity_type->get('field_ui_base_route')) {
        // Create a permission for each fieldable entity to manage
        // the fields and the display.
        $permissions['administer ' . $entity_type_id . ' fields'] = [
          'title' => $this->t('%entity_label: Administer fields', ['%entity_label' => $entity_type->getLabel()]),
          'restrict access' => TRUE,
        ];
        $permissions['administer ' . $entity_type_id . ' form display'] = [
          'title' => $this->t('%entity_label: Administer form display', ['%entity_label' => $entity_type->getLabel()]),
        ];
        $permissions['administer ' . $entity_type_id . ' display'] = [
          'title' => $this->t('%entity_label: Administer display', ['%entity_label' => $entity_type->getLabel()]),
        ];
      }
    }

    return $permissions;
  }

}
