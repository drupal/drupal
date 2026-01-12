<?php

declare(strict_types=1);

namespace Drupal\Tests\file\Unit\Upload;

use Drupal\file\Upload\ContentDispositionFilenameParser;
use Drupal\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

/**
 * Tests the ContentDispositionFilenameParser class.
 */
#[CoversClass(ContentDispositionFilenameParser::class)]
#[Group('file')]
class ContentDispositionFilenameParserTest extends UnitTestCase {

  /**
   * Tests the parseFilename() method.
   */
  public function testParseFilenameSuccess(): void {
    $request = $this->createRequest('filename="test.txt"');
    $filename = ContentDispositionFilenameParser::parseFilename($request);
    $this->assertEquals('test.txt', $filename);
  }

  /**
   * Tests parse filename invalid.
   */
  #[DataProvider('invalidHeaderProvider')]
  public function testParseFilenameInvalid(string | bool $contentDisposition): void {
    $this->expectException(BadRequestHttpException::class);
    $this->expectExceptionMessage('No filename found in "Content-Disposition" header. A file name in the format "filename=FILENAME" must be provided.');
    $request = $this->createRequest($contentDisposition);
    ContentDispositionFilenameParser::parseFilename($request);
  }

  /**
   * Tests parse filename missing.
   */
  public function testParseFilenameMissing(): void {
    $this->expectException(BadRequestHttpException::class);
    $this->expectExceptionMessage('"Content-Disposition" header is required. A file name in the format "filename=FILENAME" must be provided.');
    $request = new Request();
    ContentDispositionFilenameParser::parseFilename($request);
  }

  /**
   * Tests parse filename extended.
   */
  public function testParseFilenameExtended(): void {
    $this->expectException(BadRequestHttpException::class);
    $this->expectExceptionMessage('The extended "filename*" format is currently not supported in the "Content-Disposition" header.');
    $request = $this->createRequest('filename*="UTF-8 \' \' example.txt"');
    ContentDispositionFilenameParser::parseFilename($request);
  }

  /**
   * A data provider for invalid headers.
   */
  public static function invalidHeaderProvider(): array {
    return [
      'multiple' => ['file; filename=""'],
      'empty' => ['filename=""'],
      'bad key' => ['not_a_filename="example.txt"'],
    ];
  }

  /**
   * Creates a request with the given content-disposition header.
   */
  protected function createRequest(string $contentDisposition): Request {
    $request = new Request();
    $request->headers->set('Content-Disposition', $contentDisposition);
    return $request;
  }

}
