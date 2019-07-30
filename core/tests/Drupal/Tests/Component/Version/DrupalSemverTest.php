<?php

namespace Drupal\Tests\Component\Version;

use Drupal\Component\Version\DrupalSemver;
use PHPUnit\Framework\TestCase;

/**
 * @coversDefaultClass \Drupal\Component\Version\DrupalSemver
 * @group Version
 */
class DrupalSemverTest extends TestCase {

  /**
   * @covers ::satisfies
   * @dataProvider providerSatisfies
   */
  public function testSatisfies($version, $constraints, $result) {
    $this->assertSame($result, DrupalSemver::satisfies($version, $constraints));
  }

  public function providerSatisfies() {
    $cases = [
      ['8.8.0-dev', '8.x', TRUE],
      ['8.8.0', '8.x', TRUE],
      ['8.9.9', '8.x', TRUE],
      ['8.0.0', '8.x', TRUE],
      ['9.0.0', '8.x', FALSE],
      ['9.1.0', '8.x', FALSE],
      ['8.8.0', '~8', TRUE],
      ['8.8.0', '^8', TRUE],
      ['8.7.0', '^8.7.6', FALSE],
      ['8.7.6', '^8.7.6', TRUE],
      ['8.7.8', '^8.7.6', TRUE],
      ['9.0.0', '^8.7.6', FALSE],
      ['8.0.0', '^8 || ^9', TRUE],
      ['9.1.1', '^8 || ^9', TRUE],
      ['9.1.1', '^8.7.6 || ^9', TRUE],
      ['8.7.8', '^8.7.6 || ^9', TRUE],
      ['8.6.8', '^8.7.6 || ^9', FALSE],
      ['8.6.8', '^9', FALSE],
      ['9.1.1', '^9', TRUE],
      ['9.0.0', '9.x', TRUE],
      ['8.8.0', '9.x', FALSE],
      ['8.8.0', '7.x', FALSE],
      ['a-super-nonsense-string-will-not-throw-an-exception-but-also-will-not-work', '9.x', FALSE],
      ['a-super-nonsense-string-will-not-throw-an-exception-but-also-will-not-work', '7.x', FALSE],
      ['a-super-nonsense-string-will-not-throw-an-exception-but-also-will-not-work', '8.x', FALSE],
    ];
    $tests = [];
    foreach ($cases as $case) {
      $tests[$case[0] . ":" . $case[1]] = $case;
    }
    return $tests;
  }

}
