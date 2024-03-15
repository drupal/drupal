<?php

declare(strict_types=1);

namespace Drupal\Tests\system\Functional\Theme;

use Drupal\Tests\node\Functional\NodeTestBase;

/**
 * Tests node title for a theme.
 */
abstract class NodeTitleTestBase extends NodeTestBase {

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = ['block'];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->drupalPlaceBlock('page_title_block');

    $adminUser = $this->drupalCreateUser([
      'administer themes',
      'administer nodes',
      'create article content',
      'create page content',
    ]);
    $this->drupalLogin($adminUser);
  }

  /**
   * Get the theme name.
   *
   * @return string
   *   The theme to test.
   */
  protected function getTheme(): string {
    return explode('\\', get_class($this))[2];
  }

  /**
   * Creates one node with title 0 and tests if the node title has the correct value.
   */
  public function testNodeWithTitle0(): void {
    $theme = $this->getTheme();
    if ($theme !== $this->defaultTheme) {
      $system_theme_config = $this->container->get('config.factory')
        ->getEditable('system.theme');
      $system_theme_config
        ->set('default', $theme)
        ->save();
      \Drupal::service('theme_installer')->install([$theme]);
    }

    // Create "Basic page" content with title 0.
    $settings = [
      'title' => 0,
    ];
    $node = $this->drupalCreateNode($settings);
    // Test that 0 appears as <title>.
    $this->drupalGet('node/' . $node->id());
    $this->assertSession()->titleEquals('0 | Drupal');
    // Test that 0 appears in the template <h1>.
    $this->assertSession()->elementTextEquals('xpath', '//h1', '0');
  }

}
