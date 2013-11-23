<?php

/**
 * @file
 * Contains \Drupal\system\Tests\Form\SystemConfigFormTest.
 */

namespace Drupal\system\Tests\Form;

use Drupal\simpletest\WebTestBase;
use Drupal\form_test\SystemConfigFormTestForm;

/**
 * Tests the SystemConfigFormTestBase class.
 */
class SystemConfigFormTest extends WebTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('form_test');

  public static function getInfo() {
    return array(
      'name' => 'SystemConfigmForm tests',
      'description' => 'Tests the SystemConfigFormTestBase class.',
      'group' => 'Form API',
    );
  }

  /**
   * Tests the SystemConfigFormTestBase class.
   */
  function testSystemConfigForm() {
    $this->drupalGet('form-test/system-config-form');
    $element = $this->xpath('//div[@id = :id]/input[contains(@class, :class)]', array(':id' => 'edit-actions', ':class' => 'button--primary'));
    $this->assertTrue($element, 'The primary action submit button was found.');
    $this->drupalPostForm(NULL, array(), t('Save configuration'));
    $this->assertText(t('The configuration options have been saved.'));
  }

}
