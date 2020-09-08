<?php

namespace Drupal\Tests\views_ui\FunctionalJavascript;

use Behat\Mink\Element\NodeElement;
use Drupal\Core\Database\Database;
use Drupal\FunctionalJavascriptTests\WebDriverTestBase;
use Drupal\views\Tests\ViewTestData;

/**
 * Tests the UI preview functionality.
 *
 * @group views_ui
 */
class PreviewTest extends WebDriverTestBase {

  /**
   * Views used by this test.
   *
   * @var array
   */
  public static $testViews = ['test_preview', 'test_pager_full_ajax', 'test_mini_pager_ajax', 'test_click_sort_ajax'];

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'node',
    'views',
    'views_ui',
    'views_test_config',
  ];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'classy';

  /**
   * {@inheritdoc}
   */
  public function setUp(): void {
    parent::setUp();

    ViewTestData::createTestViews(self::class, ['views_test_config']);

    $this->enableViewsTestModule();

    $admin_user = $this->drupalCreateUser([
      'administer site configuration',
      'administer views',
      'administer nodes',
      'access content overview',
    ]);

    // Disable automatic live preview to make the sequence of calls clearer.
    \Drupal::configFactory()->getEditable('views.settings')->set('ui.always_live_preview', FALSE)->save();
    $this->drupalLogin($admin_user);
  }

  /**
   * Sets up the views_test_data.module.
   *
   * Because the schema of views_test_data.module is dependent on the test
   * using it, it cannot be enabled normally.
   */
  protected function enableViewsTestModule() {
    // Define the schema and views data variable before enabling the test module.
    \Drupal::state()->set('views_test_data_schema', $this->schemaDefinition());
    \Drupal::state()->set('views_test_data_views_data', $this->viewsData());

    \Drupal::service('module_installer')->install(['views_test_data']);
    $this->resetAll();
    $this->rebuildContainer();
    $this->container->get('module_handler')->reload();

    // Load the test dataset.
    $data_set = $this->dataSet();
    $query = Database::getConnection()->insert('views_test_data')
      ->fields(array_keys($data_set[0]));
    foreach ($data_set as $record) {
      $query->values($record);
    }
    $query->execute();
  }

  /**
   * Returns the schema definition.
   *
   * @internal
   */
  protected function schemaDefinition() {
    return ViewTestData::schemaDefinition();
  }

  /**
   * Returns the views data definition.
   */
  protected function viewsData() {
    return ViewTestData::viewsData();
  }

  /**
   * Returns a very simple test dataset.
   */
  protected function dataSet() {
    return ViewTestData::dataSet();
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
    $this->getPreviewAJAX('test_pager_full_ajax', 'default', 5);

    // Test that the pager is present and rendered.
    $elements = $this->xpath('//ul[contains(@class, :class)]/li', [':class' => 'pager__items']);
    $this->assertTrue(!empty($elements), 'Full pager found.');

    // Verify elements and links to pages.
    // We expect to find 5 elements: current page == 1, links to pages 2 and
    // and 3, links to 'next >' and 'last >>' pages.
    $this->assertClass($elements[0], 'is-active', 'Element for current page has .is-active class.');
    $this->assertNotEmpty($elements[0]->find('css', 'a'), 'Element for current page has link.');

    $this->assertClass($elements[1], 'pager__item', 'Element for page 2 has .pager__item class.');
    $this->assertNotEmpty($elements[1]->find('css', 'a'), 'Link to page 2 found.');

    $this->assertClass($elements[2], 'pager__item', 'Element for page 3 has .pager__item class.');
    $this->assertNotEmpty($elements[2]->find('css', 'a'), 'Link to page 3 found.');

    $this->assertClass($elements[3], 'pager__item--next', 'Element for next page has .pager__item--next class.');
    $this->assertNotEmpty($elements[3]->find('css', 'a'), 'Link to next page found.');

    $this->assertClass($elements[4], 'pager__item--last', 'Element for last page has .pager__item--last class.');
    $this->assertNotEmpty($elements[4]->find('css', 'a'), 'Link to last page found.');

    // Navigate to next page.
    $elements = $this->xpath('//li[contains(@class, :class)]/a', [':class' => 'pager__item--next']);
    $this->clickPreviewLinkAJAX($elements[0], 5);

    // Test that the pager is present and rendered.
    $elements = $this->xpath('//ul[contains(@class, :class)]/li', [':class' => 'pager__items']);
    $this->assertTrue(!empty($elements), 'Full pager found.');

    // Verify elements and links to pages.
    // We expect to find 7 elements: links to '<< first' and '< previous'
    // pages, link to page 1, current page == 2, link to page 3 and links
    // to 'next >' and 'last >>' pages.
    $this->assertClass($elements[0], 'pager__item--first', 'Element for first page has .pager__item--first class.');
    $this->assertNotEmpty($elements[0]->find('css', 'a'), 'Link to first page found.');

    $this->assertClass($elements[1], 'pager__item--previous', 'Element for previous page has .pager__item--previous class.');
    $this->assertNotEmpty($elements[1]->find('css', 'a'), 'Link to previous page found.');

    $this->assertClass($elements[2], 'pager__item', 'Element for page 1 has .pager__item class.');
    $this->assertNotEmpty($elements[2]->find('css', 'a'), 'Link to page 1 found.');

    $this->assertClass($elements[3], 'is-active', 'Element for current page has .is-active class.');
    $this->assertNotEmpty($elements[3]->find('css', 'a'), 'Element for current page has link.');

    $this->assertClass($elements[4], 'pager__item', 'Element for page 3 has .pager__item class.');
    $this->assertNotEmpty($elements[4]->find('css', 'a'), 'Link to page 3 found.');

    $this->assertClass($elements[5], 'pager__item--next', 'Element for next page has .pager__item--next class.');
    $this->assertNotEmpty($elements[5]->find('css', 'a'), 'Link to next page found.');

    $this->assertClass($elements[6], 'pager__item--last', 'Element for last page has .pager__item--last class.');
    $this->assertNotEmpty($elements[6]->find('css', 'a'), 'Link to last page found.');

    // Test Mini Pager.
    $this->getPreviewAJAX('test_mini_pager_ajax', 'default', 3);

    // Test that the pager is present and rendered.
    $elements = $this->xpath('//ul[contains(@class, :class)]/li', [':class' => 'pager__items']);
    $this->assertTrue(!empty($elements), 'Mini pager found.');

    // Verify elements and links to pages.
    // We expect to find current pages element with no link, next page element
    // with a link, and not to find previous page element.
    $this->assertClass($elements[0], 'is-active', 'Element for current page has .is-active class.');

    $this->assertClass($elements[1], 'pager__item--next', 'Element for next page has .pager__item--next class.');
    $this->assertNotEmpty($elements[1]->find('css', 'a'), 'Link to next page found.');

    // Navigate to next page.
    $elements = $this->xpath('//li[contains(@class, :class)]/a', [':class' => 'pager__item--next']);
    $this->clickPreviewLinkAJAX($elements[0], 3);

    // Test that the pager is present and rendered.
    $elements = $this->xpath('//ul[contains(@class, :class)]/li', [':class' => 'pager__items']);
    $this->assertTrue(!empty($elements), 'Mini pager found.');

    // Verify elements and links to pages.
    // We expect to find 3 elements: previous page with a link, current
    // page with no link, and next page with a link.
    $this->assertClass($elements[0], 'pager__item--previous', 'Element for previous page has .pager__item--previous class.');
    $this->assertNotEmpty($elements[0]->find('css', 'a'), 'Link to previous page found.');

    $this->assertClass($elements[1], 'is-active', 'Element for current page has .is-active class.');
    $this->assertEmpty($elements[1]->find('css', 'a'), 'Element for current page has no link.');

    $this->assertClass($elements[2], 'pager__item--next', 'Element for next page has .pager__item--next class.');
    $this->assertNotEmpty($elements[2]->find('css', 'a'), 'Link to next page found.');
  }

  /**
   * Tests the link to sort in the preview form.
   */
  public function testPreviewSortLink() {
    // Get the preview.
    $this->getPreviewAJAX('test_click_sort_ajax', 'page_1', 0);

    // Test that the header label is present.
    $elements = $this->xpath('//th[contains(@class, :class)]/a', [':class' => 'views-field views-field-name']);
    $this->assertTrue(!empty($elements), 'The header label is present.');

    // Verify link.
    $this->assertSession()->linkByHrefExists('preview/page_1?_wrapper_format=drupal_ajax&order=name&sort=desc', 0, 'The output URL is as expected.');

    // Click link to sort.
    $elements[0]->click();
    $sort_link = $this->assertSession()->waitForElement('xpath', '//th[contains(@class, \'views-field views-field-name is-active\')]/a');

    $this->assertNotEmpty($sort_link);

    // Verify link.
    $this->assertSession()->linkByHrefExists('preview/page_1?_wrapper_format=drupal_ajax&order=name&sort=asc', 0, 'The output URL is as expected.');
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
    $this->drupalGet('admin/structure/views/view/' . $view_name . '/edit/' . $panel_id);
    $this->getSession()->getPage()->pressButton('Update preview');
    $this->assertSession()->assertWaitOnAjaxRequest();
    $this->assertPreviewAJAX($row_count);
  }

  /**
   * Click on a preview link.
   *
   * @param \Behat\Mink\Element\NodeElement $element
   *   The element to click.
   * @param int $row_count
   *   The expected number of rows in the preview.
   */
  protected function clickPreviewLinkAJAX(NodeElement $element, $row_count) {
    $element->click();
    $this->assertSession()->assertWaitOnAjaxRequest();
    $this->assertPreviewAJAX($row_count);
  }

  /**
   * Assert that the preview contains expected data.
   *
   * @param int $row_count
   *   The expected number of rows in the preview.
   */
  protected function assertPreviewAJAX($row_count) {
    $elements = $this->getSession()->getPage()->findAll('css', '.view-content .views-row');
    $this->assertCount($row_count, $elements, 'Expected items found on page.');
  }

  /**
   * Asserts that an element has a given class.
   *
   * @param \Behat\Mink\Element\NodeElement $element
   *   The element to test.
   * @param string $class
   *   The class to assert.
   * @param string $message
   *   (optional) A verbose message to output.
   */
  protected function assertClass(NodeElement $element, $class, $message = NULL) {
    if (!isset($message)) {
      $message = "Class .$class found.";
    }
    $this->assertStringContainsString($class, $element->getAttribute('class'), $message);
  }

}
