<?php

namespace Drupal\hsbxl_members;

use Drupal\Core\Entity\Query\QueryFactory;
use Drupal\Core\Entity\EntityManagerInterface;
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
}
