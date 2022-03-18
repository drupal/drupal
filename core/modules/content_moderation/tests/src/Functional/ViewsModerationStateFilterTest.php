<?php

namespace Drupal\Tests\content_moderation\Functional;

use Drupal\node\Entity\NodeType;
use Drupal\Tests\content_moderation\Traits\ContentModerationTestTrait;
use Drupal\Tests\views\Functional\ViewTestBase;
use Drupal\views\Entity\View;
use Drupal\views\ViewEntityInterface;
use Drupal\workflows\Entity\Workflow;

/**
 * Tests the views 'moderation_state_filter' filter plugin.
 *
 * @coversDefaultClass \Drupal\content_moderation\Plugin\views\filter\ModerationStateFilter
 *
 * @group content_moderation
 */
class ViewsModerationStateFilterTest extends ViewTestBase {

  use ContentModerationTestTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'node',
    'content_moderation',
    'workflows',
    'workflow_type_test',
    'entity_test',
    'language',
    'content_translation',
    'views_ui',
  ];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected function setUp($import_test_views = TRUE, $modules = []): void {
    parent::setUp(FALSE, $modules);

    NodeType::create([
      'type' => 'example_a',
    ])->save();
    NodeType::create([
      'type' => 'example_b',
    ])->save();
    NodeType::create([
      'type' => 'example_c',
    ])->save();

    $this->createEditorialWorkflow();

    $new_workflow = Workflow::create([
      'type' => 'content_moderation',
      'id' => 'new_workflow',
      'label' => 'New workflow',
    ]);
    $new_workflow->getTypePlugin()->addState('bar', 'Bar');
    $new_workflow->getTypePlugin()->addEntityTypeAndBundle('node', 'example_c');
    $new_workflow->save();

    $this->drupalLogin($this->drupalCreateUser([
      'administer workflows',
      'administer views',
    ]));

    $this->container->get('module_installer')->install(['content_moderation_test_views']);

    $new_workflow->getTypePlugin()->removeEntityTypeAndBundle('node', 'example_c');
    $new_workflow->save();
  }

  /**
   * Tests the dependency handling of the moderation state filter.
   *
   * @covers ::calculateDependencies
   * @covers ::onDependencyRemoval
   */
  public function testModerationStateFilterDependencyHandling() {
    // First, check that the view doesn't have any config dependency when there
    // are no states configured in the filter.
    $view_id = 'test_content_moderation_state_filter_base_table';
    $view = View::load($view_id);

    $this->assertWorkflowDependencies([], $view);
    $this->assertTrue($view->status());

    // Configure the Editorial workflow for a node bundle, set the filter value
    // to use one of its states and check that the workflow is now a dependency
    // of the view.
    $this->drupalGet('admin/config/workflow/workflows/manage/editorial/type/node');
    $this->submitForm(['bundles[example_a]' => TRUE], 'Save');

    $edit['options[value][]'] = ['editorial-published'];
    $this->drupalGet("admin/structure/views/nojs/handler/{$view_id}/default/filter/moderation_state");
    $this->submitForm($edit, 'Apply');
    $this->drupalGet("admin/structure/views/view/{$view_id}");
    $this->submitForm([], 'Save');

    $view = $this->loadViewUnchanged($view_id);
    $this->assertWorkflowDependencies(['editorial'], $view);
    $this->assertTrue($view->status());

    // Create another workflow and repeat the checks above.
    $this->drupalGet('admin/config/workflow/workflows/add');
    $this->submitForm([
      'label' => 'Translation',
      'id' => 'translation',
      'workflow_type' => 'content_moderation',
    ], 'Save');
    $this->drupalGet('admin/config/workflow/workflows/manage/translation/add_state');
    $this->submitForm([
      'label' => 'Needs Review',
      'id' => 'needs_review',
    ], 'Save');
    $this->drupalGet('admin/config/workflow/workflows/manage/translation/type/node');
    $this->submitForm(['bundles[example_b]' => TRUE], 'Save');

    $edit['options[value][]'] = ['editorial-published', 'translation-needs_review'];
    $this->drupalGet("admin/structure/views/nojs/handler/{$view_id}/default/filter/moderation_state");
    $this->submitForm($edit, 'Apply');
    $this->drupalGet("admin/structure/views/view/{$view_id}");
    $this->submitForm([], 'Save');

    $view = $this->loadViewUnchanged($view_id);
    $this->assertWorkflowDependencies(['editorial', 'translation'], $view);
    $this->assertTrue(isset($view->getDisplay('default')['display_options']['filters']['moderation_state']));
    $this->assertTrue($view->status());

    // Remove the 'Translation' workflow.
    $this->drupalGet('admin/config/workflow/workflows/manage/translation/delete');
    $this->submitForm([], 'Delete');

    // Check that the view has been disabled, the filter has been deleted, the
    // view can be saved and there are no more config dependencies.
    $view = $this->loadViewUnchanged($view_id);
    $this->assertFalse($view->status());
    $this->assertFalse(isset($view->getDisplay('default')['display_options']['filters']['moderation_state']));
    $this->drupalGet("admin/structure/views/view/{$view_id}");
    $this->submitForm([], 'Save');
    $this->assertWorkflowDependencies([], $view);
  }

  /**
   * Load a view from the database after it has been modified in a sub-request.
   *
   * @param string $view_id
   *   The view ID.
   *
   * @return \Drupal\views\ViewEntityInterface
   *   A loaded view, bypassing static caches.
   */
  public function loadViewUnchanged($view_id) {
    $this->container->get('cache.config')->deleteAll();
    $this->container->get('config.factory')->reset();
    return $this->container->get('entity_type.manager')->getStorage('view')->loadUnchanged($view_id);
  }

  /**
   * Tests the moderation state filter when the configured workflow is changed.
   *
   * @dataProvider providerTestWorkflowChanges
   */
  public function testWorkflowChanges($view_id) {
    // First, apply the Editorial workflow to both of our content types.
    $this->drupalGet('admin/config/workflow/workflows/manage/editorial/type/node');
    $this->submitForm([
      'bundles[example_a]' => TRUE,
      'bundles[example_b]' => TRUE,
    ], 'Save');
    \Drupal::service('entity_type.bundle.info')->clearCachedBundles();

    // Update the view and make the default filter not exposed anymore,
    // otherwise all results will be shown when there are no more moderated
    // bundles left.
    $this->drupalGet("admin/structure/views/nojs/handler/{$view_id}/default/filter/moderation_state");
    $this->submitForm([], 'Hide filter');
    $this->drupalGet("admin/structure/views/view/{$view_id}");
    $this->submitForm([], 'Save');

    // Add a few nodes in various moderation states.
    $this->createNode(['type' => 'example_a', 'moderation_state' => 'published']);
    $this->createNode(['type' => 'example_b', 'moderation_state' => 'published']);
    $archived_node_a = $this->createNode(['type' => 'example_a', 'moderation_state' => 'archived']);
    $archived_node_b = $this->createNode(['type' => 'example_b', 'moderation_state' => 'archived']);

    // Configure the view to only show nodes in the 'archived' moderation state.
    $edit['options[value][]'] = ['editorial-archived'];
    $this->drupalGet("admin/structure/views/nojs/handler/{$view_id}/default/filter/moderation_state");
    $this->submitForm($edit, 'Apply');
    $this->drupalGet("admin/structure/views/view/{$view_id}");
    $this->submitForm([], 'Save');

    // Check that only the archived nodes from both bundles are displayed by the
    // view.
    $view = $this->loadViewUnchanged($view_id);
    $this->executeAndAssertIdenticalResultset($view, [['nid' => $archived_node_a->id()], ['nid' => $archived_node_b->id()]], ['nid' => 'nid']);

    // Remove the Editorial workflow from one of the bundles.
    $this->drupalGet('admin/config/workflow/workflows/manage/editorial/type/node');
    $this->submitForm([
      'bundles[example_a]' => TRUE,
      'bundles[example_b]' => FALSE,
    ], 'Save');
    \Drupal::service('entity_type.bundle.info')->clearCachedBundles();

    $view = $this->loadViewUnchanged($view_id);
    $this->executeAndAssertIdenticalResultset($view, [['nid' => $archived_node_a->id()]], ['nid' => 'nid']);

    // Check that the view can still be edited and saved without any
    // intervention.
    $this->drupalGet("admin/structure/views/view/{$view_id}");
    $this->submitForm([], 'Save');

    // Remove the Editorial workflow from both bundles.
    $this->drupalGet('admin/config/workflow/workflows/manage/editorial/type/node');
    $this->submitForm([
      'bundles[example_a]' => FALSE,
      'bundles[example_b]' => FALSE,
    ], 'Save');
    \Drupal::service('entity_type.bundle.info')->clearCachedBundles();

    // Check that the view doesn't return any result.
    $view = $this->loadViewUnchanged($view_id);
    $this->executeAndAssertIdenticalResultset($view, [], []);

    // Check that the view contains a broken filter, since the moderation_state
    // field was removed from the entity type.
    $this->drupalGet("admin/structure/views/view/{$view_id}");
    $this->submitForm([], 'Save');
    $this->assertSession()->pageTextContains("Broken/missing handler");
  }

  /**
   * Execute a view and assert the expected results.
   *
   * @param \Drupal\views\ViewEntityInterface $view_entity
   *   A view configuration entity.
   * @param array $expected
   *   An expected result set.
   * @param array $column_map
   *   An associative array mapping the columns of the result set from the view
   *   (as keys) and the expected result set (as values).
   */
  protected function executeAndAssertIdenticalResultset(ViewEntityInterface $view_entity, $expected, $column_map) {
    $executable = $this->container->get('views.executable')->get($view_entity);
    $this->executeView($executable);
    $this->assertIdenticalResultset($executable, $expected, $column_map);
  }

  /**
   * Data provider for testWorkflowChanges.
   *
   * @return string[]
   *   An array of view IDs.
   */
  public function providerTestWorkflowChanges() {
    return [
      'view on base table, filter on base table' => [
        'test_content_moderation_state_filter_base_table',
      ],
      'view on base table, filter on revision table' => [
        'test_content_moderation_state_filter_base_table_filter_on_revision',
      ],
    ];
  }

  /**
   * Tests the content moderation state filter caching is correct.
   */
  public function testFilterRenderCache() {
    // Initially all states of the workflow are displayed.
    $this->drupalGet('admin/config/workflow/workflows/manage/editorial/type/node');
    $this->submitForm(['bundles[example_a]' => TRUE], 'Save');
    $this->assertFilterStates(['All', 'editorial-draft', 'editorial-published', 'editorial-archived']);

    // Adding a new state to the editorial workflow will display that state in
    // the list of filters.
    $this->drupalGet('admin/config/workflow/workflows/manage/editorial/add_state');
    $this->submitForm([
      'label' => 'Foo',
      'id' => 'foo',
    ], 'Save');
    $this->assertFilterStates(['All', 'editorial-draft', 'editorial-published', 'editorial-archived', 'editorial-foo']);

    // Adding a second workflow to nodes will also show new states.
    $this->drupalGet('admin/config/workflow/workflows/manage/new_workflow/type/node');
    $this->submitForm(['bundles[example_b]' => TRUE], 'Save');
    $this->assertFilterStates(['All', 'editorial-draft', 'editorial-published', 'editorial-archived', 'editorial-foo', 'new_workflow-draft', 'new_workflow-published', 'new_workflow-bar']);

    // Add a few more states and change the exposed filter to allow multiple
    // selections so we can check that the size of the select element does not
    // exceed 8 options.
    $this->drupalGet('admin/config/workflow/workflows/manage/editorial/add_state');
    $this->submitForm([
      'label' => 'Foo 2',
      'id' => 'foo2',
    ], 'Save');
    $this->drupalGet('admin/config/workflow/workflows/manage/editorial/add_state');
    $this->submitForm([
      'label' => 'Foo 3',
      'id' => 'foo3',
    ], 'Save');

    $view_id = 'test_content_moderation_state_filter_base_table';
    $edit['options[expose][multiple]'] = TRUE;
    $this->drupalGet("admin/structure/views/nojs/handler/{$view_id}/default/filter/moderation_state");
    $this->submitForm($edit, 'Apply');
    $this->drupalGet("admin/structure/views/view/{$view_id}");
    $this->submitForm([], 'Save');

    $this->assertFilterStates(['editorial-draft', 'editorial-published', 'editorial-archived', 'editorial-foo', 'editorial-foo2', 'editorial-foo3', 'new_workflow-draft', 'new_workflow-published', 'new_workflow-bar'], TRUE);
  }

  /**
   * Assert the states which appear in the filter.
   *
   * @param array $states
   *   The states which should appear in the filter.
   * @param bool $check_size
   *   (optional) Whether to check that size of the select element is not
   *   greater than 8. Defaults to FALSE.
   *
   * @internal
   */
  protected function assertFilterStates(array $states, bool $check_size = FALSE): void {
    $this->drupalGet('/filter-test-path');

    $assert_session = $this->assertSession();

    // Check that the select contains the correct number of options.
    $assert_session->elementsCount('css', '#edit-default-revision-state option', count($states));

    // Check that the size of the select element does not exceed 8 options.
    if ($check_size) {
      $this->assertGreaterThan(8, count($states));
      $assert_session->elementAttributeContains('css', '#edit-default-revision-state', 'size', 8);
    }

    // Check that an option exists for each of the expected states.
    foreach ($states as $state) {
      $assert_session->optionExists('Default Revision State', $state);
    }
  }

  /**
   * Asserts the views dependencies on workflow config entities.
   *
   * @param string[] $workflow_ids
   *   An array of workflow IDs to check.
   * @param \Drupal\views\ViewEntityInterface $view
   *   A view configuration object.
   *
   * @internal
   */
  protected function assertWorkflowDependencies(array $workflow_ids, ViewEntityInterface $view): void {
    $dependencies = $view->getDependencies();

    $expected = [];
    foreach (Workflow::loadMultiple($workflow_ids) as $workflow) {
      $expected[] = $workflow->getConfigDependencyName();
    }

    if ($expected) {
      $this->assertSame($expected, $dependencies['config']);
    }
    else {
      $this->assertTrue(!isset($dependencies['config']));
    }
  }

}
