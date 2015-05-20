<?php

/**
 * @file
 * Contains \Drupal\config_import_test\EventSubscriber.
 */

namespace Drupal\config_import_test;

use Drupal\Core\Config\ConfigCrudEvent;
use Drupal\Core\Config\ConfigEvents;
use Drupal\Core\Config\ConfigImporterEvent;
use Drupal\Core\Config\Importer\MissingContentEvent;
use Drupal\Core\State\StateInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Config import subscriber for config import events.
 */
class EventSubscriber implements EventSubscriberInterface {

  /**
   * The key value store.
   *
   * @var \Drupal\Core\State\StateInterface
   */
  protected $state;

  /**
   * Constructs the event subscriber.
   *
   * @param \Drupal\Core\State\StateInterface $state
   *   The key value store.
   */
  public function __construct(StateInterface $state) {
    $this->state = $state;
  }

  /**
   * Validates the configuration to be imported.
   *
   * @param \Drupal\Core\Config\ConfigImporterEvent $event
   *   The Event to process.
   *
   * @throws \Drupal\Core\Config\ConfigNameException
   */
  public function onConfigImporterValidate(ConfigImporterEvent $event) {
    if ($this->state->get('config_import_test.config_import_validate_fail', FALSE)) {
      // Log more than one error to test multiple validation errors.
      $event->getConfigImporter()->logError('Config import validate error 1.');
      $event->getConfigImporter()->logError('Config import validate error 2.');
    }
  }

  /**
   * Handles the missing content event.
   *
   * @param \Drupal\Core\Config\Importer\MissingContentEvent $event
   *   The missing content event.
   */
  public function onConfigImporterMissingContentOne(MissingContentEvent $event) {
    if ($this->state->get('config_import_test.config_import_missing_content', FALSE) && $this->state->get('config_import_test.config_import_missing_content_one', FALSE) === FALSE) {
      $missing = $event->getMissingContent();
      $uuid = key($missing);
      $this->state->set('config_import_test.config_import_missing_content_one', key($missing));
      $event->resolveMissingContent($uuid);
      // Stopping propagation ensures that onConfigImporterMissingContentTwo
      // will be fired on the next batch step.
      $event->stopPropagation();
    }
  }

  /**
   * Handles the missing content event.
   *
   * @param \Drupal\Core\Config\Importer\MissingContentEvent $event
   *   The missing content event.
   */
  public function onConfigImporterMissingContentTwo(MissingContentEvent $event) {
    if ($this->state->get('config_import_test.config_import_missing_content', FALSE) && $this->state->get('config_import_test.config_import_missing_content_two', FALSE) === FALSE) {
      $missing = $event->getMissingContent();
      $uuid = key($missing);
      $this->state->set('config_import_test.config_import_missing_content_two', key($missing));
      $event->resolveMissingContent($uuid);
    }
  }

  /**
   * Reacts to a config save and records information in state for testing.
   *
   * @param \Drupal\Core\Config\ConfigCrudEvent $event
   */
  public function onConfigSave(ConfigCrudEvent $event) {
    $config = $event->getConfig();
    if ($config->getName() == 'action.settings') {
      $values = $this->state->get('ConfigImportUITest.action.settings.recursion_limit', array());
      $values[] = $config->get('recursion_limit');
      $this->state->set('ConfigImportUITest.action.settings.recursion_limit', $values);
    }

    if ($config->getName() == 'core.extension') {
      $installed = $this->state->get('ConfigImportUITest.core.extension.modules_installed', array());
      $uninstalled = $this->state->get('ConfigImportUITest.core.extension.modules_uninstalled', array());
      $original = $config->getOriginal('module');
      $data = $config->get('module');
      $install = array_diff_key($data, $original);
      if (!empty($install)) {
        $installed[] = key($install);
      }
      $uninstall = array_diff_key($original, $data);
      if (!empty($uninstall)) {
        $uninstalled[] = key($uninstall);
      }

      $this->state->set('ConfigImportUITest.core.extension.modules_installed', $installed);
      $this->state->set('ConfigImportUITest.core.extension.modules_uninstalled', $uninstalled);
    }
  }

  /**
   * Reacts to a config delete and records information in state for testing.
   *
   * @param \Drupal\Core\Config\ConfigCrudEvent $event
   */
  public function onConfigDelete(ConfigCrudEvent $event) {
    $config = $event->getConfig();
    if ($config->getName() == 'action.settings') {
      $value = $this->state->get('ConfigImportUITest.action.settings.delete', 0);
      $this->state->set('ConfigImportUITest.action.settings.delete', $value + 1);
    }
  }

  /**
   * Registers the methods in this class that should be listeners.
   *
   * @return array
   *   An array of event listener definitions.
   */
  static function getSubscribedEvents() {
    $events[ConfigEvents::SAVE][] = array('onConfigSave', 40);
    $events[ConfigEvents::DELETE][] = array('onConfigDelete', 40);
    $events[ConfigEvents::IMPORT_VALIDATE] = array('onConfigImporterValidate');
    $events[ConfigEvents::IMPORT_MISSING_CONTENT] = array(array('onConfigImporterMissingContentOne'), array('onConfigImporterMissingContentTwo', -100));
    return $events;
  }

}
