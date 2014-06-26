<?php

/**
 * @file
 * Contains \Drupal\views_ui\Tests\PreviewTest.
 */

namespace Drupal\views_ui\Tests;

use Drupal\Component\Serialization\Json;

/**
 * Tests the preview form in the UI.
 */
class PreviewTest extends UITestBase {

  /**
   * Views used by this test.
   *
   * @var array
   */
  public static $testViews = array('test_preview', 'test_pager_full', 'test_mini_pager');

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
    \Drupal::moduleHandler()->install(array('contextual'));
    $this->resetAll();

    $this->drupalGet('admin/structure/views/view/test_preview/edit');
    $this->assertResponse(200);
    $this->drupalPostForm(NULL, $edit = array(), t('Update preview'));

    $elements = $this->xpath('//div[@id="views-live-preview"]//ul[contains(@class, :ul-class)]/li[contains(@class, :li-class)]', array(':ul-class' => 'contextual-links', ':li-class' => 'filter-add'));
    $this->assertEqual(count($elements), 1, 'The contextual link to add a new field is shown.');

    $this->drupalPostForm(NULL, $edit = array('view_args' => '100'), t('Update preview'));

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

    $this->drupalPostForm(NULL, $edit = array(), t('Update preview'));

    $elements = $this->xpath('//div[@class = "view-content"]/div[contains(@class, views-row)]');
    $this->assertEqual(count($elements), 5);

    // Filter just the first result.
    $this->drupalPostForm(NULL, $edit = array('view_args' => '1'), t('Update preview'));

    $elements = $this->xpath('//div[@class = "view-content"]/div[contains(@class, views-row)]');
    $this->assertEqual(count($elements), 1);

    // Filter for no results.
    $this->drupalPostForm(NULL, $edit = array('view_args' => '100'), t('Update preview'));

    $elements = $this->xpath('//div[@class = "view-content"]/div[contains(@class, views-row)]');
    $this->assertEqual(count($elements), 0);

    // Test that area text and exposed filters are present and rendered.
    $this->assertFieldByName('id', NULL, 'ID exposed filter field found.');
    $this->assertText('Test header text', 'Rendered header text found');
    $this->assertText('Test footer text', 'Rendered footer text found.');
    $this->assertText('Test empty text', 'Rendered empty text found.');

    // Test feed preview.
    $view = array();
    $view['label'] = $this->randomName(16);
    $view['id'] = strtolower($this->randomName(16));
    $view['page[create]'] = 1;
    $view['page[title]'] = $this->randomName(16);
    $view['page[path]'] = $this->randomName(16);
    $view['page[feed]'] = 1;
    $view['page[feed_properties][path]'] = $this->randomName(16);
    $this->drupalPostForm('admin/structure/views/add', $view, t('Save and edit'));
    $this->clickLink(t('Feed'));
    $this->drupalPostForm(NULL, array(), t('Update preview'));
    $result = $this->xpath('//div[@id="views-live-preview"]/pre');
    $this->assertTrue(strpos($result[0], '<title>' . $view['page[title]'] . '</title>'), 'The Feed RSS preview was rendered.');
  }

  /**
   * Tests pagers in the preview form.
   */
  public function testPreviewWithPagersUI() {

    // Create 11 nodes and make sure that everyone is returned.
    for ($i = 0; $i < 11; $i++) {
      $this->drupalCreateNode();
    }

    // Test Full Pager.
    $this->getPreviewAJAX('test_pager_full', 'default', 5);

    // Test that the pager is present and rendered.
    $elements = $this->xpath('//ul[@class = "pager"]/li');
    $this->assertTrue(!empty($elements), 'Full pager found.');

    // Verify elements and links to pages.
    // We expect to find 5 elements: current page == 1, links to pages 2 and
    // and 3, links to 'next >' and 'last >>' pages.
    $this->assertClass($elements[0], 'pager-current', 'Element for current page has .pager-current class.');
    $this->assertFalse(isset($elements[0]->a), 'Element for current page has no link.');

    $this->assertClass($elements[1], 'pager-item', "Element for page 2 has .pager-item class.");
    $this->assertTrue($elements[1]->a, "Link to page 2 found.");

    $this->assertClass($elements[2], 'pager-item', "Element for page 3 has .pager-item class.");
    $this->assertTrue($elements[2]->a, "Link to page 3 found.");

    $this->assertClass($elements[3], 'pager-next', "Element for next page has .pager-next class.");
    $this->assertTrue($elements[3]->a, "Link to next page found.");

    $this->assertClass($elements[4], 'pager-last', "Element for last page has .pager-last class.");
    $this->assertTrue($elements[4]->a, "Link to last page found.");

    // Navigate to next page.
    $elements = $this->xpath('//li[contains(@class, :class)]/a', array(':class' => 'pager-next'));
    $this->clickPreviewLinkAJAX($elements[0]['href'], 5);

    // Test that the pager is present and rendered.
    $elements = $this->xpath('//ul[@class = "pager"]/li');
    $this->assertTrue(!empty($elements), 'Full pager found.');

    // Verify elements and links to pages.
    // We expect to find 7 elements: links to '<< first' and '< previous'
    // pages, link to page 1, current page == 2, link to page 3 and links
    // to 'next >' and 'last >>' pages.
    $this->assertClass($elements[0], 'pager-first', "Element for next page has .pager-first class.");
    $this->assertTrue($elements[0]->a, "Link to first page found.");

    $this->assertClass($elements[1], 'pager-previous', "Element for previous page has .pager-previous class.");
    $this->assertTrue($elements[1]->a, "Link to previous page found.");

    $this->assertClass($elements[2], 'pager-item', "Element for page 1 has .pager-item class.");
    $this->assertTrue($elements[2]->a, "Link to page 1 found.");

    $this->assertClass($elements[3], 'pager-current', 'Element for current page has .pager-current class.');
    $this->assertFalse(isset($elements[3]->a), 'Element for current page has no link.');

    $this->assertClass($elements[4], 'pager-item', "Element for page 3 has .pager-item class.");
    $this->assertTrue($elements[4]->a, "Link to page 3 found.");

    $this->assertClass($elements[5], 'pager-next', "Element for next page has .pager-next class.");
    $this->assertTrue($elements[5]->a, "Link to next page found.");

    $this->assertClass($elements[6], 'pager-last', "Element for last page has .pager-last class.");
    $this->assertTrue($elements[6]->a, "Link to last page found.");

    // Test Mini Pager.
    $this->getPreviewAJAX('test_mini_pager', 'default', 3);

    // Test that the pager is present and rendered.
    $elements = $this->xpath('//ul[@class = "pager"]/li');
    $this->assertTrue(!empty($elements), 'Mini pager found.');

    // Verify elements and links to pages.
    // We expect to find 3 elements: previous and current pages, with no link,
    // and next page with a link.
    $this->assertClass($elements[0], 'pager-previous', 'Element for previous page has .pager-previous class.');
    $this->assertFalse(isset($elements[0]->a), 'Element for previous page has no link.');

    $this->assertClass($elements[1], 'pager-current', 'Element for current page has .pager-current class.');
    $this->assertFalse(isset($elements[1]->a), 'Element for current page has no link.');

    $this->assertClass($elements[2], 'pager-next', "Element for next page has .pager-next class.");
    $this->assertTrue($elements[2]->a, "Link to next page found.");

    // Navigate to next page.
    $elements = $this->xpath('//li[contains(@class, :class)]/a', array(':class' => 'pager-next'));
    $this->clickPreviewLinkAJAX($elements[0]['href'], 3);

    // Test that the pager is present and rendered.
    $elements = $this->xpath('//ul[@class = "pager"]/li');
    $this->assertTrue(!empty($elements), 'Mini pager found.');

    // Verify elements and links to pages.
    // We expect to find 3 elements: previous page with a link, current
    // page with no link, and next page with a link.
    $this->assertClass($elements[0], 'pager-previous', 'Element for previous page has .pager-previous class.');
    $this->assertTrue($elements[0]->a, "Link to previous page found.");

    $this->assertClass($elements[1], 'pager-current', 'Element for current page has .pager-current class.');
    $this->assertFalse(isset($elements[1]->a), 'Element for current page has no link.');

    $this->assertClass($elements[2], 'pager-next', "Element for next page has .pager-next class.");
    $this->assertTrue($elements[2]->a, "Link to next page found.");
  }

  /**
   * Get the preview form and force an AJAX preview update.
   *
   * @param string $view_name
   *   The view to test.
   * @param string $panel_id
   *   The view panel to test.
   * @param int $row_count
   *   The expected number of rows in the preview.
   */
  protected function getPreviewAJAX($view_name, $panel_id, $row_count) {
    $this->drupalGet('admin/structure/views/view/' . $view_name . '/preview/' . $panel_id);
    $result = $this->drupalPostAjaxForm(NULL, array(), array('op' => t('Update preview')));
    $this->assertPreviewAJAX($result, $row_count);
  }

  /**
   * Mimic clicking on a preview link.
   *
   * @param string $url
   *   The url to navigate to.
   * @param int $row_count
   *   The expected number of rows in the preview.
   */
  protected function clickPreviewLinkAJAX($url, $row_count) {
    $content = $this->content;
    $drupal_settings = $this->drupalSettings;
    $ajax_settings = array(
      'wrapper' => 'views-preview-wrapper',
      'method' => 'replaceWith',
    );
    $url = $this->getAbsoluteUrl($url);
    $post = array('js' => 'true') + $this->getAjaxPageStatePostData();
    $result = Json::decode($this->drupalPost($url, 'application/vnd.drupal-ajax', $post));
    if (!empty($result)) {
      $this->drupalProcessAjaxResponse($content, $result, $ajax_settings, $drupal_settings);
    }
    $this->assertPreviewAJAX($result, $row_count);
  }

  /**
   * Assert that the AJAX response contains expected data.
   *
   * @param array $result
   *   An array of AJAX commands.
   * @param int $row_count
   *   The expected number of rows in the preview.
   */
  protected function assertPreviewAJAX($result, $row_count) {
    // Has AJAX callback replied with an insert command? If so, we can
    // assume that the page content was updated with AJAX returned data.
    $result_commands = array();
    foreach ($result as $command) {
      $result_commands[$command['command']] = $command;
    }
    $this->assertTrue(isset($result_commands['insert']), 'AJAX insert command received.');

    // Test if preview contains the expected number of rows.
    $elements = $this->xpath('//div[@class = "view-content"]/div[contains(@class, views-row)]');
    $this->assertEqual(count($elements), $row_count, 'Expected items found on page.');
  }

  /**
   * Asserts that an element has a given class.
   *
   * @param \SimpleXMLElement $element
   *   The element to test.
   * @param string $class
   *   The class to assert.
   * @param string $message
   *   (optional) A verbose message to output.
   */
  protected function assertClass(\SimpleXMLElement $element, $class, $message = NULL) {
    if (!isset($message)) {
      $message = "Class .$class found.";
    }
    $this->assertTrue(strpos($element['class'], $class) !== FALSE, $message);
  }

}
