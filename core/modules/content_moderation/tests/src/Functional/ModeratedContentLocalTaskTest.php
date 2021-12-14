<?php

namespace Drupal\Tests\content_moderation\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Tests moderated content dynamic local task.
 *
 * @group content_moderation
 */
class ModeratedContentLocalTaskTest extends BrowserTestBase {

  /**
   * A user to test with.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $adminUser;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'block',
    'content_moderation',
    'node',
  ];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->drupalPlaceBlock('local_tasks_block');

    $this->adminUser = $this->drupalCreateUser([
      'access administration pages',
      'access content overview',
      'view any unpublished content',
    ]);
  }

  /**
   * Tests the moderated content local task appears.
   */
  public function testModeratedContentLocalTask() {
    $this->drupalLogin($this->adminUser);

    // Verify the moderated content tab exists.
    $this->drupalGet('admin/content');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->linkExists('Moderated content');

    // Uninstall the node module which should also remove the tab.
    $this->container->get('module_installer')->uninstall(['node']);

    // Verify the moderated content local task does not exist without the node
    // module installed.
    $this->drupalGet('admin/content');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->linkNotExists('Moderated content');
  }

}
