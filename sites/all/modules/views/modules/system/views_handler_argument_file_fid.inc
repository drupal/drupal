<?php
// $Id: views_handler_argument_file_fid.inc,v 1.1.6.1 2009/11/02 22:01:26 merlinofchaos Exp $
/**
 * Argument handler to accept a file id.
 */
class views_handler_argument_file_fid extends views_handler_argument {
  /**
   * Override the behavior of title(). Get the title of the file.
   */
  function title() {
    $title = db_select('files', 'f')
      ->addField('f', 'filename')
      ->condition('fid', $this->argument)
      ->execute()
      ->fetchField();
    if (empty($title)) {
      return t('No title');
    }

    return check_plain($title);
  }
}
