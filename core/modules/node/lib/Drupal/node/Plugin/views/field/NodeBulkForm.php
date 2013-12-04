<?php

/**
 * @file
 * Contains \Drupal\node\Plugin\views\field\NodeBulkForm.
 */

namespace Drupal\node\Plugin\views\field;

use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\views\Plugin\views\field\ActionBulkForm;
use Drupal\Component\Annotation\PluginID;
use Drupal\Core\Cache\Cache;

/**
 * Defines a node operations bulk form element.
 *
 * @PluginID("node_bulk_form")
 */
class NodeBulkForm extends ActionBulkForm {

  /**
   * Constructs a new NodeBulkForm object.
   */
  public function __construct(array $configuration, $plugin_id, array $plugin_definition, EntityManagerInterface $manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $manager);

    // Filter the actions to only include those for the 'node' entity type.
    $this->actions = array_filter($this->actions, function ($action) {
      return $action->getType() == 'node';
    });
  }

  /**
   * {@inheritdoc}
   */
  public function viewsFormValidate(&$form, &$form_state) {
    $selected = array_filter($form_state['values'][$this->options['id']]);
    if (empty($selected)) {
      form_set_error('', $form_state, t('No items selected.'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function viewsFormSubmit(&$form, &$form_state) {
    parent::viewsFormSubmit($form, $form_state);
    if ($form_state['step'] == 'views_form_views_form') {
      Cache::invalidateTags(array('content' => TRUE));
    }
  }

}
