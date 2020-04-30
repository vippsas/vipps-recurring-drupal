<?php


namespace Drupal\vipps_recurring_payments_commerce\Plugin\Commerce\PaymentGateway;


use Drupal\commerce_payment\PaymentMethodTypeManager;
use Drupal\commerce_payment\PaymentTypeManager;
use Drupal\commerce_payment\Plugin\Commerce\PaymentGateway\OnsitePaymentGatewayBase;
use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\vipps_recurring_payments\Service\VippsHttpClient;
use Drupal\vipps_recurring_payments\Service\VippsService;
use Symfony\Component\DependencyInjection\ContainerInterface;

abstract class BaseVippsPaymentGateway extends OnsitePaymentGatewayBase {

  /**
   * @var \Drupal\vipps_recurring_payments\Service\VippsHttpClient
   */
  protected $httpClient;

  /**
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * @var \Drupal\vipps_recurring_payments\Service\VippsService
   */
  protected $vippsService;

  /**
   * BaseVippsPaymentGateway constructor.
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
    parent::__construct($configuration, $plugin_id, $plugin_definition, $entity_type_manager, $payment_type_manager, $payment_method_type_manager, $time);
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
        'charge_retry_days' => $config->get('charge_retry_days'),
      ] + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    parent::submitConfigurationForm($form, $form_state);
    if (!$form_state->getErrors()) {
      $values = $form_state->getValue($form['#parents']);
      $this->configuration['charge_interval'] = $values['charge_interval'];
    }
  }

  /**
   * Gets the Vipps service.
   */
  protected function getRequestStorageFactory() {
    return \Drupal::service('vipps_recurring_payments:request_storage_factory');
  }

}
