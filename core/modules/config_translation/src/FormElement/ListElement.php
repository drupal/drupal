<?php

namespace Drupal\config_translation\FormElement;

use Drupal\Core\Config\Config;
use Drupal\Core\Language\LanguageInterface;
use Drupal\config_translation\Form\ConfigTranslationFormBase;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\TypedData\DataDefinitionInterface;
use Drupal\Core\TypedData\TraversableTypedDataInterface;
use Drupal\Core\TypedData\TypedDataInterface;
use Drupal\language\Config\LanguageConfigOverride;

/**
 * Defines the list element for the configuration translation interface.
 */
class ListElement implements ElementInterface {

  use StringTranslationTrait;

  /**
   * The schema element this form is for.
   *
   * @var \Drupal\Core\TypedData\TraversableTypedDataInterface
   */
  protected $element;

  /**
   * Constructs a ListElement.
   *
   * @param \Drupal\Core\TypedData\TraversableTypedDataInterface $element
   *   The schema element this form element is for.
   */
  public function __construct(TraversableTypedDataInterface $element) {
    $this->element = $element;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(TypedDataInterface $schema) {
    return new static($schema);
  }

  /**
   * {@inheritdoc}
   */
  public function getTranslationBuild(LanguageInterface $source_language, LanguageInterface $translation_language, $source_config, $translation_config, array $parents, $base_key = NULL) {
    $build = array();
    foreach ($this->element as $key => $element) {
      $sub_build = array();
      $element_key = isset($base_key) ? "$base_key.$key" : $key;
      $definition = $element->getDataDefinition();

      if ($form_element = ConfigTranslationFormBase::createFormElement($element)) {
        $element_parents = array_merge($parents, array($key));
        $sub_build += $form_element->getTranslationBuild($source_language, $translation_language, $source_config[$key], $translation_config[$key], $element_parents, $element_key);

        if (empty($sub_build)) {
          continue;
        }

        // Build the sub-structure and include it with a wrapper in the form if
        // there are any translatable elements there.
        $build[$key] = array();
        if ($element instanceof TraversableTypedDataInterface) {
          $build[$key] = array(
            '#type' => 'details',
            '#title' => $this->getGroupTitle($definition, $sub_build),
            '#open' => empty($base_key),
          );
        }
        $build[$key] += $sub_build;
      }
    }

    return $build;
  }

  /**
   * {@inheritdoc}
   */
  public function setConfig(Config $base_config, LanguageConfigOverride $config_translation, $config_values, $base_key = NULL) {
    foreach ($this->element as $key => $element) {
      $element_key = isset($base_key) ? "$base_key.$key" : $key;
      if ($form_element = ConfigTranslationFormBase::createFormElement($element)) {
        // Traverse into the next level of the configuration.
        $value = isset($config_values[$key]) ? $config_values[$key] : NULL;
        $form_element->setConfig($base_config, $config_translation, $value, $element_key);
      }
    }
  }

  /**
   * Returns the title for the 'details' element of a group of schema elements.
   *
   * For some configuration elements the same element structure can be repeated
   * multiple times (for example views displays, filters, etc.). Thus, we try to
   * find a more usable title for the details summary. First check if there is
   * an element which is called title or label and use its value. Then check if
   * there is an element which contains these words and use those. Fall back
   * to the generic definition label if no such element is found.
   *
   * @param \Drupal\Core\TypedData\DataDefinitionInterface $definition
   *   The definition of the schema element.
   * @param array $group_build
   *   The renderable array for the group of schema elements.
   *
   * @return string
   *   The title for the group of schema elements.
   */
  protected function getGroupTitle(DataDefinitionInterface $definition, array $group_build) {
    $title = '';
    if (isset($group_build['title']['source'])) {
      $title = $group_build['title']['source']['#markup'];
    }
    elseif (isset($group_build['label']['source'])) {
      $title = $group_build['label']['source']['#markup'];
    }
    else {
      foreach (array_keys($group_build) as $title_key) {
        if (isset($group_build[$title_key]['source']) && (strpos($title_key, 'title') !== FALSE || strpos($title_key, 'label') !== FALSE)) {
          $title = $group_build[$title_key]['source']['#markup'];
          break;
        }
      }
    }
    return (!empty($title) ? (strip_tags($title) . ' ') : '') . $this->t($definition['label']);
  }

}
