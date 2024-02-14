<?php

declare(strict_types=1);

namespace Drupal\Tests\file\Kernel\Validation;

use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\file\Validation\UploadedFileValidator;
use Drupal\KernelTests\KernelTestBase;
use Symfony\Component\HttpFoundation\File\UploadedFile;

/**
 * Tests the uploaded file validator.
 *
 * @coversDefaultClass \Drupal\file\Validation\UploadedFileValidator
 * @group file
 */
class UploadedFileValidatorTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['file'];

  /**
   * The validator under test.
   *
   * @var \Drupal\file\Validation\UploadedFileValidator
   */
  protected UploadedFileValidator $validator;

  /**
   * The file name.
   *
   * @var string
   */
  protected string $filename;

  /**
   * The temporary file path.
   *
   * @var string
   */
  protected string $path;

  /**
   * The max 4 MB filesize to use for testing.
   *
   * @var int
   */
  protected int $maxSize = 4194304;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $fileSystem = $this->container->get('file_system');
    /** @var \Drupal\file\Validation\UploadedFileValidator $validator */
    $this->validator = new UploadedFileValidator(
      $this->container->get('validation.basic_recursive_validator_factory'),
    );
    $this->filename = $this->randomMachineName() . '.txt';
    $this->path = 'temporary://' . $this->filename;

    $fileSystem->saveData('foo', $this->path);
  }

  /**
   * @covers ::validate
   */
  public function testValidateSuccess(): void {
    $uploadedFile = new UploadedFile(
      path: $this->path,
      originalName: $this->filename,
      test: TRUE,
    );
    $violations = $this->validator->validate($uploadedFile);
    $this->assertCount(0, $violations);
  }

  /**
   * @covers ::validate
   * @dataProvider validateProvider
   */
  public function testValidateFail(int $errorCode, string $message): void {
    $uploadedFile = new UploadedFile(
      path: $this->path,
      originalName: $this->filename,
      error: $errorCode,
      test: TRUE,
    );
    $violations = $this->validator->validate($uploadedFile, [
      'maxSize' => $this->maxSize,
    ]);
    $this->assertCount(1, $violations);
    $violation = $violations->get(0);
    $this->assertInstanceOf(TranslatableMarkup::class, $violation->getMessage());
    $this->assertEquals(sprintf($message, $this->filename), $violation->getMessage());
    $this->assertEquals($errorCode, $violation->getCode());
  }

  /**
   * Data provider for ::testValidateFail.
   */
  public static function validateProvider(): array {
    return [
      'ini size' => [
        \UPLOAD_ERR_INI_SIZE,
        'The file %s could not be saved because it exceeds 4 MB, the maximum allowed size for uploads.',
      ],
      'form size' => [
        \UPLOAD_ERR_FORM_SIZE,
        'The file %s could not be saved because it exceeds 4 MB, the maximum allowed size for uploads.',
      ],
      'partial file' => [
        \UPLOAD_ERR_PARTIAL,
        'The file %s could not be saved because the upload did not complete.',
      ],
      'no file' => [
        \UPLOAD_ERR_NO_FILE,
        'The file %s could not be saved because the upload did not complete.',
      ],
      'default' => [
        \UPLOAD_ERR_CANT_WRITE,
        'The file %s could not be saved. An unknown error has occurred.',
      ],
    ];
  }

}
