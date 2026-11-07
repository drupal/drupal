<?php

declare(strict_types=1);

namespace Drupal\Tests\ckeditor5\Functional;

use Drupal\Tests\BrowserTestBase;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Tests installing a module that ships a CKEditor 5 editor in its config.
 *
 * The test module is installed together with the profile's modules, before
 * any theme is installed. The default theme in system.theme is already set at
 * that point, so CKEditor 5 must not expect it to be installed.
 *
 * @see https://www.drupal.org/project/drupal/issues/3403394
 *
 * @internal
 */
#[Group('ckeditor5')]
#[RunTestsInSeparateProcesses]
class ConfigInstallTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['ckeditor5_config_install_test'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Tests that the editor config is installed during site install.
   */
  public function testEditorInstalled(): void {
    $editor = \Drupal::entityTypeManager()->getStorage('editor')->load('ckeditor5_config_install_test');
    $this->assertNotNull($editor);
    $this->assertSame('ckeditor5', $editor->getEditor());
  }

}
