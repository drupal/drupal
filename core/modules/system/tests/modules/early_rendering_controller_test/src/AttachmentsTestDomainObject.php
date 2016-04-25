<?php

namespace Drupal\early_rendering_controller_test;

use Drupal\Core\Render\AttachmentsInterface;
use Drupal\Core\Render\AttachmentsTrait;

class AttachmentsTestDomainObject extends TestDomainObject implements AttachmentsInterface {

  use AttachmentsTrait;

}
