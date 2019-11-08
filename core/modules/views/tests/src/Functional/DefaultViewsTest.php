<?php

namespace Drupal\Tests\views\Functional;

use Drupal\Component\Render\FormattableMarkup;
use Drupal\comment\CommentInterface;
use Drupal\comment\Tests\CommentTestTrait;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\Language\LanguageInterface;
use Drupal\Core\Link;
use Drupal\Core\Url;
use Drupal\views\Views;
use Drupal\comment\Entity\Comment;
use Drupal\taxonomy\Entity\Vocabulary;
use Drupal\taxonomy\Entity\Term;
use Drupal\Tests\field\Traits\EntityReferenceTestTrait;

/**
 * Tests the default views provided by views.
 *
 * @group views
 */
class DefaultViewsTest extends ViewTestBase {

  use CommentTestTrait;
  use EntityReferenceTestTrait;

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['views', 'node', 'search', 'comment', 'taxonomy', 'block', 'user'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * An array of argument arrays to use for default views.
   *
   * @var array
   */
  protected $viewArgMap = [
    'backlinks' => [1],
    'taxonomy_term' => [1],
    'glossary' => ['all'],
  ];

  protected function setUp($import_test_views = TRUE) {
    parent::setUp($import_test_views);

    $this->drupalPlaceBlock('page_title_block');

    // Create Basic page node type.
    $this->drupalCreateContentType(['type' => 'page', 'name' => 'Basic page']);

    $vocabulary = Vocabulary::create([
      'name' => $this->randomMachineName(),
      'description' => $this->randomMachineName(),
      'vid' => mb_strtolower($this->randomMachineName()),
      'langcode' => LanguageInterface::LANGCODE_NOT_SPECIFIED,
      'help' => '',
      'nodes' => ['page' => 'page'],
      'weight' => mt_rand(0, 10),
    ]);
    $vocabulary->save();

    // Create a field.
    $field_name = mb_strtolower($this->randomMachineName());

    $handler_settings = [
      'target_bundles' => [
        $vocabulary->id() => $vocabulary->id(),
      ],
      'auto_create' => TRUE,
    ];
    $this->createEntityReferenceField('node', 'page', $field_name, NULL, 'taxonomy_term', 'default', $handler_settings, FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED);

    // Create a time in the past for the archive.
    $time = REQUEST_TIME - 3600;

    $this->addDefaultCommentField('node', 'page');

    for ($i = 0; $i <= 10; $i++) {
      $user = $this->drupalCreateUser();
      $term = $this->createTerm($vocabulary);

      $values = ['created' => $time, 'type' => 'page'];
      $values[$field_name][]['target_id'] = $term->id();

      // Make every other node promoted.
      if ($i % 2) {
        $values['promote'] = TRUE;
      }
      $values['body'][]['value'] = Link::fromTextAndUrl('Node ' . 1, Url::fromRoute('entity.node.canonical', ['node' => 1]))->toString();

      $node = $this->drupalCreateNode($values);

      $comment = [
        'uid' => $user->id(),
        'status' => CommentInterface::PUBLISHED,
        'entity_id' => $node->id(),
        'entity_type' => 'node',
        'field_name' => 'comment',
      ];
      Comment::create($comment)->save();

      $unpublished_comment = [
        'uid' => $user->id(),
        'status' => CommentInterface::NOT_PUBLISHED,
        'entity_id' => $node->id(),
        'entity_type' => 'node',
        'field_name' => 'comment',
      ];
      Comment::create($unpublished_comment)->save();
    }

    // Some views, such as the "Who's Online" view, only return results if at
    // least one user is logged in.
    $account = $this->drupalCreateUser([]);
    $this->drupalLogin($account);
  }

  /**
   * Test that all Default views work as expected.
   */
  public function testDefaultViews() {
    // Get all default views.
    $controller = $this->container->get('entity_type.manager')->getStorage('view');
    $views = $controller->loadMultiple();

    foreach ($views as $name => $view_storage) {
      $view = $view_storage->getExecutable();
      $view->initDisplay();
      foreach ($view->storage->get('display') as $display_id => $display) {
        $view->setDisplay($display_id);

        // Add any args if needed.
        if (array_key_exists($name, $this->viewArgMap)) {
          $view->preExecute($this->viewArgMap[$name]);
        }

        $this->assert(TRUE, new FormattableMarkup('View @view will be executed.', ['@view' => $view->storage->id()]));
        $view->execute();

        $tokens = ['@name' => $name, '@display_id' => $display_id];
        $this->assertTrue($view->executed, new FormattableMarkup('@name:@display_id has been executed.', $tokens));

        $count = count($view->result);
        $this->assertTrue($count > 0, new FormattableMarkup('@count results returned', ['@count' => $count]));
        $view->destroy();
      }
    }
  }

  /**
   * Returns a new term with random properties in vocabulary $vid.
   */
  public function createTerm($vocabulary) {
    $filter_formats = filter_formats();
    $format = array_pop($filter_formats);
    $term = Term::create([
      'name' => $this->randomMachineName(),
      'description' => $this->randomMachineName(),
      // Use the first available text format.
      'format' => $format->id(),
      'vid' => $vocabulary->id(),
      'langcode' => LanguageInterface::LANGCODE_NOT_SPECIFIED,
    ]);
    $term->save();
    return $term;
  }

  /**
   * Tests the archive view.
   */
  public function testArchiveView() {
    // Create additional nodes compared to the one in the setup method.
    // Create two nodes in the same month, and one in each following month.
    $node = [
      // Sun, 19 Nov 1978 05:00:00 GMT.
      'created' => 280299600,
    ];
    $this->drupalCreateNode($node);
    $this->drupalCreateNode($node);
    $node = [
      // Tue, 19 Dec 1978 05:00:00 GMT.
      'created' => 282891600,
    ];
    $this->drupalCreateNode($node);
    $node = [
      // Fri, 19 Jan 1979 05:00:00 GMT.
      'created' => 285570000,
    ];
    $this->drupalCreateNode($node);

    $view = Views::getView('archive');
    $view->setDisplay('page_1');
    $this->executeView($view);
    $columns = ['nid', 'created_year_month', 'num_records'];
    $column_map = array_combine($columns, $columns);
    // Create time of additional nodes created in the setup method.
    $created_year_month = date('Ym', REQUEST_TIME - 3600);
    $expected_result = [
      [
        'nid' => 1,
        'created_year_month' => $created_year_month,
        'num_records' => 11,
      ],
      [
        'nid' => 15,
        'created_year_month' => 197901,
        'num_records' => 1,
      ],
      [
        'nid' => 14,
        'created_year_month' => 197812,
        'num_records' => 1,
      ],
      [
        'nid' => 12,
        'created_year_month' => 197811,
        'num_records' => 2,
      ],
    ];
    $this->assertIdenticalResultset($view, $expected_result, $column_map);

    $view->storage->setStatus(TRUE);
    $view->save();
    \Drupal::service('router.builder')->rebuild();

    $this->drupalGet('archive');
    $this->assertResponse(200);
  }

}
