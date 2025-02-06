<?php

declare(strict_types=1);

namespace Drupal\module_installer_config_subscriber\EventSubscriber;

use Drupal\Core\Config\ConfigCrudEvent;
use Drupal\Core\Config\ConfigEvents;
use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\KeyValueStore\KeyValueFactoryInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Test subscriber for configuration CRUD event.
 */
class ModuleInstallConfigTestSubscriber implements EventSubscriberInterface {

  public function __construct(
    protected readonly EntityTypeManagerInterface $entityTypeManager,
    protected readonly Connection $connection,
    #[Autowire(service: 'keyvalue')]
    protected readonly KeyValueFactoryInterface $keyValue,
  ) {}

  /**
   * Reacts to a test simple configuration object being installed.
   *
   * @param \Drupal\Core\Config\ConfigCrudEvent $event
   *   The ConfigCrudEvent to process.
   */
  public function onSave(ConfigCrudEvent $event): void {
    if (($event->getConfig()->getName() !== 'module_installer_config_subscriber.settings') ||
        !($definition = $this->entityTypeManager->getDefinition('node', FALSE))) {
      return;
    }

    $table = $definition->get('data_table');
    if (!$this->connection->schema()->tableExists($table)) {
      $this->keyValue->get('module_installer_config_subscriber')->set('node_tables_missing', TRUE);
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents(): array {
    $events[ConfigEvents::SAVE][] = ['onSave'];
    return $events;
  }

}
