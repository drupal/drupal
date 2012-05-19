<?php

/**
 * @file
 * Definition of Drupal\user\User.
 */

namespace Drupal\user;

use Drupal\entity\Entity;

/**
 * Defines the user entity class.
 */
class User extends Entity {

  /**
   * The user ID.
   *
   * @var integer
   */
  public $uid;

  /**
   * The unique user name.
   *
   * @var string
   */
  public $name = '';

  /**
   * The user's password (hashed).
   *
   * @var string
   */
  public $pass;

  /**
   * The user's email address.
   *
   * @var string
   */
  public $mail = '';

  /**
   * The user's default theme.
   *
   * @var string
   */
  public $theme;

  /**
   * The user's signature.
   *
   * @var string
   */
  public $signature;

  /**
   * The user's signature format.
   *
   * @var string
   */
  public $signature_format;

  /**
   * The timestamp when the user was created.
   *
   * @var integer
   */
  public $created;

  /**
   * The timestamp when the user last accessed the site. A value of 0 means the
   * user has never accessed the site.
   *
   * @var integer
   */
  public $access = 0;

  /**
   * The timestamp when the user last logged in. A value of 0 means the user has
   * never logged in.
   *
   * @var integer
   */
  public $login = 0;

  /**
   * Whether the user is active (1) or blocked (0).
   *
   * @var integer
   */
  public $status = 0;

  /**
   * The user's timezone.
   *
   * @var string
   */
  public $timezone;

  /**
   * The user's langcode.
   *
   * @var string
   */
  public $langcode = LANGUAGE_NOT_SPECIFIED;

  /**
   * The user's preferred langcode for receiving emails and viewing the site.
   *
   * @var string
   */
  public $preferred_langcode = LANGUAGE_NOT_SPECIFIED;

  /**
   * The file ID of the user's picture.
   *
   * @var integer
   */
  public $picture = 0;

  /**
   * The email address used for initial account creation.
   *
   * @var string
   */
  public $init = '';

  /**
   * The user's roles.
   *
   * @var array
   */
  public $roles = array();

  /**
   * Implements Drupal\entity\EntityInterface::id().
   */
  public function id() {
    return $this->uid;
  }
}
