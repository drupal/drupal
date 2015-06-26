<?php

/**
 * @file
 * Contains \Drupal\contact\ContactFormInterface.
 */

namespace Drupal\contact;

use Drupal\Core\Config\Entity\ConfigEntityInterface;

/**
 * Provides an interface defining a contact form entity.
 */
interface ContactFormInterface extends ConfigEntityInterface {

  /**
   * Returns list of recipient e-mail addresses.
   *
   * @return array
   *   List of recipient e-mail addresses.
   */
  public function getRecipients();

  /**
   * Returns an auto-reply message to send to the message author.
   *
   * @return string
   *  An auto-reply message
   */
  public function getReply();

  /**
   * Returns the weight of this category (used for sorting).
   *
   * @return int
   *   The weight of this category.
   */
  public function getWeight();

  /**
   * Sets list of recipient e-mail addresses.
   *
   * @param array $recipients
   *   The desired list of e-mail addresses of this category.
   *
   * @return $this
   */
  public function setRecipients($recipients);

  /**
   * Sets an auto-reply message to send to the message author.
   *
   * @param string $reply
   *   The desired reply.
   *
   * @return $this
   */
  public function setReply($reply);

  /**
   * Sets the weight.
   *
   * @param int $weight
   *   The desired weight.
   *
   * @return $this
   */
  public function setWeight($weight);

}
