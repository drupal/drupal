<?php

namespace Drupal\entity_test_update\EventSubscriber;

use Drupal\Core\Entity\EntityDefinitionUpdateManagerInterface;
use Drupal\Core\Entity\EntityTypeEventSubscriberTrait;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\EntityTypeListenerInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\State\StateInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Defines a class for listening to entity schema changes.
 */
class EntitySchemaSubscriber implements EntityTypeListenerInterface, EventSubscriberInterface {

  use EntityTypeEventSubscriberTrait;

  /**
   * The entity definition update manager.
   *
   * @var \Drupal\Core\Entity\EntityDefinitionUpdateManagerInterface
   */
  protected $entityDefinitionUpdateManager;

  /**
   * The state service.
   *
   * @var \Drupal\Core\State\StateInterface
   */
  protected $state;

  /**
   * Constructs a new EntitySchemaSubscriber.
   *
   * @param \Drupal\Core\Entity\EntityDefinitionUpdateManagerInterface $entityDefinitionUpdateManager
   *   The entity definition update manager.
   * @param \Drupal\Core\State\StateInterface $state
   *   The state service.
   */
  public function __construct(EntityDefinitionUpdateManagerInterface $entityDefinitionUpdateManager, StateInterface $state) {
    $this->entityDefinitionUpdateManager = $entityDefinitionUpdateManager;
    $this->state = $state;
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    return static::getEntityTypeEvents();
  }

  /**
   * {@inheritdoc}
   */
  public function onEntityTypeUpdate(EntityTypeInterface $entity_type, EntityTypeInterface $original) {
    // Only add the new base field when a test needs it.
    if (!$this->state->get('entity_test_update.install_new_base_field_during_update', FALSE)) {
      return;
    }

    // Add a new base field when the entity type is updated.
    $definitions = $this->state->get('entity_test_update.additional_base_field_definitions', []);
    $definitions['new_base_field'] = BaseFieldDefinition::create('string')
      ->setName('new_base_field')
      ->setLabel(new TranslatableMarkup('A new base field'));
    $this->state->set('entity_test_update.additional_base_field_definitions', $definitions);

    $this->entityDefinitionUpdateManager->installFieldStorageDefinition('new_base_field', 'entity_test_update', 'entity_test_update', $definitions['new_base_field']);
  }

}
