<?php

namespace Drupal\Tests\hal\Functional\EntityResource\ContactForm;

use Drupal\Tests\rest\Functional\AnonResourceTestTrait;
use Drupal\Tests\rest\Functional\EntityResource\ContactForm\ContactFormResourceTestBase;

/**
 * @group hal
 */
class ContactFormHalJsonAnonTest extends ContactFormResourceTestBase {

  use AnonResourceTestTrait;

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

}
