<?php

declare(strict_types=1);

namespace Drupal\Tests\system\Kernel\Theme;

use Drupal\KernelTests\KernelTestBase;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Tests Twig environment.
 */
#[Group('Theme')]
#[RunTestsInSeparateProcesses]
class TwigEnvironmentTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['twig_theme_test'];

  /**
   * Tests template class loading with Twig embed.
   */
  public function testTwigEmbed(): void {
    $assert_session = $this->assertSession();
    // Test the Twig embed tag.
    $this->drupalGet('twig-theme-test/embed-tag');
    $assert_session->statusCodeEquals(200);
    $assert_session->responseContains('This line is from twig_theme_test/templates/twig-theme-test-embed-tag-embedded.html.twig');
  }

}
