<?php

declare(strict_types=1);

namespace Drupal\Tests\Core\Theme\Component;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\Theme\Component\ComponentMetadata;
use Drupal\Core\Render\Component\Exception\InvalidComponentException;
use Drupal\Tests\UnitTestCaseTest;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * Unit tests for the component metadata class.
 *
 * @coversDefaultClass \Drupal\Core\Theme\Component\ComponentMetadata
 * @group sdc
 */
class ComponentMetadataTest extends UnitTestCaseTest {

  /**
   * Tests that the correct data is returned for each property.
   */
  #[DataProvider('dataProviderMetadata')]
  public function testMetadata(array $metadata_info, array $expectations, bool $missing_schema, ?\Throwable $expectedException = NULL): void {
    if ($expectedException !== NULL) {
      $this->expectException($expectedException::class);
      $this->expectExceptionMessage($expectedException->getMessage());
    }
    $metadata = new ComponentMetadata($metadata_info, 'foo/', FALSE);
    $this->assertSame($expectations['path'], $metadata->path);
    $this->assertSame($expectations['status'], $metadata->status);
    $this->assertSame($expectations['thumbnail'], $metadata->getThumbnailPath());
    $this->assertEquals($expectations['props'], $metadata->schema);
  }

  /**
   * Tests the correct checks when enforcing schemas or not.
   */
  #[DataProvider('dataProviderMetadata')]
  public function testMetadataEnforceSchema(array $metadata_info, array $expectations, bool $missing_schema, ?\Throwable $expected_exception = NULL): void {
    if ($missing_schema) {
      $this->expectException(InvalidComponentException::class);
      $this->expectExceptionMessage('The component "' . $metadata_info['id'] . '" does not provide schema information. Schema definitions are mandatory for components declared in modules. For components declared in themes, schema definitions are only mandatory if the "enforce_prop_schemas" key is set to "true" in the theme info file.');
      new ComponentMetadata($metadata_info, 'foo/', TRUE);
    }
    else {
      if ($expected_exception !== NULL) {
        $this->expectException($expected_exception::class);
        $this->expectExceptionMessage($expected_exception->getMessage());
      }
      new ComponentMetadata($metadata_info, 'foo/', TRUE);
      if ($expected_exception === NULL) {
        $this->expectNotToPerformAssertions();
      }
    }
  }

  /**
   * Data provider for the test testMetadataEnforceSchema.
   *
   * @return array[]
   *   The batches of data.
   */
  public static function dataProviderMetadata(): array {
    return [
      'minimal example without schemas' => [
        [
          'path' => 'foo/bar/component-name',
          'id' => 'core:component-name',
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
      'complete example with schema, but no meta:enum' => [
        [
          '$schema' => 'https://git.drupalcode.org/project/drupal/-/raw/HEAD/core/assets/schemas/v1/metadata.schema.json',
          'id' => 'core:my-button',
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
      'complete example with schema, but no matching meta:enum' => [
        [
          '$schema' => 'https://git.drupalcode.org/project/drupal/-/raw/HEAD/core/assets/schemas/v1/metadata.schema.json',
          'id' => 'core:my-button',
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
                'meta:enum' => [
                  'power' => 'Power',
                  'fav' => 'Favorite',
                  'external' => 'External',
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
                'meta:enum' => [
                  'power' => 'Power',
                  'fav' => 'Favorite',
                  'external' => 'External',
                ],
              ],
            ],
          ],
        ],
        FALSE,
        new InvalidComponentException('The values for the iconType prop enum in component core:my-button must be defined in meta:enum.'),
      ],
      'complete example with schema (including meta:enum)' => [
        [
          '$schema' => 'https://git.drupalcode.org/project/drupal/-/raw/HEAD/core/assets/schemas/v1/metadata.schema.json',
          'id' => 'core:my-button',
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
                'meta:enum' => [
                  'power' => 'Power',
                  'like' => 'Like',
                  'external' => 'External',
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
                'meta:enum' => [
                  'power' => 'Power',
                  'like' => 'Like',
                  'external' => 'External',
                ],
              ],
            ],
          ],
        ],
        FALSE,
      ],
      'complete example with schema (including meta:enum and x-translation-context)' => [
        [
          '$schema' => 'https://git.drupalcode.org/project/drupal/-/raw/HEAD/core/assets/schemas/v1/metadata.schema.json',
          'id' => 'core:my-button',
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
                'meta:enum' => [
                  'power' => 'Power',
                  'like' => 'Like',
                  'external' => 'External',
                ],
                'x-translation-context' => 'Icon Type',
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
                'meta:enum' => [
                  'power' => 'Power',
                  'like' => 'Like',
                  'external' => 'External',
                ],
                'x-translation-context' => 'Icon Type',
              ],
            ],
          ],
        ],
        FALSE,
      ],
    ];
  }

  public static function dataProviderEnumOptionsMetadata(): array {
    $common_schema = [
      '$schema' => 'https://git.drupalcode.org/project/drupal/-/raw/HEAD/core/assets/schemas/v1/metadata.schema.json',
      'id' => 'core:my-button',
      'machineName' => 'my-button',
      'path' => 'foo/my-other/path',
      'name' => 'Button',
    ];
    return [
      'no meta:enum' => [$common_schema +
        [
          'props' => [
            'type' => 'object',
            'required' => ['text'],
            'properties' => [
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
        'iconType',
        [
          'power' => 'power',
          'like' => 'like',
          'external' => 'external',
        ],
        '',
      ],
      'meta:enum, with x-translation-context' => [$common_schema +
        [
          'props' => [
            'type' => 'object',
            'required' => ['text'],
            'properties' => [
              'target' => [
                'type' => 'string',
                'title' => 'Icon Type',
                'enum' => [
                  '',
                  '_blank',
                ],
                'meta:enum' => [
                  '' => 'Opens in same window',
                  '_blank' => 'Opens in new window',
                ],
                'x-translation-context' => 'Link target',
              ],
            ],
          ],
        ],
        'target',
        [
          '' => 'Opens in same window',
          '_blank' => 'Opens in new window',
        ],
        'Link target',
      ],
    ];
  }

  /**
   * @covers ::getEnumOptions
   */
  #[DataProvider('dataProviderEnumOptionsMetadata')]
  public function testGetEnumOptions(array $metadata_info, string $prop_name, array $expected_values, string $expected_context): void {
    $translation = $this->getStringTranslationStub();
    $container = new ContainerBuilder();
    $container->set('string_translation', $translation);
    \Drupal::setContainer($container);

    $component_metadata = new ComponentMetadata($metadata_info, 'foo/', TRUE);
    $options = $component_metadata->getEnumOptions($prop_name);
    $rendered_options = array_map(fn($value) => (string) $value, $options);
    $this->assertSame($expected_values, $rendered_options);
    foreach ($options as $translatable) {
      $this->assertSame($expected_context, $translatable->getOption('context'));
    }
  }

}
