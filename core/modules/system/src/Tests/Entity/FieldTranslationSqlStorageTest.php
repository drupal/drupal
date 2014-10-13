<?php

/**
 * @file
 * Definition of Drupal\system\Tests\Entity\FieldTranslationSqlStorageTest.
 */

namespace Drupal\system\Tests\Entity;

use Drupal\Core\Entity\FieldableEntityInterface;
use Drupal\Core\Language\LanguageInterface;
use Drupal\field\Entity\FieldStorageConfig;

/**
 * Tests Field translation SQL Storage.
 *
 * @group Entity
 */
class FieldTranslationSqlStorageTest extends EntityLanguageTestBase {

  /**
   * Tests field SQL storage.
   */
  public function testFieldSqlStorage() {
    $entity_type = 'entity_test_mul';

    $controller = $this->entityManager->getStorage($entity_type);
    $values = array(
      $this->field_name => $this->randomMachineName(),
      $this->untranslatable_field_name => $this->randomMachineName(),
    );
    $entity = $controller->create($values);
    $entity->save();

    // Tests that when changing language field language codes are still correct.
    $langcode = $this->langcodes[0];
    $entity->langcode->value = $langcode;
    $entity->save();
    $this->assertFieldStorageLangcode($entity, 'Field language successfully changed from language neutral.');
    $langcode = $this->langcodes[1];
    $entity->langcode->value = $langcode;
    $entity->save();
    $this->assertFieldStorageLangcode($entity, 'Field language successfully changed.');
    $langcode = LanguageInterface::LANGCODE_NOT_SPECIFIED;
    $entity->langcode->value = $langcode;
    $entity->save();
    $this->assertFieldStorageLangcode($entity, 'Field language successfully changed to language neutral.');

    // Test that after switching field translatability things keep working as
    // before.
    $this->toggleFieldTranslatability($entity_type, $entity_type);
    $entity = $this->reloadEntity($entity);
    foreach (array($this->field_name, $this->untranslatable_field_name) as $field_name) {
      $this->assertEqual($entity->get($field_name)->value, $values[$field_name], 'Field language works as expected after switching translatability.');
    }

    // Test that after disabling field translatability translated values are not
    // loaded.
    $this->toggleFieldTranslatability($entity_type, $entity_type);
    $entity = $this->reloadEntity($entity);
    $entity->langcode->value = $this->langcodes[0];
    $translation = $entity->addTranslation($this->langcodes[1]);
    $translated_value = $this->randomMachineName();
    $translation->get($this->field_name)->value = $translated_value;
    $translation->save();
    $this->toggleFieldTranslatability($entity_type, $entity_type);
    $entity = $this->reloadEntity($entity);
    $this->assertEqual($entity->getTranslation($this->langcodes[1])->get($this->field_name)->value, $values[$this->field_name], 'Existing field translations are not loaded for untranslatable fields.');
  }

  /**
   * Checks whether field languages are correctly stored for the given entity.
   *
   * @param \Drupal\Core\Entity\FieldableEntityInterface $entity
   *   The entity fields are attached to.
   * @param string $message
   *   (optional) A message to display with the assertion.
   */
  protected function assertFieldStorageLangcode(FieldableEntityInterface $entity, $message = '') {
    $status = TRUE;
    $entity_type = $entity->getEntityTypeId();
    $id = $entity->id();
    $langcode = $entity->getUntranslated()->language()->getId();
    $fields = array($this->field_name, $this->untranslatable_field_name);
    /** @var \Drupal\Core\Entity\Sql\DefaultTableMapping $table_mapping */
    $table_mapping = \Drupal::entityManager()->getStorage($entity_type)->getTableMapping();

    foreach ($fields as $field_name) {
      $field_storage = FieldStorageConfig::loadByName($entity_type, $field_name);
      $table = $table_mapping->getDedicatedDataTableName($field_storage);

      $record = \Drupal::database()
        ->select($table, 'f')
        ->fields('f')
        ->condition('f.entity_id', $id)
        ->condition('f.revision_id', $id)
        ->execute()
        ->fetchObject();

      if ($record->langcode != $langcode) {
        $status = FALSE;
        break;
      }
    }

    return $this->assertTrue($status, $message);
  }

}
