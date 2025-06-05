<?php

namespace Drupal\Core\Theme\Component;

use Drupal\Core\Extension\ExtensionLifecycle;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Render\Component\Exception\InvalidComponentException;

/**
 * Component metadata.
 */
class ComponentMetadata {

  use StringTranslationTrait;

  /**
   * The ID of the component, in the form of provider:machine_name.
   */
  public readonly string $id;

  /**
   * The absolute path to the component directory.
   *
   * @var string
   */
  public readonly string $path;

  /**
   * The component documentation.
   *
   * @var string
   */
  public readonly string $documentation;

  /**
   * The status of the component.
   *
   * @var string
   */
  public readonly string $status;

  /**
   * The machine name for the component.
   *
   * @var string
   */
  public readonly string $machineName;

  /**
   * The component's name.
   *
   * @var string
   */
  public readonly string $name;

  /**
   * The PNG path for the component thumbnail.
   *
   * @var string
   */
  private string $thumbnailPath;

  /**
   * The component group.
   *
   * @var string
   */
  public readonly string $group;

  /**
   * Schema for the component props.
   *
   * @var array[]|null
   *   The schemas.
   */
  public readonly ?array $schema;

  /**
   * The component description.
   *
   * @var string
   */
  public readonly string $description;

  /**
   * TRUE if the schemas for props and slots are mandatory.
   *
   * @var bool
   */
  public readonly bool $mandatorySchemas;

  /**
   * Slot information.
   *
   * @var array
   */
  public readonly array $slots;

  /**
   * The available variants.
   */
  public readonly array $variants;

  /**
   * ComponentMetadata constructor.
   *
   * @param array $metadata_info
   *   The metadata info.
   * @param string $app_root
   *   The application root.
   * @param bool $enforce_schemas
   *   Enforces the definition of schemas for props and slots.
   *
   * @throws \Drupal\Core\Render\Component\Exception\InvalidComponentException
   */
  public function __construct(array $metadata_info, string $app_root, bool $enforce_schemas) {
    $path = $metadata_info['path'];
    // Make the absolute path, relative to the Drupal root.
    $app_root = rtrim($app_root, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
    if (str_starts_with($path, $app_root)) {
      $path = substr($path, strlen($app_root));
    }
    $this->id = $metadata_info['id'];
    $this->mandatorySchemas = $enforce_schemas;
    $this->path = $path;

    [, $machine_name] = explode(':', $metadata_info['id'] ?? []);
    $this->machineName = $machine_name;
    $this->name = $metadata_info['name'] ?? mb_convert_case($machine_name, MB_CASE_TITLE);
    $this->description = $metadata_info['description'] ?? $this->t('- Description not available -');
    $this->status = ExtensionLifecycle::isValid($metadata_info['status'] ?? '')
      ? $metadata_info['status']
      : ExtensionLifecycle::STABLE;
    $this->documentation = $metadata_info['documentation'] ?? '';

    $this->group = $metadata_info['group'] ?? $this->t('All Components');

    // Save the schemas.
    $this->schema = $this->parseSchemaInfo($metadata_info);
    $this->slots = $metadata_info['slots'] ?? [];
    $this->variants = $metadata_info['variants'] ?? [];
  }

  /**
   * Parse the schema information.
   *
   * @param array $metadata_info
   *   The metadata information as decoded from the component definition file.
   *
   * @return array|null
   *   The schema for the component props.
   *
   * @throws \Drupal\Core\Render\Component\Exception\InvalidComponentException
   */
  private function parseSchemaInfo(array $metadata_info): ?array {
    if (empty($metadata_info['props'])) {
      if ($this->mandatorySchemas) {
        throw new InvalidComponentException(sprintf('The component "%s" does not provide schema information. Schema definitions are mandatory for components declared in modules. For components declared in themes, schema definitions are only mandatory if the "enforce_prop_schemas" key is set to "true" in the theme info file.', $this->id));
      }
      $schema = NULL;
    }
    else {
      $schema = $metadata_info['props'];
      if (($schema['type'] ?? 'object') !== 'object') {
        throw new InvalidComponentException('The schema for the props in the component metadata is invalid. The schema should be of type "object".');
      }
      if ($schema['additionalProperties'] ?? FALSE) {
        throw new InvalidComponentException('The schema for the %s in the component metadata is invalid. Arbitrary additional properties are not allowed.');
      }
      $schema['additionalProperties'] = FALSE;
      // All props should also support "object" this allows deferring rendering
      // in Twig to the render pipeline.
      $schema_props = $metadata_info['props'];
      foreach ($schema_props['properties'] ?? [] as $name => $prop_schema) {
        $type = $prop_schema['type'] ?? '';
        if (isset($prop_schema['enum'], $prop_schema['meta:enum'])) {
          $enum_keys_diff = array_diff($prop_schema['enum'], array_keys($prop_schema['meta:enum']));
          if (!empty($enum_keys_diff)) {
            throw new InvalidComponentException(sprintf('The values for the %s prop enum in component %s must be defined in meta:enum.', $name, $this->id));
          }
        }
        $schema['properties'][$name]['type'] = array_unique([
          ...(array) $type,
          'object',
        ]);
      }
    }
    return $schema;
  }

  /**
   * Gets the thumbnail path.
   *
   * @return string
   *   The path.
   */
  public function getThumbnailPath(): string {
    if (!isset($this->thumbnailPath)) {
      $thumbnail_path = sprintf('%s/thumbnail.png', $this->path);
      $this->thumbnailPath = file_exists($thumbnail_path) ? $thumbnail_path : '';
    }
    return $this->thumbnailPath;
  }

  /**
   * Normalizes the value object.
   *
   * @return array
   *   The normalized value object.
   */
  public function normalize(): array {
    $meta = [];
    if (!empty($this->schema['properties'])) {
      foreach ($this->schema['properties'] as $prop_name => $prop_definition) {
        if (!empty($prop_definition['meta:enum'])) {
          $meta['properties'][$prop_name] = $this->getEnumOptions($prop_name);
        }
      }
    }
    return [
      'path' => $this->path,
      'machineName' => $this->machineName,
      'status' => $this->status,
      'name' => $this->name,
      'group' => $this->group,
      'variants' => $this->variants,
      'meta' => $meta,
    ];
  }

  /**
   * Get translated options labels from enumeration.
   *
   * @param string $propertyName
   *   The enum property name.
   *
   * @return array<string, \Drupal\Core\StringTranslation\TranslatableMarkup>
   *   An array with enum options as keys and the (non-rendered)
   *   translated labels as values.
   */
  public function getEnumOptions(string $propertyName): array {
    $options = [];
    if (isset($this->schema['properties'][$propertyName])) {
      $prop_definition = $this->schema['properties'][$propertyName];
      if (!empty($prop_definition['enum'])) {
        $translation_context = $prop_definition['x-translation-context'] ?? '';
        // We convert ['a', 'b'], into ['a' => t('a'), 'b' => t('b')].
        $options = array_combine(
          $prop_definition['enum'],
          // @phpcs:ignore Drupal.Semantics.FunctionT.NotLiteralString
          array_map(fn($value) => $this->t($value, [], ['context' => $translation_context]), $prop_definition['enum']),
        );
        if (!empty($prop_definition['meta:enum'])) {
          foreach ($prop_definition['meta:enum'] as $enum_value => $enum_label) {
            $options[$enum_value] =
              // @phpcs:ignore Drupal.Semantics.FunctionT.NotLiteralString
              $this->t($enum_label, [], ['context' => $translation_context]);
          }
        }
      }
    }
    return $options;
  }

}
