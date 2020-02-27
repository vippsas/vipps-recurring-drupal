<?php

declare(strict_types=1);

namespace Drupal\vipps_recurring_payments\Factory;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\vipps_recurring_payments\Form\SettingsForm;
use Drupal\vipps_recurring_payments\RequestStorage\CancelAgreement;
use Drupal\vipps_recurring_payments\RequestStorage\CreateChargeData;
use Drupal\vipps_recurring_payments\RequestStorage\DraftAgreementData;
use Drupal\vipps_recurring_payments\RequestStorage\RequestStorageInterface;
use Drupal\vipps_recurring_payments\Service\DelayManager;
use Drupal\vipps_recurring_payments\Service\VippsApiConfig;
use Drupal\vipps_recurring_payments\Entity\ProductSubscriptionInterface;
use Drupal\vipps_recurring_payments\Service\Mobile_Detect;

class RequestStorageFactory {

  protected $vippsApiConfig;

  protected $config;

  protected $delayManager;

  protected $mobileDetect;

  public function __construct(
      VippsApiConfig $vippsApiConfig,
      ConfigFactoryInterface $configFactory,
      DelayManager $delayManager,
      Mobile_Detect $mobileDetect
  ) {
    $this->vippsApiConfig = $vippsApiConfig;
    $this->config = $configFactory->getEditable(SettingsForm::SETTINGS);
    $this->delayManager = $delayManager;
    $this->mobileDetect = $mobileDetect;
  }

  public function buildDefaultDraftAgreement(
    ProductSubscriptionInterface $product,
    string $phone,
    array $redirectPageGetParams = []
  ):DraftAgreementData {
    return new DraftAgreementData(
      $product,
      $this->vippsApiConfig->getMerchantRedirectUrl($redirectPageGetParams),
      $this->vippsApiConfig->getMerchantAgreementUrl($redirectPageGetParams),
      $phone,
      $this->mobileDetect->isMobile(),
      boolval($this->config->get('initial_charge'))
    );
  }

  public function buildCreateChargeData(ProductSubscriptionInterface $product, \DateTime $dateTime):RequestStorageInterface {
    return new CreateChargeData(
      $product,
      $this->delayManager->getDayAfterTomorrow(),//two days in the future
      intval($this->config->get('charge_retry_days'))
    );
  }

  public function buildCreateCancelData(ProductSubscriptionInterface $product):RequestStorageInterface {
    return new CancelAgreement($product);
  }
}
