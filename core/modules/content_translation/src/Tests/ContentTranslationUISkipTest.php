<?php

namespace Drupal\content_translation\Tests;

use Drupal\simpletest\WebTestBase;

/**
 * Tests the content translation UI check skip.
 *
 * @group content_translation
 */
class ContentTranslationUISkipTest extends WebTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('content_translation_test', 'user', 'node');

  /**
   * Tests the content_translation_ui_skip key functionality.
   */
  function testUICheckSkip() {
    $admin_user = $this->drupalCreateUser(array(
      'translate any entity',
      'administer content translation',
      'administer languages'
    ));
    $this->drupalLogin($admin_user);
    // Visit the content translation.
    $this->drupalGet('admin/config/regional/content-language');

    // Check the message regarding UI integration.
    $this->assertText('Test entity - Translatable skip UI check');
    $this->assertText('Test entity - Translatable check UI (Translation is not supported)');
  }

}
