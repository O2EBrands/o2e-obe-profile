<?php

namespace Drupal\o2e_obe_salesforce\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Get Available Times class is return the time slots detail.
 */
class GetAvailabeTime extends ControllerBase {

  /**
   * Request stack.
   *
   * @var RequestStack
   */
  protected $request;

  /**
   * The Get Availabe Time object.
   *
   * @var AvailableTimesService
   */
  protected $availableTime;

  /**
   * Create methods.
   */
  public static function create(ContainerInterface $container) {
     $instance = parent::create($container);
     $instance->availableTime = $container->get('o2e_obe_salesforce.available_times_service');
     $instance->request = $container->get('request_stack');
     return $instance;
  }

  /**
   * Return the time slots.
   */
  public function gettimeslots() {
    $params =$this->request->getCurrentRequest();
    $start_date = $params->get('start_date');
    $end_date = $params->get('end_date');
    if ($start_date && $end_date) {
      $response = $this->availableTime->getAvailableTimes(['start_date' => $start_date, 'end_date' => $end_date]);
      return new JsonResponse($response);
    }else {
      return new JsonResponse(['start date and End date are missing.']);
    }
  }

}
