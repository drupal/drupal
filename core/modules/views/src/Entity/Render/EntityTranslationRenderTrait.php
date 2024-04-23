<?php

namespace Drupal\views\Entity\Render;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\TypedData\TranslatableInterface;
use Drupal\views\Plugin\views\PluginBase;
use Drupal\views\ResultRow;

/**
 * Trait used to instantiate the view's entity translation renderer.
 */
trait EntityTranslationRenderTrait {

  /**
   * The renderer to be used to render the entity row.
   *
   * @var \Drupal\views\Entity\Render\EntityTranslationRendererBase
   */
  protected $entityTranslationRenderer;

  /**
   * Returns the current renderer.
   *
   * @return \Drupal\views\Entity\Render\EntityTranslationRendererBase
   *   The configured renderer.
   */
  protected function getEntityTranslationRenderer() {
    if (!isset($this->entityTranslationRenderer)) {
      $view = $this->getView();
      $rendering_language = $view->display_handler->getOption('rendering_language');
      $langcode = NULL;
      $dynamic_renderers = [
        '***LANGUAGE_entity_translation***' => 'TranslationLanguageRenderer',
        '***LANGUAGE_entity_default***' => 'DefaultLanguageRenderer',
      ];
      $entity_type = $this->getEntityTypeManager()->getDefinition($this->getEntityTypeId());
      if (isset($dynamic_renderers[$rendering_language])) {
        // Dynamic language set based on result rows or instance defaults.
        $class = '\Drupal\views\Entity\Render\\' . $dynamic_renderers[$rendering_language];
        $this->entityTranslationRenderer = new $class($view, $this->getLanguageManager(), $entity_type);
      }
      else {
        if (str_contains($rendering_language, '***LANGUAGE_')) {
          $langcode = PluginBase::queryLanguageSubstitutions()[$rendering_language];
        }
        else {
          // Specific langcode set.
          $langcode = $rendering_language;
        }
        $this->entityTranslationRenderer = new ConfigurableLanguageRenderer($view, $this->getLanguageManager(), $entity_type, $langcode);
      }
    }
    return $this->entityTranslationRenderer;
  }

  /**
   * Returns the entity translation matching the configured row language.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity object the field value being processed is attached to.
   * @param \Drupal\views\ResultRow $row
   *   The result row the field value being processed belongs to.
   * @param string $relationship
   *   The relationship to be used, or 'none' by default.
   *
   * @return \Drupal\Core\Entity\EntityInterface
   *   The entity translation object for the specified row.
   */
  public function getEntityTranslationByRelationship(EntityInterface $entity, ResultRow $row, string $relationship = 'none'): EntityInterface {
    // We assume the same language should be used for all entity fields
    // belonging to a single row, even if they are attached to different entity
    // types. Below we apply language fallback to ensure a valid value is always
    // picked.
    if ($entity instanceof TranslatableInterface && count($entity->getTranslationLanguages()) > 1) {
      $langcode = $this->getEntityTranslationRenderer()->getLangcodeByRelationship($row, $relationship);
      $translation = $this->getEntityRepository()->getTranslationFromContext($entity, $langcode);
    }
    return $translation ?? $entity;
  }

  /**
   * Returns the entity type identifier.
   *
   * @return string
   *   The entity type identifier.
   */
  abstract public function getEntityTypeId();

  /**
   * Returns the language manager.
   *
   * @return \Drupal\Core\Language\LanguageManagerInterface
   *   The language manager.
   */
  abstract protected function getLanguageManager();

  /**
   * Returns the top object of a view.
   *
   * @return \Drupal\views\ViewExecutable
   *   The view object.
   */
  abstract protected function getView();

}
