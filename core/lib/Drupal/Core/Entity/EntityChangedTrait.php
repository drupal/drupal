<?php

/**
 * @file
 * Contains \Drupal\Core\Entity\EntityChangedTrait.
 */

namespace Drupal\Core\Entity;

/**
 * Provides a trait for accessing changed time.
 */
trait EntityChangedTrait {

  /**
   * Returns the timestamp of the last entity change across all translations.
   *
   * @return int
   *   The timestamp of the last entity save operation across all
   *   translations.
   */
  public function getChangedTimeAcrossTranslations() {
    $changed = $this->getUntranslated()->getChangedTime();
    foreach ($this->getTranslationLanguages(FALSE) as $language) {
      $translation_changed = $this->getTranslation($language->getId())->getChangedTime();
      $changed = max($translation_changed, $changed);
    }
    return $changed;
  }

}
