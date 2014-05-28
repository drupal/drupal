<?php

/**
 * @file
 * Contains \Drupal\locale\Tests\LocaleConfigTranslationTest.
 */

namespace Drupal\locale\Tests;

use Drupal\Core\Language\Language;
use Drupal\simpletest\WebTestBase;
use Drupal\locale\LocaleTypedConfig;

/**
 * Tests Metadata for configuration objects.
 */
class LocaleConfigTranslationTest extends WebTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('locale', 'contact');

  public static function getInfo() {
    return array(
      'name' => 'Configuration translation',
      'description' => 'Tests translation of configuration strings.',
      'group' => 'Locale',
    );
  }

  public function setUp() {
    parent::setUp();
    // Add a default locale storage for all these tests.
    $this->storage = $this->container->get('locale.storage');

    // Enable import of translations. By default this is disabled for automated
    // tests.
    \Drupal::config('locale.settings')
      ->set('translation.import_enabled', TRUE)
      ->save();
  }

  /**
   * Tests basic configuration translation.
   */
  function testConfigTranslation() {
    // Add custom language.
    $langcode = 'xx';
    $admin_user = $this->drupalCreateUser(array('administer languages', 'access administration pages', 'translate interface', 'administer modules', 'access site-wide contact form', 'administer contact forms'));
    $this->drupalLogin($admin_user);
    $name = $this->randomName(16);
    $edit = array(
      'predefined_langcode' => 'custom',
      'langcode' => $langcode,
      'name' => $name,
      'direction' => '0',
    );
    $this->drupalPostForm('admin/config/regional/language/add', $edit, t('Add custom language'));
    // Set path prefix.
    $edit = array( "prefix[$langcode]" => $langcode );
    $this->drupalPostForm('admin/config/regional/language/detection/url', $edit, t('Save configuration'));

    // Check site name string exists and create translation for it.
    $string = $this->storage->findString(array('source' => 'Drupal', 'context' => '', 'type' => 'configuration'));
    $this->assertTrue($string, 'Configuration strings have been created upon installation.');

    // Translate using the UI so configuration is refreshed.
    $site_name = $this->randomName(20);
    $search = array(
      'string' => $string->source,
      'langcode' => $langcode,
      'translation' => 'all',
    );
    $this->drupalPostForm('admin/config/regional/translate', $search, t('Filter'));
    $textareas = $this->xpath('//textarea');
    $textarea = current($textareas);
    $lid = (string) $textarea[0]['name'];
    $edit = array(
      $lid => $site_name,
    );
    $this->drupalPostForm('admin/config/regional/translate', $edit, t('Save translations'));

    $wrapper = $this->container->get('locale.config.typed')->get('system.site');

    // Get translation and check we've only got the site name.
    $translation = $wrapper->getTranslation($langcode);
    $properties = $translation->getProperties();
    $this->assertEqual(count($properties), 1, 'Got the right number of properties after translation');

    // Check the translated site name is displayed.
    $this->drupalGet($langcode);
    $this->assertText($site_name, 'The translated site name is displayed after translations refreshed.');

    // Check default medium date format exists and create a translation for it.
    $string = $this->storage->findString(array('source' => 'D, m/d/Y - H:i', 'context' => '', 'type' => 'configuration'));
    $this->assertTrue($string, 'Configuration date formats have been created upon installation.');

    // Translate using the UI so configuration is refreshed.
    $search = array(
      'string' => $string->source,
      'langcode' => $langcode,
      'translation' => 'all',
    );
    $this->drupalPostForm('admin/config/regional/translate', $search, t('Filter'));
    $textareas = $this->xpath('//textarea');
    $textarea = current($textareas);
    $lid = (string) $textarea[0]['name'];
    $edit = array(
      $lid => 'D',
    );
    $this->drupalPostForm('admin/config/regional/translate', $edit, t('Save translations'));

    $wrapper = $this->container->get('locale.config.typed')->get('system.date_format.medium');

    // Get translation and check we've only got the site name.
    $translation = $wrapper->getTranslation($langcode);
    $format = $translation->get('pattern')->get('php')->getValue();
    $this->assertEqual($format, 'D', 'Got the right date format pattern after translation.');

    // Formatting the date 8 / 27 / 1985 @ 13:37 EST with pattern D should
    // display "Tue".
    $formatted_date = format_date(494015820, $type = 'medium', NULL, NULL, $langcode);
    $this->assertEqual($formatted_date, 'Tue', 'Got the right formatted date using the date format translation pattern.');

    // Assert strings from image module config are not available.
    $string = $this->storage->findString(array('source' => 'Medium (220x220)', 'context' => '', 'type' => 'configuration'));
    $this->assertFalse($string, 'Configuration strings have been created upon installation.');

    // Enable the image module.
    $this->drupalPostForm('admin/modules', array('modules[Field types][image][enable]' => "1"), t('Save configuration'));
    $this->rebuildContainer();

    $string = $this->storage->findString(array('source' => 'Medium (220x220)', 'context' => '', 'type' => 'configuration'));
    $this->assertTrue($string, 'Configuration strings have been created upon installation.');
    $locations = $string->getLocations();
    $this->assertTrue(isset($locations['configuration']) && isset($locations['configuration']['image.style.medium']), 'Configuration string has been created with the right location');

    // Check the string is unique and has no translation yet.
    $translations = $this->storage->getTranslations(array('language' => $langcode, 'type' => 'configuration', 'name' => 'image.style.medium'));
    $translation = reset($translations);
    $this->assertTrue(count($translations) == 1 && $translation->source == $string->source && empty($translation->translation), 'Got only one string for image configuration and has no translation.');

    // Translate using the UI so configuration is refreshed.
    $image_style_label = $this->randomName(20);
    $search = array(
      'string' => $string->source,
      'langcode' => $langcode,
      'translation' => 'all',
    );
    $this->drupalPostForm('admin/config/regional/translate', $search, t('Filter'));
    $textarea = current($this->xpath('//textarea'));
    $lid = (string) $textarea[0]['name'];
    $edit = array(
      $lid => $image_style_label,
    );
    $this->drupalPostForm('admin/config/regional/translate', $edit, t('Save translations'));

    // Check the right single translation has been created.
    $translations = $this->storage->getTranslations(array('language' => $langcode, 'type' => 'configuration', 'name' => 'image.style.medium'));
    $translation = reset($translations);
    $this->assertTrue(count($translations) == 1 && $translation->source == $string->source && $translation->translation == $image_style_label, 'Got only one translation for image configuration.');

    // Try more complex configuration data.
    $wrapper = $this->container->get('locale.config.typed')->get('image.style.medium');

    $translation = $wrapper->getTranslation($langcode);
    $property = $translation->get('label');
    $this->assertEqual($property->getValue(), $image_style_label, 'Got the right translation for image style name after translation');

    // Quick test to ensure translation file exists.
    $override = \Drupal::languageManager()->getLanguageConfigOverride('xx', 'image.style.medium');
    $this->assertEqual($override->get('label'), $image_style_label);

    // Uninstall the module.
    $this->drupalPostForm('admin/modules/uninstall', array('uninstall[image]' => "image"), t('Uninstall'));
    $this->drupalPostForm(NULL, array(), t('Uninstall'));

    // Ensure that the translated configuration has been removed.
    $override = \Drupal::languageManager()->getLanguageConfigOverride('xx', 'image.style.medium');
    $this->assertTrue($override->isNew(), 'Translated configuration for image module removed.');

    // Translate default category using the UI so configuration is refreshed.
    $category_label = $this->randomName(20);
    $search = array(
      'string' => 'Website feedback',
      'langcode' => $langcode,
      'translation' => 'all',
    );
    $this->drupalPostForm('admin/config/regional/translate', $search, t('Filter'));
    $textarea = current($this->xpath('//textarea'));
    $lid = (string) $textarea[0]['name'];
    $edit = array(
      $lid => $category_label,
    );
    $this->drupalPostForm('admin/config/regional/translate', $edit, t('Save translations'));

    // Check if this category displayed in this language will use the
    // translation. This test ensures the entity loaded from the request
    // upcasting will already work.
    $this->drupalGet($langcode . '/contact/feedback');
    $this->assertText($category_label);

    // Check if the UI does not show the translated String.
    $this->drupalGet('admin/structure/contact/manage/feedback');
    $this->assertFieldById('edit-label', 'Website feedback', 'Translation is not loaded for Edit Form.');
  }

}
