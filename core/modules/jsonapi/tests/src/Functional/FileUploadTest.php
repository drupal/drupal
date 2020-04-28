<?php

namespace Drupal\Tests\jsonapi\Functional;

use Drupal\Component\Render\PlainTextOutput;
use Drupal\Component\Serialization\Json;
use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\Url;
use Drupal\entity_test\Entity\EntityTest;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\file\Entity\File;
use Drupal\user\Entity\User;
use GuzzleHttp\RequestOptions;
use Psr\Http\Message\ResponseInterface;

/**
 * Tests binary data file upload route.
 *
 * @group jsonapi
 */
class FileUploadTest extends ResourceTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = ['entity_test', 'file'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   *
   * @see $entity
   */
  protected static $entityTypeId = 'entity_test';

  /**
   * {@inheritdoc}
   *
   * @see $entity
   */
  protected static $resourceTypeName = 'entity_test--entity_test';

  /**
   * The POST URI.
   *
   * @var string
   */
  protected static $postUri = '/jsonapi/entity_test/entity_test/field_rest_file_test';

  /**
   * Test file data.
   *
   * @var string
   */
  protected $testFileData = 'Hares sit on chairs, and mules sit on stools.';

  /**
   * The test field storage config.
   *
   * @var \Drupal\field\Entity\FieldStorageConfig
   */
  protected $fieldStorage;

  /**
   * The field config.
   *
   * @var \Drupal\field\Entity\FieldConfig
   */
  protected $field;

  /**
   * The parent entity.
   *
   * @var \Drupal\Core\Entity\EntityInterface
   */
  protected $entity;

  /**
   * Created file entity.
   *
   * @var \Drupal\file\Entity\File
   */
  protected $file;

  /**
   * An authenticated user.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $user;

  /**
   * The entity storage for the 'file' entity type.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $fileStorage;

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();

    $this->fileStorage = $this->container->get('entity_type.manager')
      ->getStorage('file');

    // Add a file field.
    $this->fieldStorage = FieldStorageConfig::create([
      'entity_type' => 'entity_test',
      'field_name' => 'field_rest_file_test',
      'type' => 'file',
      'settings' => [
        'uri_scheme' => 'public',
      ],
    ])
      ->setCardinality(FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED);
    $this->fieldStorage->save();

    $this->field = FieldConfig::create([
      'entity_type' => 'entity_test',
      'field_name' => 'field_rest_file_test',
      'bundle' => 'entity_test',
      'settings' => [
        'file_directory' => 'foobar',
        'file_extensions' => 'txt',
        'max_filesize' => '',
      ],
    ])
      ->setLabel('Test file field')
      ->setTranslatable(FALSE);
    $this->field->save();

    // Reload entity so that it has the new field.
    $this->entity = $this->entityStorage->loadUnchanged($this->entity->id());

    $this->rebuildAll();
  }

  /**
   * {@inheritdoc}
   *
   * @requires module irrelevant_for_this_test
   */
  public function testGetIndividual() {}

  /**
   * {@inheritdoc}
   *
   * @requires module irrelevant_for_this_test
   */
  public function testPostIndividual() {}

  /**
   * {@inheritdoc}
   *
   * @requires module irrelevant_for_this_test
   */
  public function testPatchIndividual() {}

  /**
   * {@inheritdoc}
   *
   * @requires module irrelevant_for_this_test
   */
  public function testDeleteIndividual() {}

  /**
   * {@inheritdoc}
   *
   * @requires module irrelevant_for_this_test
   */
  public function testCollection() {}

  /**
   * {@inheritdoc}
   *
   * @requires module irrelevant_for_this_test
   */
  public function testRelationships() {}

  /**
   * {@inheritdoc}
   */
  protected function createEntity() {
    // Create an entity that a file can be attached to.
    $entity_test = EntityTest::create([
      'name' => 'Llama',
      'type' => 'entity_test',
    ]);
    $entity_test->setOwnerId($this->account->id());
    $entity_test->save();

    return $entity_test;
  }

  /**
   * Tests using the file upload POST route; needs second request to "use" file.
   */
  public function testPostFileUpload() {
    $uri = Url::fromUri('base:' . static::$postUri);

    // DX: 405 when read-only mode is enabled.
    $response = $this->fileRequest($uri, $this->testFileData);
    $this->assertResourceErrorResponse(405, sprintf("JSON:API is configured to accept only read operations. Site administrators can configure this at %s.", Url::fromUri('base:/admin/config/services/jsonapi')->setAbsolute()->toString(TRUE)->getGeneratedUrl()), $uri, $response);
    $this->assertSame(['GET'], $response->getHeader('Allow'));

    $this->config('jsonapi.settings')->set('read_only', FALSE)->save(TRUE);

    // DX: 403 when unauthorized.
    $response = $this->fileRequest($uri, $this->testFileData);
    $this->assertResourceErrorResponse(403, $this->getExpectedUnauthorizedAccessMessage('POST'), $uri, $response);

    $this->setUpAuthorization('POST');

    // 404 when the field name is invalid.
    $invalid_uri = Url::fromUri('base:' . static::$postUri . '_invalid');
    $response = $this->fileRequest($invalid_uri, $this->testFileData);
    $this->assertResourceErrorResponse(404, 'Field "field_rest_file_test_invalid" does not exist.', $invalid_uri, $response);

    // This request will have the default 'application/octet-stream' content
    // type header.
    $response = $this->fileRequest($uri, $this->testFileData);
    $this->assertSame(201, $response->getStatusCode());
    $expected = $this->getExpectedDocument();
    $this->assertResponseData($expected, $response);

    // Check the actual file data.
    $this->assertSame($this->testFileData, file_get_contents('public://foobar/example.txt'));

    // Test the file again but using 'filename' in the Content-Disposition
    // header with no 'file' prefix.
    $response = $this->fileRequest($uri, $this->testFileData, ['Content-Disposition' => 'filename="example.txt"']);
    $this->assertSame(201, $response->getStatusCode());
    $expected = $this->getExpectedDocument(2, 'example_0.txt');
    $this->assertResponseData($expected, $response);

    // Check the actual file data.
    $this->assertSame($this->testFileData, file_get_contents('public://foobar/example_0.txt'));
    $this->assertTrue($this->fileStorage->loadUnchanged(1)->isTemporary());

    // Verify that we can create an entity that references the uploaded file.
    $entity_test_post_url = Url::fromRoute('jsonapi.entity_test--entity_test.collection.post');
    $request_options = [];
    $request_options[RequestOptions::HEADERS]['Content-Type'] = 'application/vnd.api+json';
    $request_options = NestedArray::mergeDeep($request_options, $this->getAuthenticationRequestOptions());

    $request_options[RequestOptions::BODY] = Json::encode($this->getPostDocument());
    $response = $this->request('POST', $entity_test_post_url, $request_options);
    $this->assertResourceResponse(201, FALSE, $response);
    $this->assertTrue($this->fileStorage->loadUnchanged(1)->isPermanent());
    $this->assertSame([
      [
        'target_id' => '1',
        'display' => NULL,
        'description' => "The most fascinating file ever!",
      ],
    ], EntityTest::load(2)->get('field_rest_file_test')->getValue());
  }

  /**
   * Tests using the 'file upload and "use" file in single request" POST route.
   */
  public function testPostFileUploadAndUseInSingleRequest() {
    // Update the test entity so it already has a file. This allows verifying
    // that this route appends files, and does not replace them.
    mkdir('public://foobar');
    file_put_contents('public://foobar/existing.txt', $this->testFileData);
    $existing_file = File::create([
      'uri' => 'public://foobar/existing.txt',
    ]);
    $existing_file->setOwnerId($this->account->id());
    $existing_file->setPermanent();
    $existing_file->save();
    $this->entity
      ->set('field_rest_file_test', ['target_id' => $existing_file->id()])
      ->save();

    $uri = Url::fromUri('base:' . '/jsonapi/entity_test/entity_test/' . $this->entity->uuid() . '/field_rest_file_test');

    // DX: 405 when read-only mode is enabled.
    $response = $this->fileRequest($uri, $this->testFileData);
    $this->assertResourceErrorResponse(405, sprintf("JSON:API is configured to accept only read operations. Site administrators can configure this at %s.", Url::fromUri('base:/admin/config/services/jsonapi')->setAbsolute()->toString(TRUE)->getGeneratedUrl()), $uri, $response);
    $this->assertSame(['GET'], $response->getHeader('Allow'));

    $this->config('jsonapi.settings')->set('read_only', FALSE)->save(TRUE);

    // DX: 403 when unauthorized.
    $response = $this->fileRequest($uri, $this->testFileData);
    $this->assertResourceErrorResponse(403, $this->getExpectedUnauthorizedAccessMessage('PATCH'), $uri, $response);

    $this->setUpAuthorization('PATCH');

    // 404 when the field name is invalid.
    $invalid_uri = Url::fromUri($uri->getUri() . '_invalid');
    $response = $this->fileRequest($invalid_uri, $this->testFileData);
    $this->assertResourceErrorResponse(404, 'Field "field_rest_file_test_invalid" does not exist.', $invalid_uri, $response);

    // This request fails despite the upload succeeding, because we're not
    // allowed to view the entity we're uploading to.
    $response = $this->fileRequest($uri, $this->testFileData);
    $this->assertResourceErrorResponse(403, $this->getExpectedUnauthorizedAccessMessage('GET'), $uri, $response, FALSE, ['4xx-response', 'http_response'], ['url.site', 'user.permissions']);

    $this->setUpAuthorization('GET');

    // Reuploading the same file will result in the file being uploaded twice
    // and referenced twice.
    $response = $this->fileRequest($uri, $this->testFileData);
    $this->assertSame(200, $response->getStatusCode());
    $expected = [
      'jsonapi' => [
        'meta' => [
          'links' => [
            'self' => ['href' => 'http://jsonapi.org/format/1.0/'],
          ],
        ],
        'version' => '1.0',
      ],
      'links' => [
        'self' => ['href' => Url::fromUri('base:/jsonapi/entity_test/entity_test/' . $this->entity->uuid() . '/field_rest_file_test')->setAbsolute(TRUE)->toString()],
      ],
      'data' => [
        0 => $this->getExpectedDocument(1, 'existing.txt', TRUE, TRUE)['data'],
        1 => $this->getExpectedDocument(2, 'example.txt', TRUE, TRUE)['data'],
        2 => $this->getExpectedDocument(3, 'example_0.txt', FALSE, TRUE)['data'],
      ],
    ];
    $this->assertResponseData($expected, $response);

    // The response document received for the POST request is identical to the
    // response document received by GETting the same URL.
    $request_options = [];
    $request_options[RequestOptions::HEADERS]['Content-Type'] = 'application/vnd.api+json';
    $request_options = NestedArray::mergeDeep($request_options, $this->getAuthenticationRequestOptions());
    $response = $this->request('GET', $uri, $request_options);
    $this->assertSame(200, $response->getStatusCode());
    $this->assertResponseData($expected, $response);

    // Check the actual file data.
    $this->assertSame($this->testFileData, file_get_contents('public://foobar/example.txt'));
    $this->assertSame($this->testFileData, file_get_contents('public://foobar/example_0.txt'));
  }

  /**
   * Returns the JSON:API POST document referencing the uploaded file.
   *
   * @return array
   *   A JSON:API request document.
   *
   * @see ::testPostFileUpload()
   * @see \Drupal\Tests\jsonapi\Functional\EntityTestTest::getPostDocument()
   */
  protected function getPostDocument() {
    return [
      'data' => [
        'type' => 'entity_test--entity_test',
        'attributes' => [
          'name' => 'Dramallama',
        ],
        'relationships' => [
          'field_rest_file_test' => [
            'data' => [
              'id' => File::load(1)->uuid(),
              'meta' => [
                'description' => 'The most fascinating file ever!',
              ],
              'type' => 'file--file',
            ],
          ],
        ],
      ],
    ];
  }

  /**
   * Tests using the file upload POST route with invalid headers.
   */
  public function testPostFileUploadInvalidHeaders() {
    $this->setUpAuthorization('POST');
    $this->config('jsonapi.settings')->set('read_only', FALSE)->save(TRUE);

    $uri = Url::fromUri('base:' . static::$postUri);

    // The wrong content type header should return a 415 code.
    $response = $this->fileRequest($uri, $this->testFileData, ['Content-Type' => 'application/vnd.api+json']);
    $this->assertSame(415, $response->getStatusCode());

    // An empty Content-Disposition header should return a 400.
    $response = $this->fileRequest($uri, $this->testFileData, ['Content-Disposition' => FALSE]);
    $this->assertResourceErrorResponse(400, '"Content-Disposition" header is required. A file name in the format "filename=FILENAME" must be provided.', $uri, $response);

    // An empty filename with a context in the Content-Disposition header should
    // return a 400.
    $response = $this->fileRequest($uri, $this->testFileData, ['Content-Disposition' => 'file; filename=""']);
    $this->assertResourceErrorResponse(400, 'No filename found in "Content-Disposition" header. A file name in the format "filename=FILENAME" must be provided.', $uri, $response);

    // An empty filename without a context in the Content-Disposition header
    // should return a 400.
    $response = $this->fileRequest($uri, $this->testFileData, ['Content-Disposition' => 'filename=""']);
    $this->assertResourceErrorResponse(400, 'No filename found in "Content-Disposition" header. A file name in the format "filename=FILENAME" must be provided.', $uri, $response);

    // An invalid key-value pair in the Content-Disposition header should return
    // a 400.
    $response = $this->fileRequest($uri, $this->testFileData, ['Content-Disposition' => 'not_a_filename="example.txt"']);
    $this->assertResourceErrorResponse(400, 'No filename found in "Content-Disposition" header. A file name in the format "filename=FILENAME" must be provided.', $uri, $response);

    // Using filename* extended format is not currently supported.
    $response = $this->fileRequest($uri, $this->testFileData, ['Content-Disposition' => 'filename*="UTF-8 \' \' example.txt"']);
    $this->assertResourceErrorResponse(400, 'The extended "filename*" format is currently not supported in the "Content-Disposition" header.', $uri, $response);
  }

  /**
   * Tests using the file upload POST route with a duplicate file name.
   *
   * A new file should be created with a suffixed name.
   */
  public function testPostFileUploadDuplicateFile() {
    $this->setUpAuthorization('POST');
    $this->config('jsonapi.settings')->set('read_only', FALSE)->save(TRUE);

    $uri = Url::fromUri('base:' . static::$postUri);

    // This request will have the default 'application/octet-stream' content
    // type header.
    $response = $this->fileRequest($uri, $this->testFileData);

    $this->assertSame(201, $response->getStatusCode());

    // Make the same request again. The file should be saved as a new file
    // entity that has the same file name but a suffixed file URI.
    $response = $this->fileRequest($uri, $this->testFileData);
    $this->assertSame(201, $response->getStatusCode());

    // Loading expected normalized data for file 2, the duplicate file.
    $expected = $this->getExpectedDocument(2, 'example_0.txt');
    $this->assertResponseData($expected, $response);

    // Check the actual file data.
    $this->assertSame($this->testFileData, file_get_contents('public://foobar/example_0.txt'));
  }

  /**
   * Tests using the file upload POST route twice, simulating a race condition.
   *
   * A validation error should occur when the filenames are not unique.
   */
  public function testPostFileUploadDuplicateFileRaceCondition() {
    $this->setUpAuthorization('POST');
    $this->config('jsonapi.settings')->set('read_only', FALSE)->save(TRUE);

    $uri = Url::fromUri('base:' . static::$postUri);

    // This request will have the default 'application/octet-stream' content
    // type header.
    $response = $this->fileRequest($uri, $this->testFileData);

    $this->assertSame(201, $response->getStatusCode());

    // Simulate a race condition where two files are uploaded at almost the same
    // time, by removing the first uploaded file from disk (leaving the entry in
    // the file_managed table) before trying to upload another file with the
    // same name.
    unlink(\Drupal::service('file_system')->realpath('public://foobar/example.txt'));

    // Make the same request again. The upload should fail validation.
    $response = $this->fileRequest($uri, $this->testFileData);
    $this->assertResourceErrorResponse(422, PlainTextOutput::renderFromHtml("Unprocessable Entity: file validation failed.\nThe file public://foobar/example.txt already exists. Enter a unique file URI."), $uri, $response);
  }

  /**
   * Tests using the file upload route with any path prefixes being stripped.
   *
   * @see https://developer.mozilla.org/en-US/docs/Web/HTTP/Headers/Content-Disposition#Directives
   */
  public function testFileUploadStrippedFilePath() {
    $this->setUpAuthorization('POST');
    $this->config('jsonapi.settings')->set('read_only', FALSE)->save(TRUE);

    $uri = Url::fromUri('base:' . static::$postUri);

    $response = $this->fileRequest($uri, $this->testFileData, ['Content-Disposition' => 'file; filename="directory/example.txt"']);
    $this->assertSame(201, $response->getStatusCode());
    $expected = $this->getExpectedDocument();
    $this->assertResponseData($expected, $response);

    // Check the actual file data. It should have been written to the configured
    // directory, not /foobar/directory/example.txt.
    $this->assertSame($this->testFileData, file_get_contents('public://foobar/example.txt'));

    $response = $this->fileRequest($uri, $this->testFileData, ['Content-Disposition' => 'file; filename="../../example_2.txt"']);
    $this->assertSame(201, $response->getStatusCode());
    $expected = $this->getExpectedDocument(2, 'example_2.txt', TRUE);
    $this->assertResponseData($expected, $response);

    // Check the actual file data. It should have been written to the configured
    // directory, not /foobar/directory/example.txt.
    $this->assertSame($this->testFileData, file_get_contents('public://foobar/example_2.txt'));
    $this->assertFileNotExists('../../example_2.txt');

    // Check a path from the root. Extensions have to be empty to allow a file
    // with no extension to pass validation.
    $this->field->setSetting('file_extensions', '')
      ->save();
    $this->rebuildAll();

    $response = $this->fileRequest($uri, $this->testFileData, ['Content-Disposition' => 'file; filename="/etc/passwd"']);
    $this->assertSame(201, $response->getStatusCode());
    $expected = $this->getExpectedDocument(3, 'passwd', TRUE);
    // This mime will be guessed as there is no extension.
    $expected['data']['attributes']['filemime'] = 'application/octet-stream';
    $this->assertResponseData($expected, $response);

    // Check the actual file data. It should have been written to the configured
    // directory, not /foobar/directory/example.txt.
    $this->assertSame($this->testFileData, file_get_contents('public://foobar/passwd'));
  }

  /**
   * Tests using the file upload route with a unicode file name.
   */
  public function testFileUploadUnicodeFilename() {
    $this->setUpAuthorization('POST');
    $this->config('jsonapi.settings')->set('read_only', FALSE)->save(TRUE);

    $uri = Url::fromUri('base:' . static::$postUri);

    // It is important that the filename starts with a unicode character. See
    // https://bugs.php.net/bug.php?id=77239.
    $response = $this->fileRequest($uri, $this->testFileData, ['Content-Disposition' => 'file; filename="Èxample-✓.txt"']);
    $this->assertSame(201, $response->getStatusCode());
    $expected = $this->getExpectedDocument(1, 'Èxample-✓.txt', TRUE);
    $this->assertResponseData($expected, $response);
    $this->assertSame($this->testFileData, file_get_contents('public://foobar/Èxample-✓.txt'));
  }

  /**
   * Tests using the file upload route with a zero byte file.
   */
  public function testFileUploadZeroByteFile() {
    $this->setUpAuthorization('POST');
    $this->config('jsonapi.settings')->set('read_only', FALSE)->save(TRUE);

    $uri = Url::fromUri('base:' . static::$postUri);

    // Test with a zero byte file.
    $response = $this->fileRequest($uri, NULL);
    $this->assertSame(201, $response->getStatusCode());
    $expected = $this->getExpectedDocument();
    // Modify the default expected data to account for the 0 byte file.
    $expected['data']['attributes']['filesize'] = 0;
    $this->assertResponseData($expected, $response);

    // Check the actual file data.
    $this->assertSame('', file_get_contents('public://foobar/example.txt'));
  }

  /**
   * Tests using the file upload route with an invalid file type.
   */
  public function testFileUploadInvalidFileType() {
    $this->setUpAuthorization('POST');
    $this->config('jsonapi.settings')->set('read_only', FALSE)->save(TRUE);

    $uri = Url::fromUri('base:' . static::$postUri);

    // Test with a JSON file.
    $response = $this->fileRequest($uri, '{"test":123}', ['Content-Disposition' => 'filename="example.json"']);
    $this->assertResourceErrorResponse(422, PlainTextOutput::renderFromHtml("Unprocessable Entity: file validation failed.\nOnly files with the following extensions are allowed: <em class=\"placeholder\">txt</em>."), $uri, $response);

    // Make sure that no file was saved.
    $this->assertEmpty(File::load(1));
    $this->assertFileNotExists('public://foobar/example.txt');
  }

  /**
   * Tests using the file upload route with a file size larger than allowed.
   */
  public function testFileUploadLargerFileSize() {
    // Set a limit of 50 bytes.
    $this->field->setSetting('max_filesize', 50)
      ->save();
    $this->rebuildAll();

    $this->setUpAuthorization('POST');
    $this->config('jsonapi.settings')->set('read_only', FALSE)->save(TRUE);

    $uri = Url::fromUri('base:' . static::$postUri);

    // Generate a string larger than the 50 byte limit set.
    $response = $this->fileRequest($uri, $this->randomString(100));
    $this->assertResourceErrorResponse(422, PlainTextOutput::renderFromHtml("Unprocessable Entity: file validation failed.\nThe file is <em class=\"placeholder\">100 bytes</em> exceeding the maximum file size of <em class=\"placeholder\">50 bytes</em>."), $uri, $response);

    // Make sure that no file was saved.
    $this->assertEmpty(File::load(1));
    $this->assertFileNotExists('public://foobar/example.txt');
  }

  /**
   * Tests using the file upload POST route with malicious extensions.
   */
  public function testFileUploadMaliciousExtension() {
    // Allow all file uploads but system.file::allow_insecure_uploads is set to
    // FALSE.
    $this->field->setSetting('file_extensions', '')->save();
    $this->rebuildAll();

    $this->setUpAuthorization('POST');
    $this->config('jsonapi.settings')->set('read_only', FALSE)->save(TRUE);

    $uri = Url::fromUri('base:' . static::$postUri);

    $php_string = '<?php print "Drupal"; ?>';

    // Test using a masked exploit file.
    $response = $this->fileRequest($uri, $php_string, ['Content-Disposition' => 'filename="example.php"']);
    // The filename is not munged because .txt is added and it is a known
    // extension to apache.
    $expected = $this->getExpectedDocument(1, 'example.php.txt', TRUE);
    // Override the expected filesize.
    $expected['data']['attributes']['filesize'] = strlen($php_string);
    $this->assertResponseData($expected, $response);
    $this->assertFileExists('public://foobar/example.php.txt');

    // Add php as an allowed format. Allow insecure uploads still being FALSE
    // should still not allow this. So it should still have a .txt extension
    // appended even though it is not in the list of allowed extensions.
    $this->field->setSetting('file_extensions', 'php')
      ->save();
    $this->rebuildAll();

    $response = $this->fileRequest($uri, $php_string, ['Content-Disposition' => 'filename="example_2.php"']);
    $expected = $this->getExpectedDocument(2, 'example_2.php.txt', TRUE);
    // Override the expected filesize.
    $expected['data']['attributes']['filesize'] = strlen($php_string);
    $this->assertResponseData($expected, $response);
    $this->assertFileExists('public://foobar/example_2.php.txt');
    $this->assertFileNotExists('public://foobar/example_2.php');

    // Allow .doc file uploads and ensure even a mis-configured apache will not
    // fallback to php because the filename will be munged.
    $this->field->setSetting('file_extensions', 'doc')->save();
    $this->rebuildAll();

    // Test using a masked exploit file.
    $response = $this->fileRequest($uri, $php_string, ['Content-Disposition' => 'filename="example_3.php.doc"']);
    // The filename is munged.
    $expected = $this->getExpectedDocument(3, 'example_3.php_.doc', TRUE);
    // Override the expected filesize.
    $expected['data']['attributes']['filesize'] = strlen($php_string);
    // The file mime should be 'application/msword'.
    $expected['data']['attributes']['filemime'] = 'application/msword';
    $this->assertResponseData($expected, $response);
    $this->assertFileExists('public://foobar/example_3.php_.doc');
    $this->assertFileNotExists('public://foobar/example_3.php.doc');

    // Now allow insecure uploads.
    \Drupal::configFactory()
      ->getEditable('system.file')
      ->set('allow_insecure_uploads', TRUE)
      ->save();
    // Allow all file uploads. This is very insecure.
    $this->field->setSetting('file_extensions', '')->save();
    $this->rebuildAll();

    $response = $this->fileRequest($uri, $php_string, ['Content-Disposition' => 'filename="example_4.php"']);
    $expected = $this->getExpectedDocument(4, 'example_4.php', TRUE);
    // Override the expected filesize.
    $expected['data']['attributes']['filesize'] = strlen($php_string);
    // The file mime should also now be PHP.
    $expected['data']['attributes']['filemime'] = 'application/x-httpd-php';
    $this->assertResponseData($expected, $response);
    $this->assertFileExists('public://foobar/example_4.php');
  }

  /**
   * Tests using the file upload POST route no extension configured.
   */
  public function testFileUploadNoExtensionSetting() {
    $this->setUpAuthorization('POST');
    $this->config('jsonapi.settings')->set('read_only', FALSE)->save(TRUE);

    $uri = Url::fromUri('base:' . static::$postUri);

    $this->field->setSetting('file_extensions', '')
      ->save();
    $this->rebuildAll();

    $response = $this->fileRequest($uri, $this->testFileData, ['Content-Disposition' => 'filename="example.txt"']);
    $expected = $this->getExpectedDocument(1, 'example.txt', TRUE);

    $this->assertResponseData($expected, $response);
    $this->assertFileExists('public://foobar/example.txt');
  }

  /**
   * {@inheritdoc}
   */
  protected function getExpectedUnauthorizedAccessMessage($method) {
    switch ($method) {
      case 'GET':
        return "The current user is not allowed to view this relationship. The 'view test entity' permission is required.";

      case 'POST':
        return "The current user is not permitted to upload a file for this field. The following permissions are required: 'administer entity_test content' OR 'administer entity_test_with_bundle content' OR 'create entity_test entity_test_with_bundle entities'.";

      case 'PATCH':
        return "The current user is not permitted to upload a file for this field. The 'administer entity_test content' permission is required.";
    }
  }

  /**
   * Returns the expected JSON:API document for the expected file entity.
   *
   * @param int $fid
   *   The file ID to load and create a JSON:API document for.
   * @param string $expected_filename
   *   The expected filename for the stored file.
   * @param bool $expected_as_filename
   *   Whether the expected filename should be the filename property too.
   * @param bool $expected_status
   *   The expected file status. Defaults to FALSE.
   *
   * @return array
   *   A JSON:API response document.
   */
  protected function getExpectedDocument($fid = 1, $expected_filename = 'example.txt', $expected_as_filename = FALSE, $expected_status = FALSE) {
    $author = User::load($this->account->id());
    $file = File::load($fid);
    $self_url = Url::fromUri('base:/jsonapi/file/file/' . $file->uuid())->setAbsolute()->toString(TRUE)->getGeneratedUrl();

    return [
      'jsonapi' => [
        'meta' => [
          'links' => [
            'self' => ['href' => 'http://jsonapi.org/format/1.0/'],
          ],
        ],
        'version' => '1.0',
      ],
      'links' => [
        'self' => ['href' => $self_url],
      ],
      'data' => [
        'id' => $file->uuid(),
        'type' => 'file--file',
        'links' => [
          'self' => ['href' => $self_url],
        ],
        'attributes' => [
          'created' => (new \DateTime())->setTimestamp($file->getCreatedTime())->setTimezone(new \DateTimeZone('UTC'))->format(\DateTime::RFC3339),
          'changed' => (new \DateTime())->setTimestamp($file->getChangedTime())->setTimezone(new \DateTimeZone('UTC'))->format(\DateTime::RFC3339),
          'filemime' => 'text/plain',
          'filename' => $expected_as_filename ? $expected_filename : 'example.txt',
          'filesize' => strlen($this->testFileData),
          'langcode' => 'en',
          'status' => $expected_status,
          'uri' => [
            'value' => 'public://foobar/' . $expected_filename,
            'url' => base_path() . $this->siteDirectory . '/files/foobar/' . rawurlencode($expected_filename),
          ],
          'drupal_internal__fid' => (int) $file->id(),
        ],
        'relationships' => [
          'uid' => [
            'data' => [
              'id' => $author->uuid(),
              'type' => 'user--user',
            ],
            'links' => [
              'related' => ['href' => $self_url . '/uid'],
              'self' => ['href' => $self_url . '/relationships/uid'],
            ],
          ],
        ],
      ],
    ];
  }

  /**
   * Performs a file upload request. Wraps the Guzzle HTTP client.
   *
   * @param \Drupal\Core\Url $url
   *   URL to request.
   * @param string $file_contents
   *   The file contents to send as the request body.
   * @param array $headers
   *   Additional headers to send with the request. Defaults will be added for
   *   Content-Type and Content-Disposition. In order to remove the defaults set
   *   the header value to FALSE.
   *
   * @return \Psr\Http\Message\ResponseInterface
   *   The received response.
   *
   * @see \GuzzleHttp\ClientInterface::request()
   */
  protected function fileRequest(Url $url, $file_contents, array $headers = []) {
    $request_options = [];
    $headers = $headers + [
      // Set the required (and only accepted) content type for the request.
      'Content-Type' => 'application/octet-stream',
      // Set the required Content-Disposition header for the file name.
      'Content-Disposition' => 'file; filename="example.txt"',
      // Set the required JSON:API Accept header.
      'Accept' => 'application/vnd.api+json',
    ];
    $request_options[RequestOptions::HEADERS] = array_filter($headers, function ($value) {
      return $value !== FALSE;
    });
    $request_options[RequestOptions::BODY] = $file_contents;
    $request_options = NestedArray::mergeDeep($request_options, $this->getAuthenticationRequestOptions());

    return $this->request('POST', $url, $request_options);
  }

  /**
   * {@inheritdoc}
   */
  protected function setUpAuthorization($method) {
    switch ($method) {
      case 'GET':
        $this->grantPermissionsToTestedRole(['view test entity']);
        break;

      case 'POST':
        $this->grantPermissionsToTestedRole(['create entity_test entity_test_with_bundle entities', 'access content']);
        break;

      case 'PATCH':
        $this->grantPermissionsToTestedRole(['administer entity_test content', 'access content']);
        break;
    }
  }

  /**
   * Asserts expected normalized data matches response data.
   *
   * @param array $expected
   *   The expected data.
   * @param \Psr\Http\Message\ResponseInterface $response
   *   The file upload response.
   */
  protected function assertResponseData(array $expected, ResponseInterface $response) {
    static::recursiveKSort($expected);
    $actual = Json::decode((string) $response->getBody());
    static::recursiveKSort($actual);

    $this->assertSame($expected, $actual);
  }

  /**
   * {@inheritdoc}
   */
  protected function getExpectedUnauthorizedAccessCacheability() {
    // There is cacheability metadata to check as file uploads only allows POST
    // requests, which will not return cacheable responses.
  }

}
