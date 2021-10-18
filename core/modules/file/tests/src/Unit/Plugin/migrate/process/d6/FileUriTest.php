<?php

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

  protected $migrationConfiguration = [
    'id' => 'test',
  ];

  public function testPublic() {
    $value = [
      'sites/default/files/foo.jpg',
      'sites/default/files',
      '/tmp',
      TRUE,
    ];
    $this->assertEquals('public://foo.jpg', $this->doTransform($value));
  }

  public function testPublicUnknownBasePath() {
    $value = [
      '/path/to/public/files/foo.jpg',
      'sites/default/files',
      '/tmp',
      TRUE,
    ];
    $this->assertEquals('public://path/to/public/files/foo.jpg', $this->doTransform($value));
  }

  public function testPrivate() {
    $value = [
      'sites/default/files/baz.gif',
      'sites/default/files',
      '/tmp',
      FALSE,
    ];
    $this->assertEquals('private://baz.gif', $this->doTransform($value));
  }

  public function testPrivateUnknownBasePath() {
    $value = [
      '/path/to/private/files/baz.gif',
      'sites/default/files',
      '/tmp',
      FALSE,
    ];
    $this->assertEquals('private://path/to/private/files/baz.gif', $this->doTransform($value));
  }

  public function testTemporary() {
    $value = [
      '/tmp/bar.png',
      'sites/default/files',
      '/tmp',
      TRUE,
    ];
    $this->assertEquals('temporary://bar.png', $this->doTransform($value));
  }

  protected function doTransform(array $value) {
    $executable = new MigrateExecutable($this->getMigration());
    $row = new Row();

    return (new FileUri([], 'file_uri', []))
      ->transform($value, $executable, $row, 'foo');
  }

}
