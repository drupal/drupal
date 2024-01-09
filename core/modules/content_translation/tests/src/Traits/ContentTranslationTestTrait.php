<?php

declare(strict_types=1);

namespace Drupal\Tests\content_translation\Traits;

use Drupal\Core\Language\LanguageInterface;
use Drupal\Tests\language\Traits\LanguageTestTrait;

/**
 * Provides an API to programmatically manage content translation in tests.
 */
trait ContentTranslationTestTrait {

  use LanguageTestTrait;

  /**
   * Enables content translation for the given entity type bundle.
   *
   * @param string $entity_type_id
   *   The ID of the entity type.
   * @param string $bundle
   *   The bundle name.
   * @param string|null $default_langcode
   *   The language code to use as the default language.
   */
  public function enableContentTranslation(string $entity_type_id, string $bundle, ?string $default_langcode = LanguageInterface::LANGCODE_SITE_DEFAULT): void {
    static::enableBundleTranslation($entity_type_id, $bundle, $default_langcode);
    $content_translation_manager = $this->container->get('content_translation.manager');
    $content_translation_manager->setEnabled($entity_type_id, $bundle, TRUE);
    $content_translation_manager->setBundleTranslationSettings($entity_type_id, $bundle, [
      'untranslatable_fields_hide' => FALSE,
    ]);
  }

}
