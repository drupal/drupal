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
   * Modules to install.
   *
   * Install the shortcut module so that claro.settings has its schema checked.
   * There's currently no way for Claro to provide a default and have valid
   * configuration as themes cannot react to a module install.
   *
   * @var string[]
   */
  protected static $modules = ['shortcut'];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->container->get('theme_installer')->install(['claro']);
    $this->config('system.theme')->set('default', 'claro')->save();
  }

}
