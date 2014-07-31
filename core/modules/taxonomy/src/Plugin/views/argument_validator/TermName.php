<?php

/**
 * @file
 * Contains \Drupal\taxonomy\Plugin\views\argument_validator\TermName.
 */

namespace Drupal\taxonomy\Plugin\views\argument_validator;

use Drupal\Core\Form\FormStateInterface;
use Drupal\views\ViewExecutable;
use Drupal\views\Plugin\views\display\DisplayPluginBase;
use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\views\Plugin\views\argument_validator\Entity;

/**
 * Validates whether a term name is a valid term argument.
 *
 * @ViewsArgumentValidator(
 *   id = "taxonomy_term_name",
 *   title = @Translation("Taxonomy term name"),
 *   entity_type = "taxonomy_term"
 * )
 */
class TermName extends Entity {

  /**
   * The taxonomy term storage.
   *
   * @var \Drupal\taxonomy\TermStorageInterface
   */
  protected $termStorage;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityManagerInterface $entity_manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $entity_manager);
    // Not handling exploding term names.
    $this->multipleCapable = FALSE;
    $this->termStorage = $entity_manager->getStorage('taxonomy_term');
  }

  /**
   * {@inheritdoc}
   */
  protected function defineOptions() {
    $options = parent::defineOptions();
    $options['transform'] = array('default' => FALSE, 'bool' => TRUE);

    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    parent::buildOptionsForm($form, $form_state);

    $form['transform'] = array(
      '#type' => 'checkbox',
      '#title' => t('Transform dashes in URL to spaces in term name filter values'),
      '#default_value' => $this->options['transform'],
    );
  }

  /**
   * {@inheritdoc}
   */
  public function validateArgument($argument) {
    if ($this->options['transform']) {
      $argument = str_replace('-', ' ', $argument);
    }
    $terms = $this->termStorage->loadByProperties(array('name' => $argument));

    if (!$terms) {
      // Returned empty array no terms with the name.
      return FALSE;
    }

    // Not knowing which term will be used if more than one is returned check
    // each one.
    foreach ($terms as $term) {
      if (!$this->validateEntity($term)) {
        return FALSE;
      }
    }

    return TRUE;
  }

}
