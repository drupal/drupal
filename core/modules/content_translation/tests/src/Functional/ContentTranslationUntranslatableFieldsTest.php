<?php

namespace Drupal\Tests\content_translation\Functional;

use Drupal\Core\Url;
use Drupal\language\Entity\ConfigurableLanguage;

/**
 * Tests the untranslatable fields behaviors.
 *
 * @group content_translation
 */
class ContentTranslationUntranslatableFieldsTest extends ContentTranslationTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['language', 'content_translation', 'entity_test'];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    // Configure one field as untranslatable.
    $this->drupalLogin($this->administrator);
    $edit = [
      'settings[' . $this->entityTypeId . '][' . $this->bundle . '][fields][' . $this->fieldName . ']' => 0,
    ];
    $this->drupalPostForm('admin/config/regional/content-language', $edit, 'Save configuration');

    /** @var \Drupal\Core\Entity\EntityFieldManagerInterface $entity_field_manager */
    $entity_field_manager = $this->container->get('entity_field.manager');
    $entity_field_manager->clearCachedFieldDefinitions();
    $definitions = $entity_field_manager->getFieldDefinitions($this->entityTypeId, $this->bundle);
    $this->assertFalse($definitions[$this->fieldName]->isTranslatable());
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditorPermissions() {
    return array_merge(parent::getTranslatorPermissions(), ['administer entity_test content', 'view test entity']);
  }

  /**
   * Tests that hiding untranslatable field widgets works correctly.
   */
  public function testHiddenWidgets() {
    /** @var \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager */
    $entity_type_manager = $this->container->get('entity_type.manager');
    $id = $this->createEntity([], 'en');
    /** @var \Drupal\Core\Entity\ContentEntityInterface $entity */
    $entity = $entity_type_manager
      ->getStorage($this->entityTypeId)
      ->load($id);

    // Check that the untranslatable field widget is displayed on the edit form
    // and no translatability clue is displayed yet.
    $this->drupalGet($entity->toUrl('edit-form'));
    $field_xpath = '//input[@name="' . $this->fieldName . '[0][value]"]';
    $this->assertNotEmpty($this->xpath($field_xpath));
    $clue_xpath = '//label[@for="edit-' . strtr($this->fieldName, '_', '-') . '-0-value"]/span[text()="(all languages)"]';
    $this->assertEmpty($this->xpath($clue_xpath));

    // Add a translation and check that the untranslatable field widget is
    // displayed on the translation and edit forms along with translatability
    // clues.
    $add_url = Url::fromRoute("entity.{$this->entityTypeId}.content_translation_add", [
      $entity->getEntityTypeId() => $entity->id(),
      'source' => 'en',
      'target' => 'it'
    ]);
    $this->drupalGet($add_url);
    $this->assertNotEmpty($this->xpath($field_xpath));
    $this->assertNotEmpty($this->xpath($clue_xpath));
    $this->drupalPostForm(NULL, [], 'Save');

    // Check that the widget is displayed along with its clue in the edit form
    // for both languages.
    $this->drupalGet($entity->toUrl('edit-form'));
    $this->assertNotEmpty($this->xpath($field_xpath));
    $this->assertNotEmpty($this->xpath($clue_xpath));
    $it_language = ConfigurableLanguage::load('it');
    $this->drupalGet($entity->toUrl('edit-form', ['language' => $it_language]));
    $this->assertNotEmpty($this->xpath($field_xpath));
    $this->assertNotEmpty($this->xpath($clue_xpath));

    // Configure untranslatable field widgets to be hidden on non-default
    // language edit forms.
    $edit = [
      'settings[' . $this->entityTypeId . '][' . $this->bundle . '][settings][content_translation][untranslatable_fields_hide]' => 1,
    ];
    $this->drupalPostForm('admin/config/regional/content-language', $edit, 'Save configuration');

    // Verify that the widget is displayed in the default language edit form,
    // but no clue is displayed.
    $this->drupalGet($entity->toUrl('edit-form'));
    $field_xpath = '//input[@name="' . $this->fieldName . '[0][value]"]';
    $this->assertNotEmpty($this->xpath($field_xpath));
    $this->assertEmpty($this->xpath($clue_xpath));

    // Verify no widget is displayed on the non-default language edit form.
    $this->drupalGet($entity->toUrl('edit-form', ['language' => $it_language]));
    $this->assertEmpty($this->xpath($field_xpath));
    $this->assertEmpty($this->xpath($clue_xpath));

    // Verify a warning is displayed.
    $this->assertSession()->pageTextContains('Fields that apply to all languages are hidden to avoid conflicting changes.');
    $edit_path = $entity->toUrl('edit-form')->toString();
    $link_xpath = '//a[@href=:edit_path and text()="Edit them on the original language form"]';
    $elements = $this->xpath($link_xpath, [':edit_path' => $edit_path]);
    $this->assertNotEmpty($elements);
  }

}
