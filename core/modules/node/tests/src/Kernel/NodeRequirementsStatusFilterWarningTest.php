<?php

declare(strict_types=1);

namespace Drupal\Tests\node\Kernel;

use Drupal\Core\Extension\Requirement\RequirementSeverity;
use Drupal\KernelTests\KernelTestBase;
use Drupal\Tests\user\Traits\UserCreationTrait;
use Drupal\views\Entity\View;

/**
 * Tests redundant status filter warnings raised by node_requirements().
 *
 * @group node
 */
class NodeRequirementsStatusFilterWarningTest extends KernelTestBase {

  use UserCreationTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'system',
    'user',
    'node',
    'text',
    'field',
    'views',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->installEntitySchema('node');
    $this->installEntitySchema('user');
    $this->installEntitySchema('view');
    $this->installConfig(['field', 'node', 'views']);
    $this->installSchema('node', ['node_access']);
    // Remove default views to avoid interference.
    View::load('content')->delete();
    View::load('content_recent')->delete();
  }

  /**
   * Tests node_requirements() with no node grants implementations.
   */
  public function testNoNodeGrantsImplementations(): void {
    $requirements = $this->getRequirements();
    $this->assertArrayNotHasKey('node_status_filter', $requirements);
  }

  /**
   * Tests node_requirements() with node grants but no problematic views.
   */
  public function testNodeGrantsNoProblematicViews(): void {
    $this->enableNodeAccessTestModule();

    $requirements = $this->getRequirements();
    $this->assertArrayNotHasKey('node_status_filter', $requirements);
  }

  /**
   * Tests node_requirements() with node grants and one problematic view.
   */
  public function testNodeGrantsWithProblematicView(): void {
    $this->enableNodeAccessTestModule();

    $this->createTestView(
      'test_view',
      'Test View',
      TRUE,
      [$this->getStatusFilterConfig()]
    );

    $requirements = $this->getRequirements();
    $this->assertArrayHasKey('node_status_filter', $requirements);
    $this->assertEquals(RequirementSeverity::Warning, $requirements['node_status_filter']['severity']);
  }

  /**
   * Tests node_requirements() with multiple problematic views.
   */
  public function testMultipleProblematicViews(): void {
    $this->enableNodeAccessTestModule();

    $this->createTestView(
      'test_view_1',
      'Test View 1',
      TRUE,
      [$this->getStatusFilterConfig()]
    );

    $this->createTestView(
      'test_view_2',
      'Test View 2',
      TRUE,
      [$this->getStatusFilterConfig()]
    );

    $requirements = $this->getRequirements();
    $this->assertArrayHasKey('node_status_filter', $requirements);
    $this->assertEquals(RequirementSeverity::Warning, $requirements['node_status_filter']['severity']);
  }

  /**
   * Tests node_requirements() ignores disabled views.
   */
  public function testDisabledViewsNotChecked(): void {
    $this->enableNodeAccessTestModule();

    $this->createTestView(
      'test_disabled_view',
      'Disabled Test View',
      FALSE,
      [$this->getStatusFilterConfig()]
    );

    $requirements = $this->getRequirements();
    $this->assertArrayNotHasKey('node_status_filter', $requirements);
  }

  /**
   * Tests description with one view and one access module.
   */
  public function testDescriptionWithSingleViewAndSingleModule(): void {
    $this->enableNodeAccessTestModule();
    $this->createTestView(
      'test_view',
      'Test View',
      TRUE,
      ['status' => $this->getStatusFilterConfig()]
    );

    $requirements = $this->getRequirements();
    $this->assertEquals('The <em class="placeholder">Test View (Default)</em> view uses the <em class="placeholder">Published status or admin user</em> filter but it has no effect because the following module(s) control access: <em class="placeholder">Node module access tests</em>. Review and consider removing the filter.', $this->renderStatusFilterDescription($requirements));
  }

  /**
   * Tests description with multiple views and one access module.
   */
  public function testDescriptionWithMultipleViewsAndSingleModule(): void {
    $this->enableNodeAccessTestModule();
    $this->createTestView('test_view_1', 'Test View 1', TRUE, ['status' => $this->getStatusFilterConfig()]);
    $this->createTestView('test_view_2', 'Test View 2', TRUE, ['status' => $this->getStatusFilterConfig()]);

    $requirements = $this->getRequirements();
    self::assertEquals('The following views use the <em class="placeholder">Published status or admin user</em> filter but it has no effect because the following module(s) control access: <em class="placeholder">Node module access tests</em>. Review and consider removing the filter from these views: <ul><li>Test View 1 (Default)</li><li>Test View 2 (Default)</li></ul>', $this->renderStatusFilterDescription($requirements));
  }

  /**
   * Tests description formatting with Views UI disabled.
   */
  public function testDescriptionWithViewsUiDisabled(): void {
    self::assertFalse($this->container->get('module_handler')->moduleExists('views_ui'));
    $this->enableNodeAccessTestModule();
    $this->createTestView('test_view', 'Test View', TRUE, ['status' => $this->getStatusFilterConfig()]);

    $requirements = $this->getRequirements();
    $description = $this->renderStatusFilterDescription($requirements);

    $this->assertStringContainsString('Test View (Default)', $description);
    $this->assertStringNotContainsString('href=', $description);
  }

  /**
   * Tests description when Views UI is enabled but user cannot edit views.
   */
  public function testDescriptionWithViewsUiEnabledWithoutUserHavingEditViewDisplayAccess(): void {
    $this->enableModules(['views_ui']);
    $this->enableNodeAccessTestModule();
    $this->createTestView('test_view', 'Test View', TRUE, ['status' => $this->getStatusFilterConfig()]);

    $requirements = $this->getRequirements();
    self::assertEquals('The <em class="placeholder">Test View (Default)</em> view uses the <em class="placeholder">Published status or admin user</em> filter but it has no effect because the following module(s) control access: <em class="placeholder">Node module access tests</em>. Review and consider removing the filter.', $this->renderStatusFilterDescription($requirements));
  }

  /**
   * Tests description when Views UI is enabled but user can edit views.
   */
  public function testDescriptionWithViewsUiEnabledWithUserHavingEditViewDisplayAccess(): void {
    $this->enableModules(['views_ui']);
    $this->setCurrentUser($this->createUser(['administer views']));
    $this->enableNodeAccessTestModule();
    $this->createTestView('test_view', 'Test View', TRUE, ['status' => $this->getStatusFilterConfig()]);

    $requirements = $this->getRequirements();
    self::assertEquals('The <em class="placeholder"><a href="/admin/structure/views/view/test_view/edit/default">Test View (Default)</a></em> view uses the <em class="placeholder">Published status or admin user</em> filter but it has no effect because the following module(s) control access: <em class="placeholder">Node module access tests</em>. Review and consider removing the filter.', $this->renderStatusFilterDescription($requirements));
  }

  /**
   * Get requirements by the Node module.
   *
   * @return array
   *   The requirements raised by the Node module.
   */
  private function getRequirements(): array {
    return $this->container->get('module_handler')->invoke('node', 'runtime_requirements');
  }

  /**
   * Renders the description of the node_status_filter requirement.
   */
  private function renderStatusFilterDescription(array $requirements): string {
    return (string) $requirements['node_status_filter']['description']->render();
  }

  /**
   * Helper method to create a test view.
   *
   * @param string $id
   *   The view ID.
   * @param string $label
   *   The view label.
   * @param bool $status
   *   Whether the view is enabled.
   * @param array $filters
   *   Filters to apply to the view.
   *
   * @return \Drupal\views\Entity\View
   *   The created view entity.
   */
  private function createTestView(string $id, string $label, bool $status, array $filters): View {
    $view = View::create([
      'id' => $id,
      'label' => $label,
      'status' => $status,
      'base_table' => 'node_field_data',
      'display' => [
        'default' => [
          'display_title' => 'Default',
          'display_plugin' => 'default',
          'id' => 'default',
          'display_options' => [
            'filters' => $filters,
          ],
        ],
      ],
    ]);
    $view->save();
    return $view;
  }

  /**
   * Helper to get status filter configuration.
   */
  private function getStatusFilterConfig(): array {
    return [
      'id' => 'status',
      'table' => 'node_field_data',
      'field' => 'status',
      'plugin_id' => 'node_status',
    ];
  }

  /**
   * Enables Node Access Test module.
   */
  private function enableNodeAccessTestModule(): void {
    $this->enableModules(['node_access_test']);
    self::assertTrue($this->container->get('module_handler')->hasImplementations('node_grants'));
  }

}
