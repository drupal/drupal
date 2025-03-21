<?php

declare(strict_types=1);

namespace Drupal\Tests\file\Unit\Plugin\migrate\process\d6;

use Drupal\file\Plugin\migrate\process\d6\FileUri;
use Drupal\migrate\MigrateExecutable;
use Drupal\migrate\Row;
use Drupal\Tests\migrate\Unit\MigrateTestCase;

/**
 * @coversDefaultClass \Drupal\file\Plugin\migrate\process\d6\FileUri
 * @group file
 */
class FileUriTest extends MigrateTestCase {

  /**
   * The plugin configuration.
   *
   * @var array
   */
  protected $migrationConfiguration = [
    'id' => 'test',
  ];

  /**
   * Tests with a public scheme.
   */
  public function testPublic(): void {
    $value = [
      'sites/default/files/foo.jpg',
      'sites/default/files',
      '/tmp',
      TRUE,
    ];
    $this->assertEquals('public://foo.jpg', $this->doTransform($value));
  }

  /**
   * Tests with a base path that is not known.
   */
  public function testPublicUnknownBasePath(): void {
    $value = [
      '/path/to/public/files/foo.jpg',
      'sites/default/files',
      '/tmp',
      TRUE,
    ];
    $this->assertEquals('public://path/to/public/files/foo.jpg', $this->doTransform($value));
  }

  /**
   * Tests with a private scheme.
   */
  public function testPrivate(): void {
    $value = [
      'sites/default/files/baz.gif',
      'sites/default/files',
      '/tmp',
      FALSE,
    ];
    $this->assertEquals('private://baz.gif', $this->doTransform($value));
  }

  /**
   * Tests with a private base path that is not known.
   */
  public function testPrivateUnknownBasePath(): void {
    $value = [
      '/path/to/private/files/baz.gif',
      'sites/default/files',
      '/tmp',
      FALSE,
    ];
    $this->assertEquals('private://path/to/private/files/baz.gif', $this->doTransform($value));
  }

  /**
   * Tests the temporary scheme.
   */
  public function testTemporary(): void {
    $value = [
      '/tmp/bar.png',
      'sites/default/files',
      '/tmp',
      TRUE,
    ];
    $this->assertEquals('temporary://bar.png', $this->doTransform($value));
  }

  /**
   * Performs the transform process.
   */
  protected function doTransform(array $value) {
    $executable = new MigrateExecutable($this->getMigration());
    $row = new Row();

    return (new FileUri([], 'file_uri', []))
      ->transform($value, $executable, $row, 'foo');
  }

}
