<?php

declare(strict_types=1);

namespace Drupal\Tests\layout_builder\Functional;

use Drupal\block_content\Entity\BlockContent;
use Drupal\block_content\Entity\BlockContentType;
use Drupal\layout_builder\Entity\LayoutBuilderEntityViewDisplay;
use Drupal\Tests\layout_builder\Traits\EnableLayoutBuilderTrait;
use Drupal\user\Entity\Role;
use Drupal\user\UserInterface;

/**
 * Tests overrides editing uses the correct theme.
 *
 * Block content is used for this test as its canonical & editing routes
 * are in the admin section, so we need to test that layout builder editing
 * uses the front end theme.
 *
 * @group layout_builder
 */
class LayoutBuilderOverridesEditingThemeTest extends LayoutBuilderTestBase {

  use EnableLayoutBuilderTrait;

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'test_theme';

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'block_content',
  ];

  /**
   * Permissions to grant admin user.
   */
  protected array $permissions = [
    'administer blocks',
    'access block library',
    'administer block types',
    'administer block content',
    'administer block_content display',
    'configure any layout',
    'view the administration theme',
    'edit any basic block content',
  ];

  /**
   * Admin user.
   */
  protected UserInterface $adminUser;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    // Create a basic block content type.
    BlockContentType::create([
      'id' => 'basic',
      'label' => 'Basic',
      'revision' => FALSE,
    ])->save();
    $this->adminUser = $this->drupalCreateUser($this->permissions);
  }

  /**
   * Tests editing block content with Layout Builder.
   */
  public function testEditing(): void {
    // Create a new role for additional permissions needed.
    $role = Role::create([
      'id' => 'layout_builder_tester',
      'label' => 'Layout Builder Tester',
    ]);
    // Set a different theme for the admin pages. So we can assert the theme
    // in Layout Builder is not the same as the admin theme.
    \Drupal::service('theme_installer')->install(['claro']);
    $this->config('system.theme')->set('admin', 'claro')->save();

    // Enable layout builder for the block content display.
    $display = LayoutBuilderEntityViewDisplay::create([
      'targetEntityType' => 'block_content',
      'bundle' => 'basic',
      'mode' => 'default',
      'status' => TRUE,
    ]);
    $display->save();
    $this->enableLayoutBuilder($display);
    $role->grantPermission('configure all basic block_content layout overrides');
    $role->save();
    $this->adminUser
      ->addRole($role->id())
      ->save();
    $this->drupalLogin($this->adminUser);
    // Create a block content and test the themes used.
    $blockContent = BlockContent::create([
      'info' => $this->randomMachineName(),
      'type' => 'basic',
      'langcode' => 'en',
    ]);
    $blockContent->save();
    // Assert the test_theme is being used for overrides.
    $this->drupalGet('admin/content/block/' . $blockContent->id() . '/layout');
    $this->assertSession()->statusCodeEquals(200);
    // Assert the test_theme is being used.
    $this->assertSession()->responseContains('test_theme/kitten.css');
    // Assert the claro theme is not being used.
    $this->assertSession()->elementNotExists('css', '#block-claro-content');
    // Assert the default still uses the test_theme.
    $this->drupalGet('admin/structure/block-content/manage/basic/display/default/layout');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->responseContains('test_theme/kitten.css');
    $this->assertSession()->elementNotExists('css', '#block-claro-content');
  }

}
