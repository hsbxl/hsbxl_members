<?php

namespace Drupal\hsbxl_members;

use Drupal\Core\Entity\Query\QueryFactory;
use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\taxonomy\Entity\Term;
use Drupal\hsbxl_members\Entity\Membership;
use Drupal\simplified_bookkeeping\BookkeepingService;
use Drupal\simplified_bookkeeping\Entity\BookingEntity;

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
  protected $statement;
  protected $socialtariff;

  public function __construct(QueryFactory $entity_query, EntityManagerInterface $entityManager, BookkeepingService $bookkeeping) {
    $this->entity_query = $entity_query;
    $this->entityManager = $entityManager;
    $this->bookkeeping = $bookkeeping;
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

  public function getHsbxlMember() {
    return $this->hsbxl_member;
  }

  public function setStructuredMemo($structured_memo) {
    $this->structured_memo = $structured_memo;
    $member = $this->getMemoMember($structured_memo);
    $this->setHsbxlMember($member);
    if($member) {
      $this->socialtariff = $member->get('field_social_tariff')->getValue()[0]['value'] ? TRUE : FALSE;
    }
  }




  public function setStatement($statement) {
    $this->hsbxl_member = NULL;

    if(is_object($statement)) {
      $this->statement = $statement;
    }
    if(is_int($statement)) {
      $statement = BookingEntity::load($statement);
      $this->statement = $statement;
    }

    $date = new DrupalDateTime($statement->get('field_booking_date')->getValue()[0]['value']);
    $this->setYear($date->format('Y'));
    $this->setMonth($date->format('m'));

    // we set the structured memo, which will also set the hsbxl_member.
    $this->setStructuredMemo($statement->field_booking_structured_memo->value);
  }

  public function getStatement() {
    return $this->statement;
  }





  public function getMemberships() {
    $member = $this->hsbxl_member;
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
      $memberships[] = $membership->get('field_sale')->target_id;
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
        'booking' => $membership->get('field_sale')->getValue()[0]['target_id'],
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
        'booking' => $membership->get('field_sale')->getValue()[0]['target_id'],
        'regime' => '',
      ];
    }

    return FALSE;
  }

  public function getNextMembership() {
    $last_membership = $this->getLastMembership();

    // No last membership found, return date set.
    if(!$last_membership) {
      return [
        'year' => $this->year,
        'month' => $this->month,
      ];
    }

    $lastdate = new DrupalDateTime($last_membership['year'] . '-' . $last_membership['month'] . '-1');
    $maxdate = new DrupalDateTime($last_membership['year'] . '-' . $last_membership['month'] . '-1 - 3 months');

    // If the last membership was from longer then 3 months ago, use the statement date.
    if($lastdate->format('U') < $maxdate->format('U')) {
      $date = new DrupalDateTime($last_membership['year'] . '-' . $last_membership['month'] . '-1 + 1 month');
      return [
        'year' => $date->format('Y'),
        'month' => $date->format('m'),
      ];
    }
    else{
      return [
        'year' => $this->year,
        'month' => $this->month,
      ];
    }
  }

  public function getMembershipTag() {
    // check if we have a membership tag.
    $tag = current(taxonomy_term_load_multiple_by_name('membership', 'bookkeeping_tags'));
    if(!$tag) {
      $tag = Term::create(array(
        'parent' => array(),
        'name' => 'membership',
        'vid' => 'bookkeeping_tags',
      ))->save();
    }
    return $tag;
  }

  public function getDonationTag() {
    // check if we have a membership tag.
    $tag = current(taxonomy_term_load_multiple_by_name('donation', 'bookkeeping_tags'));
    if(!$tag) {
      $tag = Term::create(array(
        'parent' => array(),
        'name' => 'donation',
        'vid' => 'bookkeeping_tags',
      ))->save();
    }
    return $tag;
  }

  public function processMembershipFee($amount) {
    $i = 0;

    // Only go further if we have a hsbxl_member.
    if(is_object($this->hsbxl_member)) {
      $social = $this->hsbxl_member->get('field_social_tariff')->getValue()[0]['value'] ? TRUE : FALSE;

      // create a membership, deduct the regime price of the amount. Repeat.
      while($amount > 0) {

        if($this->statement->get('field_booking_repeat_membership')->getValue()[0]['value'] == 'Donate'
          && $i > 0) {
          $sale_data = [
            'type' => 'sale',
            'name' => 'donation',
            'field_booking_amount' => $amount,
            'field_booking_date' => $this->statement->get('field_booking_date')->getValue()[0]['value'],
            //'field_booking' => $this->statement,
            'field_booking_tags' => $this->getDonationTag(),
            'uid' => 1
          ];
          $sale = BookingEntity::create($sale_data);
          $sale->save();

          // Save the statement with the donation sale added.
          $statement = BookingEntity::load($this->statement->id());
          $statement->field_booking[] = $sale;
          $statement->field_booking_status = 'completed';
          $statement->save();
          break;
        }

        $next_membership = $this->getNextMembership();
        $regime = $this->detectMembershipRegime($amount, $social);
        $first_name = $this->hsbxl_member->get('field_first_name')->getValue()[0]['value'];
        $last_name = $this->hsbxl_member->get('field_last_name')->getValue()[0]['value'];

        if(!$regime) {
          // we don't have enough for a minimum membership fee,
          // but we have rest amount, donate it.
          if($amount > 0) {
            $sale_data = [
              'type' => 'sale',
              'name' => 'donation',
              'field_booking_amount' => $amount,
              'field_booking_date' => $this->statement->get('field_booking_date')->getValue()[0]['value'],
              //'field_booking' => $this->statement,
              'field_booking_tags' => $this->getDonationTag(),
              'uid' => 1
            ];
            $sale = BookingEntity::create($sale_data);
            $sale->save();

            // Save the statement with the donation sale added.
            $statement = BookingEntity::load($this->statement->id());
            $statement->field_booking[] = $sale;
            $statement->field_booking_status = 'completed';
            $statement->save();

            $amount = 0;
            break;
          }
        }

        $sale_data = [
          'type' => 'sale',
          'name' => 'Membership ' . $next_membership['month'] . '-' . $next_membership['year'],
          'field_booking_amount' => $regime['minimum_price'],
          'field_booking_date' => $this->statement->get('field_booking_date')->getValue()[0]['value'],
          //'field_booking' => $this->statement,
          'field_booking_tags' => $this->getMembershipTag(),
          'uid' => 1
        ];

        $sale = BookingEntity::create($sale_data);
        $sale->save();

        $amount = $amount - $regime['minimum_price'];

        // Save the statement with the membership sale added.
        $statement = BookingEntity::load($this->statement->id());
        $statement->field_booking[] = $sale;
        if($amount == 0) {
          $statement->field_booking_status = 'completed';
        }
        $statement->save();

        $membershipdata = [
          'field_membership_member' => $this->hsbxl_member,
          'type' => 'membership',
          'name' => 'membership ' . $first_name . ' ' . $last_name . ': ' . $next_membership['month'] . '/' . $next_membership['year'],
          'field_sale' => $sale,
          'field_year' => $next_membership['year'],
          'field_month' => $next_membership['month'],
          'field_membership_payment_regime' => $regime,
          'uid' => 1,
        ];

        $membership = Membership::create($membershipdata);
        $membership->save();
        $i++;
      }
    }

    // Return the amount of membership months generated.
    return $i;
  }

  public function detectMembershipRegime($amount, $social) {
    // go over all regimes of the set year and month.
    foreach ($this->getMembershipRegimes($social) as $regime) {
      // if the amount is the minimum price or above, return regime.
      if($amount >= $regime['minimum_price']) {
        return $regime;
      }
    }

    // the amount did not fit any regime, return FALSE.
    return FALSE;
  }

  public function getMembershipRegimes($social) {
    $membership_regimes = [];

    if($this->year > 0 || $this->month > 0) {
      $date = new DrupalDateTime($this->year . '-' . $this->month . -'1');
    } else {
      $date = new DrupalDateTime('now');
    }

    $query = $this->entity_query->get('taxonomy_term');
    $query->condition('vid', 'membership_types');
    $query->condition('field_social_tariff', $social);
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

  public function queueStatements() {
    $query = $this->entity_query->get('booking');
    $query->condition('status', 1);
    $query->condition('field_booking_status', "unprocessed");
    $query->condition('type', ['bankstatement', 'cashstatement'], 'IN');
    $query->sort('field_booking_date' , 'ASC');

    $queue_factory = \Drupal::service('queue');
    $queue = $queue_factory->get('statements_queue_processor');

    foreach ($query->execute() as $bid) {
      // Add to the queue.
      $queue->createItem($bid);

      // set statement as status 'queued'.
      $booking = BookingEntity::load($bid);
      $booking->field_booking_status->value = "queued";
      $booking->save();
    }

    return;
  }

  public function processStatement($statement_id) {
    $this->setStatement((int)$statement_id);
    $statement = $this->statement;

    $a = 0;

    //if ($statement->bundle() == 'bankstatement') {

      $amount = $statement->get('field_booking_amount')->getValue()[0]['value'];
      $date = new DrupalDateTime($statement->get('field_booking_date')->getValue()[0]['value']);

      if($amount > 0) {
        $this->processMembershipFee($amount);
      }
    //}
  }

  public function createMembership() {

    $membership_data = [
      'name' => 'Membership',
      'field_sale' => $this->sale,
      'field_month' => $this->month,
      'field_year' => $this->year,
      'uid' => 1
    ];

    $membership = Membership::create($membership_data);
    $membership->save();
  }

}











