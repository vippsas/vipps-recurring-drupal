<?php

namespace Drupal\vipps_recurring_payments\Repository;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\vipps_recurring_payments\Entity\ProductSubscriptionInterface;
use Drupal\vipps_recurring_payments\Entity\VippsProductSubscription;
use Drupal\vipps_recurring_payments\Form\SettingsForm;

class WebFormProductSubscriptionRepository implements ProductSubscriptionRepositoryInterface
{
  private $config;

  public function __construct(ConfigFactoryInterface $configFactory)
  {
    $this->config = $configFactory->get(SettingsForm::SETTINGS);
  }

  public function getProduct(string $agreementId = null): ProductSubscriptionInterface
  {
    return new VippsProductSubscription(
      $this->config->get('base_interval'),
      intval($this->config->get('base_interval_count')),
      $this->config->get('agreement_title'),
      $this->config->get('agreement_description')
    );
  }
}
