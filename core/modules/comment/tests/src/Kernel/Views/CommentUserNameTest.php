<?php

declare(strict_types=1);

namespace Drupal\Tests\comment\Kernel\Views;

use Drupal\comment\Entity\Comment;
use Drupal\comment\Entity\CommentType;
use Drupal\Core\Session\AnonymousUserSession;
use Drupal\entity_test\Entity\EntityTest;
use Drupal\Tests\views\Kernel\ViewsKernelTestBase;
use Drupal\user\Entity\Role;
use Drupal\user\Entity\User;
use Drupal\views\Entity\View;
use Drupal\views\Views;

/**
 * Tests comment user name field.
 *
 * @group comment
 */
class CommentUserNameTest extends ViewsKernelTestBase {

  /**
   * Admin user.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $adminUser;
  /**
   * {@inheritdoc}
   */
  protected static $modules = ['user', 'comment', 'entity_test'];

  /**
   * {@inheritdoc}
   */
  protected function setUp($import_test_views = TRUE): void {
    parent::setUp($import_test_views);

    $this->installEntitySchema('user');
    $this->installEntitySchema('comment');
    $this->installEntitySchema('entity_test');
    // Create the anonymous role.
    $this->installConfig(['user']);

    // Create an anonymous user.
    $storage = \Drupal::entityTypeManager()->getStorage('user');
    // Insert a row for the anonymous user.
    $storage
      ->create([
        'uid' => 0,
        'name' => '',
        'status' => 0,
      ])
      ->save();

    $admin_role = Role::create([
      'id' => 'admin',
      'permissions' => [
        'view test entity',
        'administer comments',
        'access user profiles',
        'access comments',
      ],
      'label' => 'Admin',
    ]);
    $admin_role->save();

    /** @var \Drupal\user\RoleInterface $anonymous_role */
    $anonymous_role = Role::load(Role::ANONYMOUS_ID);
    $anonymous_role->grantPermission('access comments');
    $anonymous_role->save();

    $this->adminUser = User::create([
      'name' => $this->randomMachineName(),
      'roles' => [$admin_role->id()],
    ]);
    $this->adminUser->save();

    $host = EntityTest::create(['name' => $this->randomString()]);
    $host->save();

    $commentType = CommentType::create([
      'id' => 'entity_test_comment',
      'label' => t('Entity Test Comment'),
      'target_entity_type_id' => 'entity_test',
    ]);
    $commentType->save();

    // Create some comments.
    $comment = Comment::create([
      'subject' => 'My comment title',
      'uid' => $this->adminUser->id(),
      'name' => $this->adminUser->label(),
      'entity_type' => 'entity_test',
      'field_name' => 'comment',
      'entity_id' => $host->id(),
      'comment_type' => 'entity_test_comment',
      'status' => 1,
    ]);
    $comment->save();

    $comment_anonymous = Comment::create([
      'subject' => 'Anonymous comment title',
      'uid' => 0,
      'name' => 'barry',
      'mail' => 'test@example.com',
      'homepage' => 'https://example.com',
      'entity_type' => 'entity_test',
      'field_name' => 'comment',
      'entity_id' => $host->id(),
      'comment_type' => 'entity_test_comment',
      'created' => 123456,
      'status' => 1,
    ]);
    $comment_anonymous->save();
  }

  /**
   * Tests the username formatter.
   */
  public function testUsername(): void {
    $view_id = $this->randomMachineName();
    $view = View::create([
      'id' => $view_id,
      'label' => $view_id,
      'base_table' => 'comment_field_data',
      'display' => [
        'default' => [
          'display_plugin' => 'default',
          'id' => 'default',
          'display_options' => [
            'fields' => [
              'name' => [
                'table' => 'comment_field_data',
                'field' => 'name',
                'id' => 'name',
                'plugin_id' => 'field',
                'type' => 'comment_username',
              ],
              'subject' => [
                'table' => 'comment_field_data',
                'field' => 'subject',
                'id' => 'subject',
                'plugin_id' => 'field',
                'type' => 'string',
                'settings' => [
                  'link_to_entity' => TRUE,
                ],
              ],
            ],
          ],
        ],
      ],
    ]);
    $view->save();

    /** @var \Drupal\Core\Session\AccountSwitcherInterface $account_switcher */
    $account_switcher = \Drupal::service('account_switcher');

    /** @var \Drupal\Core\Render\RendererInterface $renderer */
    $renderer = \Drupal::service('renderer');

    $account_switcher->switchTo($this->adminUser);
    $executable = Views::getView($view_id);
    $build = $executable->preview();
    $this->setRawContent($renderer->renderRoot($build));

    $this->assertLink('My comment title');
    $this->assertLink('Anonymous comment title');
    // Display plugin of the view is showing the name field. When comment
    // belongs to an authenticated user the name field has no value.
    $comment_author = $this->xpath('//div[contains(@class, :class)]/span[normalize-space(text())=""]', [
      ':class' => 'views-field-subject',
    ]);
    $this->assertNotEmpty($comment_author);
    // When comment belongs to an anonymous user the name field has a value and
    // it is rendered correctly.
    $this->assertLink('barry (not verified)');

    $account_switcher->switchTo(new AnonymousUserSession());
    $executable = Views::getView($view_id);
    $executable->storage->invalidateCaches();

    $build = $executable->preview();
    $this->setRawContent($renderer->renderRoot($build));

    // No access to user-profiles, so shouldn't be able to see links.
    $this->assertNoLink($this->adminUser->label());
    // Note: External users aren't pointing to drupal user profiles.
    $this->assertLink('barry (not verified)');
    // Anonymous user does not have access to this link but can still see title.
    $this->assertText('My comment title');
    $this->assertNoLink('My comment title');
    $this->assertText('Anonymous comment title');
    $this->assertNoLink('Anonymous comment title');
  }

}
