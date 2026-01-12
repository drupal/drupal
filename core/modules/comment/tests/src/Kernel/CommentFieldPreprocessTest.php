<?php

declare(strict_types=1);

namespace Drupal\Tests\comment\Kernel;

use Drupal\Core\Render\Markup;
use Drupal\KernelTests\KernelTestBase;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use PHPUnit\Framework\Attributes\TestWith;

/**
 * Test comment field preprocess hook.
 */
#[Group('comment')]
#[Group('field')]
#[RunTestsInSeparateProcesses]
class CommentFieldPreprocessTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['comment', 'field', 'system'];

  /**
   * Tests that CommentThemeHooks::preprocessField() injects variables.
   *
   * If the Hook attribute CommentThemeHooks::preprocessField() does not target
   * the base hook "preprocess_field", and instead targets
   * "preprocess_field__comment", then preprocessField() is not invoked when
   * the active theme suggestion for a comment field is something other than
   * "field__comment". For example, this can happen if the theme has a template
   * using the field name, and the active theme suggestion becomes
   * field__{field_name}.
   */
  #[TestWith(['comment'])]
  #[TestWith(['comment_other'])]
  public function testCommentFieldPreprocess(string $fieldName): void {
    // Set active theme to test_theme, so its field--other-comment.html.twig
    // template gets picked up.
    \Drupal::service('theme_installer')->install(['test_theme']);
    /** @var \Drupal\Core\Theme\ThemeInitializationInterface $theme_initializer */
    $theme_initializer = \Drupal::service('theme.initialization');
    /** @var \Drupal\Core\Theme\ThemeManagerInterface $theme_manager */
    $theme_manager = \Drupal::service('theme.manager');
    $theme_manager->setActiveTheme($theme_initializer->getActiveThemeByName('test_theme'));

    // Create a mock field render array for a comment field.
    $build = [
      '#theme' => "field__{$fieldName}",
      '#entity_type' => 'test',
      '#bundle' => 'test',
      '#field_name' => $fieldName,
      '#label_display' => 'hidden',
      '#field_type' => 'comment',
      '#title' => 'test',
      '#is_multiple' => FALSE,
      0 => [
        '#comment_type' => 'test',
        '#comment_display_mode' => 'default',
        'comments' => [
          '#markup' => '<p>These are the comments.</p>',
        ],
        'comment_form' => [
          '#markup' => Markup::create('<form>This is the comment form.</form>'),
        ],
      ],
    ];
    $rendered = (string) \Drupal::service('renderer')->renderInIsolation($build);
    // The "comments" and "comment_form" variables are injected by
    // CommentThemeHooks::preprocessField() and should be correctly rendered by
    // test_theme template using those variables.
    $this->assertSame("<p>These are the comments.</p><form>This is the comment form.</form>\n", $rendered);
  }

}
