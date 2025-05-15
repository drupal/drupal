<?php

namespace Drupal\locale\Hook;

use Drupal\Core\Hook\Attribute\Hook;
use Drupal\Core\Language\LanguageInterface;
use Drupal\Core\Language\LanguageManagerInterface;

/**
 * Hook implementations for locale.
 */
class LocaleThemeHooks {

  public function __construct(
    protected readonly LanguageManagerInterface $languageManager,
  ) {}

  /**
   * Implements hook_preprocess_HOOK() for node templates.
   */
  #[Hook('preprocess_node')]
  public function preprocessNode(&$variables): void {
    /** @var \Drupal\node\NodeInterface $node */
    $node = $variables['node'];
    if ($node->language()->getId() != LanguageInterface::LANGCODE_NOT_SPECIFIED) {
      $interface_language = $this->languageManager->getCurrentLanguage();

      $node_language = $node->language();
      if ($node_language->getId() != $interface_language->getId()) {
        // If the node language was different from the page language, we should
        // add markup to identify the language. Otherwise the page language is
        // inherited.
        $variables['attributes']['lang'] = $node_language->getId();
        if ($node_language->getDirection() != $interface_language->getDirection()) {
          // If text direction is different form the page's text direction, add
          // direction information as well.
          $variables['attributes']['dir'] = $node_language->getDirection();
        }
      }
    }
  }

}
