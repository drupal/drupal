<?php

namespace Drupal\Tests\rest\Functional\Views;

use Drupal\node\Entity\Node;
use Drupal\Tests\views\Functional\ViewTestBase;
use Drupal\views\Tests\ViewTestData;
use Drupal\views\Views;

/**
 * Tests the display of an excluded field that is used as a token.
 *
 * @group rest
 * @see \Drupal\rest\Plugin\views\display\RestExport
 * @see \Drupal\rest\Plugin\views\row\DataFieldRow
 */
class ExcludedFieldTokenTest extends ViewTestBase {

  /**
   * @var \Drupal\views\ViewExecutable
   */
  protected $view;

  /**
   * The views that are used by this test.
   *
   * @var array
   */
  public static $testViews = ['test_excluded_field_token_display'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * The modules that need to be installed for this test.
   *
   * @var array
   */
  public static $modules = [
    'entity_test',
    'rest_test_views',
    'node',
    'field',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp($import_test_views = TRUE) {
    parent::setUp($import_test_views);

    ViewTestData::createTestViews(get_class($this), ['rest_test_views']);

    // Create some test content.
    for ($i = 1; $i <= 10; $i++) {
      Node::create([
        'type' => 'article',
        'title' => 'Article test ' . $i,
      ])->save();
    }

    $this->enableViewsTestModule();

    $this->view = Views::getView('test_excluded_field_token_display');
    $this->view->setDisplay('rest_export_1');
  }

  /**
   * Tests the display of an excluded title field when used as a token.
   */
  public function testExcludedTitleTokenDisplay() {
    $actual_json = $this->drupalGet($this->view->getPath(), ['query' => ['_format' => 'json']]);
    $this->assertSession()->statusCodeEquals(200);

    $expected = [
      ['nothing' => 'Article test 10'],
      ['nothing' => 'Article test 9'],
      ['nothing' => 'Article test 8'],
      ['nothing' => 'Article test 7'],
      ['nothing' => 'Article test 6'],
      ['nothing' => 'Article test 5'],
      ['nothing' => 'Article test 4'],
      ['nothing' => 'Article test 3'],
      ['nothing' => 'Article test 2'],
      ['nothing' => 'Article test 1'],
    ];
    $this->assertIdentical($actual_json, json_encode($expected));
  }

}
