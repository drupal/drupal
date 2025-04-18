<?php

namespace Drupal\Core\Config\Development;

use Drupal\Core\Config\ConfigCrudEvent;
use Drupal\Core\Config\Schema\SchemaIncompleteException;
use Drupal\Core\Config\TypedConfigManagerInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Psr\Log\LoggerInterface;

/**
 * Listens to the config save event and warns about invalid schema.
 */
class LenientConfigSchemaChecker extends ConfigSchemaChecker {

  /**
   * Constructs the ConfigSchemaChecker object.
   *
   * @param \Drupal\Core\Config\TypedConfigManagerInterface $typed_manager
   *   The typed config manager.
   * @param \Drupal\Core\Messenger\MessengerInterface $messenger
   *   The messenger service to display the warning.
   * @param \Psr\Log\LoggerInterface $logger
   *   The logger to save the warning.
   * @param string[] $exclude
   *   An array of config object names that are excluded from schema checking.
   */
  public function __construct(TypedConfigManagerInterface $typed_manager, protected readonly MessengerInterface $messenger, protected readonly LoggerInterface $logger, array $exclude = []) {
    parent::__construct($typed_manager, $exclude);
  }

  /**
   * Checks that configuration complies with its schema on config save.
   *
   * @param \Drupal\Core\Config\ConfigCrudEvent $event
   *   The configuration event.
   */
  public function onConfigSave(ConfigCrudEvent $event): void {
    try {
      parent::onConfigSave($event);
    }
    catch (SchemaIncompleteException $exception) {
      $message = sprintf('%s. These errors mean there is configuration that does not comply with its schema. This is not a fatal error, but it is recommended to fix these issues. For more information on configuration schemas, check out <a href="%s">the documentation</a>.', $exception->getMessage(), 'https://www.drupal.org/docs/drupal-apis/configuration-api/configuration-schemametadata');

      $this->messenger->addWarning($message);
      $this->logger->warning($message);
    }
  }

}
