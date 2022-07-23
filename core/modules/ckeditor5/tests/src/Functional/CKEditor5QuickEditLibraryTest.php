<?php

namespace Drupal\Tests\ckeditor5\Functional;

use Drupal\ckeditor5\Plugin\Editor\CKEditor5;
use Drupal\editor\Entity\Editor;
use Drupal\filter\Entity\FilterFormat;
use Drupal\Tests\BrowserTestBase;
use Drupal\user\RoleInterface;
use Symfony\Component\Validator\ConstraintViolation;

/**
 * Tests that a Quick Edit specific library loads when Quick Edit is enabled.
 *
 * @group ckeditor5
 * @group legacy
 */
class CKEditor5QuickEditLibraryTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'node',
    'ckeditor5',
    'quickedit',
  ];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * The admin user.
   *
   * @var \Drupal\user\Entity\User
   */
  protected $adminUser;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $format = FilterFormat::create([
      'format' => 'llama',
      'name' => 'Llama',
      'filters' => [],
      'roles' => [RoleInterface::AUTHENTICATED_ID],
    ]);
    $format->save();
    $editor = Editor::create([
      'format' => 'llama',
      'editor' => 'ckeditor5',
      'settings' => [
        'toolbar' => [
          'items' => [],
        ],
      ],
    ]);
    $editor->save();
    $this->assertSame([], array_map(
      function (ConstraintViolation $v) {
        return (string) $v->getMessage();
      },
      iterator_to_array(CKEditor5::validatePair($editor, $format))
    ));
    $this->drupalCreateContentType([
      'type' => 'article',
      'name' => 'Article',
    ]);

    $this->adminUser = $this->drupalCreateUser([
      'create article content',
      'use text format llama',
    ]);
    $this->drupalLogin($this->adminUser);
  }

  /**
   * Tests that the Quick Edit workaround CSS loads when needed.
   */
  public function testQuickeditTemporaryWorkaround() {
    $assert_session = $this->assertSession();

    $this->expectDeprecation('Temporary work-around until https://www.drupal.org/project/drupal/issues/3196689 lands.');

    $this->drupalGet('node/add/article');
    $assert_session->responseContains('css/quickedit-override.css');
  }

}
