<?php

/**
 * @file
 * Contains \Drupal\views\Entity\Render\EntityTranslationRenderTrait.
 */

namespace Drupal\views\Entity\Render;

use Drupal\views\Plugin\views\PluginBase;

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
      $dynamic_renderers = array(
        '***LANGUAGE_entity_translation***' => 'TranslationLanguageRenderer',
        '***LANGUAGE_entity_default***' => 'DefaultLanguageRenderer',
      );
      if (isset($dynamic_renderers[$rendering_language])) {
        // Dynamic language set based on result rows or instance defaults.
        $renderer = $dynamic_renderers[$rendering_language];
      }
      else {
        if (strpos($rendering_language, '***LANGUAGE_') !== FALSE) {
          $langcode = PluginBase::queryLanguageSubstitutions()[$rendering_language];
        }
        else {
          // Specific langcode set.
          $langcode = $rendering_language;
        }
        $renderer = 'ConfigurableLanguageRenderer';
      }
      $class = '\Drupal\views\Entity\Render\\' . $renderer;
      $entity_type = $this->getEntityManager()->getDefinition($this->getEntityTypeId());
      $this->entityTranslationRenderer = new $class($view, $this->getLanguageManager(), $entity_type, $langcode);
    }
    return $this->entityTranslationRenderer;
  }

  /**
   * Returns the entity type identifier.
   *
   * @return string
   *   The entity type identifier.
   */
  abstract public function getEntityTypeId();

  /**
   * Returns the entity manager.
   *
   * @return \Drupal\Core\Entity\EntityManagerInterface
   *   The entity manager.
   */
  abstract protected function getEntityManager();

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
