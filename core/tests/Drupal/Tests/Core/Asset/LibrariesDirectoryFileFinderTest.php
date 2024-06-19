<?php

declare(strict_types=1);

namespace Drupal\Tests\Core\Asset;

use Drupal\Core\Asset\LibrariesDirectoryFileFinder;
use Drupal\Core\Extension\ProfileExtensionList;
use Drupal\Tests\UnitTestCase;
use org\bovigo\vfs\vfsStream;

/**
 * @coversDefaultClass \Drupal\Core\Asset\LibrariesDirectoryFileFinder
 * @group Asset
 */
class LibrariesDirectoryFileFinderTest extends UnitTestCase {

  /**
   * @covers ::find
   */
  public function testFind(): void {
    // Place a library file in all the possible locations.
    $structure = [
      'sites' => [
        'example.com' => [
          'libraries' => [
            'third_party_library' => [
              'css' => [
                'example.css' => '/*Some css*/',
              ],
            ],
          ],
        ],
      ],
      'libraries' => [
        'third_party_library' => [
          'css' => [
            'example.css' => '/*Some css*/',
          ],
        ],
      ],
      'profiles' => [
        'library_testing' => [
          'libraries' => [
            'third_party_library' => [
              'css' => [
                'example.css' => '/*Some css*/',
              ],
            ],
          ],
        ],
      ],
    ];
    vfsStream::setup('root', NULL, $structure);

    $extension_list = $this->prophesize(ProfileExtensionList::class);
    $extension_list->getPath('library_testing')->willReturn('profiles/library_testing');

    $finder = new LibrariesDirectoryFileFinder('vfs://root', 'sites/example.com', $extension_list->reveal(), 'library_testing');

    // The site specific location is the first location used.
    $path = $finder->find('third_party_library/css/example.css');
    $this->assertEquals('sites/example.com/libraries/third_party_library/css/example.css', $path);

    // After removing the site specific location the root libraries folder
    // should be used.
    unlink('vfs://root/sites/example.com/libraries/third_party_library/css/example.css');
    $path = $finder->find('third_party_library/css/example.css');
    $this->assertEquals('libraries/third_party_library/css/example.css', $path);

    // The profile's libraries is now the only remaining location.
    unlink('vfs://root/libraries/third_party_library/css/example.css');
    $path = $finder->find('third_party_library/css/example.css');
    $this->assertEquals('profiles/library_testing/libraries/third_party_library/css/example.css', $path);

    // If the libraries file cannot be found FALSE is returned.
    unlink('vfs://root/profiles/library_testing/libraries/third_party_library/css/example.css');
    $this->assertFalse($finder->find('third_party_library/css/example.css'));

    // Test finding by the directory only. As all the directories still we'll
    // find the first location.
    $path = $finder->find('third_party_library');
    $this->assertEquals('sites/example.com/libraries/third_party_library', $path);
  }

}
