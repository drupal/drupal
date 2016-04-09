<?php

namespace Drupal\field_ui;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides dynamic permissions of the field_ui module.
 */
class FieldUiPermissions implements ContainerInjectionInterface {

  use StringTranslationTrait;

  /**
   * The entity manager.
   *
   * @var \Drupal\Core\Entity\EntityManagerInterface
   */
  protected $entityManager;

  /**
   * Constructs a new FieldUiPermissions instance.
   *
   * @param \Drupal\Core\Entity\EntityManagerInterface $entity_manager
   *   The entity manager.
   */
  public function __construct(EntityManagerInterface $entity_manager) {
    $this->entityManager = $entity_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static($container->get('entity.manager'));
  }

  /**
   * Returns an array of field UI permissions.
   *
   * @return array
   */
  public function fieldPermissions() {
    $permissions = [];

    foreach ($this->entityManager->getDefinitions() as $entity_type_id => $entity_type) {
      if ($entity_type->get('field_ui_base_route')) {
        // Create a permission for each fieldable entity to manage
        // the fields and the display.
        $permissions['administer ' . $entity_type_id . ' fields'] = [
          'title' => $this->t('%entity_label: Administer fields', ['%entity_label' => $entity_type->getLabel()]),
          'restrict access' => TRUE,
        ];
        $permissions['administer ' . $entity_type_id . ' form display'] = [
          'title' => $this->t('%entity_label: Administer form display', ['%entity_label' => $entity_type->getLabel()])
        ];
        $permissions['administer ' . $entity_type_id . ' display'] = [
          'title' => $this->t('%entity_label: Administer display', ['%entity_label' => $entity_type->getLabel()])
        ];
      }
    }

    return $permissions;
  }

}
