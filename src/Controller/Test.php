<?php

namespace Drupal\hsbxl_members\Controller;

use Drupal\User\Entity\User;
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

    $user = \Drupal\user\Entity\User::load(95);

    $membershipService = $this->membership;
    $membershipService->setYear('2017');
    $membershipService->setMonth('10');
    $membershipService->setHsbxlMember($user);

    kint($this->membership->getFirstMembership());

    return [
      '#type' => 'markup',
      '#markup' => $this->membership->getMembershipRegimes(),
    ];
  }
}