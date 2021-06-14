<?php

namespace Drupal\editor\EventSubscriber;

use Drupal\config_translation\ConfigEntityMapper;
use Drupal\config_translation\Event\ConfigMapperPopulateEvent;
use Drupal\config_translation\Event\ConfigTranslationEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Drupal\Core\Config\ConfigFactoryInterface;

/**
 * Adds configuration names to configuration mapper on POPULATE_MAPPER event.
 */
class EditorConfigTranslationSubscriber implements EventSubscriberInterface {

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * EditorConfigTranslationSubscriber constructor.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The factory for configuration objects.
   */
  public function __construct(ConfigFactoryInterface $config_factory) {
    $this->configFactory = $config_factory;
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events = [];
    if (class_exists('Drupal\config_translation\Event\ConfigTranslationEvents')) {
      $events[ConfigTranslationEvents::POPULATE_MAPPER][] = ['addConfigNames'];
    }
    return $events;
  }

  /**
   * Reacts to the populating of a configuration mapper.
   *
   * @param \Drupal\config_translation\Event\ConfigMapperPopulateEvent $event
   *   The configuration mapper event.
   */
  public function addConfigNames(ConfigMapperPopulateEvent $event) {
    $mapper = $event->getMapper();
    if ($mapper instanceof ConfigEntityMapper && $mapper->getType() == 'filter_format') {
      $editor_config_name = 'editor.editor.' . $mapper->getEntity()->id();
      // Only add the text editor config if it exists, otherwise we assume no
      // editor has been set for this text format.
      if (!$this->configFactory->get($editor_config_name)->isNew()) {
        $mapper->addConfigName($editor_config_name);
      }
    }
  }

}
