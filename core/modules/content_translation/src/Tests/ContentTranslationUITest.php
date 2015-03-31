<?php

/**
 * @file
 * Definition of Drupal\entity\Tests\ContentTranslationUITest.
 */

namespace Drupal\content_translation\Tests;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Language\Language;
use Drupal\Core\Language\LanguageInterface;
use Drupal\Core\Url;
use Drupal\language\Entity\ConfigurableLanguage;

/**
 * Tests the Content Translation UI.
 */
abstract class ContentTranslationUITest extends ContentTranslationTestBase {

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
   * Tests the basic translation UI.
   */
  function testTranslationUI() {
    $this->doTestBasicTranslation();
    $this->doTestTranslationOverview();
    $this->doTestOutdatedStatus();
    $this->doTestPublishedStatus();
    $this->doTestAuthoringInfo();
    $this->doTestTranslationEdit();
    $this->doTestTranslationDeletion();
  }

  /**
   * Tests the basic translation workflow.
   */
  protected function doTestBasicTranslation() {
    // Create a new test entity with original values in the default language.
    $default_langcode = $this->langcodes[0];
    $values[$default_langcode] = $this->getNewEntityValues($default_langcode);
    $this->entityId = $this->createEntity($values[$default_langcode], $default_langcode);
    $entity = entity_load($this->entityTypeId, $this->entityId, TRUE);
    $this->assertTrue($entity, 'Entity found in the database.');
    $this->drupalGet($entity->urlInfo());
    $this->assertResponse(200, 'Entity URL is valid.');
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

    $add_url = Url::fromRoute('content_translation.translation_add_' . $entity->getEntityTypeId(), [
      $entity->getEntityTypeId() => $entity->id(),
      'source' => $default_langcode,
      'target' => $langcode
    ], array('language' => $language));
    $this->drupalPostForm($add_url, $this->getEditValues($values, $langcode), $this->getFormSubmitActionForNewTranslation($entity, $langcode));
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
    $add_url = Url::fromRoute('content_translation.translation_add_' . $entity->getEntityTypeId(), [
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
    $add_url = Url::fromRoute('content_translation.translation_add_' . $entity->getEntityTypeId(), [
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
    $this->drupalGet($entity->urlInfo('drupal:content-translation-overview'));

    foreach ($this->langcodes as $langcode) {
      if ($entity->hasTranslation($langcode)) {
        $language = new Language(array('id' => $langcode));
        $view_path = $entity->url('canonical', array('language' => $language));
        $elements = $this->xpath('//table//a[@href=:href]', array(':href' => $view_path));
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
    $langcode = 'fr';
    $entity = entity_load($this->entityTypeId, $this->entityId, TRUE);
    $url = $entity->urlInfo('edit-form', array('language' => ConfigurableLanguage::load($langcode)));
    $this->drupalPostForm($url, array(), t('Delete translation'));
    $this->drupalPostForm(NULL, array(), t('Delete'));
    $entity = entity_load($this->entityTypeId, $this->entityId, TRUE);
    if ($this->assertTrue(is_object($entity), 'Entity found')) {
      $translations = $entity->getTranslationLanguages();
      $this->assertTrue(count($translations) == 2 && empty($translations[$langcode]), 'Translation successfully deleted.');
    }
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

}
