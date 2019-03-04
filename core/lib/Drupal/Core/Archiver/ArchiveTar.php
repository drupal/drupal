<?php

namespace Drupal\Core\Archiver;

/**
 * Extends Pear's Archive_Tar to use exceptions.
 */
class ArchiveTar extends \Archive_Tar {

  /**
   * {@inheritdoc}
   */
  public function _error($p_message) {
    throw new \Exception($p_message);
  }

  /**
   * {@inheritdoc}
   */
  public function _warning($p_message) {
    throw new \Exception($p_message);
  }

}
