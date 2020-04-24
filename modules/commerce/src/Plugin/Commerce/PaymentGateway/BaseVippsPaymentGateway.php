<?php


namespace Drupal\vipps_recurring_payments_commerce\Plugin\Commerce\PaymentGateway;


use Drupal\commerce_payment\Plugin\Commerce\PaymentGateway\OnsitePaymentGatewayBase;
use Drupal\Core\Form\FormStateInterface;

abstract class BaseVippsPaymentGateway extends OnsitePaymentGatewayBase {

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
        'charge_interval' => '',
        'charge_retry_days' => $config->get('charge_retry_days'),
      ] + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);

    $form['charge_interval'] = [
      '#type' => 'radios',
      '#title' => $this->t('Charge frequency'),
      '#required' => true,
      '#default_value' => $this->configuration['charge_interval'] ?? 'monthly',
      '#description' => $this->t('How often to charge'),
      '#options' => [
        'daily' => t('Daily'),
        'weekly' => t('Weekly'),
        'monthly' => t('Monthly'),
        'yearly' => t('Yearly'),
      ],
    ];

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
  protected function getVippsService() {
    return \Drupal::service('vipps_recurring_payments:vipps_service');
  }

  /**
   * Gets the Vipps service.
   */
  protected function getVippsHttpClient() {
    return \Drupal::service('vipps_recurring_payments:http_client');
  }

  /**
   * Gets the Vipps service.
   */
  protected function getRequestStorageFactory() {
    return \Drupal::service('vipps_recurring_payments:request_storage_factory');
  }

}
