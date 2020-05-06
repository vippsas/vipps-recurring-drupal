<?php

namespace Drupal\vipps_recurring_payments_commerce\PluginForm;

use Drupal\commerce_payment\PluginForm\PaymentMethodAddForm as BasePaymentMethodAddForm;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\entity\BundleFieldDefinition;

/**
 * Class VippsPaymentMethodAddForm
 */

class VippsPaymentMethodAddForm extends BasePaymentMethodAddForm implements ContainerInjectionInterface {

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);
    $form['payment_details'] = $this->buildForm($form['payment_details']);

    return $form;
  }

  private function buildForm($element) {
    $element['phone_number'] = [
      '#type' => 'textfield',
      '#title' => t('Phone number'),
      '#attributes' => ['autocomplete' => 'off'],
      '#required' => TRUE,
      '#maxlength' => 9,
      '#size' => 20,
    ];

    return $element;
  }
}
