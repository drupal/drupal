<?php

namespace Drupal\Tests\big_pipe\FunctionalJavascript;

use Drupal\FunctionalJavascriptTests\WebDriverTestBase;

/**
 * Tests placeholder preview functionality.
 *
 * @group big_pipe
 */
class BigPipePreviewTest extends WebDriverTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'big_pipe',
    'user',
    'big_pipe_bypass_js',
    'big_pipe_test',
  ];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'big_pipe_test_theme';

  /**
   * Test preview functionality within placeholders.
   */
  public function testLazyLoaderPreview() {
    $user = $this->drupalCreateUser([]);
    $display_name = $user->getDisplayName();
    $this->drupalLogin($user);

    $this->drupalGet('big_pipe_test_preview');

    // This test begins with the big_pipe_bypass_js module enabled, which blocks
    // Big Pipe's JavaScript from loading. Without that JavaScript, the
    // placeholder and previews are not replaced and we can reliably test their
    // presence.
    $this->assertSession()->elementExists('css', '#placeholder-preview-twig-container [data-big-pipe-placeholder-id] > .i-am-taking-up-space');
    $this->assertSession()->elementTextEquals('css', '#placeholder-preview-twig-container [data-big-pipe-placeholder-id] > .i-am-taking-up-space', 'LOOK AT ME I AM CONSUMING SPACE FOR LATER');
    $this->assertSession()->elementTextNotContains('css', '#placeholder-preview-twig-container', $display_name);

    $this->assertSession()->pageTextContains('There is a lamb and there is a puppy');
    $this->assertSession()->elementTextEquals('css', '#placeholder-render-array-container [data-big-pipe-placeholder-id] > #render-array-preview', 'There is a lamb and there is a puppy');
    $this->assertSession()->elementTextNotContains('css', '#placeholder-render-array-container', 'Yarhar llamas forever!');

    // Uninstall big_pipe_bypass_js.
    \Drupal::service('module_installer')->uninstall(['big_pipe_bypass_js']);
    $this->rebuildAll();
    $this->drupalGet('big_pipe_test_preview');
    $this->assertSession()->waitForElementRemoved('css', '[data-big-pipe-placeholder-id]', 20000);
    $this->assertSession()->elementTextContains('css', '#placeholder-preview-twig-container', $display_name);
    $this->assertSession()->pageTextNotContains('LOOK AT ME I AM CONSUMING SPACE FOR LATER');

    $this->assertSession()->elementTextContains('css', '#placeholder-render-array-container marquee', 'Yarhar llamas forever!');
    $this->assertSession()->pageTextNotContains('There is a lamb and there is a puppy');
  }

}
