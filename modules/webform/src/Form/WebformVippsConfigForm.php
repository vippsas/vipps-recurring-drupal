<?php

namespace Drupal\vipps_recurring_payments_webform\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\vipps_recurring_payments\Form\SettingsForm;

class WebformVippsConfigForm extends SettingsForm
{
  public function buildForm(array $form, FormStateInterface $form_state)
  {
    $form = array_merge($form, $this->getFormCustomAttributes());

    return ConfigFormBase::buildForm($form, $form_state);
  }

  public function getFormCustomAttributes(): array
  {
    $parentAttributes = parent::getFormCustomAttributes();
    $currentAttributes = [
      'charge_interval' => [
        '#type' => 'select',
        '#title' => $this->t('Charge interval'),
        '#required' => true,
        '#weight' => 4,
        '#default_value' => $this->config->get('charge_interval') ?? 'monthly',
        '#options' => [
          'yearly' => $this->t('Yearly'),
          'monthly' => $this->t('Monthly'),
          'weekly' => $this->t('Weekly'),
          'daily' => $this->t('Daily'),
          ],
        '#description' => 'How often make charges',
      ],
      'agreement_title' => [
        '#type' => 'textfield',
        '#title' => $this->t('Agreement title'),
        '#default_value' => $this->config->get('agreement_title') ?? $this->t('Agreement vipps'),
        '#required' => true,
        '#weight' => 1,
        '#description' => $this->t('The request parameter when creating an agreement'),
      ],
      'agreement_description' => [
        '#type' => 'textfield',
        '#title' => $this->t('Agreement description'),
        '#default_value' => $this->config->get('agreement_description') ?? $this->t('Agreement vipps description'),
        '#required' => true,
        '#weight' => 1,
        '#description' => $this->t('The request parameter when creating an agreement'),
      ],
    ];

    $allAttributes = array_merge($currentAttributes, $parentAttributes);
    usort($allAttributes,"cmp");
    return $allAttributes;
  }

  private function cmp($a, $b) {
    return $a['weight'] - $b['weight'];
  }
}
