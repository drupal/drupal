<?php

namespace Drupal\Core\Test\EventSubscriber;

use Drupal\Core\Database\Connection;
use Drupal\Core\Database\Schema;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\Sql\SqlEntityStorageInterface;
use Drupal\Core\Field\FieldStorageDefinitionEvent;
use Drupal\Core\Field\FieldStorageDefinitionEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Response subscriber to field storage events.
 *
 * In Kernel test field storages can be created without the entity schema, on
 * which the field storage is based, not being created. For database driver that
 * store the whole entity instance in a single JSON object, like the database
 * driver for MongoDB is doing, the kernel test will fail.
 *
 * @internal
 */
final class FieldStorageCreateCheckSubscriber implements EventSubscriberInterface {

  /**
   * The schema object for this connection.
   */
  protected Schema $schema;

  /**
   * Constructs the FieldStorageCreateCheckSubscriber object.
   *
   * @param \Drupal\Core\Database\Connection $connection
   *   The database connection.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager service.
   * @param bool $throwLogicExceptionOrDeprecationWarning
   *   When the value is TRUE a LogicException will be thrown, when the value is
   *   FALSE a deprecation warning will be given. The value defaults to FALSE.
   */
  public function __construct(
    protected Connection $connection,
    protected EntityTypeManagerInterface $entityTypeManager,
    private readonly bool $throwLogicExceptionOrDeprecationWarning = FALSE,
  ) {
    $this->schema = $this->connection->schema();
  }

  /**
   * Gets the subscribed events.
   *
   * @return array
   *   An array of subscribed event names.
   *
   * @see \Symfony\Component\EventDispatcher\EventSubscriberInterface::getSubscribedEvents()
   */
  public static function getSubscribedEvents(): array {
    return [
      FieldStorageDefinitionEvents::CREATE => ['onFieldStorageDefinitionCreateEvent'],
    ];
  }

  /**
   * Listener method for any field storage definition create event.
   *
   * @param \Drupal\Core\Field\FieldStorageDefinitionEvent $event
   *   The field storage definition event object.
   * @param string $event_name
   *   The event name.
   */
  public function onFieldStorageDefinitionCreateEvent(FieldStorageDefinitionEvent $event, $event_name): void {
    $entity_type_id = $event->getFieldStorageDefinition()->getTargetEntityTypeId();
    if ($entity_type_id) {
      $storage = $this->entityTypeManager->getStorage($entity_type_id);
      if ($storage instanceof SqlEntityStorageInterface) {
        $base_table = $storage->getTableMapping()->getBaseTable();
        if (!$this->schema->tableExists($base_table)) {
          if ($this->throwLogicExceptionOrDeprecationWarning) {
            throw new \LogicException(sprintf('Creating the "%s" field storage definition without the entity schema "%s" being installed is not allowed.',
              $event->getFieldStorageDefinition()->id(),
              $entity_type_id,
            ));
          }
          else {
            @trigger_error('Creating the "' . $event->getFieldStorageDefinition()->id() . '" field storage definition without the entity schema "' . $entity_type_id . '" being installed is deprecated in drupal:11.2.0 and will be replaced by a LogicException in drupal:12.0.0. See https://www.drupal.org/node/3493981', E_USER_DEPRECATED);
          }
        }
      }
    }
  }

}
