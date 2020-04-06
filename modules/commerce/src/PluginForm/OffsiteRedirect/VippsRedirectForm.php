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
use Drupal\vipps_recurring_payments_commerce\Resolver\ChainOrderIdResolverInterface;
use Drupal\vipps_recurring_payments_commerce\Service\CommerceService;
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
   * @var ChainOrderIdResolverInterface
   */
  protected $chainOrderIdResolver;

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
   * @param ChainOrderIdResolverInterface $chainOrderIdResolver
   * @param EventDispatcherInterface $eventDispatcher
   */
  public function __construct(VippsHttpClient $httpClient, ChainOrderIdResolverInterface $chainOrderIdResolver, EventDispatcherInterface $eventDispatcher, RequestStorageFactory $requestStorageFactory) {
    $this->httpClient = $httpClient;
    $this->chainOrderIdResolver = $chainOrderIdResolver;
    $this->eventDispatcher = $eventDispatcher;
    $this->requestStorageFactory = $requestStorageFactory;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('vipps_recurring_payments:http_client'),
      $container->get('vipps_recurring_payments_commerce.chain_order_id_resolver'),
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

    // Create payment.
    //$payment->setRemoteId($settings['prefix'] . $this->chainOrderIdResolver->resolve());

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

    $intervalService = \Drupal::service('vipps_recurring_payments:charge_intervals');
    $intervals = $intervalService->getIntervals('monthly');

    $product = new VippsProductSubscription(
      $intervals['base_interval'],
      intval($intervals['base_interval_count']),
      t('Recurring donation: ') . (int)$order->total_price->getValue()[0]['number'] . 'Kr',
      substr($order->get('field_image_description')->value,0, 44),
      'true'
    );
    $product->setPrice($order->total_price->getValue()[0]['number']);

    try {
      /**
       * @todo change the intervals monthly
       */
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
      throw new PaymentGatewayException($exception->getMessage());
    }

    // If the payment was successfully created at remote host.
    $payment->setRemoteId($draftAgreementResponse->getAgreementId());
    $payment->save();
    if ($order_changed === TRUE) {
      $order->setData('agreementId', $draftAgreementResponse->getAgreementId());
      $order->save();
    }

    return $this->buildRedirectForm($form, $form_state, $url, []);
  }
}
