<?php

namespace Drupal\Core\Render\Builder;

/**
 * Builder class for the 'status_messages' element.
 */
class StatusMessagesBuilder extends BuilderBase {

  protected $renderable = ['#theme' => 'status_messages'];

  /**
   * Set the status_headings property on the status_messages.
   *
   * @param mixed $value
   *   The value to set.
   *
   * @return $this
   */
  public function setStatusHeadings($value) {
    $this->set('status_headings', $value);
    return $this;
  }

  /**
   * Set the message_list property on the status_messages.
   *
   * @param mixed $value
   *   The value to set.
   *
   * @return $this
   */
  public function setMessageList($value) {
    $this->set('message_list', $value);
    return $this;
  }

}
