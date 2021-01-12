<?php

namespace Drupal\Tests\views_ui\Functional;

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
   * {@inheritdoc}
   */
  protected $defaultTheme = 'classy';

  /**
   * Tests contextual links in the preview form.
   */
  public function testPreviewContextual() {
    \Drupal::service('module_installer')->install(['contextual']);
    $this->resetAll();

    $this->drupalGet('admin/structure/views/view/test_preview/edit');
    $this->assertSession()->statusCodeEquals(200);
    $this->submitForm($edit = [], 'Update preview');

    $elements = $this->xpath('//div[@id="views-live-preview"]//ul[contains(@class, :ul-class)]/li[contains(@class, :li-class)]', [':ul-class' => 'contextual-links', ':li-class' => 'filter-add']);
    $this->assertCount(1, $elements, 'The contextual link to add a new field is shown.');

    $this->submitForm($edit = ['view_args' => '100'], 'Update preview');

    // Test that area text and exposed filters are present and rendered.
    $this->assertSession()->fieldExists('id');
    $this->assertText('Test header text', 'Rendered header text found');
    $this->assertText('Test footer text', 'Rendered footer text found.');
    $this->assertText('Test empty text', 'Rendered empty text found.');
  }

  /**
   * Tests arguments in the preview form.
   */
  public function testPreviewUI() {
    $this->drupalGet('admin/structure/views/view/test_preview/edit');
    $this->assertSession()->statusCodeEquals(200);

    $this->submitForm($edit = [], 'Update preview');

    $elements = $this->xpath('//div[@class = "view-content"]/div[contains(@class, views-row)]');
    $this->assertCount(5, $elements);

    // Filter just the first result.
    $this->submitForm($edit = ['view_args' => '1'], 'Update preview');

    $elements = $this->xpath('//div[@class = "view-content"]/div[contains(@class, views-row)]');
    $this->assertCount(1, $elements);

    // Filter for no results.
    $this->submitForm($edit = ['view_args' => '100'], 'Update preview');

    $elements = $this->xpath('//div[@class = "view-content"]/div[contains(@class, views-row)]');
    $this->assertCount(0, $elements);

    // Test that area text and exposed filters are present and rendered.
    $this->assertSession()->fieldExists('id');
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
    $this->drupalPostForm('admin/structure/views/add', $view, 'Save and edit');
    $this->clickLink(t('Feed'));
    $this->submitForm([], 'Update preview');
    $result = $this->xpath('//div[@id="views-live-preview"]/pre');
    $this->assertStringContainsString('<title>' . $view['page[title]'] . '</title>', $result[0]->getText(), 'The Feed RSS preview was rendered.');

    // Test the non-default UI display options.
    // Statistics only, no query.
    $settings = \Drupal::configFactory()->getEditable('views.settings');
    $settings->set('ui.show.performance_statistics', TRUE)->save();
    $this->drupalGet('admin/structure/views/view/test_preview/edit');
    $this->submitForm($edit = ['view_args' => '100'], 'Update preview');
    $this->assertText('Query build time');
    $this->assertText('Query execute time');
    $this->assertText('View render time');
    $this->assertNoRaw('<strong>Query</strong>');

    // Statistics and query.
    $settings->set('ui.show.sql_query.enabled', TRUE)->save();
    $this->submitForm($edit = ['view_args' => '100'], 'Update preview');
    $this->assertText('Query build time');
    $this->assertText('Query execute time');
    $this->assertText('View render time');
    $this->assertRaw('<strong>Query</strong>');
    $query_string = <<<SQL
SELECT "views_test_data"."name" AS "views_test_data_name"
FROM
{views_test_data} "views_test_data"
WHERE (views_test_data.id = '100')
SQL;
    $this->assertSession()->assertEscaped($query_string);

    // Test that the statistics and query are rendered above the preview.
    $this->assertLessThan(strpos($this->getSession()->getPage()->getContent(), 'view-test-preview'), strpos($this->getSession()->getPage()->getContent(), 'views-query-info'));

    // Test that statistics and query rendered below the preview.
    $settings->set('ui.show.sql_query.where', 'below')->save();
    $this->submitForm($edit = ['view_args' => '100'], 'Update preview');
    $this->assertLessThan(strpos($this->getSession()->getPage()->getContent(), 'views-query-info'), strpos($this->getSession()->getPage()->getContent(), 'view-test-preview'), 'Statistics shown below the preview.');

    // Test that the preview title isn't double escaped.
    $this->drupalPostForm("admin/structure/views/nojs/display/test_preview/default/title", $edit = ['title' => 'Double & escaped'], 'Apply');
    $this->submitForm([], 'Update preview');
    $elements = $this->xpath('//div[@id="views-live-preview"]/div[contains(@class, views-query-info)]//td[text()=:text]', [':text' => 'Double & escaped']);
    $this->assertCount(1, $elements);
  }

  /**
   * Tests the additional information query info area.
   */
  public function testPreviewAdditionalInfo() {
    \Drupal::service('module_installer')->install(['views_ui_test']);
    $this->resetAll();

    $this->drupalGet('admin/structure/views/view/test_preview/edit');
    $this->assertSession()->statusCodeEquals(200);

    $this->submitForm($edit = [], 'Update preview');

    // Check for implementation of hook_views_preview_info_alter().
    // @see views_ui_test.module
    $elements = $this->xpath('//div[@id="views-live-preview"]/div[contains(@class, views-query-info)]//td[text()=:text]', [':text' => 'Test row count']);
    $this->assertCount(1, $elements, 'Views Query Preview Info area altered.');
    // Check that additional assets are attached.
    $this->assertStringContainsString('views_ui_test/views_ui_test.test', $this->getDrupalSettings()['ajaxPageState']['libraries'], 'Attached library found.');
    $this->assertRaw('css/views_ui_test.test.css');
  }

  /**
   * Tests view validation error messages in the preview.
   */
  public function testPreviewError() {
    $this->drupalGet('admin/structure/views/view/test_preview_error/edit');
    $this->assertSession()->statusCodeEquals(200);

    $this->submitForm($edit = [], 'Update preview');

    $this->assertText('Unable to preview due to validation errors.', 'Preview error text found.');
  }

}
