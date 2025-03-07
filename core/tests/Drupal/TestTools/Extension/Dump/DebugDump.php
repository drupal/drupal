<?php

declare(strict_types=1);

namespace Drupal\TestTools\Extension\Dump;

use PHPUnit\Event\TestRunner\Finished as TestRunnerFinished;
use PHPUnit\Framework\TestCase;
use PHPUnit\Runner\Extension\Extension;
use PHPUnit\Runner\Extension\Facade;
use PHPUnit\Runner\Extension\ParameterCollection;
use PHPUnit\TextUI\Configuration\Configuration;
use Symfony\Component\VarDumper\Cloner\VarCloner;
use Symfony\Component\VarDumper\Dumper\CliDumper;

/**
 * Drupal's extension for printing dump() output results.
 *
 * @internal
 */
final class DebugDump implements Extension {

  /**
   * The path to the dump staging file.
   */
  private static string $stagingFilePath;

  /**
   * Whether colors should be used for printing.
   */
  private static bool $colors = FALSE;

  /**
   * Whether the caller of dump should be included in the report.
   */
  private static bool $printCaller = FALSE;

  /**
   * {@inheritdoc}
   */
  public function bootstrap(
    Configuration $configuration,
    Facade $facade,
    ParameterCollection $parameters,
  ): void {
    // Determine staging file path.
    self::$stagingFilePath = tempnam(sys_get_temp_dir(), 'dpd');

    // Determine color output.
    $colors = $parameters->has('colors') ? $parameters->get('colors') : FALSE;
    self::$colors = filter_var($colors, \FILTER_VALIDATE_BOOLEAN);

    // Print caller.
    $printCaller = $parameters->has('printCaller') ? $parameters->get('printCaller') : FALSE;
    self::$printCaller = filter_var($printCaller, \FILTER_VALIDATE_BOOLEAN);

    // Set the environment variable with the configuration.
    $config = json_encode([
      'stagingFilePath' => self::$stagingFilePath,
      'colors' => self::$colors,
      'printCaller' => self::$printCaller,
    ]);
    putenv('DRUPAL_PHPUNIT_DUMPER_CONFIG=' . $config);

    $facade->registerSubscriber(new TestRunnerFinishedSubscriber($this));
  }

  /**
   * Determines if the extension is enabled.
   *
   * @return bool
   *   TRUE if enabled, FALSE if disabled.
   */
  public static function isEnabled(): bool {
    return getenv('DRUPAL_PHPUNIT_DUMPER_CONFIG') !== FALSE;
  }

  /**
   * A CLI handler for \Symfony\Component\VarDumper\VarDumper.
   *
   * @param mixed $var
   *   The variable to be dumped.
   */
  public static function cliHandler(mixed $var): void {
    if (!self::isEnabled()) {
      return;
    }
    $config = (array) json_decode(getenv('DRUPAL_PHPUNIT_DUMPER_CONFIG'));

    $caller = self::getCaller();

    $cloner = new VarCloner();
    $dumper = new CliDumper();
    $dumper->setColors($config['colors']);
    $dump = [];
    $dumper->dump(
      $cloner->cloneVar($var),
      function ($line, $depth, $indent_pad) use (&$dump) {
        // A negative depth means "end of dump".
        if ($depth >= 0) {
          // Adds a two spaces indentation to the line.
          $dump[] = str_repeat($indent_pad, $depth) . $line;
        }
      }
    );

    file_put_contents(
      $config['stagingFilePath'],
      self::encodeDump($caller['test']->id(), $caller['file'], $caller['line'], $dump) . "\n",
      FILE_APPEND,
    );
  }

  /**
   * Encodes the dump for storing.
   *
   * @param string $testId
   *   The id of the test from where the dump was called.
   * @param string|null $file
   *   The path of the file from where the dump was called.
   * @param int|null $line
   *   The line number from where the dump was called.
   * @param array $dump
   *   The dump as an array of lines.
   *
   * @return string
   *   An encoded string.
   */
  private static function encodeDump(string $testId, ?string $file, ?int $line, array $dump): string {
    $data = [
      'test' => $testId,
      'file' => $file,
      'line' => $line,
      'dump' => $dump,
    ];
    $jsonData = json_encode($data);
    return base64_encode($jsonData);
  }

  /**
   * Decodes a dump retrieved from storage.
   *
   * @param string $encodedData
   *   An encoded string.
   *
   * @return array{test: string, file: string|null, line: int|null, dump: string[]}
   *   An encoded string.
   */
  private static function decodeDump(string $encodedData): array {
    $jsonData = base64_decode($encodedData);
    return (array) json_decode($jsonData);
  }

  /**
   * Returns information about the caller of dump().
   *
   * @return array{test: \PHPUnit\Framework\Event\Code\TestMethod, file: string|null, line: int|null}
   *   Caller information.
   */
  private static function getCaller(): array {
    $backtrace = debug_backtrace();

    while (!isset($backtrace[0]['function']) || $backtrace[0]['function'] !== 'dump') {
      array_shift($backtrace);
    }
    $call['file'] = $backtrace[1]['file'] ?? NULL;
    $call['line'] = $backtrace[1]['line'] ?? NULL;

    while (!isset($backtrace[0]['object']) || !($backtrace[0]['object'] instanceof TestCase)) {
      array_shift($backtrace);
    }
    $call['test'] = $backtrace[0]['object']->valueObjectForEvents();

    return $call;
  }

  /**
   * Retrieves dumps from storage.
   *
   * @return array{string, array{file: string|null, line: int|null, dump: string[]}}
   *   Caller information.
   */
  public static function getDumps(): array {
    if (!self::isEnabled()) {
      return [];
    }
    $config = (array) json_decode(getenv('DRUPAL_PHPUNIT_DUMPER_CONFIG'));
    $contents = rtrim(file_get_contents($config['stagingFilePath']));
    if (empty($contents)) {
      return [];
    }
    $encodedDumps = explode("\n", $contents);
    $dumps = [];
    foreach ($encodedDumps as $encodedDump) {
      $dump = self::decodeDump($encodedDump);
      $test = $dump['test'];
      unset($dump['test']);
      $dumps[$test][] = $dump;
    }
    return $dumps;
  }

  /**
   * Prints the dumps generated during the test.
   */
  public function testRunnerFinished(TestRunnerFinished $event): void {
    $dumps = self::getDumps();

    // Cleanup.
    unlink(self::$stagingFilePath);
    putenv('DRUPAL_PHPUNIT_DUMPER_CONFIG');

    if ($dumps === []) {
      return;
    }

    print "\n\n";

    print "dump() output\n";
    print "-------------\n\n";

    foreach ($dumps as $testId => $testDumps) {
      if (self::$printCaller) {
        print $testId . "\n";
      }
      foreach ($testDumps as $dump) {
        if (self::$printCaller) {
          print "in " . $dump['file'] . ", line " . $dump['line'] . ":\n";
        }
        foreach ($dump['dump'] as $line) {
          print $line . "\n";
        }
        if (self::$printCaller) {
          print "\n";
        }
      }
      if (self::$printCaller) {
        print "\n";
      }
    }

  }

}
