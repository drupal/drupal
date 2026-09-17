<?php

declare(strict_types=1);

namespace Drupal\Tests\default_admin\Kernel;

use Drupal\KernelTests\KernelTestBase;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Tests the update of legacy focus color preset values.
 */
#[Group('default_admin')]
#[RunTestsInSeparateProcesses]
class FocusColorPresetUpdateTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['system', 'user'];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->installConfig(['system']);
    $this->installSchema('user', ['users_data']);
    $this->container->get('theme_installer')->install(['default_admin']);
    require_once $this->root . '/core/themes/default_admin/default_admin.post_update.php';
  }

  /**
   * Tests that legacy preset values are renamed in theme and user settings.
   */
  public function testFocusColorPresetsAreRenamed(): void {
    $this->config('default_admin.settings')
      ->set('preset_focus_color', 'gin')
      ->save();
    $user_data = $this->container->get('user.data');
    $user_data->set('default_admin', 1, 'settings', [
      'preset_focus_color' => 'claro',
      'enable_dark_mode' => 'auto',
    ]);
    $user_data->set('default_admin', 2, 'preset_focus_color', 'gin');
    $user_data->set('default_admin', 3, 'settings', [
      'preset_focus_color' => 'green',
    ]);

    default_admin_post_update_rename_focus_color_presets();

    $this->assertSame('default', $this->config('default_admin.settings')->get('preset_focus_color'));
    $this->assertSame([
      'preset_focus_color' => 'legacy_green',
      'enable_dark_mode' => 'auto',
    ], $user_data->get('default_admin', 1, 'settings'));
    $this->assertSame('default', $user_data->get('default_admin', 2, 'preset_focus_color'));
    $this->assertSame([
      'preset_focus_color' => 'green',
    ], $user_data->get('default_admin', 3, 'settings'));
  }

}
