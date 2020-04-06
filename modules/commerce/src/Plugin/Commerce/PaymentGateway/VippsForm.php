<?php

namespace Drupal\vipps_recurring_payments_commerce\Plugin\Commerce\PaymentGateway;

use Drupal\commerce_payment\Entity\PaymentInterface;
use Drupal\commerce_payment\Exception\DeclineException;
use Drupal\commerce_payment\Exception\InvalidRequestException;
use Drupal\commerce_payment\Exception\PaymentGatewayException;
use Drupal\commerce_payment\Exception\SoftDeclineException;
use Drupal\commerce_payment\Plugin\Commerce\PaymentGateway\SupportsAuthorizationsInterface;
use Drupal\commerce_payment\Plugin\Commerce\PaymentGateway\SupportsRefundsInterface;
use Drupal\commerce_order\Entity\OrderInterface;
use Drupal\commerce_payment\PaymentMethodTypeManager;
use Drupal\commerce_payment\PaymentTypeManager;
use Drupal\commerce_payment\Plugin\Commerce\PaymentGateway\OffsitePaymentGatewayBase;
use Drupal\commerce_payment\Plugin\Commerce\PaymentGateway\SupportsVoidsInterface;
use Drupal\Component\Datetime\TimeInterface;
use Drupal\Component\Render\FormattableMarkup;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\vipps_recurring_payments\Form\SettingsForm;
use Drupal\vipps_recurring_payments\Service\VippsHttpClient;
use Drupal\vipps_recurring_payments\Service\VippsService;
use http\Client\Response;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Drupal\commerce_price\Price;
use zaporylie\Vipps\Exceptions\VippsException;

/**
 * Provides the Nets payment gateway.
 *
 * @CommercePaymentGateway(
 *   id = "vipss_recurring_checkout",
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
    return [
        'client_id' => '',
        'subscription_key_authorization' => '',
        'client_secret' => '',
        'subscription_key_payment' => '',
        'serial_number' => '',
        'prefix' => '',
      ] + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);

    $configFactory = $this->configFactory->getEditable(SettingsForm::SETTINGS);
    $rowData = $configFactory->getRawData();

    $form['msn'] = [
      '#type' => 'textfield',
      '#required' => true,
      '#maxlength' => 10,
      '#title' => $this->t('MSN'),
      '#default_value' => $this->configuration['msn'] ?? $rowData['msn'],
      '#description' => $this->t('Get your API keys from your Vipps developer portal.'),
      '#weight' => 6,
    ];

    $form['access_token'] = [
      '#type' => 'textfield',
      '#required' => true,
      '#maxlength' => 64,
      '#title' => $this->t('Ocp-Apim-Subscription-Key-Access-Token'),
      '#default_value' =>  $this->configuration['access_token'] ?? $rowData['access_token'],
      '#description' => $this->t('Get your API keys from your Vipps developer portal.'),
      '#weight' => 7,
    ];

    $form['subscription_key'] = [
      '#type' => 'textfield',
      '#required' => true,
      '#maxlength' => 64,
      '#title' => $this->t('Ocp-Apim-Subscription-Key-Ecom'),
      '#default_value' => $this->configuration['subscription_key'] ?? $rowData['subscription_key'],
      '#description' => $this->t('Get your API keys from your Vipps developer portal.'),
      '#weight' => 8,
    ];

    $form['client_id'] = [
      '#type' => 'textfield',
      '#required' => true,
      '#maxlength' => 64,
      '#title' => $this->t('Client id'),
      '#default_value' => $this->configuration['client_id'] ?? $rowData['client_id'],
      '#description' => $this->t('Get your API keys from your Vipps developer portal.'),
      '#weight' => 9,
    ];

    $form['client_secret'] = [
      '#type' => 'textfield',
      '#required' => true,
      '#maxlength' => 64,
      '#title' => $this->t('Secret Key'),
      '#default_value' => $this->configuration['client_secret'] ?? $rowData['client_secret'],
      '#description' => $this->t('Get your API keys from your Vipps developer portal.'),
      '#weight' => 10,
    ];

    // Test credentials
    $form['test_env'] = [
      '#type' => 'details',
      '#title' => $this->t('Test environment'),
      '#collapsible' => TRUE,
      '#collapsed' => FALSE,
      '#weight' => 11,
    ];

    $form['test_env']['test_msn'] = [
      '#type' => 'textfield',
      '#maxlength' => 10,
      '#title' => $this->t('Test MSN'),
      '#default_value' => $this->configuration['test_msn'] ?? $rowData['test_msn'],
      '#description' => $this->t('Get your API keys from your Vipps developer portal.'),
    ];

    $form['test_env']['test_access_token'] = [
      '#type' => 'textfield',
      '#maxlength' => 64,
      '#title' => $this->t('Test Access Token'),
      '#default_value' => $this->configuration['test_access_token'] ?? $rowData['test_access_token'],
      '#description' => $this->t('Get your API keys from your Vipps developer portal.'),
    ];

    $form['test_env']['test_subscription_key'] = [
      '#type' => 'textfield',
      '#maxlength' => 64,
      '#title' => $this->t('Test Subscription Key'),
      '#default_value' => $this->configuration['test_subscription_key'] ?? $rowData['test_subscription_key'],
      '#description' => $this->t('Get your API keys from your Vipps developer portal.'),
    ];

    $form['test_env']['test_client_id'] = [
      '#type' => 'textfield',
      '#maxlength' => 64,
      '#title' => $this->t('Test Client ID'),
      '#default_value' => $this->configuration['test_client_id'] ?? $rowData['test_client_id'],
      '#description' => $this->t('Get your API keys from your Vipps developer portal.'),
    ];

    $form['test_env']['test_client_secret'] = [
      '#type' => 'textfield',
      '#maxlength' => 64,
      '#title' => $this->t('Test Secret Key'),
      '#default_value' => $this->configuration['test_client_secret'] ?? $rowData['test_client_secret'],
      '#description' => $this->t('Get your API keys from your Vipps developer portal.'),
    ];

    $form['charge_retry_days'] = [
      '#type' => 'number',
      '#required' => true,
      '#title' => $this->t('Retry days'),
      '#default_value' => $this->configuration['charge_retry_days'] ?? $rowData['charge_retry_days'],
      '#weight' => 2,
      '#description' => $this->t('Vipps will retry the charge for the number of days specified in "Retry days". If 0 it will be failed after the first attempt.')
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
      $this->configuration['test_msn'] = $values['test_env']['test_msn'];
      $this->configuration['test_access_token'] = $values['test_env']['test_access_token'];
      $this->configuration['test_subscription_key'] = $values['test_env']['test_subscription_key'];
      $this->configuration['test_client_id'] = $values['test_env']['test_client_id'];
      $this->configuration['test_client_descret'] = $values['test_env']['test_client_descret'];

      $this->configuration['msn'] = $values['msn'];
      $this->configuration['access_token'] = $values['access_token'];
      $this->configuration['subscription_key'] = $values['subscription_key'];
      $this->configuration['client_id'] = $values['client_id'];
      $this->configuration['client_secret'] = $values['client_secret'];
      $this->configuration['charge_retry_days'] = $values['charge_retry_days'];
    }
  }

  /**
   * {@inheritdoc}
   * @throws \Exception
   */
  public function onReturn(OrderInterface $order, Request $request) {
    $agreementId = $order->getData('agreementId');
    $payment_storage = $this->entityTypeManager->getStorage('commerce_payment');
    $matching_payments = $payment_storage->loadByProperties(['remote_id' => $agreementId, 'order_id' => $order->id()]);
    $message_variables = [
      '%oid' => $order->id(),
    ];

    if (count($matching_payments) !== 1) {
      \Drupal::logger('vipps_recurring_commerce')->error(
        t('Order %oid: More than one matching payment found', $message_variables)
      );
      throw new PaymentGatewayException('More than one matching payment found');
    }

    /** @var \Drupal\commerce_payment\Entity\PaymentInterface $matching_payment */
    $matching_payment = reset($matching_payments);

    // Get agreement status
    $agreementStatus = $this->vippsService->agreementStatus($agreementId);
    $message_variables['%os'] = $agreementStatus;
    \Drupal::logger('vipps_recurring_commerce')->info(
      t('Order %oid status: %os', $message_variables)
    );

    switch ($agreementStatus) {
      case 'PENDING':
        $matching_payment->setState('authorization');
        $matching_payment->save();
        break;

      case 'ACTIVE':
        $matching_payment->setState('completed');
        $matching_payment->save();
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

      default:
        \Drupal::logger('vipps_recurring_commerce')->error(
          t('Order %oid: Oooops, something went wrong.', $message_variables)
        );
        throw new PaymentGatewayException("Oooops, something went wrong.");
        break;
    }
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
        t('Order %oid: Void operation failed.', [
          '%oid' => $order->id(),
        ])
      );
      throw new DeclineException($exception->getMessage());
    }

    $payment->setState('authorization_voided');
    $payment->save();
    \Drupal::logger('vipps_recurring_commerce')->info(
      t('Order %oid: Payment voided.', [
        '%oid' => $order->id(),
      ])
    );
  }

  /**
   * {@inheritdoc}
   */
  public function capturePayment(PaymentInterface $payment, Price $amount = NULL) {
    return false;
  }

  /**
   * {@inheritdoc}
   */
  public function refundPayment(PaymentInterface $payment, Price $amount = NULL) {
    // Validate.
    $this->assertPaymentState($payment, ['completed', 'partially_refunded']);

    // Let's do some refunds.
    parent::assertRefundAmount($payment, $amount);

    $remote_id = $payment->getRemoteId();
    $number = $amount->multiply(100)->getNumber();
    try {
      $service = \Drupal::service('vipps_recurring_payments.make_charges');
    }
    catch (\Exception $exception) {
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
    $payment->save();

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
  public function onNotify(Request $request) {
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
      t('Order %oid status: $os', [
        '%oid' => $order->id(),
        '%os' => $content['transactionInfo']['status'],
      ])
    );
    switch ($content['transactionInfo']['status']) {
      case 'RESERVED':
        $matching_payment->setState('authorization');
        break;

      case 'SALE':
        $matching_payment->setState('completed');
        break;

      case 'RESERVE_FAILED':
      case 'SALE_FAILED':
      case 'CANCELLED':
      case 'REJECTED':
        // @todo: There is no corresponding state in payment workflow but it's
        // still better to keep the payment with invalid state than delete it
        // entirely.
        $matching_payment->setState('failed');
        break;

      default:
        \Drupal::logger('vipps_recurring_commerce')->critical('Data: @data', ['@data' => $content]);
        return new Response('', Response::HTTP_I_AM_A_TEAPOT);
    }
    $matching_payment->save();

    return new Response('', Response::HTTP_OK);
  }
}