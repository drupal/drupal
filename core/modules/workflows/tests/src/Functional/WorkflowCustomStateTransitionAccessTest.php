<?php

namespace Drupal\Tests\workflows\Functional;

use Drupal\Tests\BrowserTestBase;
use Drupal\workflows\Entity\Workflow;

/**
 * Test custom provided workflow access for state/transition operations.
 *
 * @group workflows
 */
class WorkflowCustomStateTransitionAccessTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'workflows',
    'workflow_type_test',
  ];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * A test admin user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $adminUser;

  /**
   * A test workflow.
   *
   * @var \Drupal\workflows\WorkflowInterface
   */
  protected $testWorkflow;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->adminUser = $this->createUser(['administer workflows']);
    $this->testWorkflow = Workflow::create([
      'label' => 'Test workflow',
      'id' => 'test_type',
      'type' => 'workflow_custom_access_type',
    ]);
    $this->testWorkflow->save();
  }

  /**
   * Test the custom state/transition operation access rules.
   */
  public function testCustomWorkflowAccessOperations() {
    $this->drupalLogin($this->adminUser);
    $forbidden_paths = [
      'admin/config/workflow/workflows/manage/test_type/state/cannot_delete/delete',
      'admin/config/workflow/workflows/manage/test_type/state/cannot_update',
      'admin/config/workflow/workflows/manage/test_type/transition/cannot_update',
      'admin/config/workflow/workflows/manage/test_type/transition/cannot_delete/delete',
      'admin/config/workflow/workflows/manage/test_type/add_state',
      'admin/config/workflow/workflows/manage/test_type/add_transition',
    ];
    // Until the list of forbidden operations have been set, the admin user
    // should be able to access all the forbidden paths.
    foreach ($forbidden_paths as $forbidden_path) {
      $this->drupalGet($forbidden_path);
      $this->assertSession()->statusCodeEquals(200);
    }

    // Update the forbidden operations which deny access to the actions
    // represented by the above paths.
    $this->container->get('state')->set('workflow_type_test_forbidden_operations', [
      'update-state:cannot_update',
      'delete-state:cannot_delete',
      'update-transition:cannot_update',
      'delete-transition:cannot_delete',
      'add-state',
      'add-transition',
    ]);
    foreach ($forbidden_paths as $forbidden_path) {
      $this->drupalGet($forbidden_path);
      $this->assertSession()->statusCodeEquals(403);
    }
  }

}
