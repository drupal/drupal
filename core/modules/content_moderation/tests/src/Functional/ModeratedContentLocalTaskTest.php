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
   * A user with permission to bypass access content.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $adminUser;

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'block',
    'content_moderation',
  ];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  public function setUp(): void {
    parent::setUp();

    $this->drupalPlaceBlock('local_tasks_block');

    $this->adminUser = $this->drupalCreateUser([
      'access administration pages',
    ]);
  }

  /**
   * Tests the moderated content local task appears.
   */
  public function testModeratedContentPage() {
    $this->drupalLogin($this->adminUser);

    // Verify the moderated content local task does not exist without the node
    // module installed. We can test this works with the node module in
    // ModeratedContentViewTest::testModeratedContentPage().
    $this->drupalGet('admin/content');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->linkNotExists('Moderated content');
  }

}
