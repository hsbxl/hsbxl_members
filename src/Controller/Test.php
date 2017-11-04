<?php

namespace Drupal\hsbxl_members\Controller;

use Drupal\User\Entity\User;
use Drupal\Core\Controller\ControllerBase;
use Drupal\hsbxl_members\MembershipService;
use Drupal\simplified_bookkeeping\BookkeepingService;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class Test.
 */
class Test extends ControllerBase {
  protected $membership;

  public function __construct(MembershipService $membership, BookkeepingService $bookkeeping) {
    $this->membership = $membership;
    $this->bookkeeping = $bookkeeping;
  }

  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('hsbxl_members.membership'),
      $container->get('simplified_bookkeeping.bookkeeping')
    );
  }

  public function test() {

    //$user = \Drupal\user\Entity\User::load(64);

    $bookkeeping = $this->bookkeeping;
    $bookkeeping->setStatement(3786);
    $statements = $bookkeeping->getStatements();
    kint($statements);


    //$this->membership->setStatement(3786);
    //$statement = $this->membership->getStatement();
    //$memberships = $this->membership->getMemberships();
    //kint($statement);
    //kint($memberships);

    //$membershipService->setYear('2017');
    //$membershipService->setMonth('10');
    //$membershipService->setHsbxlMember($user);
    //$membershipService->setStructuredMemo('+++026/8042/07030+++');

    //kint($this->membership->getLastMembership());
    //kint($this->membership->getNextMembership());
    //kint($this->membership->getMemoMember());
    //kint($this->membership->detectMembershipRegime(24));
    //kint($this->membership->processMembershipFee(117));

    //kint($this->bookkeeping->genSales());

    return [
      '#type' => 'markup',
      '#markup' => 'Hello World!',
    ];
  }
}