<?php

declare(strict_types=1);

namespace Drupal\KernelTests\Core\Console;

use Drupal\autowire_test\TestInjection;
use Drupal\console_test\Command\ConsoleExampleCommand;
use Drupal\console_test\Command\ConsoleExampleConfigureCommand;
use Drupal\console_test\Command\ConsoleExamplePrivateCommand;
use Drupal\Core\DependencyInjection\Compiler\ConsoleCompilerPass;
use Drupal\KernelTests\KernelTestBase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Command\LazyCommand;
use Symfony\Component\Console\Tester\ApplicationTester;

/**
 * Tests integration with Symfony Console.
 */
#[Group('Console')]
#[RunTestsInSeparateProcesses]
#[CoversClass(ConsoleCompilerPass::class)]
#[CoversClass(ConsoleExampleCommand::class)]
class ConsoleTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'autowire_test',
    'console_test',
  ];

  /**
   * Tests the application wrapped by symfony/runtime.
   */
  public function testApplication(): void {
    $tester = $this->applicationTester();
    $code = $tester->run(['command' => 'example:command']);
    $this->assertEquals(Command::SUCCESS, $code, $tester->getDisplay());
    $this->assertStringContainsString('Dependency injection test: ' . TestInjection::class, $tester->getDisplay());
    $this->assertStringContainsString('Option test: No', $tester->getDisplay());
    $this->assertStringContainsString('Argument test: No', $tester->getDisplay());
    $this->assertStringContainsString('[OK] Done', $tester->getDisplay());

    // Test the command registered via configure() not via the #AsCommand attribute.
    $tester = $this->applicationTester();
    $code = $tester->run(['command' => 'example:command-configured']);
    $this->assertEquals(Command::SUCCESS, $code, $tester->getDisplay());
    $this->assertStringContainsString('Done with configured command.', $tester->getDisplay());

    // Test the example private command.
    $tester = $this->applicationTester();
    $code = $tester->run(['command' => 'example:command-private']);
    $this->assertEquals(Command::SUCCESS, $code, $tester->getDisplay());
    $this->assertStringContainsString('Done with private command.', $tester->getDisplay());
  }

  /**
   * Tests command loader has the discovered commands.
   */
  public function testCommandLoader(): void {
    /** @var \Symfony\Component\Console\CommandLoader\CommandLoaderInterface $commandLoader */
    $commandLoader = \Drupal::service('console.command_loader');
    $command = $commandLoader->get('example:command');
    $this->assertInstanceOf(LazyCommand::class, $command);
    // Ensure the command is registered as a service.
    $this->assertTrue(\Drupal::hasService(ConsoleExampleCommand::class));
    // Ensure the service definition is tagged as a 'console.command'.
    /** @var \Symfony\Component\DependencyInjection\Definition $definition */
    $definition = \Drupal::getContainer()->getDefinition(ConsoleExampleCommand::class);
    $this->assertTrue($definition->hasTag('console.command'), 'ConsoleExampleCommand should be tagged as a console.command');
    $this->assertTrue($definition->isAutowired(), 'ConsoleExampleCommand should be autowired');
  }

  /**
   * Tests command loader has the discovered commands IDs via configure().
   */
  public function testConfigureCommandIds(): void {
    $commandIds = \Drupal::getContainer()->getParameter('console.command.ids');
    $this->assertEquals([
      ConsoleExampleConfigureCommand::class,
      // A public alias is created for this private command.
      'console.command.public_alias.' . ConsoleExamplePrivateCommand::class,
    ], $commandIds);
  }

  /**
   * Tests running a console command.
   *
   * Tests that the command is registered to the container, can be run, returns
   * the correct status code, that autowiring dependencies inside the command
   * works, and verifies the expected output based on the supplied options and
   * arguments.
   */
  public function testConsoleCommand(): void {
    $tester = $this->applicationTester();
    $code = $tester->run(['command' => 'example:command', 'argument-test' => 'Foo', '--option-test' => TRUE], ['capture_stderr_separately' => TRUE]);
    $this->assertEquals(Command::SUCCESS, $code, $tester->getErrorOutput());
    $this->assertStringContainsString('Dependency injection test: ' . TestInjection::class, $tester->getErrorOutput());
    $this->assertStringContainsString('Option test: Yes', $tester->getErrorOutput());
    $this->assertStringContainsString('Argument test: Yes', $tester->getErrorOutput());
    $this->assertStringContainsString('[OK] Done', $tester->getDisplay());
  }

  /**
   * Tests running a console command defined in a subdirectory.
   */
  public function testSubdirectoryCommand(): void {
    $tester = $this->applicationTester();
    $code = $tester->run(['command' => 'example:sub:command'], ['capture_stderr_separately' => TRUE]);
    $this->assertEquals(Command::SUCCESS, $code, $tester->getErrorOutput());
    $this->assertStringContainsString('[OK] Done', $tester->getDisplay());
  }

  /**
   * Builds an ApplicationTester to invoke `vendor/bin/dr`.
   */
  private function applicationTester(array $context = []): ApplicationTester {
    $application = include __DIR__ . '/../../../../../../vendor/bin/dr';
    $application = $application($context);
    $application->setAutoExit(FALSE);
    return new ApplicationTester($application);
  }

}
