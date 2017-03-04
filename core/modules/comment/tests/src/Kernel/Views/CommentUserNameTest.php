<?php

namespace Drupal\Tests\comment\Kernel\Views;

use Drupal\comment\Entity\Comment;
use Drupal\Core\Session\AnonymousUserSession;
use Drupal\Tests\views\Kernel\ViewsKernelTestBase;
use Drupal\user\Entity\Role;
use Drupal\user\Entity\User;
use Drupal\views\Entity\View;
use Drupal\views\Views;

/**
 * Tests comment user name field
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
  public static $modules = ['user', 'comment', 'entity_test'];

  /**
   * {@inheritdoc}
   */
  protected function setUp($import_test_views = TRUE) {
    parent::setUp($import_test_views);

    $this->installEntitySchema('user');
    $this->installEntitySchema('comment');
    // Create the anonymous role.
    $this->installConfig(['user']);

    // Create an anonymous user.
    $storage = \Drupal::entityManager()->getStorage('user');
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
      'permissions' => ['administer comments', 'access user profiles'],
    ]);
    $admin_role->save();

    /* @var \Drupal\user\RoleInterface $anonymous_role */
    $anonymous_role = Role::load(Role::ANONYMOUS_ID);
    $anonymous_role->grantPermission('access comments');
    $anonymous_role->save();

    $this->adminUser = User::create([
      'name' => $this->randomMachineName(),
      'roles' => [$admin_role->id()],
    ]);
    $this->adminUser->save();

    // Create some comments.
    $comment = Comment::create([
      'subject' => 'My comment title',
      'uid' => $this->adminUser->id(),
      'name' => $this->adminUser->label(),
      'entity_type' => 'entity_test',
      'comment_type' => 'entity_test',
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
      'comment_type' => 'entity_test',
      'created' => 123456,
      'status' => 1,
    ]);
    $comment_anonymous->save();
  }

  /**
   * Test the username formatter.
   */
  public function testUsername() {
    $view_id = $this->randomMachineName();
    $view = View::create([
      'id' => $view_id,
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
                'type' => 'comment_username'
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

    /* @var \Drupal\Core\Session\AccountSwitcherInterface $account_switcher */
    $account_switcher = \Drupal::service('account_switcher');

    /* @var \Drupal\Core\Render\RendererInterface $renderer */
    $renderer = \Drupal::service('renderer');

    $account_switcher->switchTo($this->adminUser);
    $executable = Views::getView($view_id);
    $build = $executable->preview();
    $this->setRawContent($renderer->renderRoot($build));
    $this->verbose($this->getRawContent());

    $this->assertLink('My comment title');
    $this->assertLink('Anonymous comment title');
    $this->assertLink($this->adminUser->label());
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
    $this->verbose($this->getRawContent());
    $this->assertLink('My comment title');
    $this->assertLink('Anonymous comment title');
  }

}
