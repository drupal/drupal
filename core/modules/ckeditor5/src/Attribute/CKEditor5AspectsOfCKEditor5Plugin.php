<?php

declare(strict_types = 1);

namespace Drupal\ckeditor5\Attribute;

use Drupal\Component\Plugin\Attribute\Plugin;

/**
 * Defines the CKEditor5 aspect of CKEditor5 plugin.
 */
#[\Attribute(\Attribute::TARGET_CLASS)]
class CKEditor5AspectsOfCKEditor5Plugin extends Plugin {

  /**
   * Constructs a CKEditor5AspectsOfCKEditor5Plugin attribute.
   *
   * @param class-string[] $plugins
   *   The CKEditor 5 plugin classes provided. Found in the CKEditor5 global js
   *   object as {package.Class}.
   * @param array $config
   *   (optional) A keyed array of additional values for the CKEditor 5
   *   configuration.
   */
  public function __construct(
    public readonly array $plugins,
    public readonly array $config = [],
  ) {}

  /**
   * {@inheritdoc}
   */
  public function get(): array|object {
    return [
      'plugins' => $this->plugins,
      'config' => $this->config,
    ];
  }

}
