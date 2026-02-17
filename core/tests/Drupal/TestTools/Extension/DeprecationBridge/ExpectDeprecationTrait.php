<?php

declare(strict_types=1);

namespace Drupal\TestTools\Extension\DeprecationBridge;

@trigger_error('\Drupal\TestTools\Extension\DeprecationBridge\ExpectDeprecationTrait is deprecated in drupal:11.4.0 and is removed from drupal:12.0.0. Use $this->expectUserDeprecationMessage() or $this->expectUserDeprecationMessageMatches() instead. See https://www.drupal.org/node/3545276', E_USER_DEPRECATED);

/**
 * A trait to include in Drupal tests to manage expected deprecations.
 *
 * This code works in coordination with DeprecationHandler.
 *
 * In the future this extension might be dropped if PHPUnit adds support for
 * ignoring a specified list of deprecations.
 *
 * @deprecated in drupal:11.4.0 and is removed from drupal:12.0.0. Use
 *   $this->expectUserDeprecationMessage() or
 *   $this->expectUserDeprecationMessageMatches() instead.
 *
 * @see https://www.drupal.org/node/3545276
 *
 * @internal
 */
trait ExpectDeprecationTrait {

  /**
   * Adds an expected deprecation.
   *
   * @param string $message
   *   The expected deprecation message.
   *
   * @deprecated in drupal:11.4.0 and is removed from drupal:12.0.0. Use
   *   $this->expectUserDeprecationMessage() or
   *   $this->expectUserDeprecationMessageMatches() instead.
   *
   * @see https://www.drupal.org/node/3545276
   */
  public function expectDeprecation(string $message): void {
    @trigger_error('expectDeprecation() is deprecated in drupal:11.4.0 and is removed from drupal:12.0.0. Use $this->expectUserDeprecationMessage() or $this->expectUserDeprecationMessageMatches() instead. See https://www.drupal.org/node/3545276', E_USER_DEPRECATED);
    $this->expectUserDeprecationMessageMatches($this->regularExpressionForFormatDescription('%A' . $message . '%A'));
  }

  private function regularExpressionForFormatDescription(string $string): string {
    $string = strtr(preg_quote($string, '/'), [
      '%%' => '%',
      '%e' => preg_quote(\DIRECTORY_SEPARATOR, '/'),
      '%s' => '[^\r\n]+',
      '%S' => '[^\r\n]*',
      '%a' => '.+?',
      '%A' => '.*?',
      '%w' => '\s*',
      '%i' => '[+-]?\d+',
      '%d' => '\d+',
      '%x' => '[0-9a-fA-F]+',
      '%f' => '[+-]?(?:\d+|(?=\.\d))(?:\.\d+)?(?:[Ee][+-]?\d+)?',
      '%c' => '.',
      '%0' => '\x00',
    ]);
    return '/^' . $string . '$/s';
  }

}
