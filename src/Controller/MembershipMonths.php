<?php

namespace Drupal\hsbxl_members\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\simplified_bookkeeping\Entity\BookingEntity;
use Drupal\user\UserInterface;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Url;



/**
 * An example controller.
 */
class MembershipMonths extends ControllerBase {

  function months(AccountInterface $user = NULL) {
    $query = \Drupal::entityQuery('membership');
    $query->condition('field_membership_member', $user->id(), '=');
    $query->condition('status', 1);
    $query->sort('field_year', 'DESC');
    $query->sort('field_month', 'DESC');
    $ids = $query->execute();
    return entity_load_multiple('membership', $ids);
  }

  public function getStatement($sale_id) {
    $database = \Drupal::database();
    $result = $database
      ->select('booking__field_booking', 'b')
      ->fields('b', ['entity_id'])
      ->condition('field_booking_target_id', $sale_id)
      ->execute()
      ->fetchField();

    return BookingEntity::load($result);
  }

  public function table(UserInterface $user = NULL) {
    $months = $this->months($user);
    $total = 0; $bank_rows = [];

    // go over and create the table rows.
    foreach($months as $month) {
      //kint($month);
      $sale_id = $month->field_booking->target_id;
      $statement = $this->getStatement($sale_id);
      $url = Url::fromRoute('entity.booking.canonical', ['booking' => $statement->id()]);
      $link = \Drupal\Core\Link::fromTextAndUrl('Statement', $url);

      $bank_rows[] = [
        $month->field_month->value . '-' . $month->field_year->value,
        $link->toString(),
        $statement->field_booking_amount->value,
        //$payment->get('field_sale_payment_method')->getValue()[0]['value'],
        //'z',//$month->get('field_sale_total_amount')->getValue()[0]['value'] . '€',
      ];
      //$total = $total + $month->get('field_sale_total_amount')->getValue()[0]['value'];
      $total = $total + 1;
    }

    // Check if the total is above zero
    // if not, create an empty message row.
    if($total > 0) {
      $bank_rows[] = [
        'TOTAL',
        '',
        '= ' . $total . '€',
      ];
    }
    else {
      $bank_rows[] = [
        '', 'No membership months found.', ''
      ];
    }

    // Define the table render array
    $build[] = [
      '#type' => 'table',
      '#header' => [
        'Membership',
        'Statement',
        'Statement amount',
      ],
      '#rows' => $bank_rows,
      '#attributes' => [
        'class' => ['table', 'table-striped', 'table-condensed', 'table-bordered']
      ],
    ];

    return $build;
  }
}