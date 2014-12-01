<?php

/**
 * @file
 * Contains \Drupal\Core\Config\Testing\ConfigSchemaChecker.
 */

namespace Drupal\Core\Config\Testing;

use Drupal\Component\Utility\String;
use Drupal\Core\Config\ConfigCrudEvent;
use Drupal\Core\Config\ConfigEvents;
use Drupal\Core\Config\Schema\SchemaCheckTrait;
use Drupal\Core\Config\Schema\SchemaIncompleteException;
use Drupal\Core\Config\TypedConfigManagerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Listens to the config save event and validates schema.
 *
 * If tests have the $strictConfigSchema property set to TRUE this event
 * listener will be added to the container and throw exceptions if configuration
 * is invalid.
 *
 * @see \Drupal\simpletest\WebTestBase::setUp()
 * @see \Drupal\simpletest\KernelTestBase::containerBuild()
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
  protected $checked = array();

  /**
   * Constructs the ConfigSchemaChecker object.
   *
   * @param \Drupal\Core\Config\TypedConfigManagerInterface $typed_manager
   *   The typed config manager.
   */
  public function __construct(TypedConfigManagerInterface $typed_manager) {
    $this->typedManager = $typed_manager;
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
    $saved_config = $event->getConfig();
    $name = $saved_config->getName();
    $data = $saved_config->get();
    $checksum = crc32(serialize($data));
    // Content translation settings cannot be provided schema yet, see
    // https://www.drupal.org/node/2363155
    if ($name != 'content_translation.settings' && !isset($this->checked[$name . ':' . $checksum])) {
      $this->checked[$name . ':' . $checksum] = TRUE;
      $errors = $this->checkConfigSchema($this->typedManager, $name, $data);
      if ($errors === FALSE) {
        throw new SchemaIncompleteException(String::format('No schema for @config_name', array('@config_name' => $name)));
      }
      elseif (is_array($errors)) {
        $text_errors = [];
        foreach ($errors as $key => $error) {
          $text_errors[] = String::format('@key @error', array('@key' => $key, '@error' => $error));
        }
        throw new SchemaIncompleteException(String::format('Schema errors for @config_name with the following errors: @errors', array('@config_name' => $name, '@errors' => implode(', ', $text_errors))));
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events[ConfigEvents::SAVE][] = array('onConfigSave', 255);
    return $events;
  }

}
