<?php

namespace Drupal\Tests\views\Unit\Plugin;

/**
 * Test trait to mock dependencies of a handler.
 */
trait HandlerTestTrait {

  /**
   * The mocked view entity.
   *
   * @var \Drupal\views\Entity\View|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $view;

  /**
   * The mocked view executable.
   *
   * @var \Drupal\views\ViewExecutable|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $executable;

  /**
   * The mocked views data.
   *
   * @var \Drupal\views\ViewsData|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $viewsData;

  /**
   * The mocked display.
   *
   * @var \Drupal\views\Plugin\views\display\DisplayPluginBase|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $display;

  /**
   * Sets up a view executable and a view entity.
   */
  protected function setupExecutableAndView() {
    $this->view = $this->getMockBuilder('Drupal\views\Entity\View')
      ->disableOriginalConstructor()
      ->getMock();
    $this->executable = $this->getMockBuilder('Drupal\views\ViewExecutable')
      ->disableOriginalConstructor()
      ->getMock();
    $this->executable->storage = $this->view;
  }

  /**
   * Sets up a mocked views data object.
   */
  protected function setupViewsData() {
    $this->viewsData = $this->getMockBuilder('Drupal\views\ViewsData')
      ->disableOriginalConstructor()
      ->getMock();
  }

  /**
   * Sets up a mocked display object.
   */
  protected function setupDisplay() {
    $this->display = $this->getMockBuilder('Drupal\views\Plugin\views\display\DisplayPluginBase')
      ->disableOriginalConstructor()
      ->getMock();
  }

}
