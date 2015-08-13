<?php

/**
 * @file
 * Contains \Drupal\locale\Tests\LocaleConfigTranslationTest.
 */

namespace Drupal\locale\Tests;

use Drupal\Component\Utility\SafeMarkup;
use Drupal\simpletest\WebTestBase;
use Drupal\core\language\languageInterface;

/**
 * Tests translation of configuration strings.
 *
 * @group locale
 */
class LocaleConfigTranslationTest extends WebTestBase {

  /**
   * The language code used.
   *
   * @var string
   */
  protected $langcode;

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('locale', 'contact', 'contact_test');

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    // Add a default locale storage for all these tests.
    $this->storage = $this->container->get('locale.storage');

    // Enable import of translations. By default this is disabled for automated
    // tests.
    $this->config('locale.settings')
      ->set('translation.import_enabled', TRUE)
      ->save();

    // Add custom language.
    $this->langcode = 'xx';
    $admin_user = $this->drupalCreateUser(array('administer languages', 'access administration pages', 'translate interface', 'administer modules', 'access site-wide contact form', 'administer contact forms', 'administer site configuration'));
    $this->drupalLogin($admin_user);
    $name = $this->randomMachineName(16);
    $edit = array(
      'predefined_langcode' => 'custom',
      'langcode' => $this->langcode,
      'label' => $name,
      'direction' => LanguageInterface::DIRECTION_LTR,
    );
    $this->drupalPostForm('admin/config/regional/language/add', $edit, t('Add custom language'));
    // Set path prefix.
    $edit = ["prefix[$this->langcode]" => $this->langcode];
    $this->drupalPostForm('admin/config/regional/language/detection/url', $edit, t('Save configuration'));
  }

  /**
   * Tests basic configuration translation.
   */
  public function testConfigTranslation() {
    // Check that the maintenance message exists and create translation for it.
    $source = '@site is currently under maintenance. We should be back shortly. Thank you for your patience.';
    $string = $this->storage->findString(array('source' => $source, 'context' => '', 'type' => 'configuration'));
    $this->assertTrue($string, 'Configuration strings have been created upon installation.');

    // Translate using the UI so configuration is refreshed.
    $message = $this->randomMachineName(20);
    $search = array(
      'string' => $string->source,
      'langcode' => $this->langcode,
      'translation' => 'all',
    );
    $this->drupalPostForm('admin/config/regional/translate', $search, t('Filter'));
    $textareas = $this->xpath('//textarea');
    $textarea = current($textareas);
    $lid = (string) $textarea[0]['name'];
    $edit = array(
      $lid => $message,
    );
    $this->drupalPostForm('admin/config/regional/translate', $edit, t('Save translations'));

    // Get translation and check we've only got the message.
    $translation = \Drupal::languageManager()->getLanguageConfigOverride($this->langcode, 'system.maintenance')->get();
    $this->assertEqual(count($translation), 1, 'Got the right number of properties after translation.');
    $this->assertEqual($translation['message'], $message);

    // Check default medium date format exists and create a translation for it.
    $string = $this->storage->findString(array('source' => 'D, m/d/Y - H:i', 'context' => 'PHP date format', 'type' => 'configuration'));
    $this->assertTrue($string, 'Configuration date formats have been created upon installation.');

    // Translate using the UI so configuration is refreshed.
    $search = array(
      'string' => $string->source,
      'langcode' => $this->langcode,
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

    $translation = \Drupal::languageManager()->getLanguageConfigOverride($this->langcode, 'core.date_format.medium')->get();
    $this->assertEqual($translation['pattern'], 'D', 'Got the right date format pattern after translation.');

    // Formatting the date 8 / 27 / 1985 @ 13:37 EST with pattern D should
    // display "Tue".
    $formatted_date = format_date(494015820, $type = 'medium', NULL, 'America/New_York', $this->langcode);
    $this->assertEqual($formatted_date, 'Tue', 'Got the right formatted date using the date format translation pattern.');

    // Assert strings from image module config are not available.
    $string = $this->storage->findString(array('source' => 'Medium (220×220)', 'context' => '', 'type' => 'configuration'));
    $this->assertFalse($string, 'Configuration strings have been created upon installation.');

    // Enable the image module.
    $this->drupalPostForm('admin/modules', array('modules[Field types][image][enable]' => "1"), t('Install'));
    $this->rebuildContainer();

    $string = $this->storage->findString(array('source' => 'Medium (220×220)', 'context' => '', 'type' => 'configuration'));
    $this->assertTrue($string, 'Configuration strings have been created upon installation.');
    $locations = $string->getLocations();
    $this->assertTrue(isset($locations['configuration']) && isset($locations['configuration']['image.style.medium']), 'Configuration string has been created with the right location');

    // Check the string is unique and has no translation yet.
    $translations = $this->storage->getTranslations(['language' => $this->langcode, 'type' => 'configuration', 'name' => 'image.style.medium']);
    $this->assertEqual(count($translations), 1);
    $translation = reset($translations);
    $this->assertEqual($translation->source, $string->source);
    $this->assertTrue(empty($translation->translation));

    // Translate using the UI so configuration is refreshed.
    $image_style_label = $this->randomMachineName(20);
    $search = array(
      'string' => $string->source,
      'langcode' => $this->langcode,
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
    $translations = $this->storage->getTranslations(['language' => $this->langcode, 'type' => 'configuration', 'name' => 'image.style.medium']);
    $translation = reset($translations);
    $this->assertTrue(count($translations) == 1 && $translation->source == $string->source && $translation->translation == $image_style_label, 'Got only one translation for image configuration.');

    // Try more complex configuration data.
    $translation = \Drupal::languageManager()->getLanguageConfigOverride($this->langcode, 'image.style.medium')->get();
    $this->assertEqual($translation['label'], $image_style_label, 'Got the right translation for image style name after translation');

    // Uninstall the module.
    $this->drupalPostForm('admin/modules/uninstall', array('uninstall[image]' => "image"), t('Uninstall'));
    $this->drupalPostForm(NULL, array(), t('Uninstall'));

    // Ensure that the translated configuration has been removed.
    $override = \Drupal::languageManager()->getLanguageConfigOverride('xx', 'image.style.medium');
    $this->assertTrue($override->isNew(), 'Translated configuration for image module removed.');

    // Translate default category using the UI so configuration is refreshed.
    $category_label = $this->randomMachineName(20);
    $search = array(
      'string' => 'Website feedback',
      'langcode' => $this->langcode,
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
    $this->drupalGet($this->langcode . '/contact/feedback');
    $this->assertText($category_label);

    // Check if the UI does not show the translated String.
    $this->drupalGet('admin/structure/contact/manage/feedback');
    $this->assertFieldById('edit-label', 'Website feedback', 'Translation is not loaded for Edit Form.');
  }

  /**
   * Test translatability of optional configuration in locale.
   */
  public function testOptionalConfiguration() {
    $this->assertNodeConfig(FALSE, FALSE);
    // Enable the node module.
    $this->drupalPostForm('admin/modules', ['modules[Core][node][enable]' => "1"], t('Install'));
    $this->drupalPostForm(NULL, [], t('Continue'));
    $this->rebuildContainer();
    $this->assertNodeConfig(TRUE, FALSE);
    // Enable the views module (which node provides some optional config for).
    $this->drupalPostForm('admin/modules', ['modules[Core][views][enable]' => "1"], t('Install'));
    $this->rebuildContainer();
    $this->assertNodeConfig(TRUE, TRUE);
  }

  /**
   * Check that node configuration source strings are made available in locale.
   *
   * @param bool $required
   *   Whether to assume a sample of the required default configuration is
   *   present.
   * @param bool $optional
   *   Whether to assume a sample of the optional default configuration is
   *   present.
   */
  protected function assertNodeConfig($required, $optional) {
    // Check the required default configuration in node module.
    $string = $this->storage->findString(['source' => 'Make content sticky', 'context' => '', 'type' => 'configuration']);
    if ($required) {
      $this->assertFalse($this->config('system.action.node_make_sticky_action')->isNew());
      $this->assertTrue($string, 'Node action text can be found with node module.');
    }
    else {
      $this->assertTrue($this->config('system.action.node_make_sticky_action')->isNew());
      $this->assertFalse($string, 'Node action text can not be found without node module.');
    }

    // Check the optional default configuration in node module.
    $string = $this->storage->findString(['source' => 'No front page content has been created yet.', 'context' => '', 'type' => 'configuration']);
    if ($optional) {
      $this->assertFalse($this->config('views.view.frontpage')->isNew());
      $this->assertTrue($string, 'Node view text can be found with node and views modules.');
    }
    else {
      $this->assertTrue($this->config('views.view.frontpage')->isNew());
      $this->assertFalse($string, 'Node view text can not be found without node and/or views modules.');
    }
  }

}
