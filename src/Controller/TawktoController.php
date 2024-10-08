<?php

namespace Drupal\tawk_to\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Logger\LoggerChannelFactory;
use Drupal\tawk_to\core\TawktoGenerator;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Response;

/**
 * Route handler for various endpoints.
 */
class TawktoController extends ControllerBase {
  /**
   * Instance of TawktoGenerator.
   *
   * @var Drupal\tawk_to\core\TawktoGenerator
   */
  private $generator;

  /**
   * Class __construct method.
   *
   * @return void
   *   null
   */
  public function __construct(TawktoGenerator $generator, LoggerChannelFactory $loggerFactory) {
    $this->generator = $generator;
    $this->loggerFactory = $loggerFactory;
  }

  /**
   * Create new instance of controller.
   *
   * @return Drupal\Core\Controller\ControllerBase
   *   Controller instance
   */
  public static function create(ContainerInterface $container) {
    $generator = $container->get('tawk_to.chat_generator');
    $logger = $container->get('logger.factory');

    return new static($generator, $logger);
  }

  /**
   * Chat widget endpoint.
   *
   * @return Symfony\Component\HttpFoundation\Response
   *   Widget response
   */
  public function widget() {
    $widget = $this->generator->widget();
    return new Response($widget);
  }

  /**
   * Admin settings endpoint.
   *
   * @return array[string]mixed
   *   Admin settings template
   */
  public function settings() {
    $build = [
      '#type' => 'inline_template',
      '#template' => $this->generator->getIframe(),
    ];
    return $build;
  }

  /**
   * Set widget configuration endpoint.
   *
   * @return Symfony\Component\HttpFoundation\JsonResponse
   *   JSON response
   */
  public function setWidget() {
    if (!empty($_REQUEST) && 'POST' == $_SERVER["REQUEST_METHOD"]) {
      foreach ($_REQUEST as $key => $value) {
        if (stripos($key, 'SESS') !== FALSE) {
          if ($value == \Drupal::service('session')->getId()) {
            return $this->set($_REQUEST);
          }
        }
      }
    }
  }

  /**
   * Set widget configuration.
   *
   * @return Symfony\Component\HttpFoundation\JsonResponse
   *   JSON response
   */
  private function set($params) {
    $pid = '';
    $wid = '';
    extract($params);
    return $this->generator->setWidget($pid, $wid);
  }

  /**
   * Remove widget configuration endpoint.
   *
   * @return Symfony\Component\HttpFoundation\JsonResponse
   *   JSON response
   */
  public function removeWidget() {
    if (!empty($_REQUEST) && 'POST' == $_SERVER["REQUEST_METHOD"]) {
      foreach ($_REQUEST as $key => $value) {
        if (stripos($key, 'SESS') !== FALSE) {
          if ($value == \Drupal::service('session')->getId()) {
            return $this->remove();
          }
        }
      }
    }
  }

  /**
   * Set additional options endpoint.
   *
   * @return Symfony\Component\HttpFoundation\JsonResponse
   *   JSON response
   */
  public function setOptions() {
    if (!empty($_REQUEST['options']) && 'POST' == $_SERVER["REQUEST_METHOD"]) {
      $options = trim($_REQUEST['options']);
      return $this->generator->setOptions($options);
    }
  }

  /**
   * Remove widget.
   *
   * @return Symfony\Component\HttpFoundation\JsonResponse
   *   JSON response
   */
  private function remove() {
    return $this->generator->removeWidget();
  }

}
