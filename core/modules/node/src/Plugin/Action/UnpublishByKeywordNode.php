<?php

namespace Drupal\node\Plugin\Action;

use Drupal\Component\Utility\Tags;
use Drupal\Core\Action\ConfigurableActionBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Session\AccountInterface;

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
      if (strpos(\Drupal::service('renderer')->render($elements), $keyword) !== FALSE || strpos($node->label(), $keyword) !== FALSE) {
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
    return [
      'keywords' => [],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form['keywords'] = [
      '#title' => t('Keywords'),
      '#type' => 'textarea',
      '#description' => t('The content will be unpublished if it contains any of the phrases above. Use a case-sensitive, comma-separated list of phrases. Example: funny, bungee jumping, "Company, Inc."'),
      '#default_value' => Tags::implode($this->configuration['keywords']),
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    $this->configuration['keywords'] = Tags::explode($form_state->getValue('keywords'));
  }

  /**
   * {@inheritdoc}
   */
  public function access($object, AccountInterface $account = NULL, $return_as_object = FALSE) {
    /** @var \Drupal\node\NodeInterface $object */
    $access = $object->access('update', $account, TRUE)
      ->andIf($object->status->access('edit', $account, TRUE));

    return $return_as_object ? $access : $access->isAllowed();
  }

}
