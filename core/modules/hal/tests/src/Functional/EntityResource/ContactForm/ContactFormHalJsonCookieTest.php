<?php

namespace Drupal\Tests\hal\Functional\EntityResource\ContactForm;

use Drupal\Tests\rest\Functional\CookieResourceTestTrait;
use Drupal\Tests\rest\Functional\EntityResource\ContactForm\ContactFormResourceTestBase;

/**
 * @group hal
 */
class ContactFormHalJsonCookieTest extends ContactFormResourceTestBase {

  use CookieResourceTestTrait;

  /**
   * {@inheritdoc}
   */
  public static $modules = ['hal'];

  /**
   * {@inheritdoc}
   */
  protected static $format = 'hal_json';

  /**
   * {@inheritdoc}
   */
  protected static $mimeType = 'application/hal+json';

  /**
   * {@inheritdoc}
   */
  protected static $auth = 'cookie';

}
