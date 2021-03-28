<?php

namespace Drupal\Tests\field_ui\Functional;

use Drupal\Core\Entity\Entity\EntityFormMode;
use Drupal\Core\Entity\Entity\EntityViewMode;
use Drupal\node\Entity\NodeType;
use Drupal\Tests\BrowserTestBase;

/**
 * Tests the functionality of the Field UI route subscriber.
 *
 * @group field_ui
 */
class FieldUIRouteTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected $profile = 'standard';

  /**
   * Modules to install.
   *
   * @var string[]
   */
  protected static $modules = ['block', 'entity_test', 'field_ui'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->drupalLogin($this->rootUser);
    $this->drupalPlaceBlock('local_tasks_block');
  }

  /**
   * Ensures that entity types with bundles do not break following entity types.
   */
  public function testFieldUIRoutes() {
    $this->drupalGet('entity_test_no_id/structure/entity_test/fields');
    $this->assertText('No fields are present yet.');

    $this->drupalGet('admin/config/people/accounts/fields');
    $this->assertSession()->titleEquals('Manage fields: User | Drupal');
    $this->assertLocalTasks();

    // Test manage display tabs and titles.
    $this->drupalGet('admin/config/people/accounts/display/compact');
    $this->assertSession()->statusCodeEquals(403);

    $this->drupalGet('admin/config/people/accounts/display');
    $this->assertSession()->titleEquals('Manage display | Drupal');
    $this->assertLocalTasks();

    $edit = ['display_modes_custom[compact]' => TRUE];
    $this->submitForm($edit, 'Save');
    $this->drupalGet('admin/config/people/accounts/display/compact');
    $this->assertSession()->titleEquals('Manage display | Drupal');
    $this->assertLocalTasks();

    // Test manage form display tabs and titles.
    $this->drupalGet('admin/config/people/accounts/form-display/register');
    $this->assertSession()->statusCodeEquals(403);

    $this->drupalGet('admin/config/people/accounts/form-display');
    $this->assertSession()->titleEquals('Manage form display | Drupal');
    $this->assertLocalTasks();

    $edit = ['display_modes_custom[register]' => TRUE];
    $this->submitForm($edit, 'Save');
    $this->assertSession()->statusCodeEquals(200);
    $this->drupalGet('admin/config/people/accounts/form-display/register');
    $this->assertSession()->titleEquals('Manage form display | Drupal');
    $this->assertLocalTasks();
    $this->assertCount(1, $this->xpath('//ul/li[1]/a[contains(text(), :text)]', [':text' => 'Default']), 'Default secondary tab is in first position.');

    // Create new view mode and verify it's available on the Manage Display
    // screen after enabling it.
    EntityViewMode::create([
      'id' => 'user.test',
      'label' => 'Test',
      'targetEntityType' => 'user',
    ])->save();
    $this->container->get('router.builder')->rebuildIfNeeded();

    $edit = ['display_modes_custom[test]' => TRUE];
    $this->drupalPostForm('admin/config/people/accounts/display', $edit, 'Save');
    $this->assertSession()->linkExists('Test');

    // Create new form mode and verify it's available on the Manage Form
    // Display screen after enabling it.
    EntityFormMode::create([
      'id' => 'user.test',
      'label' => 'Test',
      'targetEntityType' => 'user',
    ])->save();
    $this->container->get('router.builder')->rebuildIfNeeded();

    $edit = ['display_modes_custom[test]' => TRUE];
    $this->drupalPostForm('admin/config/people/accounts/form-display', $edit, 'Save');
    $this->assertSession()->linkExists('Test');
  }

  /**
   * Asserts that local tasks exists.
   */
  public function assertLocalTasks() {
    $this->assertSession()->linkExists('Settings');
    $this->assertSession()->linkExists('Manage fields');
    $this->assertSession()->linkExists('Manage display');
    $this->assertSession()->linkExists('Manage form display');
  }

  /**
   * Asserts that admin routes are correctly marked as such.
   */
  public function testAdminRoute() {
    $route = \Drupal::service('router.route_provider')->getRouteByName('entity.entity_test.field_ui_fields');
    $is_admin = \Drupal::service('router.admin_context')->isAdminRoute($route);
    $this->assertTrue($is_admin, 'Admin route correctly marked for "Manage fields" page.');
  }

  /**
   * Tests titles of admin routes.
   */
  public function testBundleEntityTitles() {
    /** @var \Drupal\node\NodeTypeInterface $node_type */
    $node_type = NodeType::load('article');
    $node_type_label = $node_type->label();
    /** @var \Drupal\Core\Entity\EntityViewModeInterface $teaser_display_mode */
    $teaser_display_mode = EntityViewMode::load('node.teaser');
    $user_entity_type_label = $this->container->get('entity.manager')
      ->getStorage('user')->getEntityType()->getLabel();
    /** @var \Drupal\Core\Entity\EntityViewModeInterface $compact_display_mode */
    $compact_display_mode = EntityViewMode::load('user.compact');

    // Entities having bundles (e.g. 'node', 'taxonomy_term').
    $path = 'admin/structure/types/manage/article';
    $args = [
      '@bundle' => $node_type_label,
    ];
    $titles = [
      "$path/fields" => (string) t('Manage fields: @bundle', $args),
      "$path/fields/add-field" => (string) t('Add field to @bundle', $args),
      "$path/form-display" => (string) t('Manage form display: @bundle', $args),
      "$path/form-display/default" => (string) t('Manage form display: @bundle', $args),
      "$path/display" => (string) t('Manage display: @bundle', $args),
      "$path/display/default" => (string) t('Manage display: @bundle', $args),
      "$path/display/teaser" => (string) t('Manage display: @bundle', $args),
    ];
    // Entities without bundles (e.g. 'user').
    $path = 'admin/config/people/accounts';
    $args = ['@entity' => $user_entity_type_label];
    $titles += [
      "$path/fields" => (string) t('Manage fields for @entity', $args),
      "$path/fields/add-field" => (string) t('Add field to @entity', $args),
      "$path/form-display" => (string) t('Manage form display @mode for @entity', $args + ['@mode' => (string) t('Default')]),
      "$path/form-display/default" => (string) t('Manage form display @mode for @entity', $args + ['@mode' => (string) t('Default')]),
      "$path/display" => (string) t('Manage display @mode for @entity', $args + ['@mode' => (string) t('Default')]),
      "$path/display/default" => (string) t('Manage display @mode for @entity', $args + ['@mode' => (string) t('Default')]),
      "$path/display/compact" => (string) t('Manage display @mode for @entity', $args + ['@mode' => $compact_display_mode->label()]),
    ];

    foreach ($titles as $path => $title) {
      $this->drupalGet($path);
      $this->assertText($title);
    }
  }

}
