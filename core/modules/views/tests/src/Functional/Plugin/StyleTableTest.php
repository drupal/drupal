<?php

declare(strict_types=1);

namespace Drupal\Tests\views\Functional\Plugin;

use Drupal\Core\Database\Database;
use Drupal\dynamic_page_cache\EventSubscriber\DynamicPageCacheSubscriber;
use Drupal\Tests\views\Functional\ViewTestBase;
use Drupal\views\Entity\View;

/**
 * Tests the table style views plugin.
 *
 * @group views
 */
class StyleTableTest extends ViewTestBase {

  /**
   * Views used by this test.
   *
   * @var array
   */
  public static $testViews = ['test_table'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected function setUp($import_test_views = TRUE, $modules = ['views_test_config']): void {
    parent::setUp($import_test_views, $modules);

    $this->enableViewsTestModule();
  }

  /**
   * Tests table caption/summary/description.
   */
  public function testAccessibilitySettings(): void {
    $this->drupalGet('test-table');

    $this->assertSession()->elementExists('xpath', '//caption/child::text()');
    $this->assertSession()->elementTextEquals('xpath', '//caption/child::text()', 'caption-text');

    $this->assertSession()->elementExists('xpath', '//summary/child::text()');
    $this->assertSession()->elementTextEquals('xpath', '//summary/child::text()', 'summary-text');
    // Check that the summary has the right accessibility settings.
    $this->assertSession()->elementAttributeExists('xpath', '//summary', 'role');
    $this->assertSession()->elementAttributeExists('xpath', '//summary', 'aria-expanded');

    $this->assertSession()->elementExists('xpath', '//caption/details/child::text()[normalize-space()]');
    $this->assertSession()->elementTextEquals('xpath', '//caption/details/child::text()[normalize-space()]', 'description-text');

    // Remove the caption and ensure the caption is not displayed anymore.
    $view = View::load('test_table');
    $display = &$view->getDisplay('default');
    $display['display_options']['style']['options']['caption'] = '';
    $view->save();

    $this->drupalGet('test-table');
    $this->assertSession()->elementTextEquals('xpath', '//caption/child::text()', '');

    // Remove the table summary.
    $display = &$view->getDisplay('default');
    $display['display_options']['style']['options']['summary'] = '';
    $view->save();

    $this->drupalGet('test-table');
    $this->assertSession()->elementNotExists('xpath', '//summary/child::text()');

    // Remove the table description.
    $display = &$view->getDisplay('default');
    $display['display_options']['style']['options']['description'] = '';
    $view->save();

    $this->drupalGet('test-table');
    $this->assertSession()->elementNotExists('xpath', '//caption/details/child::text()[normalize-space()]');
  }

  /**
   * Tests table fields in columns.
   */
  public function testFieldInColumns(): void {
    $this->drupalGet('test-table');

    // Ensure that both columns are in separate tds.
    // Check for class " views-field-job ", because just "views-field-job" won't
    // do: "views-field-job-1" would also contain "views-field-job".
    // @see Drupal\system\Tests\Form\ElementTest::testButtonClasses().
    $this->assertSession()->elementExists('xpath', '//tbody/tr/td[contains(concat(" ", @class, " "), " views-field-job ")]');
    $this->assertSession()->elementExists('xpath', '//tbody/tr/td[contains(concat(" ", @class, " "), " views-field-job-1 ")]');

    // Combine the second job-column with the first one, with ', ' as separator.
    $view = View::load('test_table');
    $display = &$view->getDisplay('default');
    $display['display_options']['style']['options']['columns']['job_1'] = 'job';
    $display['display_options']['style']['options']['info']['job']['separator'] = ', ';
    $view->save();

    // Ensure that both columns are properly combined.
    $this->drupalGet('test-table');
    $this->assertSession()->elementExists('xpath', '//tbody/tr/td[contains(concat(" ", @class, " "), " views-field-job views-field-job-1 ")]');
    $this->assertSession()->elementExists('xpath', '//tbody/tr/td[contains(., "Drummer, Drummer")]');
  }

  /**
   * Tests that a number with the value of "0" is displayed in the table.
   */
  public function testNumericFieldVisible(): void {
    // Adds a new data point in the views_test_data table to have a person with
    // an age of zero.
    $data_set = $this->dataSet();
    $query = Database::getConnection()->insert('views_test_data')
      ->fields(array_keys($data_set[0]));
    $query->values([
      'name' => 'James McCartney',
      'age' => 0,
      'job' => 'Baby',
      'created' => gmmktime(6, 30, 10, 1, 1, 2000),
      'status' => 1,
    ]);
    $query->execute();

    $this->drupalGet('test-table');

    $this->assertSession()->elementExists('xpath', '//tbody/tr/td[contains(., "Baby")]');
    $this->assertSession()->elementExists('xpath', '//tbody/tr/td[text()=0]');
  }

  /**
   * Tests that empty columns are hidden when empty_column is set.
   */
  public function testEmptyColumn(): void {
    // Empty the 'job' data.
    \Drupal::database()->update('views_test_data')
      ->fields(['job' => ''])
      ->execute();

    $this->drupalGet('test-table');

    // Test that only one of the job columns still shows.
    // Ensure that empty column header is hidden.
    $this->assertSession()->elementsCount('xpath', '//thead/tr/th/a[text()="Job"]', 1);
    $this->assertSession()->elementNotExists('xpath', '//tbody/tr/td[contains(concat(" ", @class, " "), " views-field-job-1 ")]');
  }

  /**
   * Tests grouping by a field.
   */
  public function testGrouping(): void {
    /** @var \Drupal\views\ViewEntityInterface $view */
    $view = \Drupal::entityTypeManager()->getStorage('view')->load('test_table');
    // Get a reference to the display configuration so we can alter some
    // specific style options.
    $display = &$view->getDisplay('default');
    // Set job as the grouping field.
    $display['display_options']['style']['options']['grouping'][0] = [
      'field' => 'job',
      'rendered' => TRUE,
      'rendered_strip' => FALSE,
    ];
    // Clear the caption text, the rendered job field will be used as a caption.
    $display['display_options']['style']['options']['caption'] = '';
    $display['display_options']['style']['options']['summary'] = '';
    $display['display_options']['style']['options']['description'] = '';
    $view->save();

    // Add a record containing unsafe markup to be sure it's filtered out.
    $unsafe_markup = '<script>alert("Rapper");</script>';
    $unsafe_markup_data = [
      'name' => 'Marshall',
      'age' => 42,
      'job' => $unsafe_markup,
      'created' => gmmktime(0, 0, 0, 2, 15, 2001),
      'status' => 1,
    ];
    $database = $this->container->get('database');
    $database->insert('views_test_data')
      ->fields(array_keys($unsafe_markup_data))
      ->values($unsafe_markup_data)
      ->execute();

    $this->drupalGet('test-table');
    $expected_captions = [
      'Job: Speaker',
      'Job: Songwriter',
      'Job: Drummer',
      'Job: Singer',
      'Job: ' . $unsafe_markup,
    ];

    // Ensure that we don't find the caption containing unsafe markup.
    $this->assertSession()->responseNotContains($unsafe_markup);
    // Ensure that the summary isn't shown.
    $this->assertSession()->elementNotExists('xpath', '//caption/details');

    // Ensure that all expected captions are found.
    foreach ($expected_captions as $raw_caption) {
      $this->assertSession()->assertEscaped($raw_caption);
    }

    $display = &$view->getDisplay('default');
    // Remove the label from the grouping field.
    $display['display_options']['fields']['job']['label'] = '';
    $view->save();

    $this->drupalGet('test-table');
    $expected_captions = [
      'Speaker',
      'Songwriter',
      'Drummer',
      'Singer',
      $unsafe_markup,
    ];

    // Ensure that we don't find the caption containing unsafe markup.
    $this->assertSession()->responseNotContains($unsafe_markup);

    // Ensure that all expected captions are found.
    foreach ($expected_captions as $raw_caption) {
      $this->assertSession()->assertEscaped($raw_caption);
    }
  }

  /**
   * Tests responsive classes and column assigning.
   */
  public function testResponsiveMergedColumns(): void {
    /** @var \Drupal\views\ViewEntityInterface $view */
    $view = \Drupal::entityTypeManager()->getStorage('view')->load('test_table');

    // Merge the two job columns together and set the responsive priority on
    // the column that is merged to.
    $display = &$view->getDisplay('default');
    $display['display_options']['style']['options']['columns']['job'] = 'job_1';
    $display['display_options']['style']['options']['info']['job_1']['separator'] = ', ';
    $display['display_options']['style']['options']['info']['job_1']['responsive'] = 'priority-low';
    $view->save();

    // Ensure that both columns are properly combined.
    $this->drupalGet('test-table');
    $this->assertSession()->elementExists('xpath', '//tbody/tr/td[contains(concat(" ", @class, " "), " priority-low views-field views-field-job views-field-job-1 ")]');
    $this->assertSession()->elementExists('xpath', '//tbody/tr/td[contains(., "Drummer, Drummer")]');
  }

  /**
   * Tests custom CSS classes are added to table element.
   */
  public function testCssTableClass(): void {
    // Add 2 custom CSS classes separated by a space.
    $view = View::load('test_table');
    $display = &$view->getDisplay('default');
    $display['display_options']['style']['options']['class'] = 'test-css-table-class1 test-css-table-class2';
    $view->save();

    // Ensure all CSS classes are added to table element.
    $this->drupalGet('test-table');
    $this->assertSession()->elementExists('xpath', '//table[contains(concat(" ", @class, " "), " test-css-table-class1 test-css-table-class2 ")]');
  }

  /**
   * Tests the cacheability of the table display.
   */
  public function testTableCacheability(): void {
    \Drupal::service('module_installer')->uninstall(['page_cache']);

    $url = 'test-table';
    $this->drupalGet($url);
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->responseHeaderEquals(DynamicPageCacheSubscriber::HEADER, 'MISS');
    $this->drupalGet($url);
    $this->assertSession()->responseHeaderEquals(DynamicPageCacheSubscriber::HEADER, 'HIT');
  }

}
