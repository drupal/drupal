<?php

declare(strict_types=1);

namespace Drupal\KernelTests\Core\Asset;

use Drupal\KernelTests\KernelTestBase;

/**
 * Checks the status and definition contents of deprecated libraries.
 *
 * @group Asset
 * @group legacy
 */
class DeprecatedAssetsTest extends KernelTestBase {

  /**
   * Confirms the status and definition contents of deprecated libraries.
   *
   * @param string $extension
   *   The name of the extension that registered a library.
   * @param string $name
   *   The name of a registered library to retrieve.
   * @param string $deprecation_suffix
   *   The part of the deprecation message after the extension/name.
   * @param string $expected_hashed_library_definition
   *   The expected MD5 hash of the library.
   *
   * @dataProvider deprecatedLibrariesProvider
   */
  public function testDeprecatedLibraries(string $extension, string $name, string $deprecation_suffix, string $expected_hashed_library_definition): void {
    /** @var \Drupal\Core\Asset\LibraryDiscoveryInterface $library_discovery */
    $library_discovery = $this->container->get('library.discovery');

    // DrupalCI uses a precision of 100 in certain environments which breaks
    // this test.
    ini_set('serialize_precision', -1);

    $this->expectDeprecation("The $extension/$name " . $deprecation_suffix);
    $library_definition = $library_discovery->getLibraryByName($extension, $name);
    $this->assertEquals($expected_hashed_library_definition, md5(serialize($library_definition)));
  }

  /**
   * The data provider for testDeprecatedLibraries.
   *
   * Returns an array in the form of
   * @code
   *  [
   *    (string) description => [
   *      (string) extension - The name of the extension that registered a library, usually 'core'
   *      (string) name - The name of a registered library
   *      (string) deprecation_suffix - The part of the deprecation message after the extension/name
   *      (string) expected_hashed_library_definition -  The expected MD5 hash of the library
   *    ]
   *  ]
   * @endcode
   *
   * @return array
   *   See description above.
   */
  public static function deprecatedLibrariesProvider(): array {
    return [
      'Tests deprecation of library core/js-cookie' => [
        'core',
        'js-cookie',
        'asset library is deprecated in Drupal 10.1.0 and will be removed in Drupal 11.0.0. There is no replacement. See https://www.drupal.org/node/3322720',
        '5d6a84c6143d0fa766cabdb1ff0a270d',
      ],
    ];
  }

}
