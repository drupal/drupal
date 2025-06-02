<?php

declare(strict_types=1);

namespace Drupal\Tests\workspaces\FunctionalJavascript;

use Drupal\Tests\media_library\FunctionalJavascript\EntityReferenceWidgetTest;
use Drupal\user\UserInterface;
use Drupal\workspaces\Entity\Workspace;

/**
 * Tests the Media library entity reference widget in a workspace.
 *
 * @group workspaces
 */
class WorkspacesMediaLibraryIntegrationTest extends EntityReferenceWidgetTest {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'workspaces',
  ];

  /**
   * An array of test methods that are not relevant for workspaces.
   */
  const SKIP_METHODS = [
    // This test does not assert anything that can be workspace-specific.
    'testFocusNotAppliedWithoutSelectionChange',
    // This test does not assert anything that can be workspace-specific.
    'testRequiredMediaField',
    // This test tries to edit an entity in Live after it has been edited in a
    // workspace, which is not currently possible.
    'testWidgetPreview',
  ];

  /**
   * {@inheritdoc}
   */
  public function setUp(): void {
    if (in_array($this->name(), static::SKIP_METHODS, TRUE)) {
      $this->markTestSkipped('Irrelevant for this test');
    }

    parent::setUp();

    // Ensure that all the test methods are executed in the context of a
    // workspace.
    $workspace = Workspace::create(['id' => 'test', 'label' => 'Test']);
    $workspace->save();
    \Drupal::service('workspaces.manager')->setActiveWorkspace($workspace);
  }

  /**
   * {@inheritdoc}
   */
  protected function drupalCreateUser(array $permissions = [], $name = NULL, $admin = FALSE, array $values = []): UserInterface|false {
    // Ensure that users and roles are managed outside a workspace context.
    return \Drupal::service('workspaces.manager')->executeOutsideWorkspace(function () use ($permissions, $name, $admin, $values) {
      $permissions = array_merge($permissions, [
        'view any workspace',
      ]);
      return parent::drupalCreateUser($permissions, $name, $admin, $values);
    });
  }

}
