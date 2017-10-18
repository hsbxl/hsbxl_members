<?php

namespace Drupal\hsbxl_members\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\hsbxl_members\MembershipService;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class Test.
 */
class Test extends ControllerBase {
  protected $membership;

  public function __construct(MembershipService $membership) {
    $this->membership = $membership;
  }

  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('hsbxl_members.membership')
    );
  }

  public function test() {

    $membershipService = $this->membership;
    $membershipService->setYear('2017');
    $membershipService->setMonth('10');

    kint($this->membership->getMemberships());

    return [
      '#type' => 'markup',
      '#markup' => $this->membership->getMemberships(),
    ];
  }
}