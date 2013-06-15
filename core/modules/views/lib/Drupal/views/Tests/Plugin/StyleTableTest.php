<?php

/**
 * @file
 * Contains \Drupal\views\Tests\Plugin\StyleTableTest.
 */

namespace Drupal\views\Tests\Plugin;

class StyleTableTest extends PluginTestBase {

  /**
   * Views used by this test.
   *
   * @var array
   */
  public static $testViews = array('test_table');

  public static function getInfo() {
    return array(
      'name' => 'Style: Table',
      'description' => 'Tests the table style plugin.',
      'group' => 'Views Plugins',
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->enableViewsTestModule();
  }

  /**
   * Test table caption/summary/description.
   */
  public function testAccessibilitySettings() {
    $this->drupalGet('test-table');

    $result = $this->xpath('//caption');
    $this->assertTrue(count($result), 'The caption appears on the table.');
    $this->assertEqual(trim((string) $result[0]), 'caption-text');

    $result = $this->xpath('//summary');
    $this->assertTrue(count($result), 'The summary appears on the table.');
    $this->assertEqual(trim((string) $result[0]), 'summary-text');

    $result = $this->xpath('//caption/details');
    $this->assertTrue(count($result), 'The table description appears on the table.');
    $this->assertEqual(trim((string) $result[0]), 'description-text');

    // Remove the caption and ensure the caption is not displayed anymore.
    $view = entity_load('view', 'test_table');
    $display = &$view->getDisplay('default');
    $display['display_options']['style']['options']['caption'] = '';
    $view->save();

    $this->drupalGet('test-table');
    $result = $this->xpath('//caption');
    $this->assertFalse(trim((string) $result[0]), 'Ensure that the caption disappears.');

    // Remove the table summary.
    $display = &$view->getDisplay('default');
    $display['display_options']['style']['options']['summary'] = '';
    $view->save();

    $this->drupalGet('test-table');
    $result = $this->xpath('//summary');
    $this->assertFalse(count($result), 'Ensure that the summary disappears.');

    // Remove the table description.
    $display = &$view->getDisplay('default');
    $display['display_options']['style']['options']['description'] = '';
    $view->save();

    $this->drupalGet('test-table');
    $result = $this->xpath('//caption/details');
    $this->assertFalse(count($result), 'Ensure that the description disappears.');
  }

}
