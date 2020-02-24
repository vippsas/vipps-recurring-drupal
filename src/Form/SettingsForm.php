<?php

declare(strict_types=1);

namespace Drupal\vipps_recurring_payments\Form;

use Drupal\Core\Config\Config;
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
    $configFactory->set('sub_module', 'web_form');//TODO remove it later

    foreach (array_keys($this->getFormCustomAttributes()) as $attribute) {
      if($attribute === 'test_mode') {
        $this->setTestValues($form_state, $configFactory);
      } else {
        $configFactory->set($attribute, $form_state->getValue($attribute));
      }
    }

    $this->setIntervals($form_state, $configFactory);
    $configFactory->save();

    parent::submitForm($form, $form_state);
  }

  protected function setTestValues(FormStateInterface $form_state, Config $configFactory): void
  {
    $configFactory->set('test_msn', $form_state->getValue('test_msn'));
    $configFactory->set('test_access_token', $form_state->getValue('test_access_token'));
    $configFactory->set('test_subscription_key', $form_state->getValue('test_subscription_key'));
    $configFactory->set('test_client_id', $form_state->getValue('test_client_id'));
    $configFactory->set('test_client_secret', $form_state->getValue('test_client_secret'));
  }

  protected function setIntervals(FormStateInterface $form_state, Config $configFactory): void
  {
    $chargeInterval = $form_state->getValue('charge_interval');
    if(!is_null($chargeInterval)) {
      switch ($chargeInterval) {
        case 'yearly':
          $configFactory->set('base_interval', 'MONTH');
          $configFactory->set('base_interval_count', 12);
          break;
        case 'monthly':
          $configFactory->set('base_interval', 'MONTH');
          $configFactory->set('base_interval_count', 1);
          break;
        case 'weekly':
          $configFactory->set('base_interval', 'WEEK');
          $configFactory->set('base_interval_count', 1);
          break;
        case 'daily':
          $configFactory->set('base_interval', 'DAY');
          $configFactory->set('base_interval_count', 1);
          break;
        default:
          throw new \Exception('Unsupported interval');
      }
    }
  }

  protected function getFormCustomAttributes():array
  {
    return [
      'test_mode' => [
        '#type' => 'radios',
        '#title' => $this->t('Enable Test Mode'),
        '#default_value' => $this->config->get('test_mode') ?? true,
        '#options' => [
          true => $this->t('Yes'),
          false => $this->t('No'),
        ],
        '#weight' => 0,
      ],
      'msn' => [
        '#type' => 'textfield',
        '#maxlength' => 10,
        '#title' => $this->t('MSN'),
        '#default_value' => $this->config->get('msn'),
        '#description' => $this->t('Get your API keys from your Vipps developer portal.'),
        '#weight' => 6,
      ],
      'access_token' => [
        '#type' => 'textfield',
        '#maxlength' => 64,
        '#title' => $this->t('Access Token'),
        '#default_value' => $this->config->get('access_token'),
        '#description' => $this->t('Get your API keys from your Vipps developer portal.'),
        '#weight' => 7,
      ],
      'subscription_key' => [
        '#type' => 'textfield',
        '#maxlength' => 64,
        '#title' => $this->t('Subscription Key'),
        '#default_value' => $this->config->get('subscription_key'),
        '#description' => $this->t('Get your API keys from your Vipps developer portal.'),
        '#weight' => 8,
      ],
      'client_id' => [
        '#type' => 'textfield',
        '#maxlength' => 64,
        '#title' => $this->t('Client ID'),
        '#default_value' => $this->config->get('client_id'),
        '#description' => $this->t('Get your API keys from your Vipps developer portal.'),
        '#weight' => 9,
      ],
      'client_secret' => [
        '#type' => 'textfield',
        '#maxlength' => 64,
        '#title' => $this->t('Secret Key'),
        '#default_value' => $this->config->get('client_secret'),
        '#description' => $this->t('Get your API keys from your Vipps developer portal.'),
        '#weight' => 10,
      ],
      'test_env' => [
        '#type' => 'details',
        '#title' => $this->t('Test environment'),
        '#collapsible' => TRUE,
        '#collapsed' => FALSE,
        '#weight' => 11,
        'test_msn' => [
          '#type' => 'textfield',
          '#maxlength' => 10,
          '#title' => $this->t('Test MSN'),
          '#default_value' => $this->config->get('test_msn'),
          '#description' => $this->t('Get your API keys from your Vipps developer portal.'),
        ],
        'test_access_token' => [
          '#type' => 'textfield',
          '#maxlength' => 64,
          '#title' => $this->t('Test Access Token'),
          '#default_value' => $this->config->get('test_access_token'),
          '#description' => $this->t('Get your API keys from your Vipps developer portal.'),
        ],
        'test_subscription_key' => [
          '#type' => 'textfield',
          '#maxlength' => 64,
          '#title' => $this->t('Test Subscription Key'),
          '#default_value' => $this->config->get('test_subscription_key'),
          '#description' => $this->t('Get your API keys from your Vipps developer portal.'),
        ],
        'test_client_id' => [
          '#type' => 'textfield',
          '#maxlength' => 64,
          '#title' => $this->t('Test Client ID'),
          '#default_value' => $this->config->get('test_client_id'),
          '#description' => $this->t('Get your API keys from your Vipps developer portal.'),
        ],
        'test_client_secret' => [
          '#type' => 'textfield',
          '#maxlength' => 64,
          '#title' => $this->t('Test Secret Key'),
          '#default_value' => $this->config->get('test_client_secret'),
          '#description' => $this->t('Get your API keys from your Vipps developer portal.'),
        ],
      ],
      'initial_charge' => [
        '#type' => 'radios',
        '#title' => $this->t('Initial charge'),
        '#default_value' => $this->config->get('initial_charge') ?? 1,
        '#options' => [
          0 => $this->t('Off'),
          1 => $this->t('On'),
        ],
        '#weight' => 2,
        '#description' => $this->t('Will be performed the initial charge when creating an agreement'),
      ],
      'charge_retry_days' => [
        '#type' => 'number',
        '#required' => true,
        '#title' => $this->t('Retry days'),
        '#default_value' => $this->config->get('charge_retry_days') ?? 5,
        '#weight' => 2,
        '#description' => $this->t('Vipps will retry the charge for the number of days specified in "Retry days". If 0 it will be failed after the first attempt.')
      ],
//      'sub_module' => [
//        '#type' => 'radios',
//        '#title' => $this->t('Sub module'),
//        '#default_value' => $this->config->get('sub_module') ?? 'web_form',
//        '#options' => [
//          'web_form' => $this->t('Webform'),
//          'commerce' => $this->t('Commerce'),
//        ],
//      ],
    ];
  }

}
