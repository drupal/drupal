<?php

/**
 * @file
 * Definition of Drupal\system\Tests\Form\FileInclusionTest.
 */

namespace Drupal\system\Tests\Form;

use Drupal\simpletest\WebTestBase;

/**
 * Tests form API file inclusion.
 */
class FileInclusionTest extends WebTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('form_test');

  public static function getInfo() {
    return array(
      'name' => 'Form API file inclusion',
      'description' => 'Tests form API file inclusion.',
      'group' => 'Form API',
    );
  }

  /**
   * Tests loading an include specified in hook_menu().
   */
  function testLoadMenuInclude() {
    $this->drupalPostAJAX('form-test/load-include-menu', array(), array('op' => t('Save')), 'system/ajax', array(), array(), 'form-test-load-include-menu');
    $this->assertText('Submit callback called.');
  }

  /**
   * Tests loading a custom specified include.
   */
  function testLoadCustomInclude() {
    $this->drupalPost('form-test/load-include-custom', array(), t('Save'));
    $this->assertText('Submit callback called.');
  }
}
