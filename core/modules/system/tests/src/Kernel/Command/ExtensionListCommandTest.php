<?php

declare(strict_types=1);

namespace Drupal\Tests\system\Kernel\Command;

use Drupal\Core\Extension\Command\ExtensionListCommand;
use Drupal\KernelTests\DrupalApplicationTesterTrait;
use Drupal\KernelTests\KernelTestBase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Symfony\Component\Console\Command\Command;

/**
 * Tests the 'ex:list' command.
 */
#[Group('Console')]
#[RunTestsInSeparateProcesses]
#[CoversClass(ExtensionListCommand::class)]
class ExtensionListCommandTest extends KernelTestBase {

  use DrupalApplicationTesterTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'system',
  ];

  /**
   * Tests the 'ex:list' command.
   */
  public function testExtensionList(): void {
    $tester = $this->applicationTester();

    // Detailed testing using json output format.
    $cmd = ['command' => 'ex:list', '--format' => 'json'];
    $this->assertEquals(Command::SUCCESS, $tester->run($cmd));
    $json = json_decode($tester->getDisplay());
    $this->assertSame('Field types', $json->datetime->package);
    $this->assertSame('Datetime (datetime)', $json->datetime->display_name);
    $this->assertSame('Uninstalled', $json->datetime->status);
    $this->assertSame(\Drupal::VERSION, $json->datetime->version);
    $this->assertSame('Installed', $json->system->status);
    // Exercise the filtering options.
    $cmd = ['command' => 'ex:list', '--format' => 'json', '--type' => 'module', '--installed' => TRUE];
    $this->assertEquals(Command::SUCCESS, $tester->run($cmd));
    $json = json_decode($tester->getDisplay());
    $this->assertObjectHasProperty('system', $json);
    $this->assertObjectNotHasProperty('datetime', $json);

    $cmd = ['command' => 'ex:list', '--format' => 'json', '--no-core' => TRUE];
    $this->assertEquals(Command::SUCCESS, $tester->run($cmd));
    $json = json_decode($tester->getDisplay());
    $this->assertObjectNotHasProperty('system', $json);

    $cmd = ['command' => 'ex:list', '--format' => 'json', '--type' => 'theme'];
    $this->assertEquals(Command::SUCCESS, $tester->run($cmd));
    $json = json_decode($tester->getDisplay());
    $this->assertObjectNotHasProperty('system', $json);

    // Sanity check the human readable output.
    $this->assertEquals(Command::SUCCESS, $tester->run(['command' => 'ex:list']));
    $display = $tester->getDisplay();
    $this->assertStringContainsString('Package', $display);
    $this->assertStringContainsString('Version', $display);
    $this->assertStringContainsString('User', $display);
    $this->assertStringContainsString('Installed', $display);
    $this->assertStringContainsString('Uninstalled', $display);
    $this->assertStringContainsString(\Drupal::VERSION, $display);
  }

}
