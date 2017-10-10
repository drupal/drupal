<?php

namespace Drupal\Tests\Component\FileSystem;

use Drupal\Component\FileSystem\RegexDirectoryIterator;
use org\bovigo\vfs\vfsStream;
use PHPUnit\Framework\TestCase;

/**
 * @coversDefaultClass \Drupal\Component\FileSystem\RegexDirectoryIterator
 * @group FileSystem
 */
class RegexDirectoryIteratorTest extends TestCase {

  /**
   * @covers ::accept
   * @dataProvider providerTestRegexDirectoryIterator
   */
  public function testRegexDirectoryIterator(array $directory, $regex, array $expected) {
    vfsStream::setup('root', NULL, $directory);
    $iterator = new RegexDirectoryIterator(vfsStream::url('root'), $regex);

    // Create an array of filenames to assert against.
    $file_list = array_map(function (\SplFileInfo $file) {
      return $file->getFilename();
    }, array_values(iterator_to_array($iterator)));

    $this->assertSame($expected, $file_list);
  }

  /**
   * Provider for self::testRegexDirectoryIterator().
   */
  public function providerTestRegexDirectoryIterator() {
    return [
      [
        [
          '1.yml' => '',
        ],
        '/\.yml$/',
        [
          '1.yml',
        ],
      ],
      [
        [
          '1.yml' => '',
          '2.yml' => '',
          '3.txt' => '',
        ],
        '/\.yml$/',
        [
          '1.yml',
          '2.yml',
        ],
      ],
      [
        [
          '1.yml' => '',
          '2.yml' => '',
          '3.txt' => '',
        ],
        '/\.txt/',
        [
          '3.txt',
        ],
      ],
      [
        [
          '1.yml' => '',
          // Ensure we don't recurse in directories even if that match the
          // regex.
          '2.yml' => [
            '3.yml' => '',
            '4.yml' => '',
          ],
          '3.txt' => '',
        ],
        '/\.yml$/',
        [
          '1.yml',
        ],
      ],
      [
        [
          '1.yml' => '',
          '2.yml' => '',
          '3.txt' => '',
        ],
        '/^\d/',
        [
          '1.yml',
          '2.yml',
          '3.txt'
        ],
      ],
      [
        [
          '1.yml' => '',
          '2.yml' => '',
          '3.txt' => '',
        ],
        '/^\D/',
        [],
      ],
    ];
  }

}
