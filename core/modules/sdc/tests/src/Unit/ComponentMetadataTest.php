<?php

declare(strict_types=1);

namespace Drupal\Tests\sdc\Unit;

use Drupal\sdc\Component\ComponentMetadata;
use Drupal\sdc\Exception\InvalidComponentException;
use Drupal\Tests\UnitTestCaseTest;

/**
 * Unit tests for the component metadata class.
 *
 * @coversDefaultClass \Drupal\sdc\Component\ComponentMetadata
 * @group sdc
 *
 * @internal
 */
final class ComponentMetadataTest extends UnitTestCaseTest {

  /**
   * Tests that the correct data is returned for each property.
   *
   * @dataProvider dataProviderMetadata
   */
  public function testMetadata(array $metadata_info, array $expectations) {
    $metadata = new ComponentMetadata($metadata_info, 'foo/', FALSE);
    $this->assertSame($expectations['path'], $metadata->path);
    $this->assertSame($expectations['status'], $metadata->status);
    $this->assertSame($expectations['thumbnail'], $metadata->getThumbnailPath());
    $this->assertEquals($expectations['props'], $metadata->schema);
  }

  /**
   * Tests the correct checks when enforcing schemas or not.
   *
   * @dataProvider dataProviderMetadata
   */
  public function testMetadataEnforceSchema(array $metadata_info, array $expectations, bool $missing_schema) {
    if ($missing_schema) {
      $this->expectException(InvalidComponentException::class);
      $this->expectExceptionMessage('The component "' . $metadata_info['id'] . '" does not provide schema information. Schema definitions are mandatory for components declared in modules. For components declared in themes, schema definitions are only mandatory if the "enforce_prop_schemas" key is set to "true" in the theme info file.');
      new ComponentMetadata($metadata_info, 'foo/', TRUE);
    }
    else {
      new ComponentMetadata($metadata_info, 'foo/', TRUE);
      $this->expectNotToPerformAssertions();
    }
  }

  /**
   * Data provider for the test testMetadataEnforceSchema.
   *
   * @return array[]
   *   The batches of data.
   */
  public function dataProviderMetadata(): array {
    return [
      'minimal example without schemas' => [
        [
          'path' => 'foo/bar/component-name',
          'id' => 'sdc:component-name',
          'name' => 'Component Name',
          'libraryOverrides' => ['dependencies' => ['core/drupal']],
          'group' => 'my-group',
          'description' => 'My description',
        ],
        [
          'path' => 'bar/component-name',
          'status' => 'stable',
          'thumbnail' => '',
          'props' => NULL,
        ],
        TRUE,
      ],
      'complete example with schema' => [
        [
          '$schema' => 'https://git.drupalcode.org/project/sdc/-/raw/1.x/src/metadata.schema.json',
          'id' => 'sdc:my-button',
          'machineName' => 'my-button',
          'path' => 'foo/my-other/path',
          'name' => 'Button',
          'description' => 'JavaScript enhanced button that tracks the number of times a user clicked it.',
          'libraryOverrides' => ['dependencies' => ['core/drupal']],
          'group' => 'my-group',
          'props' => [
            'type' => 'object',
            'required' => ['text'],
            'properties' => [
              'text' => [
                'type' => 'string',
                'title' => 'Title',
                'description' => 'The title for the button',
                'minLength' => 2,
                'examples' => ['Press', 'Submit now'],
              ],
              'iconType' => [
                'type' => 'string',
                'title' => 'Icon Type',
                'enum' => [
                  'power',
                  'like',
                  'external',
                ],
              ],
            ],
          ],
        ],
        [
          'path' => 'my-other/path',
          'status' => 'stable',
          'thumbnail' => '',
          'group' => 'my-group',
          'additionalProperties' => FALSE,
          'props' => [
            'type' => 'object',
            'required' => ['text'],
            'additionalProperties' => FALSE,
            'properties' => [
              'text' => [
                'type' => ['string', 'object'],
                'title' => 'Title',
                'description' => 'The title for the button',
                'minLength' => 2,
                'examples' => ['Press', 'Submit now'],
              ],
              'iconType' => [
                'type' => ['string', 'object'],
                'title' => 'Icon Type',
                'enum' => [
                  'power',
                  'like',
                  'external',
                ],
              ],
            ],
          ],
        ],
        FALSE,
      ],
    ];
  }

}
