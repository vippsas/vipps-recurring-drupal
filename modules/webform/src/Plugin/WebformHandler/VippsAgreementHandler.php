<?php

namespace Drupal\vipps_recurring_payments_webform\Plugin\WebformHandler;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Routing\TrustedRedirectResponse;
use Drupal\vipps_recurring_payments\Entity\VippsProductSubscription;
use Drupal\vipps_recurring_payments\Factory\RequestStorageFactory;
use Drupal\vipps_recurring_payments\Service\VippsHttpClient;
use Drupal\vipps_recurring_payments_webform\Repository\WebformSubmissionRepository;
use Drupal\webform\Plugin\WebformHandlerBase;
use Drupal\webform\WebformSubmissionConditionsValidatorInterface;
use Drupal\webform\WebformSubmissionInterface;
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
 *   cardinality = \Drupal\webform\Plugin\WebformHandlerInterface::CARDINALITY_UNLIMITED,
 *   results = \Drupal\webform\Plugin\WebformHandlerInterface::RESULTS_PROCESSED,
 *   submission = \Drupal\webform\Plugin\WebformHandlerInterface::SUBMISSION_OPTIONAL,
 * )
 */
class VippsAgreementHandler extends WebformHandlerBase
{

  private $httpClient;

  private $requestStorageFactory;

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
    $this->submissionRepository = $submissionRepository;
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return parent::defaultConfiguration() + [
        'charge_interval'       => 'monthly',
        'initial_charge'        => 1,
        'agreement_title'       => '',
        'agreement_description' => '',
      ];
  }


  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {

    $form['vipps_handler'] = [
      '#type' => 'details',
      '#title' => $this->t('Recurring configurations'),
      '#open' => TRUE,
    ];

    // Charge interval
    $form['vipps_handler']['charge_interval'] = [
      '#type' => 'select',
      '#title' => $this->t('Charge interval'),
      '#required' => true,
      '#options' => [
        'monthly' => $this->t('Monthly'),
        'weekly' => $this->t('Weekly'),
        'daily' => $this->t('Daily'),
        'yearly' => $this->t('Yearly'),
      ],
      '#default_value' => $this->configuration['charge_interval'],
      '#description' => 'How often make charges'
    ];

    // Initial charge
    $form['vipps_handler']['initial_charge'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Initial charge'),
      '#description' => $this->t('Will be performed the initial charge when creating an agreement'),
      '#default_value' => $this->configuration['initial_charge'],
      '#return_value' => TRUE,
    ];

    // Agreement title
    $form['vipps_handler']['agreement_title'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Agreement title'),
      '#description' => $this->t('The request parameter when creating an agreement'),
      '#default_value' => $this->configuration['agreement_title'],
      '#return_value' => TRUE,
    ];

    // Agreement description
    $form['vipps_handler']['agreement_description'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Agreement description'),
      '#description' => $this->t('The request parameter when creating an agreement'),
      '#default_value' => $this->configuration['agreement_description'],
      '#return_value' => TRUE,
    ];

    $form = parent::buildConfigurationForm($form, $form_state);

    return $this->setSettingsParents($form);
  }

  /**
   * {@inheritdoc}
   */
  public function validateConfigurationForm(array &$form, FormStateInterface $form_state) {
    parent::validateConfigurationForm($form, $form_state);

//    $values = $form_state->getValues();

//    $form_state->setError($form['settings']['vipps']['agreement_title'], $values['vipps']['agreement_title']);
//    $form_state->setValues($values);
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    parent::submitConfigurationForm($form, $form_state);
    $this->applyFormStateToConfiguration($form_state);
  }


  public function confirmForm(array &$form, FormStateInterface $formState, WebformSubmissionInterface $webFormSubmission)
  {
    try {
      $intervalService = \Drupal::service('vipps_recurring_payments:charge_intervals');
      $intervals = $intervalService->getIntervals($this->configuration['charge_interval']);

      $product = new VippsProductSubscription(
        $intervals['base_interval'],
        intval($intervals['base_interval_count']),
        $this->configuration['agreement_title'],
        $this->configuration['agreement_description'],
        $this->configuration['initial_charge']
      );
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
      $submissionRepository
    );
  }
}
