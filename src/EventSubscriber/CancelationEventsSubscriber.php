<?php

namespace Drupal\vipps_recurring_payments\EventSubscriber;

use Drupal\vipps_recurring_payments\Event\UserRequestCancelationEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Class EntityTypeSubscriber.
 *
 * @package Drupal\vipps_recurring_payments\EventSubscriber
 */
class CancelationEventsSubscriber implements EventSubscriberInterface {

  /**
   * @inheritDoc
   */
  public static function getSubscribedEvents() {
    return [
      UserRequestCancelationEvent::CONFIRM_REQUEST => 'confirmRequest',
    ];
  }

  /**
   * Subscribe to the comfirm request cancelation event dispatched.
   *
   * @param \Drupal\vipps_recurring_payments\Event\UserRequestCancelationEvent $event
   *   Dat event object yo.
   */
  public function confirmRequest(UserRequestCancelationEvent $event) {
    /**
     * @todo implement the email sending
     */
  }
}
