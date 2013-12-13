<?php

/**
 * @file
 * Contains \Drupal\comment\Plugin\Action\UnpublishByKeywordComment.
 */

namespace Drupal\comment\Plugin\Action;

use Drupal\Core\Annotation\Action;
use Drupal\Core\Annotation\Translation;
use Drupal\Core\Action\ConfigurableActionBase;
use Drupal\comment\CommentInterface;

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
    $text = drupal_render($build);
    foreach ($this->configuration['keywords'] as $keyword) {
      if (strpos($text, $keyword) !== FALSE) {
        $comment->status->value = CommentInterface::NOT_PUBLISHED;
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
  public function buildConfigurationForm(array $form, array &$form_state) {
    $form['keywords'] = array(
      '#title' => t('Keywords'),
      '#type' => 'textarea',
      '#description' => t('The comment will be unpublished if it contains any of the phrases above. Use a case-sensitive, comma-separated list of phrases. Example: funny, bungee jumping, "Company, Inc."'),
      '#default_value' => drupal_implode_tags($this->configuration['keywords']),
    );
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, array &$form_state) {
    $this->configuration['keywords'] = drupal_explode_tags($form_state['values']['keywords']);
  }

}
