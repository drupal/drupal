<?php

/**
 * @file
 * Contains \Drupal\poll\Plugin\block\block\PollRecentBlock.
 */

namespace Drupal\poll\Plugin\block\block;

use Drupal\block\BlockBase;
use Drupal\Core\Annotation\Plugin;
use Drupal\Core\Annotation\Translation;

/**
 * Provides a 'Most recent poll' block.
 *
 * @Plugin(
 *   id = "poll_recent_block",
 *   subject = @Translation("Most recent poll"),
 *   module = "poll"
 * )
 */
class PollRecentBlock extends BlockBase {

  /**
   * Stores the node ID of the latest poll.
   *
   * @var int
   */
  protected $record;

  /**
   * Overrides \Drupal\block\BlockBase::blockSettings().
   */
  public function blockSettings() {
    return array(
      'properties' => array(
        'administrative' => TRUE,
      ),
    );
  }

  /**
   * Overrides \Drupal\block\BlockBase::access().
   */
  public function blockAccess() {
    if (user_access('access content')) {
      // Retrieve the latest poll.
      $select = db_select('node', 'n');
      $select->join('poll', 'p', 'p.nid = n.nid');
      $select->fields('n', array('nid'))
        ->condition('n.status', 1)
        ->condition('p.active', 1)
        ->orderBy('n.created', 'DESC')
        ->range(0, 1)
        ->addTag('node_access');

      $record = $select->execute()->fetchObject();
      if ($record) {
        $this->record = $record->nid;
        return TRUE;
      }
    }
    return FALSE;
  }

  /**
   * Implements \Drupal\block\BlockBase::blockBuild().
   */
  public function blockBuild() {
    $poll = node_load($this->record);
    if ($poll->nid) {
      $poll = poll_block_latest_poll_view($poll);
      return array(
        $poll->content
      );
    }
    return array();
  }

}
