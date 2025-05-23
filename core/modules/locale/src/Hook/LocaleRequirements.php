<?php

declare(strict_types=1);

namespace Drupal\locale\Hook;

use Drupal\Core\Extension\Requirement\RequirementSeverity;
use Drupal\Core\Hook\Attribute\Hook;
use Drupal\Core\Link;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Url;

/**
 * Requirements for the Locale module.
 */
class LocaleRequirements {

  use StringTranslationTrait;

  /**
   * Implements hook_runtime_requirements().
   */
  #[Hook('runtime_requirements')]
  public function runtime(): array {
    $requirements = [];
    $available_updates = [];
    $untranslated = [];
    $languages = locale_translatable_language_list();

    if ($languages) {
      // Determine the status of the translation updates per language.
      $status = locale_translation_get_status();
      if ($status) {
        foreach ($status as $project) {
          foreach ($project as $langcode => $project_info) {
            if (empty($project_info->type)) {
              $untranslated[$langcode] = $languages[$langcode]->getName();
            }
            elseif ($project_info->type == LOCALE_TRANSLATION_LOCAL || $project_info->type == LOCALE_TRANSLATION_REMOTE) {
              $available_updates[$langcode] = $languages[$langcode]->getName();
            }
          }
        }

        if ($available_updates || $untranslated) {
          if ($available_updates) {
            $requirements['locale_translation'] = [
              'title' => $this->t('Translation update status'),
              'value' => Link::fromTextAndUrl($this->t('Updates available'), Url::fromRoute('locale.translate_status'))->toString(),
              'severity' => RequirementSeverity::Warning,
              'description' => $this->t('Updates available for: @languages. See the <a href=":updates">Available translation updates</a> page for more information.', ['@languages' => implode(', ', $available_updates), ':updates' => Url::fromRoute('locale.translate_status')->toString()]),
            ];
          }
          else {
            $requirements['locale_translation'] = [
              'title' => $this->t('Translation update status'),
              'value' => $this->t('Missing translations'),
              'severity' => RequirementSeverity::Info,
              'description' => $this->t('Missing translations for: @languages. See the <a href=":updates">Available translation updates</a> page for more information.', ['@languages' => implode(', ', $untranslated), ':updates' => Url::fromRoute('locale.translate_status')->toString()]),
            ];
          }
        }
        else {
          $requirements['locale_translation'] = [
            'title' => $this->t('Translation update status'),
            'value' => $this->t('Up to date'),
            'severity' => RequirementSeverity::OK,
          ];
        }
      }
      else {
        $requirements['locale_translation'] = [
          'title' => $this->t('Translation update status'),
          'value' => Link::fromTextAndUrl($this->t('Can not determine status'), Url::fromRoute('locale.translate_status'))->toString(),
          'severity' => RequirementSeverity::Warning,
          'description' => $this->t('No translation status is available. See the <a href=":updates">Available translation updates</a> page for more information.', [':updates' => Url::fromRoute('locale.translate_status')->toString()]),
        ];
      }
    }
    return $requirements;
  }

}
