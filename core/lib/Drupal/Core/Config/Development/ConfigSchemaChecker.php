<?php

namespace Drupal\Core\Config\Development;

use Drupal\Component\Utility\Crypt;
use Drupal\Component\Render\FormattableMarkup;
use Drupal\Core\Config\ConfigCrudEvent;
use Drupal\Core\Config\ConfigEvents;
use Drupal\Core\Config\Schema\SchemaCheckTrait;
use Drupal\Core\Config\Schema\SchemaIncompleteException;
use Drupal\Core\Config\StorageInterface;
use Drupal\Core\Config\TypedConfigManagerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Listens to the config save event and validates schema.
 *
 * If tests have the $strictConfigSchema property set to TRUE this event
 * listener will be added to the container and throw exceptions if configuration
 * is invalid.
 *
 * @see \Drupal\KernelTests\KernelTestBase::register()
 * @see \Drupal\Core\Test\FunctionalTestSetupTrait::prepareSettings()
 */
class ConfigSchemaChecker implements EventSubscriberInterface {
  use SchemaCheckTrait;

  /**
   * The typed config manger.
   *
   * @var \Drupal\Core\Config\TypedConfigManagerInterface
   */
  protected $typedManager;

  /**
   * An array of config checked already. Keyed by config name and a checksum.
   *
   * @var array
   */
  protected $checked = [];

  /**
   * An array of config object names that are excluded from schema checking.
   *
   * @var string[]
   */
  protected $exclude = [];

  /**
   * Constructs the ConfigSchemaChecker object.
   *
   * @param \Drupal\Core\Config\TypedConfigManagerInterface $typed_manager
   *   The typed config manager.
   * @param string[] $exclude
   *   An array of config object names that are excluded from schema checking.
   */
  public function __construct(TypedConfigManagerInterface $typed_manager, array $exclude = []) {
    $this->typedManager = $typed_manager;
    $this->exclude = $exclude;
  }

  /**
   * Checks that configuration complies with its schema on config save.
   *
   * @param \Drupal\Core\Config\ConfigCrudEvent $event
   *   The configuration event.
   *
   * @throws \Drupal\Core\Config\Schema\SchemaIncompleteException
   *   Exception thrown when configuration does not match its schema.
   */
  public function onConfigSave(ConfigCrudEvent $event) {
    // Only validate configuration if in the default collection. Other
    // collections may have incomplete configuration (for example language
    // overrides only). These are not valid in themselves.
    $saved_config = $event->getConfig();
    if ($saved_config->getStorage()->getCollectionName() != StorageInterface::DEFAULT_COLLECTION) {
      return;
    }

    $name = $saved_config->getName();
    $data = $saved_config->get();
    $checksum = Crypt::hashBase64(serialize($data));
    if (!in_array($name, $this->exclude) && !isset($this->checked[$name . ':' . $checksum])) {
      $this->checked[$name . ':' . $checksum] = TRUE;
      $errors = $this->checkConfigSchema($this->typedManager, $name, $data);
      if ($errors === FALSE) {
        throw new SchemaIncompleteException("No schema for $name");
      }
      elseif (is_array($errors)) {
        $text_errors = [];
        foreach ($errors as $key => $error) {
          $text_errors[] = new FormattableMarkup('@key @error', ['@key' => $key, '@error' => $error]);
        }
        throw new SchemaIncompleteException("Schema errors for $name with the following errors: " . implode(', ', $text_errors));
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents(): array {
    $events[ConfigEvents::SAVE][] = ['onConfigSave', 255];
    return $events;
  }

}
