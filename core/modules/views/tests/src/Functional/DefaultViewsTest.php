<?php

declare(strict_types=1);

namespace Drupal\Tests\views\Functional;

use Drupal\comment\CommentInterface;
use Drupal\comment\Entity\Comment;
use Drupal\comment\Tests\CommentTestTrait;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\Language\LanguageInterface;
use Drupal\Core\Link;
use Drupal\Core\Url;
use Drupal\filter\FilterFormatRepositoryInterface;
use Drupal\taxonomy\Entity\Term;
use Drupal\taxonomy\Entity\Vocabulary;
use Drupal\Tests\field\Traits\EntityReferenceFieldCreationTrait;
use Drupal\views\Entity\View;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Tests the default views provided by views.
 */
#[Group('views')]
#[RunTestsInSeparateProcesses]
class DefaultViewsTest extends ViewTestBase {

  use CommentTestTrait;
  use EntityReferenceFieldCreationTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'views',
    'node',
    'comment',
    'taxonomy',
    'block',
    'user',
    'views_test_config',
  ];

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
    'taxonomy_term' => [1],
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp($import_test_views = TRUE, $modules = []): void {
    parent::setUp($import_test_views, $modules);

    $this->drupalPlaceBlock('page_title_block');

    // Create Basic page node type.
    $this->drupalCreateContentType(['type' => 'page', 'name' => 'Basic page']);

    $vocabulary = Vocabulary::create([
      'name' => $this->randomMachineName(),
      'description' => $this->randomMachineName(),
      'vid' => $this->randomMachineName(),
      'langcode' => LanguageInterface::LANGCODE_NOT_SPECIFIED,
      'help' => '',
      'nodes' => ['page' => 'page'],
      'weight' => mt_rand(0, 10),
    ]);
    $vocabulary->save();

    // Create a field.
    $field_name = $this->randomMachineName();

    $handler_settings = [
      'target_bundles' => [
        $vocabulary->id() => $vocabulary->id(),
      ],
      'auto_create' => TRUE,
    ];
    $this->createEntityReferenceField('node', 'page', $field_name, '', 'taxonomy_term', 'default', $handler_settings, FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED);

    // Create a time in the past for the archive.
    $time = \Drupal::time()->getRequestTime() - 3600;

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
   * Tests that all Default views work as expected.
   */
  public function testDefaultViews(): void {
    // Get all enabled default views.
    $controller = $this->container->get('entity_type.manager')->getStorage('view');
    $views = \array_filter($controller->loadMultiple(), static fn (View $view): bool => $view->status());
    self::assertGreaterThan(0, \count($views));

    foreach ($views as $name => $view_storage) {
      $view = $view_storage->getExecutable();
      $view->initDisplay();
      foreach ($view->storage->get('display') as $display_id => $display) {
        $view->setDisplay($display_id);

        // Add any args if needed.
        if (array_key_exists($name, $this->viewArgMap)) {
          $view->preExecute($this->viewArgMap[$name]);
        }

        $view->execute();

        $this->assertTrue($view->executed, "$name:$display_id has been executed.");

        $this->assertNotEmpty($view->result);
        $view->destroy();
      }
    }
  }

  /**
   * Returns a new term with random properties in vocabulary $vid.
   */
  public function createTerm($vocabulary) {
    $filter_formats = \Drupal::service(FilterFormatRepositoryInterface::class)->getAllFormats();
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

}
