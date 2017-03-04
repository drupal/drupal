<?php

namespace Drupal\Tests\migrate\Unit\process;

use Drupal\migrate\Plugin\migrate\process\UrlEncode;
use Drupal\migrate\MigrateExecutable;
use Drupal\migrate\MigrateMessage;
use Drupal\migrate\Row;
use Drupal\Tests\migrate\Unit\MigrateTestCase;

/**
 * @coversDefaultClass \Drupal\migrate\Plugin\migrate\process\UrlEncode
 * @group file
 */
class UrlEncodeTest extends MigrateTestCase {

  /**
   * @inheritdoc
   */
  protected $migrationConfiguration = [
    'id' => 'test',
  ];

  /**
   * The data provider for testing URL encoding scenarios.
   *
   * @return array
   *   An array of URLs to test.
   */
  public function urlDataProvider() {
    return [
      'A URL with no characters requiring encoding' => ['http://example.com/normal_url.html', 'http://example.com/normal_url.html'],
      'The definitive use case - encoding spaces in URLs' => ['http://example.com/url with spaces.html', 'http://example.com/url%20with%20spaces.html'],
      'Definitive use case 2 - spaces in directories' => ['http://example.com/dir with spaces/foo.html', 'http://example.com/dir%20with%20spaces/foo.html'],
      'Local filespecs without spaces should not be transformed' => ['/tmp/normal.txt', '/tmp/normal.txt'],
      'Local filespecs with spaces should not be transformed' => ['/tmp/with spaces.txt', '/tmp/with spaces.txt'],
      'Make sure URL characters (:, ?, &) are not encoded but others are.' => ['https://example.com/?a=b@c&d=e+f%', 'https://example.com/?a%3Db%40c&d%3De%2Bf%25'],
    ];
  }

  /**
   * Cover various encoding scenarios.
   * @dataProvider urlDataProvider
   */
  public function testUrls($input, $output) {
    $this->assertEquals($output, $this->doTransform($input));
  }

  /**
   * Perform the urlencode process plugin over the given value.
   *
   * @param string $value
   *   URL to be encoded.
   *
   * @return string
   *   Encoded URL.
   */
  protected function doTransform($value) {
    $executable = new MigrateExecutable($this->getMigration(), new MigrateMessage());
    $row = new Row();

    return (new UrlEncode([], 'urlencode', []))
      ->transform($value, $executable, $row, 'foobaz');
  }

}
