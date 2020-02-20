<?php

declare(strict_types=1);

namespace Drupal\vipps_recurring_payments\Form;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class SettingsForm
 *
 * @package Drupal\vipps_recurring_payments\Form
 */
class SettingsForm extends ConfigFormBase{

  public const SETTINGS = 'vipps_recurring_payments.settings';

  protected $config;

  public function __construct(ConfigFactoryInterface $config_factory)
  {
    parent::__construct($config_factory);

    $this->config = $this->config(static::SETTINGS);
  }

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

  protected function getFormCustomAttributes():array
  {


    return [
      'msn' => [
        '#type' => 'textfield',
        '#maxlength' => 10,
        '#title' => $this->t('MSN'),
        '#default_value' => $this->config->get('msn'),
        '#description' => $this->t('Get your API keys from your Vipps developer portal.'),
      ],
      'access_token' => [
        '#type' => 'textfield',
        '#maxlength' => 64,
        '#title' => $this->t('Live Access Token'),
        '#default_value' => $this->config->get('access_token'),
        '#description' => $this->t('Get your API keys from your Vipps developer portal.'),
      ],
      'subscription_key' => [
        '#type' => 'textfield',
        '#maxlength' => 64,
        '#title' => $this->t('Live Subscription Key'),
        '#default_value' => $this->config->get('subscription_key'),
        '#description' => $this->t('Get your API keys from your Vipps developer portal.'),
      ],
      'client_id' => [
        '#type' => 'textfield',
        '#maxlength' => 64,
        '#title' => $this->t('Live Client ID'),
        '#default_value' => $this->config->get('client_id'),
        '#description' => $this->t('Get your API keys from your Vipps developer portal.'),
      ],
      'client_secret' => [
        '#type' => 'textfield',
        '#maxlength' => 64,
        '#title' => $this->t('Live Secret Key'),
        '#default_value' => $this->config->get('client_secret'),
        '#description' => $this->t('Get your API keys from your Vipps developer portal.'),
      ],
      'test_msn' => [
        '#type' => 'textfield',
        '#required' => true,
        '#maxlength' => 10,
        '#title' => $this->t('Test MSN'),
        '#default_value' => $this->config->get('test_msn'),
        '#description' => $this->t('Get your API keys from your Vipps developer portal.'),
      ],
      'test_access_token' => [
        '#type' => 'textfield',
        '#required' => true,
        '#maxlength' => 64,
        '#title' => $this->t('Test Live Access Token'),
        '#default_value' => $this->config->get('test_access_token'),
        '#description' => $this->t('Get your API keys from your Vipps developer portal.'),
      ],
      'test_subscription_key' => [
        '#type' => 'textfield',
        '#required' => true,
        '#maxlength' => 64,
        '#title' => $this->t('Test Live Subscription Key'),
        '#default_value' => $this->config->get('test_subscription_key'),
        '#description' => $this->t('Get your API keys from your Vipps developer portal.'),
      ],
      'test_client_id' => [
        '#type' => 'textfield',
        '#required' => true,
        '#maxlength' => 64,
        '#title' => $this->t('Test Live Client ID'),
        '#default_value' => $this->config->get('test_client_id'),
        '#description' => $this->t('Get your API keys from your Vipps developer portal.'),
      ],
      'test_client_secret' => [
        '#type' => 'textfield',
        '#required' => true,
        '#maxlength' => 64,
        '#title' => $this->t('Test Live Secret Key'),
        '#default_value' => $this->config->get('test_client_secret'),
        '#description' => $this->t('Get your API keys from your Vipps developer portal.'),
      ],
      'initial_charge' => [
        '#type' => 'radios',
        '#title' => $this->t('Initial charge'),
        '#default_value' => $this->config->get('initial_charge') ?? 1,
        '#options' => [
          0 => $this->t('Off'),
          1 => $this->t('On'),
        ],
      ],
      'charge_retry_days' => [
        '#type' => 'number',
        '#required' => true,
        '#title' => $this->t('Retry days'),
        '#default_value' => $this->config->get('charge_retry_days') ?? 5,
      ],
      'sub_module' => [
        '#type' => 'radios',
        '#title' => $this->t('Sub module'),
        '#default_value' => $this->config->get('sub_module') ?? 'web_form',
        '#options' => [
          'web_form' => $this->t('Webform'),
          'commerce' => $this->t('Commerce'),
        ],
      ],
      'test_mode' => [
        '#type' => 'radios',
        '#title' => $this->t('Enable Test Mode'),
        '#default_value' => $this->config->get('test_mode') ?? true,
        '#options' => [
          true => $this->t('Yes'),
          false => $this->t('No'),
        ],
      ],
    ];
  }

}
