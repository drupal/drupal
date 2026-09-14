<?php

declare(strict_types=1);

namespace Drupal\FunctionalJavascriptTests\Theme;

use Drupal\Tests\block\FunctionalJavascript\BlockFilterTest;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Runs BlockFilterTest in Admin.
 *
 * @see \Drupal\Tests\block\FunctionalJavascript\BlockFilterTest.
 */
#[Group('block')]
#[RunTestsInSeparateProcesses]
class AdminBlockFilterTest extends BlockFilterTest {

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->container->get('theme_installer')->install(['default_admin']);
    $this->config('system.theme')->set('default', 'default_admin')->save();
  }

}
