<?php

namespace Drupal\content_translation\Tests;

use Drupal\Core\Cache\Cache;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Language\Language;
use Drupal\Core\Language\LanguageInterface;
use Drupal\Core\Url;
use Drupal\language\Entity\ConfigurableLanguage;
use Drupal\Component\Utility\SafeMarkup;
use Drupal\system\Tests\Cache\AssertPageCacheContextsAndTagsTrait;

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
   * Flag that tells whether the HTML escaping of all languages works or not
   * after SafeMarkup change.
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
  function testTranslationUI() {
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
    $entity = entity_load($this->entityTypeId, $this->entityId, TRUE);
    $this->assertTrue($entity, 'Entity found in the database.');
    $this->drupalGet($entity->urlInfo());
    $this->assertResponse(200, 'Entity URL is valid.');

    // Ensure that the content language cache context is not yet added to the
    // page.
    $this->assertCacheContexts($this->defaultCacheContexts);

    $this->drupalGet($entity->urlInfo('drupal:content-translation-overview'));
    $this->assertNoText('Source language', 'Source language column correctly hidden.');

    $translation = $this->getTranslation($entity, $default_langcode);
    foreach ($values[$default_langcode] as $property => $value) {
      $stored_value = $this->getValue($translation, $property, $default_langcode);
      $value = is_array($value) ? $value[0]['value'] : $value;
      $message = format_string('@property correctly stored in the default language.', array('@property' => $property));
      $this->assertEqual($stored_value, $value, $message);
    }

    // Add a content translation.
    $langcode = 'it';
    $language = ConfigurableLanguage::load($langcode);
    $values[$langcode] = $this->getNewEntityValues($langcode);

    $entity_type_id = $entity->getEntityTypeId();
    $add_url = Url::fromRoute("entity.$entity_type_id.content_translation_add", [
      $entity->getEntityTypeId() => $entity->id(),
      'source' => $default_langcode,
      'target' => $langcode
    ], array('language' => $language));
    $this->drupalPostForm($add_url, $this->getEditValues($values, $langcode), $this->getFormSubmitActionForNewTranslation($entity, $langcode));

    // Assert that HTML is escaped in "all languages" in UI after SafeMarkup
    // change.
    if ($this->testHTMLEscapeForAllLanguages) {
      $this->assertNoRaw('&lt;span class=&quot;translation-entity-all-languages&quot;&gt;(all languages)&lt;/span&gt;');
      $this->assertRaw('<span class="translation-entity-all-languages">(all languages)</span>');
    }

    // Ensure that the content language cache context is not yet added to the
    // page.
    $entity = entity_load($this->entityTypeId, $this->entityId, TRUE);
    $this->drupalGet($entity->urlInfo());
    $this->assertCacheContexts(Cache::mergeContexts(['languages:language_content'], $this->defaultCacheContexts));

    // Reset the cache of the entity, so that the new translation gets the
    // updated values.
    $metadata_source_translation = $this->manager->getTranslationMetadata($entity->getTranslation($default_langcode));
    $metadata_target_translation = $this->manager->getTranslationMetadata($entity->getTranslation($langcode));

    $author_field_name = $entity->hasField('content_translation_uid') ? 'content_translation_uid' : 'uid';
    if ($entity->getFieldDefinition($author_field_name)->isTranslatable()) {
      $this->assertEqual($metadata_target_translation->getAuthor()->id(), $this->translator->id(),
        SafeMarkup::format('Author of the target translation @langcode correctly stored for translatable owner field.', array('@langcode' => $langcode)));

      $this->assertNotEqual($metadata_target_translation->getAuthor()->id(), $metadata_source_translation->getAuthor()->id(),
        SafeMarkup::format('Author of the target translation @target different from the author of the source translation @source for translatable owner field.',
          array('@target' => $langcode, '@source' => $default_langcode)));
    }
    else {
      $this->assertEqual($metadata_target_translation->getAuthor()->id(), $this->editor->id(), 'Author of the entity remained untouched after translation for non translatable owner field.');
    }

    $created_field_name = $entity->hasField('content_translation_created') ? 'content_translation_created' : 'created';
    if ($entity->getFieldDefinition($created_field_name)->isTranslatable()) {
      $this->assertTrue($metadata_target_translation->getCreatedTime() > $metadata_source_translation->getCreatedTime(),
        SafeMarkup::format('Translation creation timestamp of the target translation @target is newer than the creation timestamp of the source translation @source for translatable created field.',
          array('@target' => $langcode, '@source' => $default_langcode)));
    }
    else {
      $this->assertEqual($metadata_target_translation->getCreatedTime(), $metadata_source_translation->getCreatedTime(), 'Creation timestamp of the entity remained untouched after translation for non translatable created field.');
    }

    if ($this->testLanguageSelector) {
      $this->assertNoFieldByXPath('//select[@id="edit-langcode-0-value"]', NULL, 'Language selector correctly disabled on translations.');
    }
    $entity = entity_load($this->entityTypeId, $this->entityId, TRUE);
    $this->drupalGet($entity->urlInfo('drupal:content-translation-overview'));
    $this->assertNoText('Source language', 'Source language column correctly hidden.');

    // Switch the source language.
    $langcode = 'fr';
    $language = ConfigurableLanguage::load($langcode);
    $source_langcode = 'it';
    $edit = array('source_langcode[source]' => $source_langcode);
    $entity_type_id =  $entity->getEntityTypeId();
    $add_url = Url::fromRoute("entity.$entity_type_id.content_translation_add", [
      $entity->getEntityTypeId() => $entity->id(),
      'source' => $default_langcode,
      'target' => $langcode
    ], array('language' => $language));
    // This does not save anything, it merely reloads the form and fills in the
    // fields with the values from the different source language.
    $this->drupalPostForm($add_url, $edit, t('Change'));
    $this->assertFieldByXPath("//input[@name=\"{$this->fieldName}[0][value]\"]", $values[$source_langcode][$this->fieldName][0]['value'], 'Source language correctly switched.');

    // Add another translation and mark the other ones as outdated.
    $values[$langcode] = $this->getNewEntityValues($langcode);
    $edit = $this->getEditValues($values, $langcode) + array('content_translation[retranslate]' => TRUE);
    $entity_type_id = $entity->getEntityTypeId();
    $add_url = Url::fromRoute("entity.$entity_type_id.content_translation_add", [
      $entity->getEntityTypeId() => $entity->id(),
      'source' => $source_langcode,
      'target' => $langcode
    ], array('language' => $language));
    $this->drupalPostForm($add_url, $edit, $this->getFormSubmitActionForNewTranslation($entity, $langcode));
    $entity = entity_load($this->entityTypeId, $this->entityId, TRUE);
    $this->drupalGet($entity->urlInfo('drupal:content-translation-overview'));
    $this->assertText('Source language', 'Source language column correctly shown.');

    // Check that the entered values have been correctly stored.
    foreach ($values as $langcode => $property_values) {
      $translation = $this->getTranslation($entity, $langcode);
      foreach ($property_values as $property => $value) {
        $stored_value = $this->getValue($translation, $property, $langcode);
        $value = is_array($value) ? $value[0]['value'] : $value;
        $message = format_string('%property correctly stored with language %language.', array('%property' => $property, '%language' => $langcode));
        $this->assertEqual($stored_value, $value, $message);
      }
    }
  }

  /**
   * Tests that the translation overview shows the correct values.
   */
  protected function doTestTranslationOverview() {
    $entity = entity_load($this->entityTypeId, $this->entityId, TRUE);
    $translate_url = $entity->urlInfo('drupal:content-translation-overview');
    $this->drupalGet($translate_url);
    $translate_url->setAbsolute(FALSE);

    foreach ($this->langcodes as $langcode) {
      if ($entity->hasTranslation($langcode)) {
        $language = new Language(array('id' => $langcode));
        $view_url = $entity->url('canonical', ['language' => $language]);
        $elements = $this->xpath('//table//a[@href=:href]', [':href' => $view_url]);
        $this->assertEqual((string) $elements[0], $entity->getTranslation($langcode)->label(), format_string('Label correctly shown for %language translation.', array('%language' => $langcode)));
        $edit_path = $entity->url('edit-form', array('language' => $language));
        $elements = $this->xpath('//table//ul[@class="dropbutton"]/li/a[@href=:href]', array(':href' => $edit_path));
        $this->assertEqual((string) $elements[0], t('Edit'), format_string('Edit link correct for %language translation.', array('%language' => $langcode)));
      }
    }
  }

  /**
   * Tests up-to-date status tracking.
   */
  protected function doTestOutdatedStatus() {
    $entity = entity_load($this->entityTypeId, $this->entityId, TRUE);
    $langcode = 'fr';
    $languages = \Drupal::languageManager()->getLanguages();

    // Mark translations as outdated.
    $edit = array('content_translation[retranslate]' => TRUE);
    $edit_path = $entity->urlInfo('edit-form', array('language' => $languages[$langcode]));
    $this->drupalPostForm($edit_path, $edit, $this->getFormSubmitAction($entity, $langcode));
    $entity = entity_load($this->entityTypeId, $this->entityId, TRUE);

    // Check that every translation has the correct "outdated" status, and that
    // the Translation fieldset is open if the translation is "outdated".
    foreach ($this->langcodes as $added_langcode) {
      $url = $entity->urlInfo('edit-form', array('language' => ConfigurableLanguage::load($added_langcode)));
      $this->drupalGet($url);
      if ($added_langcode == $langcode) {
        $this->assertFieldByXPath('//input[@name="content_translation[retranslate]"]', FALSE, 'The retranslate flag is not checked by default.');
        $this->assertFalse($this->xpath('//details[@id="edit-content-translation" and @open="open"]'), 'The translation tab should be collapsed by default.');
      }
      else {
        $this->assertFieldByXPath('//input[@name="content_translation[outdated]"]', TRUE, 'The translate flag is checked by default.');
        $this->assertTrue($this->xpath('//details[@id="edit-content-translation" and @open="open"]'), 'The translation tab is correctly expanded when the translation is outdated.');
        $edit = array('content_translation[outdated]' => FALSE);
        $this->drupalPostForm($url, $edit, $this->getFormSubmitAction($entity, $added_langcode));
        $this->drupalGet($url);
        $this->assertFieldByXPath('//input[@name="content_translation[retranslate]"]', FALSE, 'The retranslate flag is now shown.');
        $entity = entity_load($this->entityTypeId, $this->entityId, TRUE);
        $this->assertFalse($this->manager->getTranslationMetadata($entity->getTranslation($added_langcode))->isOutdated(), 'The "outdated" status has been correctly stored.');
      }
    }
  }

  /**
   * Tests the translation publishing status.
   */
  protected function doTestPublishedStatus() {
    $entity = entity_load($this->entityTypeId, $this->entityId, TRUE);

    // Unpublish translations.
    foreach ($this->langcodes as $index => $langcode) {
      if ($index > 0) {
        $url = $entity->urlInfo('edit-form', array('language' => ConfigurableLanguage::load($langcode)));
        $edit = array('content_translation[status]' => FALSE);
        $this->drupalPostForm($url, $edit, $this->getFormSubmitAction($entity, $langcode));
        $entity = entity_load($this->entityTypeId, $this->entityId, TRUE);
        $this->assertFalse($this->manager->getTranslationMetadata($entity->getTranslation($langcode))->isPublished(), 'The translation has been correctly unpublished.');
      }
    }

    // Check that the last published translation cannot be unpublished.
    $this->drupalGet($entity->urlInfo('edit-form'));
    $this->assertFieldByXPath('//input[@name="content_translation[status]" and @disabled="disabled"]', TRUE, 'The last translation is published and cannot be unpublished.');
  }

  /**
   * Tests the translation authoring information.
   */
  protected function doTestAuthoringInfo() {
    $entity = entity_load($this->entityTypeId, $this->entityId, TRUE);
    $values = array();

    // Post different authoring information for each translation.
    foreach ($this->langcodes as $index => $langcode) {
      $user = $this->drupalCreateUser();
      $values[$langcode] = array(
        'uid' => $user->id(),
        'created' => REQUEST_TIME - mt_rand(0, 1000),
      );
      $edit = array(
        'content_translation[uid]' => $user->getUsername(),
        'content_translation[created]' => format_date($values[$langcode]['created'], 'custom', 'Y-m-d H:i:s O'),
      );
      $url = $entity->urlInfo('edit-form', array('language' => ConfigurableLanguage::load($langcode)));
      $this->drupalPostForm($url, $edit, $this->getFormSubmitAction($entity, $langcode));
    }

    $entity = entity_load($this->entityTypeId, $this->entityId, TRUE);
    foreach ($this->langcodes as $langcode) {
      $metadata = $this->manager->getTranslationMetadata($entity->getTranslation($langcode));
      $this->assertEqual($metadata->getAuthor()->id(), $values[$langcode]['uid'], 'Translation author correctly stored.');
      $this->assertEqual($metadata->getCreatedTime(), $values[$langcode]['created'], 'Translation date correctly stored.');
    }

    // Try to post non valid values and check that they are rejected.
    $langcode = 'en';
    $edit = array(
      // User names have by default length 8.
      'content_translation[uid]' => $this->randomMachineName(12),
      'content_translation[created]' => '19/11/1978',
    );
    $this->drupalPostForm($entity->urlInfo('edit-form'), $edit, $this->getFormSubmitAction($entity, $langcode));
    $this->assertTrue($this->xpath('//div[contains(@class, "error")]//ul'), 'Invalid values generate a list of form errors.');
    $metadata = $this->manager->getTranslationMetadata($entity->getTranslation($langcode));
    $this->assertEqual($metadata->getAuthor()->id(), $values[$langcode]['uid'], 'Translation author correctly kept.');
    $this->assertEqual($metadata->getCreatedTime(), $values[$langcode]['created'], 'Translation date correctly kept.');
  }

  /**
   * Tests translation deletion.
   */
  protected function doTestTranslationDeletion() {
    // Confirm and delete a translation.
    $this->drupalLogin($this->translator);
    $langcode = 'fr';
    $entity = entity_load($this->entityTypeId, $this->entityId, TRUE);
    $language = ConfigurableLanguage::load($langcode);
    $url = $entity->urlInfo('edit-form', array('language' => $language));
    $this->drupalPostForm($url, array(), t('Delete translation'));
    $this->drupalPostForm(NULL, array(), t('Delete @language translation', array('@language' => $language->getName())));
    $entity = entity_load($this->entityTypeId, $this->entityId, TRUE);
    if ($this->assertTrue(is_object($entity), 'Entity found')) {
      $translations = $entity->getTranslationLanguages();
      $this->assertTrue(count($translations) == 2 && empty($translations[$langcode]), 'Translation successfully deleted.');
    }

    // Check that the translator cannot delete the original translation.
    $args = [$this->entityTypeId => $entity->id(), 'language' => 'en'];
    $this->drupalGet(Url::fromRoute("entity.$this->entityTypeId.content_translation_delete", $args));
    $this->assertResponse(403);
  }

  /**
   * Returns an array of entity field values to be tested.
   */
  protected function getNewEntityValues($langcode) {
    return array($this->fieldName => array(array('value' => $this->randomMachineName(16))));
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
    return t('Save') . $this->getFormSubmitSuffix($entity, $langcode);
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
    $entity = entity_load($this->entityTypeId, $this->entityId, TRUE);
    $languages = $this->container->get('language_manager')->getLanguages();

    foreach ($this->langcodes as $langcode) {
      // We only want to test the title for non-english translations.
      if ($langcode != 'en') {
        $options = array('language' => $languages[$langcode]);
        $url = $entity->urlInfo('edit-form', $options);
        $this->drupalGet($url);

        $this->assertRaw($entity->getTranslation($langcode)->label());
      }
    }
  }

  /**
   * Tests the basic translation workflow.
   */
  protected function doTestTranslationChanged() {
    $entity = entity_load($this->entityTypeId, $this->entityId, TRUE);
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

        $edit = array(
          $this->fieldName . '[0][value]' => $this->randomString(),
        );
        $edit_path = $entity->urlInfo('edit-form', array('language' => $language));
        $this->drupalPostForm($edit_path, $edit, $this->getFormSubmitAction($entity, $langcode));

        $entity = entity_load($this->entityTypeId, $this->entityId, TRUE);
        $this->assertEqual(
          $entity->getChangedTimeAcrossTranslations(), $entity->getTranslation($langcode)->getChangedTime(),
          format_string('Changed time for language %language is the latest change over all languages.', array('%language' => $language->getName()))
        );
      }

      $timestamps = array();
      foreach ($entity->getTranslationLanguages() as $language) {
        $next_timestamp = $entity->getTranslation($language->getId())->getChangedTime();
        if (!in_array($next_timestamp, $timestamps)) {
          $timestamps[] = $next_timestamp;
        }
      }

      if ($translatable_changed_field) {
        $this->assertEqual(
          count($timestamps), count($entity->getTranslationLanguages()),
          'All timestamps from all languages are different.'
        );
      }
      else {
        $this->assertEqual(
          count($timestamps), 1,
          'All timestamps from all languages are identical.'
        );
      }
    }
  }

  /**
   * Test the changed time after API and FORM save without changes.
   */
  public function doTestChangedTimeAfterSaveWithoutChanges() {
    $entity = entity_load($this->entityTypeId, $this->entityId, TRUE);
    // Test only entities, which implement the EntityChangedInterface.
    if ($entity->getEntityType()->isSubclassOf('Drupal\Core\Entity\EntityChangedInterface')) {
      $changed_timestamp = $entity->getChangedTime();

      $entity->save();
      $entity = entity_load($this->entityTypeId, $this->entityId, TRUE);
      $this->assertEqual($changed_timestamp, $entity->getChangedTime(), 'The entity\'s changed time wasn\'t updated after API save without changes.');

      // Ensure different save timestamps.
      sleep(1);

      // Save the entity on the regular edit form.
      $language = $entity->language();
      $edit_path = $entity->urlInfo('edit-form', array('language' => $language));
      $this->drupalPostForm($edit_path, [], $this->getFormSubmitAction($entity, $language->getId()));

      $entity = entity_load($this->entityTypeId, $this->entityId, TRUE);
      $this->assertNotEqual($changed_timestamp, $entity->getChangedTime(), 'The entity\'s changed time was updated after form save without changes.');
    }
  }

}
