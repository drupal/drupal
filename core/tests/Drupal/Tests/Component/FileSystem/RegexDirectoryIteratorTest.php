<?php

declare(strict_types=1);

namespace Drupal\Tests\Component\FileSystem;

use Drupal\Component\FileSystem\RegexDirectoryIterator;
use org\bovigo\vfs\vfsStream;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;

/**
 * Tests Drupal\Component\FileSystem\RegexDirectoryIterator.
 */
#[CoversClass(RegexDirectoryIterator::class)]
#[Group('FileSystem')]
class RegexDirectoryIteratorTest extends TestCase {

  /**
   * @legacy-covers ::accept
   */
  #[DataProvider('providerTestRegexDirectoryIterator')]
  public function testRegexDirectoryIterator(array $directory, $regex, array $expected): void {
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
  public static function providerTestRegexDirectoryIterator(): array {
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
          '3.txt',
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
