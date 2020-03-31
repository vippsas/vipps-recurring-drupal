<?php

namespace Drupal\vipps_recurring_payments_commerce\Plugin\Commerce\PaymentGateway;

use Drupal\commerce_payment\Entity\PaymentInterface;
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
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Drupal\commerce_price\Price;

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
   * @param \Psr\Log\LoggerInterface $logger
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityTypeManagerInterface $entity_type_manager, PaymentTypeManager $payment_type_manager, PaymentMethodTypeManager $payment_method_type_manager, TimeInterface $time, VippsHttpClient $httpClient, ConfigFactoryInterface $configFactory) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $entity_type_manager, $payment_type_manager, $payment_method_type_manager, $time);
    $this->httpClient = $httpClient;
    $this->configFactory = $configFactory;
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
      $container->get('config.factory')
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
    // Verify payment method
    $payment_method = $order->get('field_payment_method')->value;

    var_dump($request);
    die();

    $remote_id = $request->get('transactionId') ?? null;
    $response_code = $request->get('responseCode') ?? $request->get('status');
    $capture = $request->get('capture');

    if($payment_method === 'avtale_giro_bank_id') {
      $message_variables = [
        '%oid' => $order->id(),
        '%rc' => $response_code,
      ];

      if (empty($response_code)) {
        throw new PaymentGatewayException(new FormattableMarkup('Return from Nets has wrong values for order: %oid and responseCode: %rc.', $message_variables));
      }

      if($response_code === 'cancel') {
        $message_variables['reason'] = "Canceled";
        $this->logger->error('There was a problem with payment for order %oid, reason: %reason', $message_variables);
        throw new PaymentGatewayException('Error at payment gateway.');
      } else if($response_code === 'error') {
        $message_variables['reason'] = "Error";
        $this->logger->error('There was a problem with payment for order %oid, reason: %reason', $message_variables);
        throw new PaymentGatewayException('Error at payment gateway.');
      }

      // We cannot rely on data we receive from NETS as there is no authorization,
      // checksum or hash. We will retrieve transaction status from NETS API.
      //$payment_settings = $this->configuration;
      //$nets_transaction = $this->nets->queryTransactionInvoice($payment_settings);

    } else {
      // @todo: Check transaction ID mismatch.
      if ($this->tempStore->get('transaction_id') !== $remote_id) {
        throw new PaymentGatewayException(new FormattableMarkup('Mismatch between transaction ID in user session %session and transaction ID returned by NETS %nets.', ['%session' => $this->tempStore->get('transaction_id'), '%nets' => $remote_id]));
      }

      $message_variables = [
        '%oid' => $order->id(),
        '%tid' => $remote_id,
        '%rc' => $response_code,
      ];

      if (empty($remote_id) || empty($response_code)) {
        throw new PaymentGatewayException(new FormattableMarkup('Return from Nets has wrong values for order: %oid, transactionId: %tid and responseCode: %rc.', $message_variables));
      }

      // We cannot rely on data we receive from NETS as there is no authorization,
      // checksum or hash. We will retrieve transaction status from NETS API.
      $payment_settings = $this->configuration;
      $nets_transaction = $this->nets->queryTransaction($payment_settings, $remote_id);

      if (isset($nets_transaction->ErrorLog)
        && isset($nets_transaction->ErrorLog->PaymentError)
        && isset($nets_transaction->ErrorLog->PaymentError->ResponseText)) {

        $message_variables['%reason'] = $nets_transaction->ErrorLog->PaymentError->ResponseText;
        $this->logger->error('There was a problem with payment for order %oid, reason: %reason', $message_variables);
        throw new PaymentGatewayException('Error at payment gateway.');
      }
    }

    $action = $capture === '1' ? 'SALE' : 'AUTH';

    /** @var PaymentInterface $payment */
    $payment = $this->entityTypeManager->getStorage('commerce_payment')->create([
      'state' => 'new',
      'amount' => $order->getTotalPrice(),
      'payment_gateway' => $this->entityId,
      'order_id' => $order->id(),
      'remote_state' => $response_code,
      'remote_id' => $remote_id,
      // We set this one so payment which hasn't been save can read a balance.
      'refunded_amount' => new Price(0, $order->getTotalPrice()->getCurrencyCode()),
    ]);

    if($payment_method !== 'avtale_giro_bank_id') {
      try {
        $this->nets->processTransaction($payment, $action);
      } catch(\Exception $e) {
        $this->tempStore->delete('transaction_id');
        throw new PaymentGatewayException("Authorization failed.");
      }
    }

    $payment->setState($action === 'SALE' ? 'completed' : 'authorization');
    $payment->setAuthorizedTime($this->time->getCurrentTime());
    $payment->save();

    // Delete transaction if from the session now that we have it in entity.
    $this->tempStore->delete('transaction_id');
  }

  /**
   * {@inheritdoc}
   */
  public function voidPayment(PaymentInterface $payment, Price $amount = NULL) {
    $this->assertPaymentState($payment, ['authorization']);

    try {
      // Void the payment.
      $this->nets->processTransaction($payment, 'ANNUL');
    }
    catch (\Exception $e) {
      throw new SoftDeclineException(t('Unable to void payment. Message: @message', ['@message' => $e->getMessage()]));
    }

    $payment->setState('authorization_voided');
    $payment->save();
  }

  /**
   * {@inheritdoc}
   */
  public function capturePayment(PaymentInterface $payment, Price $amount = NULL) {
    $this->assertPaymentState($payment, ['authorization']);

    // If not specified, capture the entire amount.
    $amount = $amount ?: $payment->getAmount();
    $this->assertCaptureAmount($payment, $amount);

    if ($amount->lessThan($payment->getAmount())) {
      /** @var \Drupal\commerce_payment\Entity\PaymentInterface $parent_payment */
      $parent_payment = $payment;
      $payment = $parent_payment->createDuplicate();
    }

    try {
      $this->nets->processTransaction($payment, 'CAPTURE', $this->toMinorUnits($amount));
    }
    catch (\Exception $e) {
      throw new SoftDeclineException(t('Unable to capture payment. Message @message', array('@message' => $e->getMessage())));
    }

    // Set transaction status and amount to the one captured.
    $payment->setState('completed');
    $payment->setAmount($amount);
    $payment->save();

    // Update parent payment if one exists.
    if (isset($parent_payment)) {
      $parent_payment->setAmount($parent_payment->getAmount()->subtract($amount));
      if ($parent_payment->getAmount()->isZero()) {
        $parent_payment->setState('authorization_voided');
      }
      $parent_payment->save();
    }
  }

  /**
   * {@inheritdoc}
   */
  public function refundPayment(PaymentInterface $payment, Price $amount = NULL) {
    $this->assertPaymentState($payment, ['completed', 'partially_refunded']);

    // If not specified, refund the entire amount.
    $amount = $amount ?: $payment->getAmount();
    $this->assertRefundAmount($payment, $amount);

    try {
      $this->nets->processTransaction($payment, 'CREDIT', $this->toMinorUnits($amount));
    }
    catch (\Exception  $e) {
      throw new SoftDeclineException(t('Unable to credit payment. Message: @message',
        ['@message' => $e->getMessage()]));
    }

    // Set the state.
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

}