<?php

/**
 * @file
 * Contains \Drupal\comment\Plugin\views\field\LinkDelete.
 */

namespace Drupal\comment\Plugin\views\field;

use Drupal\Component\Annotation\PluginID;
use Drupal\views\ResultRow;

/**
 * Field handler to present a link to delete a comment.
 *
 * @ingroup views_field_handlers
 *
 * @PluginID("comment_link_delete")
 */
class LinkDelete extends Link {

  public function access() {
    //needs permission to administer comments in general
    return user_access('administer comments');
  }

  protected function renderLink($data, ResultRow $values) {
    $text = !empty($this->options['text']) ? $this->options['text'] : t('delete');
    $cid =  $this->getValue($values, 'cid');

    $this->options['alter']['make_link'] = TRUE;
    $this->options['alter']['path'] = "comment/" . $cid . "/delete";
    $this->options['alter']['query'] = drupal_get_destination();

    return $text;
  }

}
