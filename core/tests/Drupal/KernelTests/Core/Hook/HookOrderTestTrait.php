<?php

declare(strict_types=1);

namespace Drupal\KernelTests\Core\Hook;

/**
 * @group Hook
 */
trait HookOrderTestTrait {

  /**
   * Asserts that two lists of call strings are the same.
   *
   * It is meant for strings produced with __FUNCTION__ or __METHOD__.
   *
   * The assertion fails exactly when a regular ->assertSame() would fail, but
   * it provides a more useful output on failure.
   *
   * @param list<string> $expected
   *   Expected list of strings.
   * @param list<string> $actual
   *   Actual list of strings.
   * @param string $message
   *   Message to pass to ->assertSame().
   */
  protected function assertSameCallList(array $expected, array $actual, string $message = ''): void {
    // Format without the numeric array keys, but in a way that can be easily
    // copied into the test.
    $format = function (array $strings): string {
      if (!$strings) {
        return '[]';
      }
      $parts = array_map(
        static function (string $call_string) {
          if (preg_match('@^(\w+\\\\)*(\w+)::(\w+)@', $call_string, $matches)) {
            [,, $class_shortname, $method] = $matches;
            return $class_shortname . '::class . ' . var_export('::' . $method, TRUE);
          }
          return var_export($call_string, TRUE);
        },
        $strings,
      );
      return "[\n  " . implode(",\n  ", $parts) . ",\n]";
    };
    $this->assertSame(
      $format($expected),
      $format($actual),
      $message,
    );
    // Finally, assert that array keys and the full class names are really the
    // same, in a way that provides useful output on failure.
    $this->assertSame($expected, $actual, $message);
  }

}
