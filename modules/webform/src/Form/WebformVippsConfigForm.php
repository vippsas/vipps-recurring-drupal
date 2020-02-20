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
    return array_merge([
      'base_interval' => [
        '#type' => 'select',
        '#required' => true,
        '#title' => $this->t('Interval'),
        '#default_value' => $this->config->get('base_interval') ?? 'MONTH',
        '#options' => [
          'DAY' => $this->t('Day'),
          'WEEK' => $this->t('Week'),
          'MONTH' => $this->t('Month'),
        ],
        '#description' => 'Intervals are defined with a interval type MONTH, WEEK, or DAY and frequency as a count.
        E.g. Bi-weekly subscription: Interval = "WEEK", Interval count = "2"',
      ],
      'base_interval_count' => [
        '#type' => 'number',
        '#min' => 1,
        '#max' => 12,
        '#required' => true,
        '#title' => $this->t('Interval count'),
        '#default_value' => $this->config->get('base_interval_count') ?? 1,
        '#description' => 'Intervals are defined with a interval type MONTH, WEEK, or DAY and frequency as a count.
        E.g. Bi-weekly subscription: Interval = "WEEK", Interval count = "2"',
      ],
      'agreement_title' => [
        '#type' => 'textfield',
        '#title' => $this->t('Agreement title'),
        '#default_value' => $this->config->get('agreement_title') ?? $this->t('Agreement vipps'),
        '#required' => true,
      ],
      'agreement_description' => [
        '#type' => 'textfield',
        '#title' => $this->t('Agreement description'),
        '#default_value' => $this->config->get('agreement_description') ?? $this->t('Agreement vipps description'),
        '#required' => true,
      ],
    ], parent::getFormCustomAttributes());
  }
}
