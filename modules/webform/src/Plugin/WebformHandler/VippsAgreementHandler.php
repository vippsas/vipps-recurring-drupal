<?php

namespace Drupal\vipps_recurring_payments_webform\Plugin\WebformHandler;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Routing\TrustedRedirectResponse;
use Drupal\vipps_recurring_payments\Factory\RequestStorageFactory;
use Drupal\vipps_recurring_payments\Repository\ProductSubscriptionRepositoryInterface;
use Drupal\vipps_recurring_payments\Service\VippsHttpClient;
use Drupal\vipps_recurring_payments_webform\Repository\WebformSubmissionRepository;
use Drupal\webform\Annotation\WebformHandler;
use Drupal\webform\Plugin\WebformHandlerBase;
use Drupal\webform\WebformSubmissionConditionsValidatorInterface;
use Drupal\webform\WebformSubmissionInterface;
use Drupal\Core\Annotation\Translation;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * Form submission handler.
 *
 * @WebformHandler(
 *   id = "vipps_agreement_handler",
 *   label = @Translation("Vipps agreement handler"),
 *   category = @Translation("Webform Handler"),
 *   description = @Translation("This handler submits data to Vipps and stores agreement ID"),
 *   cardinality = \Drupal\webform\Plugin\WebformHandlerInterface::CARDINALITY_SINGLE,
 *   results = \Drupal\webform\Plugin\WebformHandlerInterface::RESULTS_PROCESSED,
 * )
 */
class VippsAgreementHandler extends WebformHandlerBase
{

  private $httpClient;

  private $requestStorageFactory;

  private $productSubscriptionRepository;

  private $submissionRepository;

  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    LoggerChannelFactoryInterface $logger_factory,
    ConfigFactoryInterface $config_factory,
    EntityTypeManagerInterface $entity_type_manager,
    WebformSubmissionConditionsValidatorInterface $conditions_validator,
    VippsHttpClient $httpClient,
    RequestStorageFactory $requestStorageFactory,
    ProductSubscriptionRepositoryInterface $productSubscriptionRepository,
    WebformSubmissionRepository $submissionRepository
  )
  {
    parent::__construct(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $logger_factory,
      $config_factory,
      $entity_type_manager,
      $conditions_validator
    );

    $this->httpClient = $httpClient;
    $this->requestStorageFactory = $requestStorageFactory;
    $this->productSubscriptionRepository = $productSubscriptionRepository;
    $this->submissionRepository = $submissionRepository;
  }

  public function confirmForm(array &$form, FormStateInterface $formState, WebformSubmissionInterface $webFormSubmission)
  {
    try {
      $product = $this->productSubscriptionRepository->getProduct();
      $product->setPrice($this->getAmount($formState));

      $draftAgreementResponse = $this->httpClient->draftAgreement(
        $this->httpClient->auth(),
        $this->requestStorageFactory->buildDefaultDraftAgreement(
          $product,
          $formState->getValue('phone'),
          ['submission_id' => $webFormSubmission->id()]
        )
      );

      $this->submissionRepository->setAgreementId($webFormSubmission, $draftAgreementResponse->getAgreementId());

      new TrustedRedirectResponse($draftAgreementResponse->getVippsConfirmationUrl(), 302);
      $redirect = new RedirectResponse($draftAgreementResponse->getVippsConfirmationUrl(), 302);
      $redirect->send();
    } catch (\Throwable $exception) {
      $this->loggerFactory->get('vipps')->error($exception->getMessage());
    }
  }

  private function getAmount(FormStateInterface $formState):?float
  {
    $amount = !empty($formState->getValue('amount_select')) ? $formState->getValue('amount_select') :
      $formState->getValue('amount');

    return floatval($amount);
  }

  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition)
  {
    /* @var $loggerFactory LoggerChannelFactoryInterface */
    $loggerFactory = $container->get('logger.factory');

    /* @var $configFactory ConfigFactoryInterface */
    $configFactory = $container->get('config.factory');

    /* @var $entityTypeManager EntityTypeManagerInterface */
    $entityTypeManager = $container->get('entity_type.manager');

    /* @var $conditionsValidator WebformSubmissionConditionsValidatorInterface */
    $conditionsValidator = $container->get('webform_submission.conditions_validator');

    /* @var $httpClient VippsHttpClient */
    $httpClient = $container->get('vipps_recurring_payments:http_client');

    /* @var RequestStorageFactory $requestStorageFactory */
    $requestStorageFactory = $container->get('vipps_recurring_payments:request_storage_factory');

    /* @var ProductSubscriptionRepositoryInterface $productSubscriptionRepository */
    $productSubscriptionRepository = $container->get('vipps_recurring_payments:product_subscription_repository');

    /* @var WebformSubmissionRepository $submissionRepository */
    $submissionRepository = $container->get('vipps_recurring_payments_webform:submission_repository');

    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $loggerFactory,
      $configFactory,
      $entityTypeManager,
      $conditionsValidator,
      $httpClient,
      $requestStorageFactory,
      $productSubscriptionRepository,
      $submissionRepository
    );
  }
}
