<?php

namespace Drupal\layout_builder\Attribute;

use Drupal\Component\Plugin\Attribute\Plugin;
use Drupal\layout_builder\SectionStorage\SectionStorageDefinition;

/**
 * Defines a SectionStorage attribute.
 *
 * Plugin Namespace: Plugin\SectionStorage
 *
 * @see \Drupal\layout_builder\SectionStorage\SectionStorageManager
 * @see plugin_api
 */
#[\Attribute(\Attribute::TARGET_CLASS)]
class SectionStorage extends Plugin {

  /**
   * Constructs a SectionStorage attribute.
   *
   * @param string $id
   *   The plugin ID.
   * @param int $weight
   *   (optional) The plugin weight.
   *   When an entity with layout is rendered, section storage plugins are
   *   checked, in order of their weight, to determine which one should be used
   *   to render the layout.
   * @param \Drupal\Component\Plugin\Context\ContextDefinitionInterface[] $context_definitions
   *   (optional) Any required context definitions.
   *   When an entity with layout is rendered, all section storage plugins which
   *   match a particular set of contexts are checked, in order of their weight,
   *   to determine which plugin should be used to render the layout.
   *   @see \Drupal\layout_builder\SectionStorage\SectionStorageManagerInterface::findByContext()
   * @param bool $handles_permission_check
   *   (optional) Indicates that this section storage handles its own
   *   permission checking. If FALSE, the 'configure any layout' permission
   *   will be required during routing access. If TRUE, Layout Builder will
   *   not enforce any access restrictions for the storage, so the section
   *   storage's implementation of access() must perform the access checking
   *   itself.
   * @param bool $allow_inline_blocks
   *   (optional) If TRUE, the 'Create content block' link will be shown as
   *   part of the choose block off-canvas dialog. If FALSE, the link will be
   *   hidden and will not be possible to add new inline blocks from the Layout
   *   Builder UI.
   * @param string|null $deriver
   *   (optional) The deriver class.
   */
  public function __construct(
    public readonly string $id,
    public readonly int $weight = 0,
    public readonly array $context_definitions = [],
    public readonly bool $handles_permission_check = FALSE,
    public readonly bool $allow_inline_blocks = TRUE,
    public readonly ?string $deriver = NULL,
  ) {}

  /**
   * {@inheritdoc}
   */
  public function get(): SectionStorageDefinition {
    return new SectionStorageDefinition([
      'id' => $this->id,
      'class' => $this->class,
      'weight' => $this->weight,
      'context_definitions' => $this->context_definitions,
      'handles_permission_check' => $this->handles_permission_check,
      'allow_inline_blocks' => $this->allow_inline_blocks,
    ]);
  }

}
