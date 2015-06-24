<?php

/**
 * @file
 * Contains \Drupal\comment\Plugin\Action\UnpublishByKeywordComment.
 */

namespace Drupal\comment\Plugin\Action;

use Drupal\Component\Utility\Tags;
use Drupal\Core\Action\ConfigurableActionBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Unpublishes a comment containing certain keywords.
 *
 * @Action(
 *   id = "comment_unpublish_by_keyword_action",
 *   label = @Translation("Unpublish comment containing keyword(s)"),
 *   type = "comment"
 * )
 */
class UnpublishByKeywordComment extends ConfigurableActionBase {

  /**
   * {@inheritdoc}
   */
  public function execute($comment = NULL) {
    $build = comment_view($comment);
    $text = \Drupal::service('renderer')->renderPlain($build);
    foreach ($this->configuration['keywords'] as $keyword) {
      if (strpos($text, $keyword) !== FALSE) {
        $comment->setPublished(FALSE);
        $comment->save();
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
      '#description' => t('The comment will be unpublished if it contains any of the phrases above. Use a case-sensitive, comma-separated list of phrases. Example: funny, bungee jumping, "Company, Inc."'),
      '#default_value' => Tags::implode($this->configuration['keywords']),
    );
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
    /** @var \Drupal\comment\CommentInterface $object */
    $result = $object->access('update', $account, TRUE)
      ->andIf($object->status->access('edit', $account, TRUE));

    return $return_as_object ? $result : $result->isAllowed();
  }

}
