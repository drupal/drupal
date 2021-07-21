<?php

namespace Drupal\Tests\content_translation\Functional;

use Drupal\Core\Url;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\language\Entity\ConfigurableLanguage;

/**
 * Tests the untranslatable fields behaviors.
 *
 * @group content_translation
 */
class ContentTranslationUntranslatableFieldsTest extends ContentTranslationPendingRevisionTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['field_test'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    // Configure one field as untranslatable.
    $this->drupalLogin($this->administrator);
    $edit = [
      'settings[' . $this->entityTypeId . '][' . $this->bundle . '][fields][' . $this->fieldName . ']' => 0,
    ];
    $this->drupalGet('admin/config/regional/content-language');
    $this->submitForm($edit, 'Save configuration');

    /** @var \Drupal\Core\Entity\EntityFieldManagerInterface $entity_field_manager */
    $entity_field_manager = $this->container->get('entity_field.manager');
    $entity_field_manager->clearCachedFieldDefinitions();
    $definitions = $entity_field_manager->getFieldDefinitions($this->entityTypeId, $this->bundle);
    $this->assertFalse($definitions[$this->fieldName]->isTranslatable());
  }

  /**
   * {@inheritdoc}
   */
  protected function setupTestFields() {
    parent::setupTestFields();

    $field_storage = FieldStorageConfig::create([
      'field_name' => 'field_multilingual',
      'type' => 'test_field',
      'entity_type' => $this->entityTypeId,
      'cardinality' => 1,
    ]);
    $field_storage->save();
    FieldConfig::create([
      'field_storage' => $field_storage,
      'bundle' => $this->bundle,
      'label' => 'Untranslatable-but-visible test field',
      'translatable' => FALSE,
    ])->save();
    \Drupal::service('entity_display.repository')->getFormDisplay($this->entityTypeId, $this->bundle, 'default')
      ->setComponent('field_multilingual', [
        'type' => 'test_field_widget_multilingual',
      ])
      ->save();
  }

  /**
   * Tests that hiding untranslatable field widgets works correctly.
   */
  public function testHiddenWidgets() {
    /** @var \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager */
    $entity_type_manager = $this->container->get('entity_type.manager');
    $id = $this->createEntity(['title' => $this->randomString()], 'en');
    /** @var \Drupal\Core\Entity\ContentEntityInterface $entity */
    $entity = $entity_type_manager
      ->getStorage($this->entityTypeId)
      ->load($id);

    // Check that the untranslatable field widget is displayed on the edit form
    // and no translatability clue is displayed yet.
    $en_edit_url = $entity->toUrl('edit-form');
    $this->drupalGet($en_edit_url);
    $field_xpath = '//input[@name="' . $this->fieldName . '[0][value]"]';
    $this->assertNotEmpty($this->xpath($field_xpath));
    $clue_xpath = '//label[@for="edit-' . strtr($this->fieldName, '_', '-') . '-0-value"]/span[text()="(all languages)"]';
    $this->assertEmpty($this->xpath($clue_xpath));
    $this->assertSession()->pageTextContains('Untranslatable-but-visible test field');

    // Add a translation and check that the untranslatable field widget is
    // displayed on the translation and edit forms along with translatability
    // clues.
    $add_url = Url::fromRoute("entity.{$this->entityTypeId}.content_translation_add", [
      $entity->getEntityTypeId() => $entity->id(),
      'source' => 'en',
      'target' => 'it',
    ]);
    $this->drupalGet($add_url);
    $this->assertNotEmpty($this->xpath($field_xpath));
    $this->assertNotEmpty($this->xpath($clue_xpath));
    $this->assertSession()->pageTextContains('Untranslatable-but-visible test field');
    $this->submitForm([], 'Save');

    // Check that the widget is displayed along with its clue in the edit form
    // for both languages.
    $this->drupalGet($en_edit_url);
    $this->assertNotEmpty($this->xpath($field_xpath));
    $this->assertNotEmpty($this->xpath($clue_xpath));
    $it_edit_url = $entity->toUrl('edit-form', ['language' => ConfigurableLanguage::load('it')]);
    $this->drupalGet($it_edit_url);
    $this->assertNotEmpty($this->xpath($field_xpath));
    $this->assertNotEmpty($this->xpath($clue_xpath));

    // Configure untranslatable field widgets to be hidden on non-default
    // language edit forms.
    $settings_key = 'settings[' . $this->entityTypeId . '][' . $this->bundle . '][settings][content_translation][untranslatable_fields_hide]';
    $settings_url = 'admin/config/regional/content-language';
    $this->drupalGet($settings_url);
    $this->submitForm([$settings_key => 1], 'Save configuration');

    // Verify that the widget is displayed in the default language edit form,
    // but no clue is displayed.
    $this->drupalGet($en_edit_url);
    $field_xpath = '//input[@name="' . $this->fieldName . '[0][value]"]';
    $this->assertNotEmpty($this->xpath($field_xpath));
    $this->assertEmpty($this->xpath($clue_xpath));
    $this->assertSession()->pageTextContains('Untranslatable-but-visible test field');

    // Verify no widget is displayed on the non-default language edit form.
    $this->drupalGet($it_edit_url);
    $this->assertEmpty($this->xpath($field_xpath));
    $this->assertEmpty($this->xpath($clue_xpath));
    $this->assertSession()->pageTextContains('Untranslatable-but-visible test field');

    // Verify a warning is displayed.
    $this->assertSession()->pageTextContains('Fields that apply to all languages are hidden to avoid conflicting changes.');
    $edit_path = $entity->toUrl('edit-form')->toString();
    $link_xpath = '//a[@href=:edit_path and text()="Edit them on the original language form"]';
    $elements = $this->xpath($link_xpath, [':edit_path' => $edit_path]);
    $this->assertNotEmpty($elements);

    // Configure untranslatable field widgets to be displayed on non-default
    // language edit forms.
    $this->drupalGet($settings_url);
    $this->submitForm([$settings_key => 0], 'Save configuration');

    // Check that the widget is displayed along with its clue in the edit form
    // for both languages.
    $this->drupalGet($en_edit_url);
    $this->assertNotEmpty($this->xpath($field_xpath));
    $this->assertNotEmpty($this->xpath($clue_xpath));
    $this->drupalGet($it_edit_url);
    $this->assertNotEmpty($this->xpath($field_xpath));
    $this->assertNotEmpty($this->xpath($clue_xpath));

    // Enable content moderation and verify that widgets are hidden despite them
    // being configured to be displayed.
    $this->enableContentModeration();
    $this->drupalGet($it_edit_url);
    $this->assertEmpty($this->xpath($field_xpath));
    $this->assertEmpty($this->xpath($clue_xpath));

    // Verify a warning is displayed.
    $this->assertSession()->pageTextContains('Fields that apply to all languages are hidden to avoid conflicting changes.');
    $elements = $this->xpath($link_xpath, [':edit_path' => $edit_path]);
    $this->assertNotEmpty($elements);

    // Verify that checkboxes on the language content settings page are checked
    // and disabled for moderated bundles.
    $this->drupalGet($settings_url);
    $field_name = "settings[{$this->entityTypeId}][{$this->bundle}][settings][content_translation][untranslatable_fields_hide]";
    $this->assertSession()->fieldValueEquals($field_name, 1);
    $this->assertSession()->fieldDisabled($field_name);
    $this->submitForm([$settings_key => 0], 'Save configuration');
    $this->assertSession()->fieldValueEquals($field_name, 1);
    $this->assertSession()->fieldDisabled($field_name);
  }

}
