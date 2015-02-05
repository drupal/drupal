<?php

/**
 * @file
 * Contains \Drupal\views\Entity\Render\ConfigurableLanguageRenderer.
 */

namespace Drupal\views\Entity\Render;

use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\views\ResultRow;
use Drupal\views\ViewExecutable;

/**
 * Renders entities in a configured language.
 */
class ConfigurableLanguageRenderer extends RendererBase {

  /**
   * A specific language code for rendering if available.
   *
   * @var string|null
   */
  protected $langcode;

  /**
   * Constructs a renderer object.
   *
   * @param \Drupal\views\ViewExecutable $view
   *   The entity row being rendered.
   * @param \Drupal\Core\Language\LanguageManagerInterface $language_manager
   *   The language manager.
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The entity type.
   * @param string|null $langcode
   *   A specific language code to set, if available.
   */
  public function __construct(ViewExecutable $view, LanguageManagerInterface $language_manager, EntityTypeInterface $entity_type, $langcode) {
    parent::__construct($view, $language_manager, $entity_type);
    $this->langcode = $langcode;
  }

  /**
   * {@inheritdoc}
   */
  public function getLangcode(ResultRow $row) {
    return $this->langcode;
  }

}
