<?php

/**
 * @file
 * Contains \Drupal\views\Tests\UI\PreviewTest.
 */

namespace Drupal\views\Tests\UI;

/**
 * Tests the preview form in the UI.
 */
class PreviewTest extends UITestBase {

  public static function getInfo() {
    return array(
      'name' => 'Preview functionality',
      'description' => 'Tests the UI preview functionality.',
      'group' => 'Views UI',
    );
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
  }

}
