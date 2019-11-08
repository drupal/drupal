<?php

namespace Drupal\Tests\system\Functional\Theme;

use Drupal\Tests\BrowserTestBase;

/**
 * Tests Twig environment.
 *
 * @group Theme
 */
class TwigEnvironmentTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['twig_theme_test'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Tests template class loading with Twig embed.
   */
  public function testTwigEmbed() {
    $assert_session = $this->assertSession();
    // Test the Twig embed tag.
    $this->drupalGet('twig-theme-test/embed-tag');
    $assert_session->statusCodeEquals(200);
    $assert_session->responseContains('This line is from twig_theme_test/templates/twig-theme-test-embed-tag-embedded.html.twig');
  }

}
