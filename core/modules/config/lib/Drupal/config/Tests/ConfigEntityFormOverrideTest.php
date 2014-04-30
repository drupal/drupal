<?php

/**
 * @file
 * Contains \Drupal\config\Tests\ConfigEntityFormOverrideTest.
 */

namespace Drupal\config\Tests;

use Drupal\simpletest\WebTestBase;

/**
 * Tests that config overrides do not bleed through in entity forms.
 */
class ConfigEntityFormOverrideTest extends WebTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = array('config_test');

  /**
   * {@inheritdoc}
   */
  public static function getInfo() {
    return array(
      'name' => 'Config entity form overrides',
      'description' => 'Tests that config overrides do not affect entity forms.',
      'group' => 'Configuration',
    );
  }

  /**
   * Tests that overrides do not affect forms.
   */
  public function testFormsWithOverrides() {
    $overridden_name = 'Overridden label';

    // Set up an override.
    $settings['config']['config_test.dynamic.dotted.default']['label'] = (object) array(
      'value' => $overridden_name,
      'required' => TRUE,
    );
    $this->writeSettings($settings);

    // Test that everything on the form is the same, but that the override
    // worked for the config entity label.
    $this->drupalGet('admin/structure/config_test');
    $this->assertText($overridden_name);

    $this->drupalGet('admin/structure/config_test/manage/dotted.default');
    $elements = $this->xpath('//input[@name="label"]');
    $this->assertIdentical((string) $elements[0]['value'], 'Default');
    $this->assertNoText($overridden_name);
    $edit = array(
      'label' => 'Custom label',
    );

    $this->drupalPostForm(NULL, $edit, t('Save'));
    $this->drupalGet('admin/structure/config_test');
    $this->assertText($overridden_name);
    $this->assertNoText($edit['label']);

    $this->drupalGet('admin/structure/config_test/manage/dotted.default');
    $elements = $this->xpath('//input[@name="label"]');
    $this->assertIdentical((string) $elements[0]['value'], $edit['label']);
  }

}
