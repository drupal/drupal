<?php

namespace Drupal\sdc\Plugin;

use Drupal\Core\Plugin\PluginBase;
use Drupal\sdc\Component\ComponentMetadata;
use Drupal\sdc\Exception\InvalidComponentException;

/**
 * Simple value object that contains information about the component.
 *
 * @internal
 */
final class Component extends PluginBase {

  /**
   * The component's metadata.
   *
   * @var \Drupal\sdc\Component\ComponentMetadata
   */
  public readonly ComponentMetadata $metadata;

  /**
   * The component machine name.
   *
   * @var string
   */
  public readonly string $machineName;

  /**
   * The Twig template for the component.
   *
   * @var string
   */
  public readonly string $template;

  /**
   * The library definition to be attached with the component.
   *
   * @var array
   */
  public readonly array $library;

  /**
   * Component constructor.
   *
   * @throws \Drupal\sdc\Exception\InvalidComponentException
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    if (str_contains($plugin_id, '/')) {
      $message = sprintf('Component ID cannot contain slashes: %s', $plugin_id);
      throw new InvalidComponentException($message);
    }
    $template = $plugin_definition['template'] ?? NULL;
    if (!$template) {
      $message = sprintf(
        'Unable to find the Twig template for the component "%s".',
        $plugin_id
      );
      throw new InvalidComponentException($message);
    }
    $this->template = $template;
    $this->machineName = $plugin_definition['machineName'];
    $this->library = $plugin_definition['library'] ?? [];
    $this->metadata = new ComponentMetadata(
      $plugin_definition,
      $configuration['app_root'],
      (bool) ($configuration['enforce_schemas'] ?? FALSE)
    );
  }

  /**
   * The template path.
   *
   * @return string|null
   *   The path to the template.
   */
  public function getTemplatePath(): ?string {
    return $this->metadata->path . DIRECTORY_SEPARATOR . $this->template;
  }

  /**
   * The auto-computed library name.
   *
   * @return string
   *   The library name.
   */
  public function getLibraryName(): string {
    $library_id = $this->getPluginId();
    $library_id = str_replace(':', '--', $library_id);
    return sprintf('sdc/%s', $library_id);
  }

}
