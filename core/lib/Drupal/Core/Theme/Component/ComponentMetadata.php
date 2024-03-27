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
    $this->parseSchemaInfo($metadata_info);
    $this->slots = $metadata_info['slots'] ?? [];
  }

  /**
   * Parse the schema information.
   *
   * @param array $metadata_info
   *   The metadata information as decoded from the component definition file.
   *
   * @throws \Drupal\Core\Render\Component\Exception\InvalidComponentException
   */
  private function parseSchemaInfo(array $metadata_info): void {
    if (empty($metadata_info['props'])) {
      if ($this->mandatorySchemas) {
        throw new InvalidComponentException(sprintf('The component "%s" does not provide schema information. Schema definitions are mandatory for components declared in modules. For components declared in themes, schema definitions are only mandatory if the "enforce_prop_schemas" key is set to "true" in the theme info file.', $metadata_info['id']));
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
        $schema['properties'][$name]['type'] = array_unique([
          ...(array) $type,
          'object',
        ]);
      }
    }
    $this->schema = $schema;
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
    return [
      'path' => $this->path,
      'machineName' => $this->machineName,
      'status' => $this->status,
      'name' => $this->name,
      'group' => $this->group,
    ];
  }

}
