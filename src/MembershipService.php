<?php

namespace Drupal\hsbxl_members;

use Drupal\Core\Entity\Query\QueryFactory;
use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Datetime\DrupalDateTime;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class MembershipService.
 */
class MembershipService {
  protected $hsbxl_member;
  protected $year;
  protected $month;
  protected $entity_query;

  public function __construct(QueryFactory $entity_query, EntityManagerInterface $entityManager) {
    $this->entity_query = $entity_query;
    $this->entityManager = $entityManager;
  }

  /**
   * @param int $year
   */
  public function setYear($year) {
    $this->year = $year;
  }

  /**
   * @param int $month
   */
  public function setMonth($month) {
    $this->month = $month;
  }

  /**
   * @param mixed $hsbxl_member
   */
  public function setHsbxlMember($hsbxl_member) {
    $this->hsbxl_member = $hsbxl_member;
  }

  /**
   * @return array
   */
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
        'name' => $membership_regime->get('name')->value,
        'minimum_price' => $membership_regime->get('field_minimum_price')->value,
        'start_date' => $membership_regime->get('field_start_date')->value,
        'end_date' => $membership_regime->get('field_end_date')->value,
      );
    }

    return $membership_regimes;
  }
}











