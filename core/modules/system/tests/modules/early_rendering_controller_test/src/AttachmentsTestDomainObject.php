<?php

declare(strict_types=1);

namespace Drupal\early_rendering_controller_test;

use Drupal\Core\Render\AttachmentsInterface;
use Drupal\Core\Render\AttachmentsTrait;

/**
 * Test domain class with implemented AttachmentsInterface.
 */
class AttachmentsTestDomainObject extends TestDomainObject implements AttachmentsInterface {

  use AttachmentsTrait;

}
