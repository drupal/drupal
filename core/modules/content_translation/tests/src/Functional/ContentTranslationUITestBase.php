<?php

namespace Drupal\Tests\content_translation\Functional;

use Drupal\Component\Render\FormattableMarkup;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Language\Language;
use Drupal\Core\Language\LanguageInterface;
use Drupal\Core\Url;
use Drupal\language\Entity\ConfigurableLanguage;
use Drupal\Tests\system\Functional\Cache\AssertPageCacheContextsAndTagsTrait;

/**
 * Tests the Content Translation UI.
 */
abstract class ContentTranslationUITestBase extends ContentTranslationTestBase {

  use AssertPageCacheContextsAndTagsTrait;

  /**
   * The id of the entity being translated.
   *
   * @var mixed
   */
  protected $entityId;

  /**
   * Whether the behavior of the language selector should be tested.
   *
   * @var bool
   */
  protected $testLanguageSelector = TRUE;

  /**
   * Flag to determine if "all languages" rendering is tested.
   *
   * @var bool
   */
  protected $testHTMLEscapeForAllLanguages = FALSE;

  /**
   * Default cache contexts expected on a non-translated entity.
   *
   * Cache contexts will not be checked if this list is empty.
   *
   * @var string[]
   */
  protected $defaultCacheContexts = ['languages:language_interface', 'theme', 'url.query_args:_wrapper_format', 'user.permissions'];

  /**
   * Tests the basic translation UI.
   */
  public function testTranslationUI() {
    $this->doTestBasicTranslation();
    $this->doTestTranslationOverview();
    $this->doTestOutdatedStatus();
    $this->doTestPublishedStatus();
    $this->doTestAuthoringInfo();
    $this->doTestTranslationEdit();
    $this->doTestTranslationChanged();
    $this->doTestChangedTimeAfterSaveWithoutChanges();
    $this->doTestTranslationDeletion();
  }

  /**
   * Tests the basic translation workflow.
   */
  protected function doTestBasicTranslation() {
    // Create a new test entity with original values in the default language.
    $default_langcode = $this->langcodes[0];
    $values[$default_langcode] = $this->getNewEntityValues($default_langcode);
    // Create the entity with the editor as owner, so that afterwards a new
    // translation is created by the translator and the translation author is
    // tested.
    $this->drupalLogin($this->editor);
    $this->entityId = $this->createEntity($values[$default_langcode], $default_langcode);
    $this->drupalLogin($this->translator);
    $storage = $this->container->get('entity_type.manager')
      ->getStorage($this->entityTypeId);
    $storage->resetCache([$this->entityId]);
    $entity = $storage->load($this->entityId);
    $this->assertNotEmpty($entity, 'Entity found in the database.');
    $this->drupalGet($entity->toUrl());
    $this->assertSession()->statusCodeEquals(200);

    // Ensure that the content language cache context is not yet added to the
    // page.
    $this->assertCacheContexts($this->defaultCacheContexts);

    $this->drupalGet($entity->toUrl('drupal:content-translation-overview'));
    $this->assertNoText('Source language');

    $translation = $this->getTranslation($entity, $default_langcode);
    foreach ($values[$default_langcode] as $property => $value) {
      $stored_value = $this->getValue($translation, $property, $default_langcode);
      $value = is_array($value) ? $value[0]['value'] : $value;
      $message = new FormattableMarkup('@property correctly stored in the default language.', ['@property' => $property]);
      $this->assertEqual($value, $stored_value, $message);
    }

    // Add a content translation.
    $langcode = 'it';
    $language = ConfigurableLanguage::load($langcode);
    $values[$langcode] = $this->getNewEntityValues($langcode);

    $entity_type_id = $entity->getEntityTypeId();
    $add_url = Url::fromRoute("entity.$entity_type_id.content_translation_add", [
      $entity->getEntityTypeId() => $entity->id(),
      'source' => $default_langcode,
      'target' => $langcode,
    ], ['language' => $language]);
    $this->drupalPostForm($add_url, $this->getEditValues($values, $langcode), $this->getFormSubmitActionForNewTranslation($entity, $langcode));

    // Assert that HTML is not escaped unexpectedly.
    if ($this->testHTMLEscapeForAllLanguages) {
      $this->assertNoRaw('&lt;span class=&quot;translation-entity-all-languages&quot;&gt;(all languages)&lt;/span&gt;');
      $this->assertRaw('<span class="translation-entity-all-languages">(all languages)</span>');
    }

    // Ensure that the content language cache context is not yet added to the
    // page.
    $storage = $this->container->get('entity_type.manager')
      ->getStorage($this->entityTypeId);
    $storage->resetCache([$this->entityId]);
    $entity = $storage->load($this->entityId);
    $this->drupalGet($entity->toUrl());
    $this->assertCacheContexts(Cache::mergeContexts(['languages:language_content'], $this->defaultCacheContexts));

    // Reset the cache of the entity, so that the new translation gets the
    // updated values.
    $metadata_source_translation = $this->manager->getTranslationMetadata($entity->getTranslation($default_langcode));
    $metadata_target_translation = $this->manager->getTranslationMetadata($entity->getTranslation($langcode));

    $author_field_name = $entity->hasField('content_translation_uid') ? 'content_translation_uid' : 'uid';
    if ($entity->getFieldDefinition($author_field_name)->isTranslatable()) {
      $this->assertEqual($this->translator->id(), $metadata_target_translation->getAuthor()->id(), new FormattableMarkup('Author of the target translation @langcode correctly stored for translatable owner field.', ['@langcode' => $langcode]));

      $this->assertNotEquals($metadata_target_translation->getAuthor()->id(), $metadata_source_translation->getAuthor()->id(),
        new FormattableMarkup('Author of the target translation @target different from the author of the source translation @source for translatable owner field.',
          ['@target' => $langcode, '@source' => $default_langcode]));
    }
    else {
      $this->assertEqual($this->editor->id(), $metadata_target_translation->getAuthor()->id(), 'Author of the entity remained untouched after translation for non translatable owner field.');
    }

    $created_field_name = $entity->hasField('content_translation_created') ? 'content_translation_created' : 'created';
    if ($entity->getFieldDefinition($created_field_name)->isTranslatable()) {
      // Verify that the translation creation timestamp of the target
      // translation language is newer than the creation timestamp of the source
      // translation default language for the translatable created field.
      $this->assertGreaterThan($metadata_source_translation->getCreatedTime(), $metadata_target_translation->getCreatedTime());
    }
    else {
      $this->assertEqual($metadata_source_translation->getCreatedTime(), $metadata_target_translation->getCreatedTime(), 'Creation timestamp of the entity remained untouched after translation for non translatable created field.');
    }

    if ($this->testLanguageSelector) {
      // Verify that language selector is correctly disabled on translations.
      $this->assertSession()->fieldNotExists('edit-langcode-0-value');
    }
    $storage->resetCache([$this->entityId]);
    $entity = $storage->load($this->entityId);
    $this->drupalGet($entity->toUrl('drupal:content-translation-overview'));
    $this->assertNoText('Source language');

    // Switch the source language.
    $langcode = 'fr';
    $language = ConfigurableLanguage::load($langcode);
    $source_langcode = 'it';
    $edit = ['source_langcode[source]' => $source_langcode];
    $entity_type_id = $entity->getEntityTypeId();
    $add_url = Url::fromRoute("entity.$entity_type_id.content_translation_add", [
      $entity->getEntityTypeId() => $entity->id(),
      'source' => $default_langcode,
      'target' => $langcode,
    ], ['language' => $language]);
    // This does not save anything, it merely reloads the form and fills in the
    // fields with the values from the different source language.
    $this->drupalPostForm($add_url, $edit, 'Change');
    $this->assertSession()->fieldValueEquals("{$this->fieldName}[0][value]", $values[$source_langcode][$this->fieldName][0]['value']);

    // Add another translation and mark the other ones as outdated.
    $values[$langcode] = $this->getNewEntityValues($langcode);
    $edit = $this->getEditValues($values, $langcode) + ['content_translation[retranslate]' => TRUE];
    $entity_type_id = $entity->getEntityTypeId();
    $add_url = Url::fromRoute("entity.$entity_type_id.content_translation_add", [
      $entity->getEntityTypeId() => $entity->id(),
      'source' => $source_langcode,
      'target' => $langcode,
    ], ['language' => $language]);
    $this->drupalPostForm($add_url, $edit, $this->getFormSubmitActionForNewTranslation($entity, $langcode));
    $storage->resetCache([$this->entityId]);
    $entity = $storage->load($this->entityId);
    $this->drupalGet($entity->toUrl('drupal:content-translation-overview'));
    $this->assertText('Source language');

    // Check that the entered values have been correctly stored.
    foreach ($values as $langcode => $property_values) {
      $translation = $this->getTranslation($entity, $langcode);
      foreach ($property_values as $property => $value) {
        $stored_value = $this->getValue($translation, $property, $langcode);
        $value = is_array($value) ? $value[0]['value'] : $value;
        $message = new FormattableMarkup('%property correctly stored with language %language.', ['%property' => $property, '%language' => $langcode]);
        $this->assertEqual($value, $stored_value, $message);
      }
    }
  }

  /**
   * Tests that the translation overview shows the correct values.
   */
  protected function doTestTranslationOverview() {
    $storage = $this->container->get('entity_type.manager')
      ->getStorage($this->entityTypeId);
    $storage->resetCache([$this->entityId]);
    $entity = $storage->load($this->entityId);
    $translate_url = $entity->toUrl('drupal:content-translation-overview');
    $this->drupalGet($translate_url);
    $translate_url->setAbsolute(FALSE);

    foreach ($this->langcodes as $langcode) {
      if ($entity->hasTranslation($langcode)) {
        $language = new Language(['id' => $langcode]);
        $view_url = $entity->toUrl('canonical', ['language' => $language])->toString();
        $elements = $this->xpath('//table//a[@href=:href]', [':href' => $view_url]);
        $this->assertEqual($entity->getTranslation($langcode)->label(), $elements[0]->getText(), new FormattableMarkup('Label correctly shown for %language translation.', ['%language' => $langcode]));
        $edit_path = $entity->toUrl('edit-form', ['language' => $language])->toString();
        $elements = $this->xpath('//table//ul[@class="dropbutton"]/li/a[@href=:href]', [':href' => $edit_path]);
        $this->assertEqual(t('Edit'), $elements[0]->getText(), new FormattableMarkup('Edit link correct for %language translation.', ['%language' => $langcode]));
      }
    }
  }

  /**
   * Tests up-to-date status tracking.
   */
  protected function doTestOutdatedStatus() {
    $storage = $this->container->get('entity_type.manager')
      ->getStorage($this->entityTypeId);
    $storage->resetCache([$this->entityId]);
    $entity = $storage->load($this->entityId);
    $langcode = 'fr';
    $languages = \Drupal::languageManager()->getLanguages();

    // Mark translations as outdated.
    $edit = ['content_translation[retranslate]' => TRUE];
    $edit_path = $entity->toUrl('edit-form', ['language' => $languages[$langcode]]);
    $this->drupalPostForm($edit_path, $edit, $this->getFormSubmitAction($entity, $langcode));
    $storage->resetCache([$this->entityId]);
    $entity = $storage->load($this->entityId);

    // Check that every translation has the correct "outdated" status, and that
    // the Translation fieldset is open if the translation is "outdated".
    foreach ($this->langcodes as $added_langcode) {
      $url = $entity->toUrl('edit-form', ['language' => ConfigurableLanguage::load($added_langcode)]);
      $this->drupalGet($url);
      if ($added_langcode == $langcode) {
        // Verify that the retranslate flag is not checked by default.
        $this->assertSession()->fieldValueEquals('content_translation[retranslate]', FALSE);
        $this->assertEmpty($this->xpath('//details[@id="edit-content-translation" and @open="open"]'), 'The translation tab should be collapsed by default.');
      }
      else {
        // Verify that the translate flag is checked by default.
        $this->assertSession()->fieldValueEquals('content_translation[outdated]', TRUE);
        $this->assertNotEmpty($this->xpath('//details[@id="edit-content-translation" and @open="open"]'), 'The translation tab is correctly expanded when the translation is outdated.');
        $edit = ['content_translation[outdated]' => FALSE];
        $this->drupalPostForm($url, $edit, $this->getFormSubmitAction($entity, $added_langcode));
        $this->drupalGet($url);
        // Verify that retranslate flag is now shown.
        $this->assertSession()->fieldValueEquals('content_translation[retranslate]', FALSE);
        $storage = $this->container->get('entity_type.manager')
          ->getStorage($this->entityTypeId);
        $storage->resetCache([$this->entityId]);
        $entity = $storage->load($this->entityId);
        $this->assertFalse($this->manager->getTranslationMetadata($entity->getTranslation($added_langcode))->isOutdated(), 'The "outdated" status has been correctly stored.');
      }
    }
  }

  /**
   * Tests the translation publishing status.
   */
  protected function doTestPublishedStatus() {
    $storage = $this->container->get('entity_type.manager')
      ->getStorage($this->entityTypeId);
    $storage->resetCache([$this->entityId]);
    $entity = $storage->load($this->entityId);

    // Unpublish translations.
    foreach ($this->langcodes as $index => $langcode) {
      if ($index > 0) {
        $url = $entity->toUrl('edit-form', ['language' => ConfigurableLanguage::load($langcode)]);
        $edit = ['content_translation[status]' => FALSE];
        $this->drupalPostForm($url, $edit, $this->getFormSubmitAction($entity, $langcode));
        $storage = $this->container->get('entity_type.manager')
          ->getStorage($this->entityTypeId);
        $storage->resetCache([$this->entityId]);
        $entity = $storage->load($this->entityId);
        $this->assertFalse($this->manager->getTranslationMetadata($entity->getTranslation($langcode))->isPublished(), 'The translation has been correctly unpublished.');
      }
    }

    // Check that the last published translation cannot be unpublished.
    $this->drupalGet($entity->toUrl('edit-form'));
    $this->assertSession()->fieldDisabled('content_translation[status]');
    $this->assertSession()->fieldValueEquals('content_translation[status]', TRUE);
  }

  /**
   * Tests the translation authoring information.
   */
  protected function doTestAuthoringInfo() {
    $storage = $this->container->get('entity_type.manager')
      ->getStorage($this->entityTypeId);
    $storage->resetCache([$this->entityId]);
    $entity = $storage->load($this->entityId);
    $values = [];

    // Post different authoring information for each translation.
    foreach ($this->langcodes as $index => $langcode) {
      $user = $this->drupalCreateUser();
      $values[$langcode] = [
        'uid' => $user->id(),
        'created' => REQUEST_TIME - mt_rand(0, 1000),
      ];
      $edit = [
        'content_translation[uid]' => $user->getAccountName(),
        'content_translation[created]' => $this->container->get('date.formatter')->format($values[$langcode]['created'], 'custom', 'Y-m-d H:i:s O'),
      ];
      $url = $entity->toUrl('edit-form', ['language' => ConfigurableLanguage::load($langcode)]);
      $this->drupalPostForm($url, $edit, $this->getFormSubmitAction($entity, $langcode));
    }

    $storage = $this->container->get('entity_type.manager')
      ->getStorage($this->entityTypeId);
    $storage->resetCache([$this->entityId]);
    $entity = $storage->load($this->entityId);
    foreach ($this->langcodes as $langcode) {
      $metadata = $this->manager->getTranslationMetadata($entity->getTranslation($langcode));
      $this->assertEqual($values[$langcode]['uid'], $metadata->getAuthor()->id(), 'Translation author correctly stored.');
      $this->assertEqual($values[$langcode]['created'], $metadata->getCreatedTime(), 'Translation date correctly stored.');
    }

    // Try to post non valid values and check that they are rejected.
    $langcode = 'en';
    $edit = [
      // User names have by default length 8.
      'content_translation[uid]' => $this->randomMachineName(12),
      'content_translation[created]' => '19/11/1978',
    ];
    $this->drupalPostForm($entity->toUrl('edit-form'), $edit, $this->getFormSubmitAction($entity, $langcode));
    $this->assertSession()->elementExists('xpath', '//div[contains(@class, "error")]//ul');
    $metadata = $this->manager->getTranslationMetadata($entity->getTranslation($langcode));
    $this->assertEqual($values[$langcode]['uid'], $metadata->getAuthor()->id(), 'Translation author correctly kept.');
    $this->assertEqual($values[$langcode]['created'], $metadata->getCreatedTime(), 'Translation date correctly kept.');
  }

  /**
   * Tests translation deletion.
   */
  protected function doTestTranslationDeletion() {
    // Confirm and delete a translation.
    $this->drupalLogin($this->translator);
    $langcode = 'fr';
    $storage = $this->container->get('entity_type.manager')
      ->getStorage($this->entityTypeId);
    $storage->resetCache([$this->entityId]);
    $entity = $storage->load($this->entityId);
    $language = ConfigurableLanguage::load($langcode);
    $url = $entity->toUrl('edit-form', ['language' => $language]);
    $this->drupalPostForm($url, [], 'Delete translation');
    $this->submitForm([], 'Delete ' . $language->getName() . ' translation');
    $storage->resetCache([$this->entityId]);
    $entity = $storage->load($this->entityId, TRUE);
    $this->assertIsObject($entity);
    $translations = $entity->getTranslationLanguages();
    $this->assertCount(2, $translations);
    $this->assertArrayNotHasKey($langcode, $translations);

    // Check that the translator cannot delete the original translation.
    $args = [$this->entityTypeId => $entity->id(), 'language' => 'en'];
    $this->drupalGet(Url::fromRoute("entity.$this->entityTypeId.content_translation_delete", $args));
    $this->assertSession()->statusCodeEquals(403);
  }

  /**
   * Returns an array of entity field values to be tested.
   */
  protected function getNewEntityValues($langcode) {
    return [$this->fieldName => [['value' => $this->randomMachineName(16)]]];
  }

  /**
   * Returns an edit array containing the values to be posted.
   */
  protected function getEditValues($values, $langcode, $new = FALSE) {
    $edit = $values[$langcode];
    $langcode = $new ? LanguageInterface::LANGCODE_NOT_SPECIFIED : $langcode;
    foreach ($values[$langcode] as $property => $value) {
      if (is_array($value)) {
        $edit["{$property}[0][value]"] = $value[0]['value'];
        unset($edit[$property]);
      }
    }
    return $edit;
  }

  /**
   * Returns the form action value when submitting a new translation.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity being tested.
   * @param string $langcode
   *   Language code for the form.
   *
   * @return string
   *   Name of the button to hit.
   */
  protected function getFormSubmitActionForNewTranslation(EntityInterface $entity, $langcode) {
    $entity->addTranslation($langcode, $entity->toArray());
    return $this->getFormSubmitAction($entity, $langcode);
  }

  /**
   * Returns the form action value to be used to submit the entity form.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity being tested.
   * @param string $langcode
   *   Language code for the form.
   *
   * @return string
   *   Name of the button to hit.
   */
  protected function getFormSubmitAction(EntityInterface $entity, $langcode) {
    return 'Save' . $this->getFormSubmitSuffix($entity, $langcode);
  }

  /**
   * Returns appropriate submit button suffix based on translatability.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity being tested.
   * @param string $langcode
   *   Language code for the form.
   *
   * @return string
   *   Submit button suffix based on translatability.
   */
  protected function getFormSubmitSuffix(EntityInterface $entity, $langcode) {
    return '';
  }

  /**
   * Returns the translation object to use to retrieve the translated values.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity being tested.
   * @param string $langcode
   *   The language code identifying the translation to be retrieved.
   *
   * @return \Drupal\Core\TypedData\TranslatableInterface
   *   The translation object to act on.
   */
  protected function getTranslation(EntityInterface $entity, $langcode) {
    return $entity->getTranslation($langcode);
  }

  /**
   * Returns the value for the specified property in the given language.
   *
   * @param \Drupal\Core\Entity\EntityInterface $translation
   *   The translation object the property value should be retrieved from.
   * @param string $property
   *   The property name.
   * @param string $langcode
   *   The property value.
   *
   * @return
   *   The property value.
   */
  protected function getValue(EntityInterface $translation, $property, $langcode) {
    $key = $property == 'user_id' ? 'target_id' : 'value';
    return $translation->get($property)->{$key};
  }

  /**
   * Returns the name of the field that implements the changed timestamp.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity being tested.
   *
   * @return string
   *   The field name.
   */
  protected function getChangedFieldName($entity) {
    return $entity->hasField('content_translation_changed') ? 'content_translation_changed' : 'changed';
  }

  /**
   * Tests edit content translation.
   */
  protected function doTestTranslationEdit() {
    $storage = $this->container->get('entity_type.manager')
      ->getStorage($this->entityTypeId);
    $storage->resetCache([$this->entityId]);
    $entity = $storage->load($this->entityId);
    $languages = $this->container->get('language_manager')->getLanguages();

    foreach ($this->langcodes as $langcode) {
      // We only want to test the title for non-english translations.
      if ($langcode != 'en') {
        $options = ['language' => $languages[$langcode]];
        $url = $entity->toUrl('edit-form', $options);
        $this->drupalGet($url);

        $this->assertRaw($entity->getTranslation($langcode)->label());
      }
    }
  }

  /**
   * Tests the basic translation workflow.
   */
  protected function doTestTranslationChanged() {
    $storage = $this->container->get('entity_type.manager')
      ->getStorage($this->entityTypeId);
    $storage->resetCache([$this->entityId]);
    $entity = $storage->load($this->entityId);
    $changed_field_name = $this->getChangedFieldName($entity);
    $definition = $entity->getFieldDefinition($changed_field_name);
    $config = $definition->getConfig($entity->bundle());

    foreach ([FALSE, TRUE] as $translatable_changed_field) {
      if ($definition->isTranslatable()) {
        // For entities defining a translatable changed field we want to test
        // the correct behavior of that field even if the translatability is
        // revoked. In that case the changed timestamp should be synchronized
        // across all translations.
        $config->setTranslatable($translatable_changed_field);
        $config->save();
      }
      elseif ($translatable_changed_field) {
        // For entities defining a non-translatable changed field we cannot
        // declare the field as translatable on the fly by modifying its config
        // because the schema doesn't support this.
        break;
      }

      foreach ($entity->getTranslationLanguages() as $language) {
        // Ensure different timestamps.
        sleep(1);

        $langcode = $language->getId();

        $edit = [
          $this->fieldName . '[0][value]' => $this->randomString(),
        ];
        $edit_path = $entity->toUrl('edit-form', ['language' => $language]);
        $this->drupalPostForm($edit_path, $edit, $this->getFormSubmitAction($entity, $langcode));

        $storage = $this->container->get('entity_type.manager')
          ->getStorage($this->entityTypeId);
        $storage->resetCache([$this->entityId]);
        $entity = $storage->load($this->entityId);
        $this->assertEqual(
          $entity->getChangedTimeAcrossTranslations(), $entity->getTranslation($langcode)->getChangedTime(),
          new FormattableMarkup('Changed time for language %language is the latest change over all languages.', ['%language' => $language->getName()])
        );
      }

      $timestamps = [];
      foreach ($entity->getTranslationLanguages() as $language) {
        $next_timestamp = $entity->getTranslation($language->getId())->getChangedTime();
        if (!in_array($next_timestamp, $timestamps)) {
          $timestamps[] = $next_timestamp;
        }
      }

      if ($translatable_changed_field) {
        $this->assertEqual(count($entity->getTranslationLanguages()), count($timestamps), 'All timestamps from all languages are different.');
      }
      else {
        $this->assertEqual(1, count($timestamps), 'All timestamps from all languages are identical.');
      }
    }
  }

  /**
   * Test the changed time after API and FORM save without changes.
   */
  public function doTestChangedTimeAfterSaveWithoutChanges() {
    $storage = $this->container->get('entity_type.manager')
      ->getStorage($this->entityTypeId);
    $storage->resetCache([$this->entityId]);
    $entity = $storage->load($this->entityId);
    // Test only entities, which implement the EntityChangedInterface.
    if ($entity instanceof EntityChangedInterface) {
      $changed_timestamp = $entity->getChangedTime();

      $entity->save();
      $storage = $this->container->get('entity_type.manager')
        ->getStorage($this->entityTypeId);
      $storage->resetCache([$this->entityId]);
      $entity = $storage->load($this->entityId);
      $this->assertEqual($changed_timestamp, $entity->getChangedTime(), 'The entity\'s changed time wasn\'t updated after API save without changes.');

      // Ensure different save timestamps.
      sleep(1);

      // Save the entity on the regular edit form.
      $language = $entity->language();
      $edit_path = $entity->toUrl('edit-form', ['language' => $language]);
      $this->drupalPostForm($edit_path, [], $this->getFormSubmitAction($entity, $language->getId()));

      $storage->resetCache([$this->entityId]);
      $entity = $storage->load($this->entityId);
      $this->assertNotEquals($changed_timestamp, $entity->getChangedTime(), 'The entity\'s changed time was updated after form save without changes.');
    }
  }

}
