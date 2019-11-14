<?php

namespace Drupal\views\Plugin\views\field;

use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityRepositoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Field\FieldTypePluginManagerInterface;
use Drupal\Core\Field\FormatterPluginManager;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Render\RendererInterface;

/**
 * A stub class to provide backward compatibility for EntityField.
 *
 * @deprecated in drupal:8.3.0 and is removed from drupal:9.0.0.
 *   Use \Drupal\views\Plugin\views\field\EntityField instead.
 *
 * @see https://www.drupal.org/node/3089106
 */
class Field extends EntityField {

  /**
   * Field constructor.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityTypeManagerInterface $entity_type_manager, FormatterPluginManager $formatter_plugin_manager, FieldTypePluginManagerInterface $field_type_plugin_manager, LanguageManagerInterface $language_manager, RendererInterface $renderer, EntityRepositoryInterface $entity_repository = NULL, EntityFieldManagerInterface $entity_field_manager = NULL) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $entity_type_manager, $formatter_plugin_manager, $field_type_plugin_manager, $language_manager, $renderer);
    @trigger_error(__CLASS__ . ' is deprecated in drupal:8.8.0 and is removed from drupal:9.0.0. Use \Drupal\views\Plugin\views\field\EntityField instead. See https://www.drupal.org/node/3089106', E_USER_DEPRECATED);
  }

}
