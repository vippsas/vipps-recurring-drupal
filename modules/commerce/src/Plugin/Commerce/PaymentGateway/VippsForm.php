<?php

namespace Drupal\vipps_recurring_payments_commerce\Plugin\Commerce\PaymentGateway;

use Drupal\advancedqueue\Entity\Queue;
use Drupal\advancedqueue\Job;
use Drupal\commerce_payment\Entity\PaymentInterface;
use Drupal\commerce_payment\Exception\DeclineException;
use Drupal\commerce_payment\Exception\InvalidRequestException;
use Drupal\commerce_payment\Exception\PaymentGatewayException;
use Drupal\commerce_payment\Plugin\Commerce\PaymentGateway\SupportsAuthorizationsInterface;
use Drupal\commerce_payment\Plugin\Commerce\PaymentGateway\SupportsRefundsInterface;
use Drupal\commerce_order\Entity\OrderInterface;
use Drupal\commerce_payment\PaymentMethodTypeManager;
use Drupal\commerce_payment\PaymentTypeManager;
use Drupal\commerce_payment\Plugin\Commerce\PaymentGateway\OffsitePaymentGatewayBase;
use Drupal\commerce_payment\Plugin\Commerce\PaymentGateway\SupportsVoidsInterface;
use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\vipps_recurring_payments\Entity\PeriodicCharges;
use Drupal\vipps_recurring_payments\Entity\VippsAgreements;
use Drupal\vipps_recurring_payments\Entity\VippsProductSubscription;
use Drupal\vipps_recurring_payments\Service\VippsHttpClient;
use Drupal\vipps_recurring_payments\Service\VippsService;
use http\Client\Response;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Drupal\commerce_price\Price;

/**
 * Provides the Nets payment gateway.
 *
 * @CommercePaymentGateway(
 *   id = "vipps_recurring_checkout",
 *   label = "Vipps Recurring Checkout",
 *   display_label = "Vipps Recurring Checkout",
 *    forms = {
 *     "offsite-payment" = "Drupal\vipps_recurring_payments_commerce\PluginForm\OffsiteRedirect\VippsRedirectForm",
 *   },
 *   requires_billing_information = FALSE,
 * )
 */
class VippsForm extends OffsitePaymentGatewayBase implements SupportsVoidsInterface, SupportsAuthorizationsInterface, SupportsRefundsInterface {

  /**
   * Service used for making API calls using Nets Checkout library.
   *
   * @var \Drupal\commerce_nets\NetsManager
   */
  protected $nets;

  /**
   * The logger.
   *
   * @var \Psr\Log\LoggerInterface
   */
  protected $logger;

  /**
   * Session storage.
   *
   * @var \Drupal\Core\TempStore\PrivateTempStore
   */
  protected $tempStore;

  /**
   * The log storage.
   *
   * @var \Drupal\commerce_log\LogStorageInterface
   */
  protected $logStorage;

  /**
   * @var VippsHttpClient
   */
  protected $httpClient;

  /**
   * @var ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * @var VippsService
   */
  private $vippsService;

  /**
   * NetsCheckout constructor.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\commerce_payment\PaymentTypeManager $payment_type_manager
   *   The payment type manager.
   * @param \Drupal\commerce_payment\PaymentMethodTypeManager $payment_method_type_manager
   *   The payment method type manager.
   * @param \Drupal\Component\Datetime\TimeInterface $time
   *   The time.
   * @param VippsHttpClient $httpClient
   * @param ConfigFactoryInterface $configFactory
   * @param VippsService $vippsService
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityTypeManagerInterface $entity_type_manager, PaymentTypeManager $payment_type_manager, PaymentMethodTypeManager $payment_method_type_manager, TimeInterface $time, VippsHttpClient $httpClient, ConfigFactoryInterface $configFactory, VippsService $vippsService) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $entity_type_manager, $payment_type_manager, $payment_method_type_manager, $time, $vippsService);
    $this->httpClient = $httpClient;
    $this->configFactory = $configFactory;
    $this->vippsService = $vippsService;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager'),
      $container->get('plugin.manager.commerce_payment_type'),
      $container->get('plugin.manager.commerce_payment_method_type'),
      $container->get('datetime.time'),
      $container->get('vipps_recurring_payments:http_client'),
      $container->get('config.factory'),
      $container->get('vipps_recurring_payments:vipps_service')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    $config = \Drupal::config('vipps_recurring_payments.settings');

    return [
        'msn' => $config->get('msn'),
        'access_token' => $config->get('access_token'),
        'subscription_key' => $config->get('subscription_key'),
        'client_id' => $config->get('client_id'),
        'client_secret' => $config->get('client_secret'),
        'frequency' => '',
        'charge_retry_days' => $config->get('charge_retry_days'),
      ] + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);

    $form['frequency'] = [
      '#type' => 'radios',
      '#title' => $this->t('Charge frequency'),
      '#required' => true,
      '#default_value' => $this->configuration['frequency'] ?? 'daily',
      '#description' => $this->t('Define the charges frequency.'),
      '#options' => [
        'daily' => t('Daily'),
        'weekly' => t('Weekly'),
        'monthly' => t('Monthly'),
        'yearly' => t('Yearly'),
      ],
    ];

    // Test credentials
    $form['test_env'] = [
      '#type' => 'details',
      '#title' => $this->t('Test environment'),
      '#collapsible' => TRUE,
      '#collapsed' => FALSE,
      '#weight' => 11,
    ];

    $form['test_env']['test_frequency'] = [
      '#type' => 'radios',
      '#title' => $this->t('Test Charge frequency'),
      '#required' => true,
      '#default_value' => $this->configuration['test_frequency'] ?? 'daily',
      '#description' => $this->t('Define the charges frequency.'),
      '#options' => [
        'daily' => t('Daily'),
        'weekly' => t('Weekly'),
        'monthly' => t('Monthly'),
        'yearly' => t('Yearly'),
      ],
    ];

    return array_merge($form);
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    parent::submitConfigurationForm($form, $form_state);
    if (!$form_state->getErrors()) {
      $values = $form_state->getValue($form['#parents']);
      $config = \Drupal::config('vipps_recurring_payments.settings')->getRawData();

      $this->configuration['test_msn'] = $config['test_msn'];
      $this->configuration['test_access_token'] = $config['test_access_token'];
      $this->configuration['test_subscription_key'] = $config['test_subscription_key'];
      $this->configuration['test_client_id'] = $config['test_client_id'];
      $this->configuration['test_client_secret'] = $config['test_client_secret'];
      $this->configuration['test_frequency'] = $values['test_env']['test_frequency'];

      $this->configuration['msn'] = $config['msn'];
      $this->configuration['access_token'] = $config['access_token'];
      $this->configuration['subscription_key'] = $config['subscription_key'];
      $this->configuration['client_id'] = $config['client_id'];
      $this->configuration['client_secret'] = $config['client_secret'];
      $this->configuration['frequency'] = $values['frequency'];
      $this->configuration['charge_retry_days'] = $config['charge_retry_days'];
    }
  }

  /**
   * {@inheritdoc}
   * @throws \Exception
   */
  public function onReturn(OrderInterface $order, Request $request) {
    $payment_storage = $this->entityTypeManager->getStorage('commerce_payment');
    $agreementId = $order->getData('vipps_current_transaction');
    $matching_payments = $payment_storage->loadByProperties(['remote_id' => $agreementId, 'order_id' => $order->id()]);
    $message_variables = [
      '%oid' => $order->id(),
    ];

    if (count($matching_payments) !== 1) {
      \Drupal::logger('vipps_recurring_commerce')->error(
        'Order %oid: More than one matching payment found', $message_variables
      );
      throw new PaymentGatewayException('More than one matching payment found');
    }

    /** @var \Drupal\commerce_payment\Entity\PaymentInterface $matching_payment */
    $matching_payment = reset($matching_payments);

    // Get agreement status
    $agreementStatus = $this->vippsService->agreementStatus($agreementId);
    $message_variables['%os'] = $agreementStatus;
    \Drupal::logger('vipps_recurring_commerce')->info(
      'Order %oid status: %os', $message_variables
    );

    //Force to pending
    //$agreementStatus = 'PENDING';

    switch ($agreementStatus) {
      case 'PENDING':
        $matching_payment->setState('authorization');
        $order->getState()->applyTransitionById('place');
        break;

      case 'ACTIVE':
        $matching_payment->setState('completed');
        $matching_payment->save();
        $order->getState()->applyTransitionById('place');
        break;

      case 'STOPPED':
      case 'EXPIRED':
      case 'CANCEL':
      case 'REJECTED':
        // @todo: There is no corresponding state in payment workflow but it's
        // still better to keep the payment with invalid state than delete it
        // entirely.
        $matching_payment->setState('failed');
        $order->getState()->applyTransitionById('cancel');

      default:
        \Drupal::logger('vipps_recurring_commerce')->error(
          'Order %oid: Oooops, something went wrong.', $message_variables
        );
        throw new PaymentGatewayException("Oooops, something went wrong.");
        break;
    }

    $matching_payment->save();
    $order->save();

    $this->confirmAgreement($agreementId);
  }

  /**
   * Vipps treats onReturn and onCancel in the same way.
   *
   * {@inheritdoc}
   */
  public function onCancel(OrderInterface $order, Request $request) {
    parent::onReturn($order, $request);
  }

  /**
   * {@inheritdoc}
   */
  public function voidPayment(PaymentInterface $payment) {
    $this->assertPaymentState($payment, ['authorization']);
    $remote_id = $payment->getRemoteId();
    $order = $payment->getOrder();
    try {
      $this->vippsService->cancelAgreement([$remote_id]);
    }
    catch (\Exception $exception) {
      \Drupal::logger('vipps_recurring_commerce')->error(
        'Order %oid: Void operation failed.', [
          '%oid' => $order->id()
          ]
      );
      throw new DeclineException($exception->getMessage());
    }

    $payment->setState('authorization_voided');
    $payment->save();
    \Drupal::logger('vipps_recurring_commerce')->info(
      'Order %oid: Payment voided.', [
        '%oid' => $order->id(),
      ]
    );
  }

  /**
   * {@inheritdoc}
   */
  public function capturePayment(PaymentInterface $payment, Price $amount = NULL) {
    return true;
    // Assert things.
    $this->assertPaymentState($payment, ['authorization']);
    // If not specified, capture the entire amount.
    $amount = $amount ?: $payment->getAmount();

//    if ($amount->lessThan($payment->getAmount())) {
//      /** @var \Drupal\commerce_payment\Entity\PaymentInterface $parent_payment */
//      $parent_payment = $payment;
//      $payment = $parent_payment->createDuplicate();
//    }
//    $agreementId = $payment->getRemoteId();
//
//    try {
//      $this->vippsService->captureCharges($agreementId, (int)$amount->multiply(100)->getNumber());
//    }
//    catch (VippsException $exception) {
//      if ($exception->getError()->getCode() == 61) {
//        // Insufficient funds.
//        // Check if order has already been captured and for what amount,.
//
//      }
//      throw new DeclineException($exception->getMessage());
//    }
//    catch (\Exception $exception) {
//      throw new DeclineException($exception->getMessage());
//    }
//
//    $payment->setState('completed');
//    $payment->setAmount($amount);
//    $payment->save();
  }

  /**
   * {@inheritdoc}
   */
  public function refundPayment(PaymentInterface $payment, Price $amount = NULL) {
    return true;
    /*$agreementId = $payment->getRemoteId();

    try {
      $this->vippsService->refundCharges($agreementId, $amount->getNumber());
    } catch (\Exception $exception) {
      throw new DeclineException($exception->getMessage());
    }

    $old_refunded_amount = $payment->getRefundedAmount();
    $new_refunded_amount = $old_refunded_amount->add($amount);
    if ($new_refunded_amount->lessThan($payment->getAmount())) {
      $payment->setState('partially_refunded');
    }
    else {
      $payment->setState('refunded');
    }

    $payment->setRefundedAmount($new_refunded_amount);
    $payment->save();*/
  }

  /**
   * Asserts that the refund amount is valid.
   *
   * @param \Drupal\commerce_payment\Entity\PaymentInterface $payment
   *   The payment.
   * @param \Drupal\commerce_price\Price $capture_amount
   *   The amount to be captured.
   *
   * @throws \Drupal\commerce_payment\Exception\InvalidRequestException
   *   Thrown when the capture amount is larger than the payment amount.
   */
  protected function assertCaptureAmount(PaymentInterface $payment, Price $capture_amount) {
    $amount = $payment->getAmount();
    if ($capture_amount->greaterThan($amount)) {
      throw new InvalidRequestException(sprintf("Can't capture more than %s.", $amount->__toString()));
    }
  }

  /**
   * {@inheritdoc}
   *
   * Checks for status changes, and saves it.
   */
  public function onNotify(Request $request)
  {
    // @todo: Validate order and payment existance.
    /** @var \Drupal\commerce_payment\Entity\PaymentGatewayInterface $commerce_payment_gateway */
    $commerce_payment_gateway = $request->attributes->get('commerce_payment_gateway');

    /** @var \Drupal\commerce_order\Entity\OrderInterface $order */
    $order = $request->attributes->get('order');
    if (!$order instanceof OrderInterface) {
      return new Response('', Response::HTTP_FORBIDDEN);
    }

    $payment_storage = $this->entityTypeManager->getStorage('commerce_payment');

    // Validate authorization header.
    if ($order->getData('vipps_auth_key') !== $request->headers->get('Authorization')) {
      return new Response('', Response::HTTP_FORBIDDEN);
    }

    $content = $request->getContent();

    $message_variables = [
      '%oid' => $order->id(),
    ];

    $remote_id = $request->attributes->get('remote_id');
    $matching_payments = $payment_storage->loadByProperties(['remote_id' => $remote_id, 'payment_gateway' => $commerce_payment_gateway->id()]);
    if (count($matching_payments) !== 1) {
      \Drupal::logger('vipps_recurring_commerce')->critical(t('Order %oid: More than one matching payment found', $message_variables));
      return new Response('', Response::HTTP_FORBIDDEN);
    }
    /** @var \Drupal\commerce_payment\Entity\PaymentInterface $matching_payment */
    // $old_state = $matching_payment->getState()->getId();
    $matching_payment = reset($matching_payments);

    $content = json_decode($content, TRUE);
    \Drupal::logger('vipps_recurring_commerce')->error(
      'Order %oid status: $os', [
        '%oid' => $order->id(),
        '%os' => $content['transactionInfo']['status'],
      ]
    );

    switch ($content['transactionInfo']['status']) {
      case 'PENDING':
        $matching_payment->setState('authorization');
        $matching_payment->save();
        $order->getState()->applyTransitionById('place');
        $order->save();
        break;

      case 'ACTIVE':
        $matching_payment->setState('completed');
        $matching_payment->save();
        $order->getState()->applyTransitionById('place');
        $order->save();
        break;

      case 'STOPPED':
      case 'EXPIRED':
      case 'CANCEL':
      case 'REJECTED':
        // @todo: There is no corresponding state in payment workflow but it's
        // still better to keep the payment with invalid state than delete it
        // entirely.
        $matching_payment->setState('failed');
        $matching_payment->save();
        $order->getState()->applyTransitionById('cancel');
        $order->save();

      default:
        \Drupal::logger('vipps_recurring_commerce')->error(
          'Order %oid: Oooops, something went wrong.', $message_variables
        );
        throw new PaymentGatewayException("Oooops, something went wrong.");
        break;
    }

    $this->confirmAgreement($remote_id);
  }

  /**
   * @param $agreementId
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  private function confirmAgreement($agreementId) {
    // Add agreement to job queue
    $agreementData = $this->httpClient->getRetrieveAgreement(
      $this->httpClient->auth(),
      $agreementId
    );

    $message_variables = [
      '%aid' => $agreementId,
      '%as' => $agreementData->getStatus(),
    ];

    if(!$agreementData->isActive()) {
      \Drupal::logger('vipps_recurring_commerce')->error(
        'Order %oid: Agreement %aid has status %as', $message_variables
      );
      return;
    }

    $delayManager = \Drupal::service('vipps_recurring_payments:delay_manager');

    /**
     * Create a Node of vipps_agreement type
     */
    $agreementNode = new VippsAgreements([
      'type' => 'vipps_agreements',
    ], 'vipps_agreements');
    $agreementNode->set('status', 1);
    $agreementNode->setStatus($agreementData->getStatus());
    $agreementNode->setIntervals($this->configuration['frequency'] ?? 'MONTHLY');
    $agreementNode->setAgreementId($agreementId);
    $agreementNode->setMobile('');
    $agreementNode->setPrice($agreementData->getPrice());

    $agreementNode->save();
    $agreementNodeId = $agreementNode->id();

    /**
     * Store first charge as periodic_charges entity
     */
    $charges = $this->httpClient->getCharges(
      $this->httpClient->auth(),
      $agreementId
    );

    if (isset($charges)) {
      $chargeNode = new PeriodicCharges([
        'type' => 'periodic_charges',
      ], 'periodic_charges');
      $chargeNode->set('status', 1);
      $chargeNode->setChargeId($charges[0]->id);
      $chargeNode->setPrice($charges[0]->amount);
      $chargeNode->setParentId($agreementNodeId);
      $chargeNode->setStatus($charges[0]->status);
      $chargeNode->setDescription($charges[0]->description);
      $chargeNode->save();
    }

    $intervalService = \Drupal::service('vipps_recurring_payments:charge_intervals');
    $intervals = $intervalService->getIntervals($this->configuration['frequency']);

    /**
     * @todo update descriptions
     */
    $product = new VippsProductSubscription(
      $intervals['base_interval'],
      intval($intervals['base_interval_count']),
      t('Recurring payment: ') . $agreementData->getPrice(),
      t('Initial Charge'),
      boolval(TRUE)
    );
    $product->setPrice($agreementData->getPrice());

    $job = Job::create('create_charge_job', [
      'orderId' => $agreementId,
      'agreementNodeId' => $agreementNodeId
    ]);

    $queue = Queue::load('vipps_recurring_payments');
    $queue->enqueueJob($job, $delayManager->getCountSecondsToNextPayment($product));

    $message_variables['%aid'] = $agreementId;
    \Drupal::logger('vipps_recurring_commerce')->info(
      'Order %oid: Subscription %aid has been done successfully', $message_variables
    );
  }
}