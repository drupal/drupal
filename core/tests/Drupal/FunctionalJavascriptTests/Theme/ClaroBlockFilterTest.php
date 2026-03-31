<?php

declare(strict_types=1);

namespace Drupal\FunctionalJavascriptTests\Theme;

use Drupal\Tests\block\FunctionalJavascript\BlockFilterTest;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Runs BlockFilterTest in Claro.
 *
 * @see \Drupal\Tests\block\FunctionalJavascript\BlockFilterTest.
 */
#[Group('block')]
#[RunTestsInSeparateProcesses]
class ClaroBlockFilterTest extends BlockFilterTest {

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->container->get('theme_installer')->install(['claro']);
    $this->config('system.theme')->set('default', 'claro')->save();
  }

}
