<?php

declare(strict_types=1);

namespace Drupal\language_test\Hook;

use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Url;
use Drupal\language\Plugin\LanguageNegotiation\LanguageNegotiationUI;
use Drupal\Core\Language\LanguageInterface;
use Drupal\Core\Hook\Attribute\Hook;

/**
 * Hook implementations for language_test.
 */
class LanguageTestHooks {

  use StringTranslationTrait;

  /**
   * Implements hook_page_top().
   */
  #[Hook('page_top')]
  public function pageTop(): void {
    if (\Drupal::moduleHandler()->moduleExists('language')) {
      $this->storeLanguageNegotiation();
      \Drupal::messenger()->addStatus($this->t('Language negotiation method: @name', [
        '@name' => \Drupal::languageManager()->getNegotiatedLanguageMethod() ?? 'Not defined',
      ]));
    }
  }

  /**
   * Implements hook_language_types_info().
   */
  #[Hook('language_types_info')]
  public function languageTypesInfo(): array {
    if (\Drupal::keyValue('language_test')->get('language_types')) {
      return [
        'test_language_type' => [
          'name' => $this->t('Test'),
          'description' => $this->t('A test language type.'),
        ],
        'fixed_test_language_type' => [
          'fixed' => [
            'test_language_negotiation_method',
          ],
          'locked' => TRUE,
        ],
      ];
    }
    return [];
  }

  /**
   * Implements hook_language_types_info_alter().
   */
  #[Hook('language_types_info_alter')]
  public function languageTypesInfoAlter(array &$language_types): void {
    if (\Drupal::keyValue('language_test')->get('content_language_type')) {
      $language_types[LanguageInterface::TYPE_CONTENT]['locked'] = FALSE;
      unset($language_types[LanguageInterface::TYPE_CONTENT]['fixed']);
      // By default languages are not configurable. Make
      // LanguageInterface::TYPE_CONTENT configurable.
      $config = \Drupal::configFactory()->getEditable('language.types');
      $configurable = $config->get('configurable');
      if (!in_array(LanguageInterface::TYPE_CONTENT, $configurable)) {
        $configurable[] = LanguageInterface::TYPE_CONTENT;
        $config->set('configurable', $configurable)->save();
      }
    }
  }

  /**
   * Implements hook_language_negotiation_info_alter().
   */
  #[Hook('language_negotiation_info_alter')]
  public function languageNegotiationInfoAlter(array &$negotiation_info): void {
    if (\Drupal::keyValue('language_test')->get('language_negotiation_info_alter')) {
      unset($negotiation_info[LanguageNegotiationUI::METHOD_ID]);
    }
  }

  /**
   * Implements hook_language_fallback_candidates_alter().
   */
  #[Hook('language_fallback_candidates_alter')]
  public function languageFallbackCandidatesAlter(array &$candidates, array $context): void {
    if (\Drupal::state()->get('language_test.fallback_alter.candidates')) {
      unset($candidates[LanguageInterface::LANGCODE_NOT_SPECIFIED]);
    }
  }

  /**
   * Implements hook_language_fallback_candidates_OPERATION_alter().
   */
  #[Hook('language_fallback_candidates_test_alter')]
  public function languageFallbackCandidatesTestAlter(array &$candidates, array $context): void {
    if (\Drupal::state()->get('language_test.fallback_operation_alter.candidates')) {
      $langcode = LanguageInterface::LANGCODE_NOT_APPLICABLE;
      $candidates[$langcode] = $langcode;
    }
  }

  /**
   * Implements hook_module_preinstall().
   */
  #[Hook('module_preinstall')]
  public function modulePreinstall(): void {
    \Drupal::state()->set('language_test.language_count_preinstall', count(\Drupal::languageManager()->getLanguages()));
  }

  /**
   * Implements hook_language_switch_links_alter().
   */
  #[Hook('language_switch_links_alter')]
  public function languageSwitchLinksAlter(array &$links, $type, Url $url): void {
    // Record which languages had links passed in.
    \Drupal::state()->set('language_test.language_switch_link_ids', array_keys($links));
  }

  /**
   * Store the last negotiated languages.
   */
  public function storeLanguageNegotiation(): void {
    $last = [];
    foreach (\Drupal::languageManager()->getDefinedLanguageTypes() as $type) {
      $last[$type] = \Drupal::languageManager()->getCurrentLanguage($type)->getId();
    }
    \Drupal::keyValue('language_test')->set('language_negotiation_last', $last);
  }

}
