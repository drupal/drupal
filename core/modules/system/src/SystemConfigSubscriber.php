<?php

namespace Drupal\system;

use Drupal\Core\Config\ConfigCrudEvent;
use Drupal\Core\Config\ConfigEvents;
use Drupal\Core\Config\ConfigImporterEvent;
use Drupal\Core\Routing\RouteBuilderInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * System Config subscriber.
 */
class SystemConfigSubscriber implements EventSubscriberInterface {
  use StringTranslationTrait;

  /**
   * The router builder.
   *
   * @var \Drupal\Core\Routing\RouteBuilderInterface
   */
  protected $routerBuilder;

  /**
   * Constructs the SystemConfigSubscriber.
   *
   * @param \Drupal\Core\Routing\RouteBuilderInterface $router_builder
   *   The router builder service.
   */
  public function __construct(RouteBuilderInterface $router_builder) {
    $this->routerBuilder = $router_builder;
  }

  /**
   * Rebuilds the router when the default or admin theme is changed.
   *
   * @param \Drupal\Core\Config\ConfigCrudEvent $event
   */
  public function onConfigSave(ConfigCrudEvent $event) {
    $saved_config = $event->getConfig();
    if ($saved_config->getName() == 'system.theme' && ($event->isChanged('admin') || $event->isChanged('default'))) {
      $this->routerBuilder->setRebuildNeeded();
    }
  }

  /**
   * Checks that the configuration synchronization is valid.
   *
   * This event listener prevents deleting all configuration. If there is
   * nothing to import then event propagation is stopped because there is no
   * config import to validate.
   *
   * @param \Drupal\Core\Config\ConfigImporterEvent $event
   *   The config import event.
   */
  public function onConfigImporterValidateNotEmpty(ConfigImporterEvent $event) {
    $importList = $event->getConfigImporter()->getStorageComparer()->getSourceStorage()->listAll();
    if (empty($importList)) {
      $event->getConfigImporter()->logError($this->t('This import is empty and if applied would delete all of your configuration, so has been rejected.'));
      $event->stopPropagation();
    }
  }

  /**
   * Checks that the configuration synchronization is valid.
   *
   * This event listener checks that the system.site:uuid's in the source and
   * target match.
   *
   * @param ConfigImporterEvent $event
   *   The config import event.
   */
  public function onConfigImporterValidateSiteUUID(ConfigImporterEvent $event) {
    if (!$event->getConfigImporter()->getStorageComparer()->validateSiteUuid()) {
      $event->getConfigImporter()->logError($this->t('Site UUID in source storage does not match the target storage.'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events[ConfigEvents::SAVE][] = ['onConfigSave', 0];
    // The empty check has a high priority so that it can stop propagation if
    // there is no configuration to import.
    $events[ConfigEvents::IMPORT_VALIDATE][] = ['onConfigImporterValidateNotEmpty', 512];
    $events[ConfigEvents::IMPORT_VALIDATE][] = ['onConfigImporterValidateSiteUUID', 256];
    return $events;
  }

}
