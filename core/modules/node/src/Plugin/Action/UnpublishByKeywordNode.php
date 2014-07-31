<?php

/**
 * @file
 * Contains \Drupal\node\Plugin\Action\UnpublishByKeywordNode.
 */

namespace Drupal\node\Plugin\Action;

use Drupal\Component\Utility\Tags;
use Drupal\Core\Action\ConfigurableActionBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Unpublishes a node containing certain keywords.
 *
 * @Action(
 *   id = "node_unpublish_by_keyword_action",
 *   label = @Translation("Unpublish content containing keyword(s)"),
 *   type = "node"
 * )
 */
class UnpublishByKeywordNode extends ConfigurableActionBase {

  /**
   * {@inheritdoc}
   */
  public function execute($node = NULL) {
    foreach ($this->configuration['keywords'] as $keyword) {
      $elements = node_view(clone $node);
      if (strpos(drupal_render($elements), $keyword) !== FALSE || strpos($node->label(), $keyword) !== FALSE) {
        $node->setPublished(FALSE);
        $node->save();
        break;
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return array(
      'keywords' => array(),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form['keywords'] = array(
      '#title' => t('Keywords'),
      '#type' => 'textarea',
      '#description' => t('The content will be unpublished if it contains any of the phrases above. Use a case-sensitive, comma-separated list of phrases. Example: funny, bungee jumping, "Company, Inc."'),
      '#default_value' => Tags::implode($this->configuration['keywords']),
    );
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    $this->configuration['keywords'] = Tags::explode($form_state['values']['keywords']);
  }

}
