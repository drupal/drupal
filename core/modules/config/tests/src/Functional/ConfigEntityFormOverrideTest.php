<?php

namespace Drupal\Tests\config\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Tests that config overrides do not bleed through in entity forms and lists.
 *
 * @group config
 */
class ConfigEntityFormOverrideTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = ['config_test'];

  /**
   * Tests that overrides do not affect forms or listing screens.
   */
  public function testFormsWithOverrides() {
    $this->drupalLogin($this->drupalCreateUser(['administer site configuration']));

    $original_label = 'Default';
    $overridden_label = 'Overridden label';
    $edited_label = 'Edited label';

    $config_test_storage = $this->container->get('entity.manager')->getStorage('config_test');

    // Set up an override.
    $settings['config']['config_test.dynamic.dotted.default']['label'] = (object) [
      'value' => $overridden_label,
      'required' => TRUE,
    ];
    $this->writeSettings($settings);

    // Test that the overridden label is loaded with the entity.
    $this->assertEqual($config_test_storage->load('dotted.default')->label(), $overridden_label);

    // Test that the original label on the listing page is intact.
    $this->drupalGet('admin/structure/config_test');
    $this->assertText($original_label);
    $this->assertNoText($overridden_label);

    // Test that the original label on the editing page is intact.
    $this->drupalGet('admin/structure/config_test/manage/dotted.default');
    $elements = $this->xpath('//input[@name="label"]');
    $this->assertIdentical($elements[0]->getValue(), $original_label);
    $this->assertNoText($overridden_label);

    // Change to a new label and test that the listing now has the edited label.
    $edit = [
      'label' => $edited_label,
    ];
    $this->drupalPostForm(NULL, $edit, t('Save'));
    $this->drupalGet('admin/structure/config_test');
    $this->assertNoText($overridden_label);
    $this->assertText($edited_label);

    // Test that the editing page now has the edited label.
    $this->drupalGet('admin/structure/config_test/manage/dotted.default');
    $elements = $this->xpath('//input[@name="label"]');
    $this->assertIdentical($elements[0]->getValue(), $edited_label);

    // Test that the overridden label is still loaded with the entity.
    $this->assertEqual($config_test_storage->load('dotted.default')->label(), $overridden_label);
  }

}
