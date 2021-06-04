<?php

namespace Drupal\Tests\comment\Kernel\Views;

use Drupal\comment\Entity\Comment;
use Drupal\comment\Entity\CommentType;
use Drupal\entity_test\Entity\EntityTest;
use Drupal\language\Entity\ConfigurableLanguage;
use Drupal\Tests\views\Kernel\ViewsKernelTestBase;
use Drupal\user\Entity\Role;
use Drupal\user\Entity\User;
use Drupal\views\Views;

/**
 * Tests comment admin view filters.
 *
 * @group comment
 */
class CommentAdminViewTest extends ViewsKernelTestBase {

  /**
   * Comments.
   *
   * @var \Drupal\comment\Entity\Comment[]
   */
  protected $comments = [];

  /**
   * Admin user.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $adminUser;
  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'user',
    'comment',
    'entity_test',
    'language',
    'locale',
  ];

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

    // Create user 1 so that the user created later in the test has a different
    // user ID.
    // @todo Remove in https://www.drupal.org/node/540008.
    User::create(['uid' => 1, 'name' => 'user1'])->save();

    // Enable another language.
    ConfigurableLanguage::createFromLangcode('ur')->save();
    // Rebuild the container to update the default language container variable.
    $this->container->get('kernel')->rebuildContainer();

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
    // Created admin role.
    $admin_role = Role::create([
      'id' => 'admin',
      'permissions' => ['administer comments', 'skip comment approval'],
    ]);
    $admin_role->save();
    // Create the admin user.
    $this->adminUser = User::create([
      'name' => $this->randomMachineName(),
      'roles' => [$admin_role->id()],
    ]);
    $this->adminUser->save();
    // Create a comment type.
    CommentType::create([
      'id' => 'comment',
      'label' => 'Default comments',
      'target_entity_type_id' => 'entity_test',
      'description' => 'Default comment field',
    ])->save();
    // Create a commented entity.
    $entity = EntityTest::create();
    $entity->name->value = $this->randomMachineName();
    $entity->save();

    // Create some comments.
    $comment = Comment::create([
      'subject' => 'My comment title',
      'uid' => $this->adminUser->id(),
      'entity_type' => 'entity_test',
      'field_name' => 'comment',
      'comment_type' => 'comment',
      'status' => 1,
      'entity_id' => $entity->id(),
    ]);
    $comment->save();

    $this->comments[] = $comment;

    $comment_anonymous = Comment::create([
      'subject' => 'Anonymous comment title',
      'uid' => 0,
      'name' => 'barry',
      'mail' => 'test@example.com',
      'homepage' => 'https://example.com',
      'entity_type' => 'entity_test',
      'field_name' => 'comment',
      'comment_type' => 'comment',
      'created' => 123456,
      'status' => 1,
      'entity_id' => $entity->id(),
    ]);
    $comment_anonymous->save();
    $this->comments[] = $comment_anonymous;
  }

  /**
   * Tests comment admin view filters.
   */
  public function testFilters() {
    $this->doTestFilters('page_published');
    // Unpublish the comments to test the Unapproved comments tab.
    foreach ($this->comments as $comment) {
      $comment->setUnpublished();
      $comment->save();
    }
    $this->doTestFilters('page_unapproved');
  }

  /**
   * Tests comment admin view display.
   *
   * @param string $display_id
   *   The display ID.
   */
  protected function doTestFilters($display_id) {
    $comment = $this->comments[0];
    $comment_anonymous = $this->comments[1];
    /** @var \Drupal\Core\Session\AccountSwitcherInterface $account_switcher */
    $account_switcher = \Drupal::service('account_switcher');

    /** @var \Drupal\Core\Render\RendererInterface $renderer */
    $renderer = \Drupal::service('renderer');

    $account_switcher->switchTo($this->adminUser);
    $executable = Views::getView('comment');
    $build = $executable->preview($display_id);
    $this->setRawContent($renderer->renderRoot($build));

    // Assert the exposed filters on the admin page.
    $this->assertField('subject');
    $this->assertField('author_name');
    $this->assertField('langcode');

    $elements = $this->cssSelect('input[type="checkbox"]');
    $this->assertCount(2, $elements, 'There are two comments on the page.');
    $this->assertText($comment->label());
    $this->assertText($comment_anonymous->label());
    $executable->destroy();

    // Test the Subject filter.
    $executable->setExposedInput(['subject' => 'Anonymous']);
    $build = $executable->preview($display_id);
    $this->setRawContent($renderer->renderRoot($build));

    $elements = $this->cssSelect('input[type="checkbox"]');
    $this->assertCount(1, $elements, 'Only anonymous comment is visible.');
    $this->assertNoText($comment->label());
    $this->assertText($comment_anonymous->label());
    $executable->destroy();

    $executable->setExposedInput(['subject' => 'My comment']);
    $build = $executable->preview($display_id);
    $this->setRawContent($renderer->renderRoot($build));

    $elements = $this->cssSelect('input[type="checkbox"]');
    $this->assertCount(1, $elements, 'Only admin comment is visible.');
    $this->assertText($comment->label());
    $this->assertNoText($comment_anonymous->label());
    $executable->destroy();

    // Test the combine filter using author name.
    $executable->setExposedInput(['author_name' => 'barry']);
    $build = $executable->preview($display_id);
    $this->setRawContent($renderer->renderRoot($build));

    $elements = $this->cssSelect('input[type="checkbox"]');
    $this->assertCount(1, $elements, 'Only anonymous comment is visible.');
    $this->assertNoText($comment->label());
    $this->assertText($comment_anonymous->label());
    $executable->destroy();

    // Test the combine filter using username.
    $executable->setExposedInput(['author_name' => $this->adminUser->label()]);
    $build = $executable->preview($display_id);
    $this->setRawContent($renderer->renderRoot($build));

    $elements = $this->cssSelect('input[type="checkbox"]');
    $this->assertCount(1, $elements, 'Only admin comment is visible.');
    $this->assertText($comment->label());
    $this->assertNoText($comment_anonymous->label());
    $executable->destroy();

    // Test the language filter.
    $executable->setExposedInput(['langcode' => '***LANGUAGE_site_default***']);
    $build = $executable->preview($display_id);
    $this->setRawContent($renderer->renderRoot($build));

    $elements = $this->cssSelect('input[type="checkbox"]');
    $this->assertCount(2, $elements, 'Both comments are visible.');
    $this->assertText($comment->label());
    $this->assertText($comment_anonymous->label());
    $executable->destroy();

    // Tests comment translation filter.
    if (!$comment->hasTranslation('ur')) {
      // If we don't have the translation then create one.
      $comment_translation = $comment->addTranslation('ur', ['subject' => 'ur title']);
      $comment_translation->save();
    }
    else {
      // If we have the translation then unpublish it.
      $comment_translation = $comment->getTranslation('ur');
      $comment_translation->setUnpublished();
      $comment_translation->save();
    }
    if (!$comment_anonymous->hasTranslation('ur')) {
      // If we don't have the translation then create one.
      $comment_anonymous_translation = $comment_anonymous->addTranslation('ur', ['subject' => 'ur Anonymous title']);
      $comment_anonymous_translation->save();
    }
    else {
      // If we have the translation then unpublish it.
      $comment_anonymous_translation = $comment_anonymous->getTranslation('ur');
      $comment_anonymous_translation->setUnpublished();
      $comment_anonymous_translation->save();
    }

    $executable->setExposedInput(['langcode' => 'ur']);
    $build = $executable->preview($display_id);
    $this->setRawContent($renderer->renderRoot($build));

    $elements = $this->cssSelect('input[type="checkbox"]');
    $this->assertCount(2, $elements, 'Both comments are visible.');
    $this->assertNoText($comment->label());
    $this->assertNoText($comment_anonymous->label());
    $this->assertText($comment_translation->label());
    $this->assertText($comment_anonymous_translation->label());
    $executable->destroy();
  }

}
