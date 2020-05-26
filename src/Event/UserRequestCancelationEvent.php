<?php

namespace Drupal\vipps_recurring_payments\Event;


use Drupal\vipps_recurring_payments\Entity\VippsAgreements;
use Symfony\Component\EventDispatcher\Event;

class UserRequestCancelationEvent extends Event {

  const CONFIRM_REQUEST = 'user_request_cancelation';

  /**
   * The vipps agreement.
   *
   * @var VippsAgreements
   */
  public $vippsAgreement;

  /**
   * Constructs the object.
   *
   * @param \Drupal\vipps_recurring_payments\Entity\VippsAgreements $vippsAgreement
   *   The account of the user logged in.
   */
  public function __construct(VippsAgreements $vippsAgreement) {
    $this->vippsAgreement = $vippsAgreement;
  }

}
