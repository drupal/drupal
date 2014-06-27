<?php

/**
 * @file
 * Contains \Drupal\locale\Tests\LocaleLocaleLookupTest.
 */

namespace Drupal\locale\Tests;

use Drupal\Core\Language\Language;
use Drupal\simpletest\WebTestBase;

/**
 * Tests that LocaleLookup does not cause circular references.
 */
class LocaleLocaleLookupTest extends WebTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('locale', 'menu_link');

  /**
   * {@inheritdoc}
   */
  public static function getInfo() {
    return array(
      'name' => 'Test LocaleLookup',
      'description' => 'Tests LocaleLookup does not cause circular references.',
      'group' => 'Locale',
    );
  }

  /**
   * Tests hasTranslation().
   */
  public function testCircularDependency() {
    // Change the language default object to different values.
    $new_language_default = new Language(array(
      'id' => 'fr',
      'name' => 'French',
      'direction' => 0,
      'weight' => 0,
      'method_id' => 'language-default',
      'default' => TRUE,
    ));
    language_save($new_language_default);
    $this->drupalLogin($this->root_user);
    // Ensure that we can enable early_translation_test on a non-english site.
    $this->drupalPostForm('admin/modules', array('modules[Testing][early_translation_test][enable]' => TRUE), t('Save configuration'));
    $this->assertResponse(200);
  }

}
