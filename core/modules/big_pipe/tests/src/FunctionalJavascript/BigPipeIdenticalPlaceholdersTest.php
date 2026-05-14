<?php

declare(strict_types=1);

namespace Drupal\Tests\big_pipe\FunctionalJavascript;

use Drupal\Core\Url;
use Drupal\FunctionalJavascriptTests\WebDriverTestBase;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Tests multiple occurrences of the same placeholder.
 */
#[Group('big_pipe')]
#[RunTestsInSeparateProcesses]
class BigPipeIdenticalPlaceholdersTest extends WebDriverTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'big_pipe',
    'big_pipe_test',
  ];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'big_pipe_test_theme';

  /**
   * Tests that all occurrences of the same placeholder are replaced.
   */
  public function testIdenticalPlaceholders(): void {
    $this->drupalLogin($this->drupalCreateUser());
    $assert_session = $this->assertSession();
    $this->drupalGet(Url::fromRoute('big_pipe_test_multi_occurrence'));
    $this->assertNotNull($assert_session->waitForElement('css', 'script[data-big-pipe-event="stop"]'));
    $assert_session->elementsCount('css', 'p.multiple-occurrence-instance', 3);
  }

}
