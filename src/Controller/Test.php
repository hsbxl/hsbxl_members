<?php

namespace Drupal\hsbxl_members\Controller;

use Drupal\hsbxl_numbers\NumbersService;
use Drupal\User\Entity\User;
use Drupal\Core\Controller\ControllerBase;
use Drupal\hsbxl_members\MembershipService;
use Drupal\simplified_bookkeeping\BookkeepingService;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * Class Test.
 */
class Test extends ControllerBase {
  protected $membership;

  public function __construct(MembershipService $membership, BookkeepingService $bookkeeping, NumbersService $numbersService) {
    $this->membership = $membership;
    $this->bookkeeping = $bookkeeping;
    $this->numbers = $numbersService;
  }

  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('hsbxl_members.membership'),
      $container->get('simplified_bookkeeping.bookkeeping'),
      $container->get('hsbxl_numbers.numbers')
    );
  }

  public function test() {

    $this->numbers->setMonth('09');
    $this->numbers->setYear('2017');
    //$this->numbers->getIncomeDonations();
    $members = $this->numbers->currentMembers();

    return new JsonResponse($members);


    //$user = \Drupal\user\Entity\User::load(64);

    //$bookkeeping = $this->bookkeeping;
    //$bookkeeping->setStatement(3786);
    //$statements = $bookkeeping->getStatements();


    //$this->membership->setStatement(3786);
    //$statement = $this->membership->getStatement();
    //$memberships = $this->membership->getMemberships();
    //kint($statement);
    //kint($memberships);


    //$this->membership->setYear('2013');
    //$this->membership->setMonth('10');
    //$regimes = $this->membership->getMembershipRegimes();
    //$this->membership->processStatement(8869);
    //kint($regimes);
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