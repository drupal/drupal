<?php

namespace Drupal\views_ui\Tests;

use Drupal\Component\Serialization\Json;
use Drupal\Core\EventSubscriber\MainContentViewSubscriber;

/**
 * Tests the UI preview functionality.
 *
 * @group views_ui
 */
class PreviewTest extends UITestBase {

  /**
   * Views used by this test.
   *
   * @var array
   */
  public static $testViews = ['test_preview', 'test_preview_error', 'test_pager_full', 'test_mini_pager', 'test_click_sort'];

  /**
   * Tests contextual links in the preview form.
   */
  public function testPreviewContextual() {
    \Drupal::service('module_installer')->install(['contextual']);
    $this->resetAll();

    $this->drupalGet('admin/structure/views/view/test_preview/edit');
    $this->assertResponse(200);
    $this->drupalPostForm(NULL, $edit = [], t('Update preview'));

    $elements = $this->xpath('//div[@id="views-live-preview"]//ul[contains(@class, :ul-class)]/li[contains(@class, :li-class)]', [':ul-class' => 'contextual-links', ':li-class' => 'filter-add']);
    $this->assertEqual(count($elements), 1, 'The contextual link to add a new field is shown.');

    $this->drupalPostForm(NULL, $edit = ['view_args' => '100'], t('Update preview'));

    // Test that area text and exposed filters are present and rendered.
    $this->assertFieldByName('id', NULL, 'ID exposed filter field found.');
    $this->assertText('Test header text', 'Rendered header text found');
    $this->assertText('Test footer text', 'Rendered footer text found.');
    $this->assertText('Test empty text', 'Rendered empty text found.');
  }

  /**
   * Tests arguments in the preview form.
   */
  public function testPreviewUI() {
    $this->drupalGet('admin/structure/views/view/test_preview/edit');
    $this->assertResponse(200);

    $this->drupalPostForm(NULL, $edit = [], t('Update preview'));

    $elements = $this->xpath('//div[@class = "view-content"]/div[contains(@class, views-row)]');
    $this->assertEqual(count($elements), 5);

    // Filter just the first result.
    $this->drupalPostForm(NULL, $edit = ['view_args' => '1'], t('Update preview'));

    $elements = $this->xpath('//div[@class = "view-content"]/div[contains(@class, views-row)]');
    $this->assertEqual(count($elements), 1);

    // Filter for no results.
    $this->drupalPostForm(NULL, $edit = ['view_args' => '100'], t('Update preview'));

    $elements = $this->xpath('//div[@class = "view-content"]/div[contains(@class, views-row)]');
    $this->assertEqual(count($elements), 0);

    // Test that area text and exposed filters are present and rendered.
    $this->assertFieldByName('id', NULL, 'ID exposed filter field found.');
    $this->assertText('Test header text', 'Rendered header text found');
    $this->assertText('Test footer text', 'Rendered footer text found.');
    $this->assertText('Test empty text', 'Rendered empty text found.');

    // Test feed preview.
    $view = [];
    $view['label'] = $this->randomMachineName(16);
    $view['id'] = strtolower($this->randomMachineName(16));
    $view['page[create]'] = 1;
    $view['page[title]'] = $this->randomMachineName(16);
    $view['page[path]'] = $this->randomMachineName(16);
    $view['page[feed]'] = 1;
    $view['page[feed_properties][path]'] = $this->randomMachineName(16);
    $this->drupalPostForm('admin/structure/views/add', $view, t('Save and edit'));
    $this->clickLink(t('Feed'));
    $this->drupalPostForm(NULL, [], t('Update preview'));
    $result = $this->xpath('//div[@id="views-live-preview"]/pre');
    $this->assertTrue(strpos($result[0], '<title>' . $view['page[title]'] . '</title>'), 'The Feed RSS preview was rendered.');

    // Test the non-default UI display options.
    // Statistics only, no query.
    $settings = \Drupal::configFactory()->getEditable('views.settings');
    $settings->set('ui.show.performance_statistics', TRUE)->save();
    $this->drupalGet('admin/structure/views/view/test_preview/edit');
    $this->drupalPostForm(NULL, $edit = ['view_args' => '100'], t('Update preview'));
    $this->assertText(t('Query build time'));
    $this->assertText(t('Query execute time'));
    $this->assertText(t('View render time'));
    $this->assertNoRaw('<strong>Query</strong>');

    // Statistics and query.
    $settings->set('ui.show.sql_query.enabled', TRUE)->save();
    $this->drupalPostForm(NULL, $edit = ['view_args' => '100'], t('Update preview'));
    $this->assertText(t('Query build time'));
    $this->assertText(t('Query execute time'));
    $this->assertText(t('View render time'));
    $this->assertRaw('<strong>Query</strong>');
    $this->assertText("SELECT views_test_data.name AS views_test_data_name\nFROM \n{views_test_data} views_test_data\nWHERE (views_test_data.id = &#039;100&#039; )");

    // Test that the statistics and query are rendered above the preview.
    $this->assertTrue(strpos($this->getRawContent(), 'views-query-info') < strpos($this->getRawContent(), 'view-test-preview'), 'Statistics shown above the preview.');

    // Test that statistics and query rendered below the preview.
    $settings->set('ui.show.sql_query.where', 'below')->save();
    $this->drupalPostForm(NULL, $edit = ['view_args' => '100'], t('Update preview'));
    $this->assertTrue(strpos($this->getRawContent(), 'view-test-preview') < strpos($this->getRawContent(), 'views-query-info'), 'Statistics shown below the preview.');

    // Test that the preview title isn't double escaped.
    $this->drupalPostForm("admin/structure/views/nojs/display/test_preview/default/title", $edit = ['title' => 'Double & escaped'], t('Apply'));
    $this->drupalPostForm(NULL, [], t('Update preview'));
    $elements = $this->xpath('//div[@id="views-live-preview"]/div[contains(@class, views-query-info)]//td[text()=:text]', [':text' => t('Double & escaped')]);
    $this->assertEqual(1, count($elements));
  }

  /**
   * Tests the taxonomy term preview AJAX.
   *
   * This tests a specific regression in the taxonomy term view preview.
   *
   * @see https://www.drupal.org/node/2452659
   */
  public function testTaxonomyAJAX() {
    \Drupal::service('module_installer')->install(['taxonomy']);
    $this->getPreviewAJAX('taxonomy_term', 'page_1', 0);
  }

  /**
   * Tests pagers in the preview form.
   */
  public function testPreviewWithPagersUI() {

    // Create 11 nodes and make sure that everyone is returned.
    $this->drupalCreateContentType(['type' => 'page']);
    for ($i = 0; $i < 11; $i++) {
      $this->drupalCreateNode();
    }

    // Test Full Pager.
    $this->getPreviewAJAX('test_pager_full', 'default', 5);

    // Test that the pager is present and rendered.
    $elements = $this->xpath('//ul[contains(@class, :class)]/li', [':class' => 'pager__items']);
    $this->assertTrue(!empty($elements), 'Full pager found.');

    // Verify elements and links to pages.
    // We expect to find 5 elements: current page == 1, links to pages 2 and
    // and 3, links to 'next >' and 'last >>' pages.
    $this->assertClass($elements[0], 'is-active', 'Element for current page has .is-active class.');
    $this->assertTrue($elements[0]->a, 'Element for current page has link.');

    $this->assertClass($elements[1], 'pager__item', 'Element for page 2 has .pager__item class.');
    $this->assertTrue($elements[1]->a, 'Link to page 2 found.');

    $this->assertClass($elements[2], 'pager__item', 'Element for page 3 has .pager__item class.');
    $this->assertTrue($elements[2]->a, 'Link to page 3 found.');

    $this->assertClass($elements[3], 'pager__item--next', 'Element for next page has .pager__item--next class.');
    $this->assertTrue($elements[3]->a, 'Link to next page found.');

    $this->assertClass($elements[4], 'pager__item--last', 'Element for last page has .pager__item--last class.');
    $this->assertTrue($elements[4]->a, 'Link to last page found.');

    // Navigate to next page.
    $elements = $this->xpath('//li[contains(@class, :class)]/a', [':class' => 'pager__item--next']);
    $this->clickPreviewLinkAJAX($elements[0]['href'], 5);

    // Test that the pager is present and rendered.
    $elements = $this->xpath('//ul[contains(@class, :class)]/li', [':class' => 'pager__items']);
    $this->assertTrue(!empty($elements), 'Full pager found.');

    // Verify elements and links to pages.
    // We expect to find 7 elements: links to '<< first' and '< previous'
    // pages, link to page 1, current page == 2, link to page 3 and links
    // to 'next >' and 'last >>' pages.
    $this->assertClass($elements[0], 'pager__item--first', 'Element for first page has .pager__item--first class.');
    $this->assertTrue($elements[0]->a, 'Link to first page found.');

    $this->assertClass($elements[1], 'pager__item--previous', 'Element for previous page has .pager__item--previous class.');
    $this->assertTrue($elements[1]->a, 'Link to previous page found.');

    $this->assertClass($elements[2], 'pager__item', 'Element for page 1 has .pager__item class.');
    $this->assertTrue($elements[2]->a, 'Link to page 1 found.');

    $this->assertClass($elements[3], 'is-active', 'Element for current page has .is-active class.');
    $this->assertTrue($elements[3]->a, 'Element for current page has link.');

    $this->assertClass($elements[4], 'pager__item', 'Element for page 3 has .pager__item class.');
    $this->assertTrue($elements[4]->a, 'Link to page 3 found.');

    $this->assertClass($elements[5], 'pager__item--next', 'Element for next page has .pager__item--next class.');
    $this->assertTrue($elements[5]->a, 'Link to next page found.');

    $this->assertClass($elements[6], 'pager__item--last', 'Element for last page has .pager__item--last class.');
    $this->assertTrue($elements[6]->a, 'Link to last page found.');

    // Test Mini Pager.
    $this->getPreviewAJAX('test_mini_pager', 'default', 3);

    // Test that the pager is present and rendered.
    $elements = $this->xpath('//ul[contains(@class, :class)]/li', [':class' => 'pager__items']);
    $this->assertTrue(!empty($elements), 'Mini pager found.');

    // Verify elements and links to pages.
    // We expect to find current pages element with no link, next page element
    // with a link, and not to find previous page element.
    $this->assertClass($elements[0], 'is-active', 'Element for current page has .is-active class.');

    $this->assertClass($elements[1], 'pager__item--next', 'Element for next page has .pager__item--next class.');
    $this->assertTrue($elements[1]->a, 'Link to next page found.');

    // Navigate to next page.
    $elements = $this->xpath('//li[contains(@class, :class)]/a', [':class' => 'pager__item--next']);
    $this->clickPreviewLinkAJAX($elements[0]['href'], 3);

    // Test that the pager is present and rendered.
    $elements = $this->xpath('//ul[contains(@class, :class)]/li', [':class' => 'pager__items']);
    $this->assertTrue(!empty($elements), 'Mini pager found.');

    // Verify elements and links to pages.
    // We expect to find 3 elements: previous page with a link, current
    // page with no link, and next page with a link.
    $this->assertClass($elements[0], 'pager__item--previous', 'Element for previous page has .pager__item--previous class.');
    $this->assertTrue($elements[0]->a, 'Link to previous page found.');

    $this->assertClass($elements[1], 'is-active', 'Element for current page has .is-active class.');
    $this->assertFalse(isset($elements[1]->a), 'Element for current page has no link.');

    $this->assertClass($elements[2], 'pager__item--next', 'Element for next page has .pager__item--next class.');
    $this->assertTrue($elements[2]->a, 'Link to next page found.');
  }

  /**
   * Tests the additional information query info area.
   */
  public function testPreviewAdditionalInfo() {
    \Drupal::service('module_installer')->install(['views_ui_test']);
    $this->resetAll();

    $this->drupalGet('admin/structure/views/view/test_preview/edit');
    $this->assertResponse(200);

    $this->drupalPostForm(NULL, $edit = [], t('Update preview'));

    // Check for implementation of hook_views_preview_info_alter().
    // @see views_ui_test.module
    $elements = $this->xpath('//div[@id="views-live-preview"]/div[contains(@class, views-query-info)]//td[text()=:text]', [':text' => t('Test row count')]);
    $this->assertEqual(count($elements), 1, 'Views Query Preview Info area altered.');
    // Check that additional assets are attached.
    $this->assertTrue(strpos($this->getDrupalSettings()['ajaxPageState']['libraries'], 'views_ui_test/views_ui_test.test') !== FALSE, 'Attached library found.');
    $this->assertRaw('css/views_ui_test.test.css', 'Attached CSS asset found.');
  }

  /**
   * Tests view validation error messages in the preview.
   */
  public function testPreviewError() {
    $this->drupalGet('admin/structure/views/view/test_preview_error/edit');
    $this->assertResponse(200);

    $this->drupalPostForm(NULL, $edit = [], t('Update preview'));

    $this->assertText('Unable to preview due to validation errors.', 'Preview error text found.');
  }

  /**
   * Tests the link to sort in the preview form.
   */
  public function testPreviewSortLink() {

    // Get the preview.
    $this->getPreviewAJAX('test_click_sort', 'page_1', 0);

    // Test that the header label is present.
    $elements = $this->xpath('//th[contains(@class, :class)]/a', [':class' => 'views-field views-field-name']);
    $this->assertTrue(!empty($elements), 'The header label is present.');

    // Verify link.
    $this->assertLinkByHref('preview/page_1?_wrapper_format=drupal_ajax&order=name&sort=desc', 0, 'The output URL is as expected.');

    // Click link to sort.
    $this->clickPreviewLinkAJAX($elements[0]['href'], 0);

    // Test that the header label is present.
    $elements = $this->xpath('//th[contains(@class, :class)]/a', [':class' => 'views-field views-field-name is-active']);
    $this->assertTrue(!empty($elements), 'The header label is present.');

    // Verify link.
    $this->assertLinkByHref('preview/page_1?_wrapper_format=drupal_ajax&order=name&sort=asc', 0, 'The output URL is as expected.');
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
    $result = $this->drupalPostAjaxForm(NULL, [], ['op' => t('Update preview')]);
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
    $ajax_settings = [
      'wrapper' => 'views-preview-wrapper',
      'method' => 'replaceWith',
    ];
    $url = $this->getAbsoluteUrl($url);
    $post = ['js' => 'true'] + $this->getAjaxPageStatePostData();
    $result = Json::decode($this->drupalPost($url, '', $post, ['query' => [MainContentViewSubscriber::WRAPPER_FORMAT => 'drupal_ajax']]));
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
    $result_commands = [];
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
