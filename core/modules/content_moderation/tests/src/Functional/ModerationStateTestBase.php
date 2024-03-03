<?php

declare(strict_types=1);

namespace Drupal\Tests\content_moderation\Functional;

use Drupal\Core\Session\AccountInterface;
use Drupal\node\Entity\NodeType;
use Drupal\Tests\BrowserTestBase;
use Drupal\Tests\content_moderation\Traits\ContentModerationTestTrait;
use Drupal\user\Entity\Role;

/**
 * Defines a base class for moderation state tests.
 */
abstract class ModerationStateTestBase extends BrowserTestBase {

  use ContentModerationTestTrait;

  /**
   * Profile to use.
   *
   * @var string
   */
  protected $profile = 'testing';

  /**
   * Admin user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $adminUser;

  /**
   * Permissions to grant admin user.
   *
   * @var array
   */
  protected $permissions = [
    'administer workflows',
    'access administration pages',
    'administer content types',
    'administer nodes',
    'view latest version',
    'view any unpublished content',
    'access content overview',
    'use editorial transition create_new_draft',
    'use editorial transition publish',
    'use editorial transition archive',
    'use editorial transition archived_draft',
    'use editorial transition archived_published',
    'administer taxonomy',
  ];

  /**
   * The editorial workflow entity.
   *
   * @var \Drupal\workflows\Entity\Workflow
   */
  protected $workflow;

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = [
    'content_moderation',
    'block',
    'block_content',
    'node',
    'entity_test',
    'taxonomy',
  ];

  /**
   * Sets the test up.
   */
  protected function setUp(): void {
    parent::setUp();
    $this->workflow = $this->createEditorialWorkflow();
    $this->adminUser = $this->drupalCreateUser($this->permissions);
    $this->drupalPlaceBlock('local_tasks_block', ['id' => 'tabs_block']);
    $this->drupalPlaceBlock('page_title_block');
    $this->drupalPlaceBlock('local_actions_block', ['id' => 'actions_block']);
  }

  /**
   * Gets the permission machine name for a transition.
   *
   * @param string $workflow_id
   *   The workflow ID.
   * @param string $transition_id
   *   The transition ID.
   *
   * @return string
   *   The permission machine name for a transition.
   */
  protected function getWorkflowTransitionPermission($workflow_id, $transition_id) {
    return 'use ' . $workflow_id . ' transition ' . $transition_id;
  }

  /**
   * Creates a content-type from the UI.
   *
   * @param string $content_type_name
   *   Content type human name.
   * @param string $content_type_id
   *   Machine name.
   * @param bool $moderated
   *   TRUE if should be moderated.
   * @param string $workflow_id
   *   The workflow to attach to the bundle.
   */
  protected function createContentTypeFromUi($content_type_name, $content_type_id, $moderated = FALSE, $workflow_id = 'editorial') {
    $this->drupalGet('admin/structure/types');
    $this->clickLink('Add content type');

    $edit = [
      'name' => $content_type_name,
      'type' => $content_type_id,
    ];
    $this->submitForm($edit, 'Save');

    // Check the content type has been set to create new revisions.
    $this->assertTrue(NodeType::load($content_type_id)->shouldCreateNewRevision());

    if ($moderated) {
      $this->enableModerationThroughUi($content_type_id, $workflow_id);
    }
  }

  /**
   * Enable moderation for a specified content type, using the UI.
   *
   * @param string $content_type_id
   *   Machine name.
   * @param string $workflow_id
   *   The workflow to attach to the bundle.
   */
  public function enableModerationThroughUi($content_type_id, $workflow_id = 'editorial') {
    $this->drupalGet('/admin/config/workflow/workflows');
    $this->assertSession()->linkByHrefExists('admin/config/workflow/workflows/manage/' . $workflow_id);
    $edit['bundles[' . $content_type_id . ']'] = TRUE;
    $this->drupalGet('admin/config/workflow/workflows/manage/' . $workflow_id . '/type/node');
    $this->submitForm($edit, 'Save');
    // Ensure the parent environment is up-to-date.
    // @see content_moderation_workflow_insert()
    \Drupal::service('entity_type.bundle.info')->clearCachedBundles();
    \Drupal::service('entity_field.manager')->clearCachedFieldDefinitions();
    /** @var \Drupal\Core\Routing\RouteBuilderInterface $router_builder */
    $router_builder = $this->container->get('router.builder');
    $router_builder->rebuildIfNeeded();
  }

  /**
   * Grants given user permission to create content of given type.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   User to grant permission to.
   * @param string $content_type_id
   *   Content type ID.
   */
  protected function grantUserPermissionToCreateContentOfType(AccountInterface $account, $content_type_id) {
    $role_ids = $account->getRoles(TRUE);
    /** @var \Drupal\user\RoleInterface $role */
    $role_id = reset($role_ids);
    $role = Role::load($role_id);
    $role->grantPermission(sprintf('create %s content', $content_type_id));
    $role->grantPermission(sprintf('edit any %s content', $content_type_id));
    $role->grantPermission(sprintf('delete any %s content', $content_type_id));
    $role->save();
  }

}
