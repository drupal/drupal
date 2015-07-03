<?php

/**
 * @file
 * Contains \Drupal\block\EventSubscriber\CurrentLanguageContext.
 */

namespace Drupal\block\EventSubscriber;

use Drupal\block\Event\BlockContextEvent;
use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Plugin\Context\Context;
use Drupal\Core\Plugin\Context\ContextDefinition;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Sets the current language as a context.
 */
class CurrentLanguageContext extends BlockContextSubscriberBase {

  use StringTranslationTrait;

  /**
   * The language manager.
   *
   * @var \Drupal\Core\Language\LanguageManagerInterface
   */
  protected $languageManager;

  /**
   * Constructs a new CurrentLanguageContext.
   *
   * @param \Drupal\Core\Language\LanguageManagerInterface
   *   The language manager.
   */
  public function __construct(LanguageManagerInterface $language_manager) {
    $this->languageManager = $language_manager;
  }

  /**
   * {@inheritdoc}
   */
  public function onBlockActiveContext(BlockContextEvent $event) {
    // Add a context for each language type.
    $language_types = $this->languageManager->getLanguageTypes();
    $info = $this->languageManager->getDefinedLanguageTypesInfo();
    foreach ($language_types as $type_key) {
      if (isset($info[$type_key]['name'])) {
        $context = new Context(new ContextDefinition('language', $info[$type_key]['name']));
        $context->setContextValue($this->languageManager->getCurrentLanguage($type_key));

        $cacheability = new CacheableMetadata();
        $cacheability->setCacheContexts(['languages:' . $type_key]);
        $context->addCacheableDependency($cacheability);

        $event->setContext('language.' . $type_key, $context);
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function onBlockAdministrativeContext(BlockContextEvent $event) {
    $this->onBlockActiveContext($event);
  }

}
