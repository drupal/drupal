<?php

namespace Drupal\search\Hook;

use Drupal\Component\Utility\UrlHelper;
use Drupal\Core\Datetime\DateFormatterInterface;
use Drupal\Core\Hook\Attribute\Hook;
use Drupal\Core\Language\LanguageInterface;
use Drupal\Core\Language\LanguageManagerInterface;

/**
 * Theme hook implementations for search.
 */
class SearchThemeHooks {

  public function __construct(
    protected readonly LanguageManagerInterface $languageManager,
    protected readonly DateFormatterInterface $dateFormatter,
  ) {}

  /**
   * Implements hook_theme().
   */
  #[Hook('theme')]
  public function theme() : array {
    return [
      'search_result' => [
        'variables' => [
          'result' => NULL,
          'plugin_id' => NULL,
        ],
        'initial preprocess' => static::class . ':preprocessSearchResult',
      ],
    ];
  }

  /**
   * Prepares variables for individual search result templates.
   *
   * Default template: search-result.html.twig
   *
   * @param array $variables
   *   An array with the following elements:
   *   - result: Individual search result.
   *   - plugin_id: Plugin the search results came from.
   *   - title_prefix: Additional output populated by modules, intended to be
   *     displayed in front of the main title tag that appears in the template.
   *   - title_suffix: Additional output populated by modules, intended to be
   *     displayed after the main title tag that appears in the template.
   *   - title_attributes: HTML attributes for the title.
   *   - content_attributes: HTML attributes for the content.
   */
  public function preprocessSearchResult(array &$variables): void {
    $language_interface = $this->languageManager->getCurrentLanguage();

    $result = $variables['result'];
    $variables['url'] = UrlHelper::stripDangerousProtocols($result['link']);
    $variables['title'] = $result['title'];
    if (isset($result['langcode']) && $result['langcode'] != $language_interface->getId() && $result['langcode'] != LanguageInterface::LANGCODE_NOT_SPECIFIED) {
      $variables['title_attributes']['lang'] = $result['langcode'];
      $variables['content_attributes']['lang'] = $result['langcode'];
    }

    $info = [];
    if (!empty($result['plugin_id'])) {
      $info['plugin_id'] = $result['plugin_id'];
    }
    if (!empty($result['user'])) {
      $info['user'] = $result['user'];
    }
    if (!empty($result['date'])) {
      $info['date'] = $this->dateFormatter->format($result['date'], 'short');
    }
    if (isset($result['extra']) && is_array($result['extra'])) {
      $info = array_merge($info, $result['extra']);
    }
    // Check for existence. User search does not include snippets.
    $variables['snippet'] = $result['snippet'] ?? '';
    // Provide separated and grouped meta information.
    $variables['info_split'] = $info;
    $variables['info'] = [
      '#type' => 'inline_template',
      '#template' => '{{ info|safe_join(" - ") }}',
      '#context' => ['info' => $info],
    ];
  }

  /**
   * Implements hook_theme_suggestions_HOOK().
   */
  #[Hook('theme_suggestions_search_result')]
  public function themeSuggestionsSearchResult(array $variables): array {
    return [
      'search_result__' . $variables['plugin_id'],
    ];
  }

  /**
   * Implements hook_preprocess_HOOK() for block templates.
   */
  #[Hook('preprocess_block')]
  public function preprocessBlock(&$variables): void {
    if ($variables['plugin_id'] == 'search_form_block') {
      $variables['attributes']['role'] = 'search';
    }
  }

}
