<?php

namespace Drupal\KernelTests\Core\Entity;

use Drupal\entity_test\Entity\EntityTestMul;
use Drupal\language\Entity\ConfigurableLanguage;

/**
 * Tests proper cloning of content entities.
 *
 * @group Entity
 */
class ContentEntityCloneTest extends EntityKernelTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = ['language', 'entity_test'];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    // Enable an additional language.
    ConfigurableLanguage::createFromLangcode('de')->save();

    $this->installEntitySchema('entity_test_mul');
  }

  /**
   * Tests if entity references on fields are still correct after cloning.
   */
  public function testFieldEntityReferenceAfterClone() {
    $user = $this->createUser();

    // Create a test entity.
    $entity = EntityTestMul::create([
      'name' => $this->randomString(),
      'user_id' => $user->id(),
      'language' => 'en',
    ]);

    $clone = clone $entity->addTranslation('de');

    $this->assertEqual($entity->getTranslationLanguages(), $clone->getTranslationLanguages(), 'The entity and its clone have the same translation languages.');

    $default_langcode = $entity->getUntranslated()->language()->getId();
    foreach (array_keys($clone->getTranslationLanguages()) as $langcode) {
      $translation = $clone->getTranslation($langcode);
      foreach ($translation->getFields() as $field_name => $field) {
        if ($field->getFieldDefinition()->isTranslatable()) {
          $args = ['%field_name' => $field_name, '%langcode' => $langcode];
          $this->assertEqual($langcode, $field->getEntity()->language()->getId(), format_string('Translatable field %field_name on translation %langcode has correct entity reference in translation %langcode after cloning.', $args));
        }
        else {
          $args = ['%field_name' => $field_name, '%langcode' => $langcode, '%default_langcode' => $default_langcode];
          $this->assertEqual($default_langcode, $field->getEntity()->language()->getId(), format_string('Non translatable field %field_name on translation %langcode has correct entity reference in the default translation %default_langcode after cloning.', $args));
        }
      }
    }
  }

}
