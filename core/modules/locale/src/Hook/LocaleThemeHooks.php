<?php

namespace Drupal\locale\Hook;

use Drupal\Core\Datetime\DateFormatterInterface;
use Drupal\Core\Hook\Attribute\Hook;
use Drupal\Core\Language\LanguageInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Link;
use Drupal\Core\Routing\RedirectDestinationInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Url;

/**
 * Hook implementations for locale.
 */
class LocaleThemeHooks {

  use StringTranslationTrait;

  public function __construct(
    protected readonly LanguageManagerInterface $languageManager,
    protected readonly DateFormatterInterface $dateFormatter,
    protected readonly RedirectDestinationInterface $redirectDestination,
  ) {}

  /**
   * Implements hook_theme().
   */
  #[Hook('theme')]
  public function theme() : array {
    return [
      'locale_translation_last_check' => [
        'variables' => [
          'last' => NULL,
        ],
        'initial preprocess' => static::class . ':preprocessLocaleTranslationLastCheck',
      ],
      'locale_translation_update_info' => [
        'variables' => [
          'updates' => [],
          'not_found' => [],
        ],
        'initial preprocess' => static::class . ':preprocessLocaleTranslationUpdateInfo',
      ],
    ];
  }

  /**
   * Prepares variables for translation status information templates.
   *
   * Translation status information is displayed per language.
   *
   * Default template: locale-translate-edit-form-strings.html.twig.
   *
   * @param array $variables
   *   An associative array containing:
   *   - updates: The projects which have updates.
   *   - not_found: The projects which updates are not found.
   *
   * @see \Drupal\locale\Form\TranslationStatusForm
   */
  public function preprocessLocaleTranslationUpdateInfo(array &$variables): void {
    foreach ($variables['updates'] as $update) {
      $variables['modules'][] = $update['name'];
    }
  }

  /**
   * Prepares variables for most recent translation update templates.
   *
   * Displays the last time we checked for locale update data. In addition to
   * properly formatting the given timestamp, this function also provides a
   * "Check manually" link that refreshes the available update and redirects
   * back to the same page.
   *
   * Default template: locale-translation-last-check.html.twig.
   *
   * @param array $variables
   *   An associative array containing:
   *   - last: The timestamp when the site last checked for available updates.
   *
   * @see \Drupal\locale\Form\TranslationStatusForm
   */
  public function preprocessLocaleTranslationLastCheck(array &$variables): void {
    $last = $variables['last'];
    $variables['last_checked'] = ($last != NULL);
    $variables['time'] = $variables['last_checked'] ? $this->dateFormatter->formatTimeDiffSince($last) : NULL;
    $variables['link'] = Link::fromTextAndUrl($this->t('Check manually'), Url::fromRoute('locale.check_translation', [], ['query' => $this->redirectDestination->getAsArray()]))->toString();
  }

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
