<?php

declare(strict_types=1);

namespace Drupal\vipps_recurring_payments\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class SettingsForm
 *
 * @package Drupal\vipps_recurring_payments\Form
 */
class SettingsForm extends ConfigFormBase{

  public const SETTINGS = 'vipps_recurring_payments.settings';

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      static::SETTINGS,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'vipps_recurring_admin_settings';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    $form = array_merge($form, $this->getFormCustomAttributes());

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {

    $configFactory = $this->configFactory->getEditable(static::SETTINGS);

    foreach (array_keys($this->getFormCustomAttributes()) as $attribute) {
      $configFactory->set($attribute, $form_state->getValue($attribute));
    }

    $configFactory->save();

    parent::submitForm($form, $form_state);
  }

  private function getFormCustomAttributes():array
  {
    $config = $this->config(static::SETTINGS);

    return [
      'msn' => [
        '#type' => 'textfield',
        '#required' => true,
        '#maxlength' => 10,
        '#title' => $this->t('MSN'),
        '#default_value' => $config->get('msn'),
      ],
      'access_token' => [
        '#type' => 'textfield',
        '#required' => true,
        '#maxlength' => 64,
        '#title' => $this->t('Ocp-Apim-Subscription-Key-Access-Token'),
        '#default_value' => $config->get('access_token'),
      ],
      'subscription_key' => [
        '#type' => 'textfield',
        '#required' => true,
        '#maxlength' => 64,
        '#title' => $this->t('Ocp-Apim-Subscription-Key-Ecom'),
        '#default_value' => $config->get('subscription_key'),
      ],
      'client_id' => [
        '#type' => 'textfield',
        '#required' => true,
        '#maxlength' => 64,
        '#title' => $this->t('Client id'),
        '#default_value' => $config->get('client_id'),
      ],
      'client_secret' => [
        '#type' => 'textfield',
        '#required' => true,
        '#maxlength' => 64,
        '#title' => $this->t('Client secret'),
        '#default_value' => $config->get('client_secret'),
      ],
      'base_url' => [
        '#type' => 'textfield',
        '#required' => true,
        '#maxlength' => 64,
        '#title' => $this->t('Request url'),
        '#default_value' => $config->get('base_url'),
      ],
      'merchant_redirect_url' => [
        '#type' => 'textfield',
        '#required' => true,
        '#maxlength' => 64,
        '#title' => $this->t('Merchant redirect url'),
        '#default_value' => $config->get('merchant_redirect_url'),
      ],
      'merchant_agreement_url' => [
        '#type' => 'textfield',
        '#required' => true,
        '#maxlength' => 64,
        '#title' => $this->t('Merchant redirect url'),
        '#default_value' => $config->get('merchant_agreement_url'),
      ],
      'initial_charge' => [
        '#type' => 'radios',
        '#title' => $this->t('Initial charge'),
        '#default_value' => $config->get('initial_charge') ?? 1,
        '#options' => [
          0 => $this->t('Off'),
          1 => $this->t('On'),
        ],
      ],
      'charge_retry_days' => [
        '#type' => 'number',
        '#required' => true,
        '#title' => $this->t('Retry days'),
        '#default_value' => $config->get('charge_retry_days') ?? 5,
      ],
      'sub_module' => [
        '#type' => 'radios',
        '#title' => $this->t('Sub module'),
        '#default_value' => $config->get('sub_module') ?? 'web_form',
        '#options' => [
          'web_form' => $this->t('Webform'),
          'commerce' => $this->t('Commerce'),
        ],
      ],
    ];
  }

}
