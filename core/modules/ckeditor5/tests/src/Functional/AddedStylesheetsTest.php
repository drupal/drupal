<?php

declare(strict_types=1);

namespace Drupal\Tests\ckeditor5\Functional;

use Drupal\ckeditor5\Plugin\Editor\CKEditor5;
use Drupal\editor\Entity\Editor;
use Drupal\filter\Entity\FilterFormat;
use Drupal\Tests\BrowserTestBase;
use Drupal\user\RoleInterface;
use Drupal\user\Entity\User;
use Symfony\Component\Validator\ConstraintViolation;

/**
 * Test the ckeditor5-stylesheets theme config property.
 *
 * @group ckeditor5
 */
class AddedStylesheetsTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'node',
    'ckeditor5',
  ];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * The editor user.
   *
   * @var \Drupal\editor\Entity\Editor
   */
  protected Editor $editor;

  /**
   * The admin user.
   *
   * @var \Drupal\user\Entity\User
   */
  protected User $adminUser;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $filtered_html_format = FilterFormat::create([
      'format' => 'llama',
      'name' => 'Llama',
      'filters' => [],
      'roles' => [RoleInterface::AUTHENTICATED_ID],
    ]);
    $filtered_html_format->save();
    $this->editor = Editor::create([
      'format' => 'llama',
      'editor' => 'ckeditor5',
      'settings' => [
        'toolbar' => [
          'items' => [],
        ],
      ],
    ]);
    $this->editor->save();
    $this->assertSame([], array_map(
      function (ConstraintViolation $v) {
        return (string) $v->getMessage();
      },
      iterator_to_array(CKEditor5::validatePair($this->editor, $filtered_html_format))
    ));
    // Create node type.
    $this->drupalCreateContentType([
      'type' => 'article',
      'name' => 'Article',
    ]);

    $this->adminUser = $this->drupalCreateUser([
      'create article content',
      'use text format llama',
      'administer themes',
      'view the administration theme',
      'administer filters',
    ]);
    $this->drupalLogin($this->adminUser);
  }

  /**
   * Test the ckeditor5-stylesheets theme config.
   */
  public function testCkeditorStylesheets(): void {
    $assert_session = $this->assertSession();

    /** @var \Drupal\Core\Extension\ThemeInstallerInterface $theme_installer */
    $theme_installer = \Drupal::service('theme_installer');
    $theme_installer->install(['test_ckeditor_stylesheets_relative', 'claro']);
    $this->config('system.theme')->set('admin', 'claro')->save();
    $this->config('node.settings')->set('use_admin_theme', TRUE)->save();

    $this->drupalGet('node/add/article');
    $assert_session->responseNotContains('test_ckeditor_stylesheets_relative/css/yokotsoko.css');

    // Confirm that the missing ckeditor5-stylesheets configuration can be
    // bypassed.
    $this->drupalGet('admin/config/content/formats/manage/llama');
    $assert_session->pageTextNotContains('ckeditor_stylesheets configured without a corresponding ckeditor5-stylesheets configuration.');

    // Install a theme with ckeditor5-stylesheets configured. Do this manually
    // to confirm `library_info` cache tags are invalidated.
    $this->drupalGet('admin/appearance');
    $this->clickLink('Set Test relative CKEditor stylesheets as default theme');

    // Confirm the stylesheet added via `ckeditor5-stylesheets` is present.
    $this->drupalGet('node/add/article');
    $assert_session->responseContains('test_ckeditor_stylesheets_relative/css/yokotsoko.css');

    // Change the default theme to Stark, and confirm the stylesheet added via
    // `ckeditor5-stylesheets` is no longer present.
    $this->drupalGet('admin/appearance');
    $this->clickLink('Set Stark as default theme');
    $this->drupalGet('node/add/article');
    $assert_session->responseNotContains('test_ckeditor_stylesheets_relative/css/yokotsoko.css');
  }

}
