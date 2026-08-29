<?php

declare(strict_types=1);

namespace Drupal\Tests\default_admin\Kernel;

use Drupal\Core\Theme\ThemeInitializationInterface;
use Drupal\default_admin\Hook\PreprocessHooks;
use Drupal\language\Entity\ConfigurableLanguage;
use Drupal\node\Entity\Node;
use Drupal\node\Entity\NodeType;
use Drupal\taxonomy\Entity\Term;
use Drupal\taxonomy\VocabularyInterface;
use Drupal\Tests\taxonomy\Traits\TaxonomyTestTrait;
use Drupal\Tests\user\Traits\UserCreationTrait;
use Drupal\Tests\views\Kernel\ViewsKernelTestBase;
use Drupal\views\Entity\View;
use Drupal\views\Plugin\views\field\FieldPluginBase;
use Drupal\views\ResultRow;
use Drupal\views\ViewExecutable;
use Drupal\views\Views;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Tests the publication status marker of views status fields.
 *
 * The theme suggestion views_view_field__status is derived from the ID of the
 * views field, so views-view-field--status.html.twig is used for the status
 * field of every entity type, and also for fields that hold something else
 * than a publication state.
 *
 * @see \Drupal\default_admin\Hook\PreprocessHooks::preprocessViewsViewFieldStatus()
 * @see \Drupal\views\Plugin\views\field\FieldPluginBase::themeFunctions()
 */
#[Group('default_admin')]
#[RunTestsInSeparateProcesses]
class ViewsViewFieldStatusTest extends ViewsKernelTestBase {

  use TaxonomyTestTrait;
  use UserCreationTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'field',
    'language',
    'node',
    'taxonomy',
    'text',
  ];

  /**
   * The vocabulary of the test terms.
   */
  protected VocabularyInterface $vocabulary;

  /**
   * {@inheritdoc}
   */
  protected function setUp($import_test_views = TRUE): void {
    parent::setUp(FALSE);

    $this->installEntitySchema('user');
    $this->installEntitySchema('node');
    $this->installEntitySchema('taxonomy_term');
    $this->installSchema('node', ['node_access']);
    $this->installConfig(['filter', 'language', 'node']);

    // The translation language renderer only adds the language code of the row
    // to the query when the site is multilingual.
    // @see \Drupal\views\Entity\Render\TranslationLanguageRenderer::query()
    ConfigurableLanguage::createFromLangcode('es')->save();

    NodeType::create(['type' => 'page', 'name' => 'Page'])->save();
    $this->vocabulary = $this->createVocabulary();

    // Unpublished rows are only returned when node access is bypassed.
    $this->setUpCurrentUser([], [
      'access content',
      'administer taxonomy',
      'bypass node access',
    ]);

    // Install and activate the theme, so that its template and its preprocess
    // implementation are used while rendering. Installing the theme rebuilds
    // the container, which is what registers the
    // #[Hook('preprocess_views_view_field__status')] implementation of the
    // theme.
    // @see \Drupal\Core\Hook\ThemeHookCollectorPass
    \Drupal::service('theme_installer')->install(['default_admin']);
    $this->container = \Drupal::getContainer();
    \Drupal::theme()->setActiveTheme(\Drupal::service(ThemeInitializationInterface::class)->initTheme('default_admin'));
  }

  /**
   * Tests the marker of published and unpublished nodes.
   */
  public function testNodePublicationState(): void {
    Node::create([
      'type' => 'page',
      'title' => 'Published node',
      'status' => TRUE,
    ])->save();
    Node::create([
      'type' => 'page',
      'title' => 'Unpublished node',
      'status' => FALSE,
    ])->save();

    $this->createStatusView('test_node_status', 'node', 'status');

    $view = Views::getView('test_node_status');
    $this->executeView($view);
    $this->assertCount(2, $view->result);

    $this->assertSame([
      'marker' => 'draft',
      'marker marker--published' => 'live',
    ], $this->renderStatusFieldByMarker($view));
  }

  /**
   * Tests that the marker of a node row follows the row translation.
   */
  public function testNodeTranslationPublicationState(): void {
    $node = Node::create([
      'type' => 'page',
      'title' => 'Published in English',
      'status' => TRUE,
    ]);
    $node->addTranslation('es', [
      'title' => 'Unpublished Spanish translation',
      'status' => FALSE,
    ]);
    $node->save();

    $this->createStatusView('test_node_translation_status', 'node', 'status', '***LANGUAGE_entity_translation***');

    $view = Views::getView('test_node_translation_status');
    $this->executeView($view);
    // The data table holds one row per translation.
    $this->assertCount(2, $view->result);

    $this->assertSame([
      'marker' => 'draft',
      'marker marker--published' => 'live',
    ], $this->renderStatusFieldByMarker($view));
  }

  /**
   * Tests the marker of a publishable entity type other than the node type.
   */
  public function testTermPublicationState(): void {
    Term::create([
      'vid' => $this->vocabulary->id(),
      'name' => 'Published term',
      'status' => TRUE,
    ])->save();
    Term::create([
      'vid' => $this->vocabulary->id(),
      'name' => 'Unpublished term',
      'status' => FALSE,
    ])->save();

    $this->createStatusView('test_term_status', 'taxonomy_term', 'status');

    $view = Views::getView('test_term_status');
    $this->executeView($view);
    $this->assertCount(2, $view->result);

    $this->assertSame([
      'marker' => 'draft',
      'marker marker--published' => 'live',
    ], $this->renderStatusFieldByMarker($view));
  }

  /**
   * Tests that the marker of a term row follows the row translation.
   *
   * This covers the regression the template logic was moved into a preprocess
   * implementation for. The template used to read the row translation from the
   * row property node_field_data_langcode, which is the language code alias of
   * a node based view. On a taxonomy term view that property does not exist, so
   * the default translation was used and this row got the marker of a published
   * term while its output said the term was unpublished.
   */
  public function testTermTranslationPublicationState(): void {
    $term = Term::create([
      'vid' => $this->vocabulary->id(),
      'name' => 'Published in English',
      'status' => TRUE,
    ]);
    $term->addTranslation('es', [
      'name' => 'Unpublished Spanish translation',
      'status' => FALSE,
    ]);
    $term->save();

    $this->createStatusView('test_term_translation_status', 'taxonomy_term', 'status', '***LANGUAGE_entity_translation***');

    $view = Views::getView('test_term_translation_status');
    $this->executeView($view);
    $this->assertCount(2, $view->result);

    $this->assertSame([
      'marker' => 'draft',
      'marker marker--published' => 'live',
    ], $this->renderStatusFieldByMarker($view));
  }

  /**
   * Tests that a field of a handler without an entity has no marker.
   */
  public function testFieldWithoutEntity(): void {
    // The 'status' column of the views test table is handled by the 'boolean'
    // field plugin, which is not an entity field handler and has no entity to
    // read a publication state from.
    $storage = View::create([
      'id' => 'test_status_without_entity',
      'label' => 'test_status_without_entity',
      'module' => 'views',
      'base_table' => 'views_test_data',
      'base_field' => 'id',
    ]);
    $executable = $storage->getExecutable();
    $executable->newDisplay('default', 'Default', 'default');
    $display = $executable->displayHandlers->get('default');
    $display->setOption('pager', ['type' => 'none', 'options' => ['offset' => 0]]);
    $display->setOption('fields', [
      'status' => [
        'id' => 'status',
        'table' => 'views_test_data',
        'field' => 'status',
        'plugin_id' => 'boolean',
        'type' => 'custom',
        'type_custom_true' => 'live',
        'type_custom_false' => 'draft',
      ],
    ]);
    $storage->save();

    $view = Views::getView('test_status_without_entity');
    $this->executeView($view);
    $this->assertCount(5, $view->result);

    foreach ($view->result as $row) {
      $output = $this->renderStatusField($view, $row);
      $this->assertStringNotContainsString('marker', $output);
      $this->assertContains($output, ['live', 'draft']);
    }
  }

  /**
   * Tests that the preprocess implementation owns the is_published variable.
   */
  public function testExistingPublishedVariableIsOverwritten(): void {
    $variables = [
      'field' => $this->createStub(FieldPluginBase::class),
      'row' => new ResultRow(),
      'is_published' => TRUE,
    ];

    \Drupal::classResolver(PreprocessHooks::class)->preprocessViewsViewFieldStatus($variables);

    $this->assertArrayHasKey('is_published', $variables);
    $this->assertNull($variables['is_published']);
  }

  /**
   * Tests that the entity is resolved from the configured relationship.
   */
  public function testRelationshipEntityPublicationState(): void {
    $node = Node::create([
      'type' => 'page',
      'title' => 'Published relationship entity',
      'status' => TRUE,
    ]);
    $node->save();

    $this->createStatusView('test_relationship_status', 'node', 'status');

    $view = Views::getView('test_relationship_status');
    $this->executeView($view);
    $this->assertCount(1, $view->result);

    $field = $view->field['status'];
    $field->options['relationship'] = 'test_relationship';
    $row = $view->result[0];
    $row->_entity = NULL;
    $row->_relationship_entities['test_relationship'] = $node;
    $variables = [
      'field' => $field,
      'row' => $row,
    ];

    \Drupal::classResolver(PreprocessHooks::class)->preprocessViewsViewFieldStatus($variables);

    $this->assertTrue($variables['is_published']);
  }

  /**
   * Creates a view of an entity type with a single field with the ID 'status'.
   *
   * @param string $id
   *   The ID of the view.
   * @param string $entity_type_id
   *   The ID of the entity type the view is based on.
   * @param string $field_name
   *   The name of the entity field to render.
   * @param string|null $rendering_language
   *   (optional) The rendering language of the display.
   */
  protected function createStatusView(string $id, string $entity_type_id, string $field_name, ?string $rendering_language = NULL): void {
    $entity_type = \Drupal::entityTypeManager()->getDefinition($entity_type_id);
    $base_table = $entity_type->getDataTable();

    $storage = View::create([
      'id' => $id,
      'label' => $id,
      'module' => 'views',
      'base_table' => $base_table,
      'base_field' => $entity_type->getKey('id'),
    ]);

    $executable = $storage->getExecutable();
    $executable->newDisplay('default', 'Default', 'default');
    $display = $executable->displayHandlers->get('default');
    $display->setOption('pager', ['type' => 'none', 'options' => ['offset' => 0]]);
    // The array key and the 'id' are the views field ID. The theme suggestion
    // is derived from that ID, not from the name of the rendered field.
    $display->setOption('fields', [
      'status' => [
        'id' => 'status',
        'table' => $base_table,
        'field' => $field_name,
        'entity_type' => $entity_type_id,
        'entity_field' => $field_name,
        'plugin_id' => 'field',
        'type' => 'boolean',
        'settings' => [
          'format' => 'custom',
          'format_custom_true' => 'live',
          'format_custom_false' => 'draft',
        ],
      ],
    ]);

    if ($rendering_language !== NULL) {
      $display->setOption('rendering_language', $rendering_language);
    }

    $storage->save();
  }

  /**
   * Renders the status field of every row of a view, keyed by marker class.
   *
   * @param \Drupal\views\ViewExecutable $view
   *   An executed view with a field with the ID 'status'.
   *
   * @return array
   *   The text inside the marker, keyed by the class attribute of the marker
   *   element. Output that is not wrapped in a marker is keyed by an empty
   *   string. Sorted by key, because the order of the rows of a view is not
   *   relevant for this test.
   */
  protected function renderStatusFieldByMarker(ViewExecutable $view): array {
    $rendered = [];
    foreach ($view->result as $row) {
      $output = $this->renderStatusField($view, $row);
      if (preg_match('#^<span class="(marker[^"]*)">(.*)</span>$#s', $output, $matches) === 1) {
        $rendered[$matches[1]] = $matches[2];
      }
      else {
        $rendered[''] = $output;
      }
    }
    ksort($rendered);
    return $rendered;
  }

  /**
   * Renders the status field of a single row through the theme system.
   *
   * @param \Drupal\views\ViewExecutable $view
   *   An executed view with a field with the ID 'status'.
   * @param \Drupal\views\ResultRow $row
   *   The row to render the field of.
   *
   * @return string
   *   The rendered field output, without surrounding whitespace.
   */
  protected function renderStatusField(ViewExecutable $view, ResultRow $row): string {
    $field = $view->field['status'];
    $build = [
      '#theme' => $field->themeFunctions(),
      '#view' => $view,
      '#field' => $field,
      '#row' => $row,
    ];
    return trim((string) \Drupal::service('renderer')->renderInIsolation($build));
  }

}
