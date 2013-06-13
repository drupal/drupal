<?php

/**
 * @file
 * Contains \Drupal\views_ui\Tests\PreviewTest.
 */

namespace Drupal\views_ui\Tests;

/**
 * Tests the preview form in the UI.
 */
class PreviewTest extends UITestBase {

  /**
   * Views used by this test.
   *
   * @var array
   */
  public static $testViews = array('test_preview');

  public static function getInfo() {
    return array(
      'name' => 'Preview functionality',
      'description' => 'Tests the UI preview functionality.',
      'group' => 'Views UI',
    );
  }

  /**
   * Tests contextual links in the preview form.
   */
  protected function testPreviewContextual() {
    module_enable(array('contextual'));
    $this->drupalGet('admin/structure/views/view/test_preview/edit');
    $this->assertResponse(200);
    $this->drupalPost(NULL, $edit = array(), t('Update preview'));

    $elements = $this->xpath('//div[@id="views-live-preview"]//ul[contains(@class, :ul-class)]/li[contains(@class, :li-class)]', array(':ul-class' => 'contextual-links', ':li-class' => 'filter-add'));
    $this->assertEqual(count($elements), 1, 'The contextual link to add a new field is shown.');

    $this->drupalPost(NULL, $edit = array('view_args' => '100'), t('Update preview'));

    // Test that area text and exposed filters are present and rendered.
    $this->assertFieldByName('id', NULL, 'ID exposed filter field found.');
    $this->assertText('Test header text', 'Rendered header text found');
    $this->assertText('Test footer text', 'Rendered footer text found.');
    $this->assertText('Test empty text', 'Rendered empty text found.');
  }

  /**
   * Tests arguments in the preview form.
   */
  function testPreviewUI() {
    $this->drupalGet('admin/structure/views/view/test_preview/edit');
    $this->assertResponse(200);

    $this->drupalPost(NULL, $edit = array(), t('Update preview'));

    $elements = $this->xpath('//div[@class = "view-content"]/div[contains(@class, views-row)]');
    $this->assertEqual(count($elements), 5);

    // Filter just the first result.
    $this->drupalPost(NULL, $edit = array('view_args' => '1'), t('Update preview'));

    $elements = $this->xpath('//div[@class = "view-content"]/div[contains(@class, views-row)]');
    $this->assertEqual(count($elements), 1);

    // Filter for no results.
    $this->drupalPost(NULL, $edit = array('view_args' => '100'), t('Update preview'));

    $elements = $this->xpath('//div[@class = "view-content"]/div[contains(@class, views-row)]');
    $this->assertEqual(count($elements), 0);

    // Test that area text and exposed filters are present and rendered.
    $this->assertFieldByName('id', NULL, 'ID exposed filter field found.');
    $this->assertText('Test header text', 'Rendered header text found');
    $this->assertText('Test footer text', 'Rendered footer text found.');
    $this->assertText('Test empty text', 'Rendered empty text found.');
  }

  /**
   * Tests the actual preview response.
   */
  public function testPreviewController() {
    $result = $this->drupalGetAJAX('admin/structure/views/view/test_preview/preview/default');

    $result_commands = array();
    // Build a list of the result commands keyed by the js command.
    foreach ($result as $command) {
      $result_commands[$command['command']] = $command;
    }
    $this->assertTrue(isset($result_commands['insert']));
  }

}
