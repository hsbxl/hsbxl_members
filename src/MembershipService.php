<?php

namespace Drupal\hsbxl_members;

use Drupal\Core\Entity\Query\QueryFactory;
use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Datetime\DrupalDateTime;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\hsbxl_members\Entity\Membership;

/**
 * Class MembershipService.
 */
class MembershipService {
  protected $year;
  protected $month;
  protected $sale;
  protected $hsbxl_member;
  protected $structured_memo;
  protected $entity_query;

  public function __construct(QueryFactory $entity_query, EntityManagerInterface $entityManager) {
    $this->entity_query = $entity_query;
    $this->entityManager = $entityManager;
  }


  public function setYear($year) {
    $this->year = $year;
  }

  public function setMonth($month) {
    $this->month = $month;
  }

  public function setSale($sale) {
    $this->sale = $sale;
  }

  public function setHsbxlMember($hsbxl_member) {
    $this->hsbxl_member = $hsbxl_member;
  }

  public function setStructuredMemo($structured_memo) {
    $this->structured_memo = $structured_memo;
    $this->setHsbxlMember($this->getMemoMember($structured_memo));
  }



  public function getMemberships() {
    $memberships = [];
    $query = $this->entity_query->get('membership');

    if($this->year > 0) {
      $query->condition('field_year', $this->year);
    }

    if($this->month > 0) {
      $query->condition('field_month', $this->month);
    }

    $query->condition('status', 1);
    $mids = $query->execute();

    $memberships_storage = $this
      ->entityManager
      ->getStorage('membership');

    foreach ($mids as $mid) {
      $membership = $memberships_storage->load($mid);
      $memberships[] = $membership->get('field_booking')->target_id;
      unset($membership);
    }

    sort($mids);
    return $mids;
  }

  public function getFirstMembership() {
    $query = $this->entity_query->get('membership');
    $query->condition('field_membership_member', $this->hsbxl_member->id());
    $query->condition('status', 1);
    $query->sort('field_year' , 'ASC');
    $query->sort('field_month' , 'ASC');

    $memberships_storage = $this
      ->entityManager
      ->getStorage('membership');

    foreach ($query->execute() as $mid) {
      $membership = $memberships_storage->load($mid);
      return [
        'year' => $membership->get('field_year')->getValue()[0]['value'],
        'month' => $membership->get('field_month')->getValue()[0]['value'],
        'booking' => $membership->get('field_booking')->getValue()[0]['target_id'],
        'regime' => '',
      ];
    }

    return FALSE;
  }

  public function getLastMembership() {
    $query = $this->entity_query->get('membership');
    $query->condition('field_membership_member', $this->hsbxl_member->id());
    $query->condition('status', 1);
    $query->sort('field_year' , 'DESC');
    $query->sort('field_month' , 'DESC');

    $memberships_storage = $this
      ->entityManager
      ->getStorage('membership');

    foreach ($query->execute() as $mid) {
      $membership = $memberships_storage->load($mid);
      return [
        'year' => $membership->get('field_year')->getValue()[0]['value'],
        'month' => $membership->get('field_month')->getValue()[0]['value'],
        'booking' => $membership->get('field_booking')->getValue()[0]['target_id'],
        'regime' => '',
      ];
    }

    return FALSE;
  }

  public function getNextMembership() {
    $last_membership = $this->getLastMembership();

    // No last membership found, return now.
    if(!$last_membership) {
      $date = new DrupalDateTime('now');
      return [
        'year' => $date->format('Y'),
        'month' => $date->format('m'),
      ];
    }

    $date = new DrupalDateTime($last_membership['year'] . '-' . $last_membership['month'] . '-1 + 1 month');
    return [
      'year' => $date->format('Y'),
      'month' => $date->format('m'),
    ];
  }

  public function processMembershipFee($amount) {
    $i = 0;

    // Only go further if we have a hsbxl_member.
    if(is_object($this->hsbxl_member)) {
      // create a membership, deduct the regime price of the amount. Repeat.
      while($amount > 0) {
        $next_membership = $this->getNextMembership();
        $regime = $this->detectMembershipRegime($amount);
        $first_name = $this->hsbxl_member->get('field_first_name')->getValue()[0]['value'];
        $last_name = $this->hsbxl_member->get('field_last_name')->getValue()[0]['value'];
        if(!$regime) {
          break;
        }

        $membershipdata = [
          'field_membership_member' => $this->hsbxl_member,
          'type' => 'membership',
          'name' => 'membership ' . $first_name . ' ' . $last_name . ': ' . $next_membership['month'] . '/' . $next_membership['year'],
          //'field_booking' => $entity->id(),
          'field_year' => $next_membership['year'],
          'field_month' => $next_membership['month'],
          'field_membership_payment_regime_id' => $regime['id'],
          'field_membership_payment_regime_name' => $regime['name'],
        ];

        $membership = Membership::create($membershipdata);
        $membership->save();
        $i++;

        $amount = $amount - $regime['minimum_price'];
      }
    }

    // Return the amount of membership months generated.
    return $i;
  }

  public function detectMembershipRegime($amount) {
    // go over all regimes of the set year and month.
    foreach ($this->getMembershipRegimes() as $regime) {
      // if the amount is the minimum price or above, return regime.
      if($amount >= $regime['minimum_price']) {
        return $regime;
      }
    }

    // the amount did not fit any regime, return FALSE.
    return FALSE;
  }

  public function getMembershipRegimes() {
    $membership_regimes = [];

    if($this->year > 0 || $this->month > 0) {
      $date = new DrupalDateTime($this->year . '-' . $this->month . -'1');
    } else {
      $date = new DrupalDateTime('now');
    }

    $query = $this->entity_query->get('taxonomy_term');
    $query->condition('vid', 'membership_types');
    $query->condition('field_start_date', $date->format(DATETIME_DATETIME_STORAGE_FORMAT), '<=');
    $query->condition('field_end_date', $date->format(DATETIME_DATETIME_STORAGE_FORMAT), '>=');
    $query->sort('field_minimum_price' , 'DESC');

    $memberships_regimes_storage = $this
      ->entityManager
      ->getStorage('taxonomy_term');

    foreach ($query->execute() as $tid) {
      $membership_regime = $memberships_regimes_storage->load($tid);
      $membership_regimes[] = array(
        'id' => $membership_regime->get('tid')->value,
        'name' => $membership_regime->get('name')->value,
        'minimum_price' => (int)$membership_regime->get('field_minimum_price')->value,
        'start_date' => $membership_regime->get('field_start_date')->value,
        'end_date' => $membership_regime->get('field_end_date')->value,
      );
    }

    return $membership_regimes;
  }

  public function getMemoMember() {
    $query = \Drupal::service('entity.query')
      ->get('user')
      ->condition('field_structured_memo', $this->structured_memo);

    $entity_ids = $query->execute();

    if(count($entity_ids)) {
      $member = \Drupal::entityTypeManager()->getStorage('user')->load(current($entity_ids));
      return $member;
    }

    return FALSE;
  }

}











