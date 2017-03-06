<?php

namespace Drupal\KernelTests\Core\Entity;
use Drupal\entity_test\Entity\EntityTestMulRev;
use Drupal\language\Entity\ConfigurableLanguage;

/**
 * Tests translating a non-revisionable field.
 *
 * @group Entity
 */
class EntityNonRevisionableTranslatableFieldTest extends EntityKernelTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = ['entity_test', 'language', 'content_translation'];

  protected function setUp() {
    parent::setUp();

    $this->installEntitySchema('entity_test_mulrev');
    $this->installEntitySchema('configurable_language');

    ConfigurableLanguage::createFromLangcode('es')->save();
  }

  /**
   * Tests translating a non-revisionable field.
   */
  public function testTranslatingNonRevisionableField() {
    /** @var \Drupal\Core\Entity\ContentEntityBase $entity */
    $entity = EntityTestMulRev::create();
    $entity->set('non_rev_field', 'Hello');
    $entity->save();

    $translation = $entity->addTranslation('es');
    $translation->set('non_rev_field', 'Hola');
    $translation->save();

    $reloaded = EntityTestMulRev::load($entity->id());
    $this->assertEquals('Hello', $reloaded->getTranslation('en')->get('non_rev_field')->value);

    $this->assertEquals('Hola', $reloaded->getTranslation('es')->get('non_rev_field')->value);
  }

}
