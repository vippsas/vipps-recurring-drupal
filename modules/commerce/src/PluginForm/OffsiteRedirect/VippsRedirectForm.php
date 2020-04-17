<?php

namespace Drupal\vipps_recurring_payments_commerce\PluginForm\OffsiteRedirect;

use Drupal;
use Drupal\commerce_payment\Entity\Payment;
use Drupal\commerce_payment\Exception\PaymentGatewayException;
use Drupal\commerce_payment\PluginForm\PaymentOffsiteForm as BasePaymentOffsiteForm;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\vipps_recurring_payments\Entity\VippsProductSubscription;
use Drupal\vipps_recurring_payments\Factory\RequestStorageFactory;
use Drupal\vipps_recurring_payments\Service\VippsHttpClient;
use Drupal\vipps_recurring_payments_commerce\Plugin\Commerce\PaymentGateway\VippsForm;
use Exception;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Class VippsCheckoutForm.
 *
 * Handles the initiation of vipps payments.
 */
class VippsRedirectForm extends BasePaymentOffsiteForm implements ContainerInjectionInterface {
  /**
   * @var VippsHttpClient
   */
  protected $httpClient;

  /**
   * @var EventDispatcherInterface
   */
  protected $eventDispatcher;

  /**
   * @var RequestStorageFactory
   */
  private $requestStorageFactory;

  /**
   * VippsLandingPageRedirectForm constructor.
   *
   * @param VippsHttpClient $httpClient
   * @param EventDispatcherInterface $eventDispatcher
   * @param RequestStorageFactory $requestStorageFactory
   */
  public function __construct(VippsHttpClient $httpClient, EventDispatcherInterface $eventDispatcher, RequestStorageFactory $requestStorageFactory) {
    $this->httpClient = $httpClient;
    $this->eventDispatcher = $eventDispatcher;
    $this->requestStorageFactory = $requestStorageFactory;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('vipps_recurring_payments:http_client'),
      $container->get('event_dispatcher'),
      $container->get('vipps_recurring_payments:request_storage_factory')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);

    /** @var Payment $payment */
    // When dumping here, we have a new entity, use that by default.
    $payment = $this->entity;
    /** @var VippsForm $plugin */
    $settings = $payment->getPaymentGateway()->getPluginConfiguration();

    $token = $this->httpClient->auth();

    // Save order.
    $order = $payment->getOrder();
    $order_changed = FALSE;
    if ($order->getData('vipps_auth_key') === NULL) {
      $order->setData('vipps_auth_key', $token);
      $order_changed = TRUE;
    }

    if ($order->getData('vipps_current_transaction') !== $payment->getRemoteId()) {
      $order->setData('vipps_current_transaction', $payment->getRemoteId());
      $order_changed = TRUE;
    }

    /**
     * @todo change frequency
     */
    $intervalService = \Drupal::service('vipps_recurring_payments:charge_intervals');
    $intervals = $intervalService->getIntervals($settings['frequency']);

    /**
     * @todo update the product description. We are using field_image_description
     */
    $product = new VippsProductSubscription(
      $intervals['base_interval'],
      intval($intervals['base_interval_count']),
      t('Recurring payment: ') . $order->total_price->getValue()[0]['currency_code'] . (int) $order->total_price->getValue()[0]['number'],
      t('Initial Charge'),
      'true'
    );
    $product->setPrice($order->total_price->getValue()[0]['number']);

    try {
      $draftAgreementResponse = $this->httpClient->draftAgreement(
        $token,
        $this->requestStorageFactory->buildDefaultDraftAgreement(
          $product,
          '',
          [
            'commerce_order' => $order->id(),
            'step' => 'payment'
          ]
        )
      );
      $url = $draftAgreementResponse->getVippsConfirmationUrl();
    }
    catch (Exception $exception) {
      \Drupal::logger('vipps_recurring_commerce')->error(
        'Order %oid: problems drafting the agreement', ['%oid' => $order->id()]
      );
      throw new PaymentGatewayException($exception->getMessage());
    }

    // If the payment was successfully created at remote host.
    $payment->setRemoteId($draftAgreementResponse->getAgreementId());
    $payment->save();
    if ($order_changed === TRUE) {
      $order->setData('vipps_current_transaction', $draftAgreementResponse->getAgreementId());
      $order->save();
    }

    return $this->buildRedirectForm($form, $form_state, $url, []);
  }
}
