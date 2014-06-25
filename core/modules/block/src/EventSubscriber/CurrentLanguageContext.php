<?php

/**
 * @file
 * Contains \Drupal\block\EventSubscriber\CurrentLanguageContext.
 */

namespace Drupal\block\EventSubscriber;

use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Plugin\Context\Context;
use Drupal\Core\Plugin\Context\ContextDefinition;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Sets the current language as a context.
 */
class CurrentLanguageContext extends BlockConditionContextSubscriberBase {

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
  protected function determineBlockContext() {
    $context = new Context(new ContextDefinition('language', $this->t('Current language')));
    $context->setContextValue($this->languageManager->getCurrentLanguage());
    $this->addContext('language', $context);
  }

}
