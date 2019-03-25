<?php

namespace Drupal\Tests\media_library\Kernel;

use Drupal\KernelTests\KernelTestBase;
use Drupal\media_library\MediaLibraryState;
use Drupal\Tests\media\Traits\MediaTypeCreationTrait;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

/**
 * Tests the media library state value object.
 *
 * @group media_library
 *
 * @coversDefaultClass \Drupal\media_library\MediaLibraryState
 */
class MediaLibraryStateTest extends KernelTestBase {

  use MediaTypeCreationTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'media',
    'media_library',
    'file',
    'field',
    'image',
    'system',
    'views',
    'user',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->installEntitySchema('user');
    $this->installEntitySchema('file');
    $this->installSchema('file', 'file_usage');
    $this->installSchema('system', 'sequences');
    $this->installEntitySchema('media');
    $this->installConfig([
      'field',
      'system',
      'file',
      'image',
      'media',
      'media_library',
    ]);

    // Create some media types to validate against.
    $this->createMediaType('file', ['id' => 'file']);
    $this->createMediaType('image', ['id' => 'image']);
    $this->createMediaType('video_file', ['id' => 'video']);
  }

  /**
   * Tests the media library state methods.
   */
  public function testMethods() {
    $opener_id = 'test';
    $allowed_media_type_ids = ['file', 'image'];
    $selected_media_type_id = 'image';
    $remaining_slots = 2;

    $state = MediaLibraryState::create($opener_id, $allowed_media_type_ids, $selected_media_type_id, $remaining_slots);
    $this->assertSame($opener_id, $state->getOpenerId());
    $this->assertSame($allowed_media_type_ids, $state->getAllowedTypeIds());
    $this->assertSame($selected_media_type_id, $state->getSelectedTypeId());
    $this->assertSame($remaining_slots, $state->getAvailableSlots());
    $this->assertTrue($state->hasSlotsAvailable());

    $state = MediaLibraryState::create($opener_id, $allowed_media_type_ids, $selected_media_type_id, 0);
    $this->assertFalse($state->hasSlotsAvailable());
  }

  /**
   * Tests the media library state creation.
   *
   * @param string $opener_id
   *   The opener ID.
   * @param string[] $allowed_media_type_ids
   *   The allowed media type IDs.
   * @param string $selected_type_id
   *   The selected media type ID.
   * @param int $remaining_slots
   *   The number of remaining items the user is allowed to select or add in the
   *   library.
   * @param string $exception_message
   *   The expected exception message.
   *
   * @covers ::create
   * @dataProvider providerCreate
   */
  public function testCreate($opener_id, array $allowed_media_type_ids, $selected_type_id, $remaining_slots, $exception_message = '') {
    if ($exception_message) {
      $this->setExpectedException(\InvalidArgumentException::class, $exception_message);
    }
    $state = MediaLibraryState::create($opener_id, $allowed_media_type_ids, $selected_type_id, $remaining_slots);
    $this->assertInstanceOf(MediaLibraryState::class, $state);
  }

  /**
   * Data provider for testCreate().
   *
   * @return array
   *   The data sets to test.
   */
  public function providerCreate() {
    $test_data = [];

    // Assert no exception is thrown when we add the parameters as expected.
    $test_data['valid parameters'] = [
      'test',
      ['file', 'image'],
      'image',
      2,
    ];

    // Assert an exception is thrown when the opener ID parameter is empty.
    $test_data['empty opener ID'] = [
      '',
      ['file', 'image'],
      'image',
      2,
      'The opener ID parameter is required and must be a string.',
    ];
    // Assert an exception is thrown when the opener ID parameter is not a
    // valid string.
    $test_data['integer opener ID'] = [
      1,
      ['file', 'image'],
      'image',
      2,
      'The opener ID parameter is required and must be a string.',
    ];
    $test_data['boolean opener ID'] = [
      TRUE,
      ['file', 'image'],
      'image',
      2,
      'The opener ID parameter is required and must be a string.',
    ];
    $test_data['spaces opener ID'] = [
      '   ',
      ['file', 'image'],
      'image',
      2,
      'The opener ID parameter is required and must be a string.',
    ];

    // Assert an exception is thrown when the allowed types parameter is empty.
    $test_data['empty allowed types'] = [
      'test',
      [],
      'image',
      2,
      'The allowed types parameter is required and must be an array of strings.',
    ];
    // It is not possible to assert a non-array allowed types parameter, since
    // that would throw a TypeError which is not a subclass of Exception.
    // Continue asserting an exception is thrown when the allowed types
    // parameter contains elements that are not a valid string.
    $test_data['integer in allowed types'] = [
      'test',
      [1, 'image'],
      'image',
      2,
      'The allowed types parameter is required and must be an array of strings.',
    ];
    $test_data['boolean in allowed types'] = [
      'test',
      [TRUE, 'image'],
      'image',
      2,
      'The allowed types parameter is required and must be an array of strings.',
    ];
    $test_data['spaces in allowed types'] = [
      'test',
      ['   ', 'image'],
      'image',
      2,
      'The allowed types parameter is required and must be an array of strings.',
    ];

    // Assert an exception is thrown when the selected type parameter is empty.
    $test_data['empty selected type'] = [
      'test',
      ['file', 'image'],
      '',
      2,
      'The selected type parameter is required and must be a string.',
    ];
    // Assert an exception is thrown when the selected type parameter is not a
    // valid string.
    $test_data['numeric selected type'] = [
      'test',
      ['file', 'image'],
      1,
      2,
      'The selected type parameter is required and must be a string.',
    ];
    $test_data['boolean selected type'] = [
      'test',
      ['file', 'image'],
      TRUE,
      2,
      'The selected type parameter is required and must be a string.',
    ];
    $test_data['spaces selected type'] = [
      'test',
      ['file', 'image'],
      '   ',
      2,
      'The selected type parameter is required and must be a string.',
    ];
    // Assert an exception is thrown when the selected type parameter is not in
    // the list of allowed types.
    $test_data['non-present selected type'] = [
      'test',
      ['file', 'image'],
      'video',
      2,
      'The selected type parameter must be present in the list of allowed types.',
    ];

    // Assert an exception is thrown when the remaining slots parameter is
    // empty.
    $test_data['empty remaining slots'] = [
      'test',
      ['file', 'image'],
      'image',
      '',
      'The remaining slots parameter is required and must be numeric.',
    ];
    // Assert an exception is thrown when the remaining slots parameter is
    // not numeric.
    $test_data['string remaining slots'] = [
      'test',
      ['file', 'image'],
      'image',
      'fail',
      'The remaining slots parameter is required and must be numeric.',
    ];
    $test_data['boolean remaining slots'] = [
      'test',
      ['file', 'image'],
      'image',
      TRUE,
      'The remaining slots parameter is required and must be numeric.',
    ];

    return $test_data;
  }

  /**
   * Tests the hash validation when the state is created from a request.
   *
   * @param array $query_overrides
   *   The query parameters to override.
   * @param bool $exception_expected
   *   Whether an AccessDeniedHttpException is expected or not.
   *
   * @covers ::fromRequest
   * @dataProvider providerFromRequest
   */
  public function testFromRequest(array $query_overrides, $exception_expected) {
    // Override the query parameters and verify an exception is thrown when
    // required state parameters are changed.
    $query = MediaLibraryState::create('test', ['file', 'image'], 'image', 2)->all();
    $query = array_merge($query, $query_overrides);
    if ($exception_expected) {
      $this->setExpectedException(BadRequestHttpException::class, "Invalid media library parameters specified.");
    }
    $state = MediaLibraryState::fromRequest(new Request($query));
    $this->assertInstanceOf(MediaLibraryState::class, $state);
  }

  /**
   * Data provider for testFromRequest().
   *
   * @return array
   *   The data sets to test.
   */
  public function providerFromRequest() {
    $test_data = [];

    // Assert no exception is thrown when we use valid state parameters.
    $test_data['valid parameters'] = [
      [],
      FALSE,
    ];

    // Assert no exception is thrown when we override all query parameters with
    // the same data.
    $test_data['changed with same values'] = [
      [
        'media_library_opener_id' => 'test',
        'media_library_allowed_types' => ['file', 'image'],
        'media_library_selected_type' => 'image',
        'media_library_remaining' => 2,
      ],
      FALSE,
    ];

    // Assert an exception is thrown when we change the opener ID parameter.
    $test_data['changed opener ID'] = [
      ['media_library_opener_id' => 'fail'],
      TRUE,
    ];

    // Assert an exception is thrown when we change the allowed types parameter.
    $test_data['changed allowed types'] = [
      ['media_library_allowed_types' => ['audio', 'image']],
      TRUE,
    ];

    // Assert an exception is thrown when we change the selected type parameter.
    $test_data['changed selected type'] = [
      ['media_library_selected_type' => 'file'],
      TRUE,
    ];

    // Assert an exception is thrown when we change the remaining slots
    // parameter.
    $test_data['changed remaining'] = [
      ['media_library_remaining' => 4],
      TRUE,
    ];

    // Assert an exception is thrown when we change the actual hash.
    $test_data['changed hash'] = [
      ['hash' => 'fail'],
      TRUE,
    ];

    return $test_data;
  }

}
