<?php

namespace Drupal\vipps_recurring_payments_commerce\Plugin\Commerce\PaymentMethodType;

use Drupal\commerce_payment\Plugin\Commerce\PaymentMethodType\PaymentMethodTypeBase;
use Drupal\entity\BundleFieldDefinition;

/**
 * Provides the Vipps payment method type.
 *
 * @CommercePaymentMethodType(
 *   id = "vipps_payment_method",
 *   label = @Translation("Vipps"),
 *   create_label = @Translation("Vipps Phone Number"),
 * )
 */

class VippsPaymentMethod extends PaymentMethodTypeBase {

  /**
   * @inheritDoc
   */
  public function buildLabel(\Drupal\commerce_payment\Entity\PaymentMethodInterface $payment_method) {
    return $this->t('Vipps payment Method', []);
  }

  /**
   * {@inheritdoc}
   */
  public function buildFieldDefinitions() {
    $fields = parent::buildFieldDefinitions();

    $fields['phone_number'] = BundleFieldDefinition::create('string')
      ->setLabel(t('Phone Number'))
      ->setDescription(t('The Vipps hone Number'))
      ->setRequired(TRUE);

    $fields['agreement_title'] = BundleFieldDefinition::create('string')
      ->setLabel(t('Product name'))
      ->setDescription(t('Product title'))
      ->setRequired(TRUE);

    $fields['agreement_description'] = BundleFieldDefinition::create('string')
      ->setLabel(t('Product description'))
      ->setDescription(t('Product description'))
      ->setRequired(TRUE);

    return $fields;
  }
}
