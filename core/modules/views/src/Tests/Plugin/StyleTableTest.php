<?php

/**
 * @file
 * Contains \Drupal\views\Tests\Plugin\StyleTableTest.
 */

namespace Drupal\views\Tests\Plugin;

/**
 * Tests the table style views plugin.
 *
 * @group views
 */
class StyleTableTest extends PluginTestBase {

  /**
   * Views used by this test.
   *
   * @var array
   */
  public static $testViews = array('test_table');

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

  /**
   * Test table fields in columns.
   */
  public function testFieldInColumns() {
    $this->drupalGet('test-table');

    // Ensure that both columns are in separate tds.
    // Check for class " views-field-job ", because just "views-field-job" won't
    // do: "views-field-job-1" would also contain "views-field-job".
    // @see Drupal\system\Tests\Form\ElementTest::testButtonClasses().
    $result = $this->xpath('//tbody/tr/td[contains(concat(" ", @class, " "), " views-field-job ")]');
    $this->assertTrue(count($result), 'Ensure there is a td with the class views-field-job');
    $result = $this->xpath('//tbody/tr/td[contains(concat(" ", @class, " "), " views-field-job-1 ")]');
    $this->assertTrue(count($result), 'Ensure there is a td with the class views-field-job-1');

    // Combine the second job-column with the first one, with ', ' as separator.
    $view = entity_load('view', 'test_table');
    $display = &$view->getDisplay('default');
    $display['display_options']['style']['options']['columns']['job_1'] = 'job';
    $display['display_options']['style']['options']['info']['job']['separator'] = ', ';
    $view->save();

    // Ensure that both columns are properly combined.
    $this->drupalGet('test-table');

    $result = $this->xpath('//tbody/tr/td[contains(concat(" ", @class, " "), " views-field-job views-field-job-1 ")]');
    $this->assertTrue(count($result), 'Ensure that the job column class names are joined into a single column');

    $result = $this->xpath('//tbody/tr/td[contains(., "Drummer, Drummer")]');
    $this->assertTrue(count($result), 'Ensure the job column values are joined into a single column');
  }

}
