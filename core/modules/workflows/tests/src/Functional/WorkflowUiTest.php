<?php

declare(strict_types=1);

namespace Drupal\Tests\workflows\Functional;

use Drupal\Core\Url;
use Drupal\Tests\BrowserTestBase;
use Drupal\workflows\Entity\Workflow;

/**
 * Tests workflow creation UI.
 *
 * @group workflows
 */
class WorkflowUiTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['workflows', 'workflow_type_test', 'block'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    // We're testing local actions.
    $this->drupalPlaceBlock('local_actions_block');
  }

  /**
   * Tests route access/permissions.
   */
  public function testAccess(): void {
    // Create a minimal workflow for testing.
    $workflow = Workflow::create(['id' => 'test', 'type' => 'workflow_type_test', 'label' => 'Test']);
    $workflow
      ->getTypePlugin()
      ->addState('draft', 'Draft')
      ->addState('published', 'Published')
      ->addTransition('publish', 'Publish', ['draft', 'published'], 'published');
    $workflow->save();

    $paths = [
      'admin/config/workflow/workflows',
      'admin/config/workflow/workflows/add',
      'admin/config/workflow/workflows/manage/test',
      'admin/config/workflow/workflows/manage/test/delete',
      'admin/config/workflow/workflows/manage/test/add_state',
      'admin/config/workflow/workflows/manage/test/state/published',
      'admin/config/workflow/workflows/manage/test/state/published/delete',
      'admin/config/workflow/workflows/manage/test/add_transition',
      'admin/config/workflow/workflows/manage/test/transition/publish',
      'admin/config/workflow/workflows/manage/test/transition/publish/delete',
    ];

    foreach ($paths as $path) {
      $this->drupalGet($path);
      // No access.
      $this->assertSession()->statusCodeEquals(403);
    }
    $this->drupalLogin($this->createUser(['administer workflows']));
    foreach ($paths as $path) {
      $this->drupalGet($path);
      // User has access.
      $this->assertSession()->statusCodeEquals(200);
    }

    // Ensure that default states can not be deleted.
    \Drupal::state()->set('workflow_type_test.required_states', ['published']);
    $this->drupalGet('admin/config/workflow/workflows/manage/test/state/published/delete');
    $this->assertSession()->statusCodeEquals(403);
    \Drupal::state()->set('workflow_type_test.required_states', []);

    // Delete one of the states and ensure the other test cannot be deleted.
    $this->drupalGet('admin/config/workflow/workflows/manage/test/state/published/delete');
    $this->submitForm([], 'Delete');
    $this->drupalGet('admin/config/workflow/workflows/manage/test/state/draft/delete');
    $this->assertSession()->statusCodeEquals(403);
  }

  /**
   * Tests the machine name validation of the state add form.
   */
  public function testStateMachineNameValidation(): void {
    Workflow::create([
      'id' => 'test_workflow',
      'label' => 'Test workflow',
      'type' => 'workflow_type_test',
    ])->save();

    $this->drupalLogin($this->createUser(['administer workflows']));

    $this->drupalGet('admin/config/workflow/workflows/manage/test_workflow/add_state');
    $this->submitForm([
      'label' => 'Test State',
      'id' => 'Invalid ID',
    ], 'Save');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->pageTextContains('The machine-readable name must contain only lowercase letters, numbers, and underscores.');

    $this->drupalGet('admin/config/workflow/workflows/manage/test_workflow/add_transition');
    $this->submitForm([
      'label' => 'Test Transition',
      'id' => 'Invalid ID',
    ], 'Save');
    $this->assertSession()->pageTextContains('The machine-readable name must contain only lowercase letters, numbers, and underscores.');
  }

  /**
   * Tests the creation of a workflow through the UI.
   */
  public function testWorkflowCreation(): void {
    $workflow_storage = $this->container->get('entity_type.manager')->getStorage('workflow');
    /** @var \Drupal\workflows\WorkflowInterface $workflow */
    $this->drupalLogin($this->createUser(['access administration pages', 'administer workflows']));
    $this->drupalGet('admin/config/workflow');
    $this->assertSession()->linkByHrefExists('admin/config/workflow/workflows');
    $this->clickLink('Workflows');
    $this->assertSession()->pageTextContains('Workflows');
    $this->assertSession()->pageTextContains('There are no workflows yet.');
    $this->clickLink('Add workflow');
    $this->submitForm(['label' => 'Test', 'id' => 'test', 'workflow_type' => 'workflow_type_test'], 'Save');
    $this->assertSession()->pageTextContains('Created the Test Workflow.');
    $this->assertSession()->addressEquals('admin/config/workflow/workflows/manage/test/add_state');
    $this->drupalGet('/admin/config/workflow/workflows/manage/test');
    $this->assertSession()->pageTextContains('This workflow has no states and will be disabled until there is at least one, add a new state.');
    $this->assertSession()->pageTextContains('There are no states yet.');
    $this->clickLink('Add a new state');
    $this->submitForm(['label' => 'Published', 'id' => 'published'], 'Save');
    $this->assertSession()->pageTextContains('Created Published state.');
    $workflow = $workflow_storage->loadUnchanged('test');
    $this->assertFalse($workflow->getTypePlugin()->getState('published')->canTransitionTo('published'), 'No default transition from published to published exists.');

    $this->clickLink('Add a new state');
    // Don't create a draft to draft transition by default.
    $this->submitForm(['label' => 'Draft', 'id' => 'draft'], 'Save');
    $this->assertSession()->pageTextContains('Created Draft state.');
    $workflow = $workflow_storage->loadUnchanged('test');
    $this->assertFalse($workflow->getTypePlugin()->getState('draft')->canTransitionTo('draft'), 'Can not transition from draft to draft');

    $this->clickLink('Add a new transition');
    $this->submitForm(['id' => 'publish', 'label' => 'Publish', 'from[draft]' => 'draft', 'to' => 'published'], 'Save');
    $this->assertSession()->pageTextContains('Created Publish transition.');
    $workflow = $workflow_storage->loadUnchanged('test');
    $this->assertTrue($workflow->getTypePlugin()->getState('draft')->canTransitionTo('published'), 'Can transition from draft to published');

    $this->clickLink('Add a new transition');
    $this->assertCount(2, $this->cssSelect('input[name="to"][type="radio"]'));
    $this->assertCount(0, $this->cssSelect('input[name="to"][checked="checked"][type="radio"]'));
    $this->submitForm(['id' => 'create_new_draft', 'label' => 'Create new draft', 'from[draft]' => 'draft', 'to' => 'draft'], 'Save');
    $this->assertSession()->pageTextContains('Created Create new draft transition.');
    $workflow = $workflow_storage->loadUnchanged('test');
    $this->assertTrue($workflow->getTypePlugin()->getState('draft')->canTransitionTo('draft'), 'Can transition from draft to draft');

    // The fist state to edit on the page should be published.
    $this->clickLink('Edit');
    $this->assertSession()->fieldValueEquals('label', 'Published');
    // Change the label.
    $this->submitForm(['label' => 'Live'], 'Save');
    $this->assertSession()->pageTextContains('Saved Live state.');

    // Allow published to draft.
    $this->clickLink('Edit', 3);
    $this->submitForm(['from[published]' => 'published'], 'Save');
    $this->assertSession()->pageTextContains('Saved Create new draft transition.');
    $workflow = $workflow_storage->loadUnchanged('test');
    $this->assertTrue($workflow->getTypePlugin()->getState('published')->canTransitionTo('draft'), 'Can transition from published to draft');

    // Try creating a duplicate transition.
    $this->clickLink('Add a new transition');
    $this->submitForm(['id' => 'create_new_draft', 'label' => 'Create new draft', 'from[published]' => 'published', 'to' => 'draft'], 'Save');
    $this->assertSession()->pageTextContains('The machine-readable name is already in use. It must be unique.');
    // Try creating a transition which duplicates the states of another.
    $this->submitForm(['id' => 'create_new_draft2', 'label' => 'Create new draft again', 'from[published]' => 'published', 'to' => 'draft'], 'Save');
    $this->assertSession()->pageTextContains('The transition from Live to Draft already exists.');

    // Create a new transition.
    $this->submitForm(['id' => 'save_and_publish', 'label' => 'Save and publish', 'from[published]' => 'published', 'to' => 'published'], 'Save');
    $this->assertSession()->pageTextContains('Created Save and publish transition.');
    // Edit the new transition and try to add an existing transition.
    $this->clickLink('Edit', 4);
    $this->submitForm(['from[draft]' => 'draft'], 'Save');
    $this->assertSession()->pageTextContains('The transition from Draft to Live already exists.');

    // Delete the transition.
    $workflow = $workflow_storage->loadUnchanged('test');
    $this->assertTrue($workflow->getTypePlugin()->hasTransitionFromStateToState('published', 'published'), 'Can transition from published to published');
    $this->clickLink('Delete');
    $this->assertSession()->pageTextContains('Are you sure you want to delete Save and publish from Test?');
    $this->submitForm([], 'Delete');
    $workflow = $workflow_storage->loadUnchanged('test');
    $this->assertFalse($workflow->getTypePlugin()->hasTransitionFromStateToState('published', 'published'), 'Cannot transition from published to published');

    // Try creating a duplicate state.
    $this->drupalGet('admin/config/workflow/workflows/manage/test');
    $this->clickLink('Add a new state');
    $this->submitForm(['label' => 'Draft', 'id' => 'draft'], 'Save');
    $this->assertSession()->pageTextContains('The machine-readable name is already in use. It must be unique.');

    // Ensure that weight changes the state ordering.
    $workflow = $workflow_storage->loadUnchanged('test');
    $this->assertEquals('published', $workflow->getTypePlugin()->getInitialState()->id());
    $this->drupalGet('admin/config/workflow/workflows/manage/test');
    $this->submitForm(['states[draft][weight]' => '-1'], 'Save');
    $workflow = $workflow_storage->loadUnchanged('test');
    $this->assertEquals('draft', $workflow->getTypePlugin()->getInitialState()->id());

    // Verify that we are still on the workflow edit page.
    $this->assertSession()->addressEquals('admin/config/workflow/workflows/manage/test');

    // Ensure that weight changes the transition ordering.
    $this->assertEquals(['publish', 'create_new_draft'], array_keys($workflow->getTypePlugin()->getTransitions()));
    $this->drupalGet('admin/config/workflow/workflows/manage/test');
    $this->submitForm(['transitions[create_new_draft][weight]' => '-1'], 'Save');
    $workflow = $workflow_storage->loadUnchanged('test');
    $this->assertEquals(['create_new_draft', 'publish'], array_keys($workflow->getTypePlugin()->getTransitions()));

    // Verify that we are still on the workflow edit page.
    $this->assertSession()->addressEquals('admin/config/workflow/workflows/manage/test');

    // Ensure that a delete link for the published state exists before deleting
    // the draft state.
    $published_delete_link = Url::fromRoute('entity.workflow.delete_state_form', [
      'workflow' => $workflow->id(),
      'workflow_state' => 'published',
    ])->toString();
    $draft_delete_link = Url::fromRoute('entity.workflow.delete_state_form', [
      'workflow' => $workflow->id(),
      'workflow_state' => 'draft',
    ])->toString();
    $this->assertSession()->elementContains('css', 'tr[data-drupal-selector="edit-states-published"]', 'Delete');
    $this->assertSession()->linkByHrefExists($published_delete_link);
    $this->assertSession()->linkByHrefExists($draft_delete_link);

    // Make the published state a default state and ensure it is no longer
    // linked.
    \Drupal::state()->set('workflow_type_test.required_states', ['published']);
    $this->getSession()->reload();
    $this->assertSession()->linkByHrefNotExists($published_delete_link);
    $this->assertSession()->linkByHrefExists($draft_delete_link);
    $this->assertSession()->elementNotContains('css', 'tr[data-drupal-selector="edit-states-published"]', 'Delete');
    \Drupal::state()->set('workflow_type_test.required_states', []);
    $this->getSession()->reload();
    $this->assertSession()->elementContains('css', 'tr[data-drupal-selector="edit-states-published"]', 'Delete');
    $this->assertSession()->linkByHrefExists($published_delete_link);
    $this->assertSession()->linkByHrefExists($draft_delete_link);

    // Delete the Draft state.
    $this->clickLink('Delete');
    $this->assertSession()->pageTextContains('Are you sure you want to delete Draft from Test?');
    $this->submitForm([], 'Delete');
    $this->assertSession()->pageTextContains('State Draft deleted.');
    $workflow = $workflow_storage->loadUnchanged('test');
    $this->assertFalse($workflow->getTypePlugin()->hasState('draft'), 'Draft state deleted');
    $this->assertTrue($workflow->getTypePlugin()->hasState('published'), 'Workflow still has published state');

    // The last state cannot be deleted so the only delete link on the page will
    // be for the workflow.
    $this->assertSession()->linkByHrefNotExists($published_delete_link);
    $this->clickLink('Delete');
    $this->assertSession()->pageTextContains('Are you sure you want to delete Test?');
    $this->submitForm([], 'Delete');
    $this->assertSession()->pageTextContains('Workflow Test deleted.');
    $this->assertSession()->pageTextContains('There are no workflows yet.');
    $this->assertNull($workflow_storage->loadUnchanged('test'), 'The test workflow has been deleted');

    // Ensure that workflow types with default configuration are initialized
    // correctly.
    $this->drupalGet('admin/config/workflow/workflows');
    $this->clickLink('Add workflow');
    $this->submitForm(['label' => 'Test 2', 'id' => 'test2', 'workflow_type' => 'workflow_type_required_state_test'], 'Save');
    $this->assertSession()->addressEquals('admin/config/workflow/workflows/manage/test2');
    $workflow = $workflow_storage->loadUnchanged('test2');
    $this->assertTrue($workflow->getTypePlugin()->hasState('fresh'), 'The workflow has the "fresh" state');
    $this->assertTrue($workflow->getTypePlugin()->hasState('rotten'), 'The workflow has the "rotten" state');
    $this->assertTrue($workflow->getTypePlugin()->hasTransition('rot'), 'The workflow has the "rot" transition');
    $this->assertSession()->pageTextContains('Fresh');
    $this->assertSession()->pageTextContains('Rotten');
  }

  /**
   * Tests the workflow configuration form.
   */
  public function testWorkflowConfigurationForm(): void {
    $workflow = Workflow::create(['id' => 'test', 'type' => 'workflow_type_complex_test', 'label' => 'Test']);
    $workflow
      ->getTypePlugin()
      ->addState('published', 'Published')
      ->addTransition('publish', 'Publish', ['published'], 'published');
    $workflow->save();

    $this->drupalLogin($this->createUser(['administer workflows']));

    // Add additional information to the workflow via the configuration form.
    $this->drupalGet('admin/config/workflow/workflows/manage/test');
    $this->assertSession()->pageTextContains('Example global workflow setting');
    $this->submitForm(['type_settings[example_setting]' => 'Extra global settings'], 'Save');

    $workflow_storage = $this->container->get('entity_type.manager')->getStorage('workflow');
    $workflow = $workflow_storage->loadUnchanged('test');
    $this->assertEquals('Extra global settings', $workflow->getTypePlugin()->getConfiguration()['example_setting']);
  }

  /**
   * Tests a workflow, state, and transition can have a numeric ID and label.
   */
  public function testNumericIds(): void {
    $this->drupalLogin($this->createUser(['administer workflows']));
    $this->drupalGet('admin/config/workflow/workflows');
    $this->clickLink('Add workflow');
    $this->submitForm(['label' => 123, 'id' => 123, 'workflow_type' => 'workflow_type_complex_test'], 'Save');

    $this->assertSession()->addressEquals('admin/config/workflow/workflows/manage/123/add_state');

    $this->submitForm(['label' => 456, 'id' => 456], 'Save');
    $this->assertSession()->pageTextContains('Created 456 state.');

    $this->clickLink('Add a new state');
    $this->submitForm(['label' => 789, 'id' => 789], 'Save');
    $this->assertSession()->pageTextContains('Created 789 state.');

    $this->clickLink('Add a new transition');
    $this->submitForm(['id' => 101112, 'label' => 101112, 'from[456]' => 456, 'to' => 789], 'Save');
    $this->assertSession()->pageTextContains('Created 101112 transition.');

    $workflow = $this->container->get('entity_type.manager')->getStorage('workflow')->loadUnchanged(123);
    $this->assertEquals(123, $workflow->id());
    $this->assertEquals(456, $workflow->getTypePlugin()->getState(456)->id());
    $this->assertEquals(101112, $workflow->getTypePlugin()->getTransition(101112)->id());
    $this->assertEquals(789, $workflow->getTypePlugin()->getTransition(101112)->to()->id());
  }

  /**
   * Tests the sorting of states and transitions by weight and label.
   */
  public function testSorting(): void {
    $workflow = Workflow::create(['id' => 'test', 'type' => 'workflow_type_complex_test', 'label' => 'Test']);
    $workflow
      ->getTypePlugin()
      ->setConfiguration([
        'states' => [
          'two_a' => [
            'label' => 'two a',
            'weight' => 2,
          ],
          'three' => [
            'label' => 'three',
            'weight' => 3,
          ],
          'two_b' => [
            'label' => 'two b',
            'weight' => 2,
          ],
          'one' => [
            'label' => 'one',
            'weight' => 1,
          ],
        ],
        'transitions' => [
          'three' => [
            'label' => 'three',
            'from' => ['three'],
            'to' => 'three',
            'weight' => 3,
          ],
          'two_a' => [
            'label' => 'two a',
            'from' => ['two_a'],
            'to' => 'two_a',
            'weight' => 2,
          ],
          'one' => [
            'label' => 'one',
            'from' => ['one'],
            'to' => 'one',
            'weight' => 1,
          ],
          'two_b' => [
            'label' => 'two b',
            'from' => ['two_b'],
            'to' => 'two_b',
            'weight' => 2,
          ],
        ],
      ]);
    $workflow->save();

    $this->drupalLogin($this->createUser(['administer workflows']));
    $this->drupalGet('admin/config/workflow/workflows/manage/test');
    $expected_states = ['one', 'two a', 'two b', 'three'];
    $elements = $this->xpath('//details[@id="edit-states-container"]//table/tbody/tr');
    foreach ($elements as $key => $element) {
      $this->assertEquals($expected_states[$key], $element->find('xpath', 'td')->getText());
    }
    $expected_transitions = ['one', 'two a', 'two b', 'three'];
    $elements = $this->xpath('//details[@id="edit-transitions-container"]//table/tbody/tr');
    foreach ($elements as $key => $element) {
      $this->assertEquals($expected_transitions[$key], $element->find('xpath', 'td')->getText());
    }

    // Ensure that there are enough weights to satisfy the potential number of
    // states and transitions.
    $this->assertSession()
      ->selectExists('states[three][weight]')
      ->selectOption('2');
    $this->assertSession()
      ->selectExists('states[three][weight]')
      ->selectOption('-2');
    $this->assertSession()
      ->selectExists('transitions[three][weight]')
      ->selectOption('2');
    $this->assertSession()
      ->selectExists('transitions[three][weight]')
      ->selectOption('-2');
  }

}
