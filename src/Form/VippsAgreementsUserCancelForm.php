<?php

namespace Drupal\vipps_recurring_payments\Form;

use Drupal\Core\Entity\ContentEntityConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\vipps_recurring_payments\ResponseApiData\CancelAgreementResponse;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * Provides a form for user request to cancel Vipps agreements entities.
 *
 * @ingroup vipps_recurring_payments
 */
class VippsAgreementsUserCancelForm extends ContentEntityConfirmFormBase {

  /**
   * The Vipps agreements storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $vippsAgreementsStorage;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    $instance = parent::create($container);
    return $instance;
  }

  /**
   * Returns a unique string identifying the form.
   *
   * @return string
   *   The unique string identifying the form.
   */
  public function getFormId() {
    return 'user_cancel_form';
  }

  /**
   * @inheritDoc
   */
  public function getQuestion() {
    $entity = $this->entity;
    return $this->t('Are you sure you want to cancel the agreement %arg?', ['%arg' => $entity->label()]);
  }

  /**
   * @inheritDoc
   */
  public function getCancelUrl() {
    return new Url('vipps_recurring_payments.user_agreement_list', ['user' => \Drupal::currentUser()->id()]);
  }

  /**
   * @inheritDoc
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $entity = $this->entity;
    $form_state->setRedirect('vipps_recurring_payments.user_agreement_list', ['user' => \Drupal::currentUser()->id()]);

    try {
      $vippsService = \Drupal::service('vipps_recurring_payments:vipps_service');
      /** @var CancelAgreementResponse $response */
      $response = $vippsService->cancelAgreement([$entity->label()])->toArray();
    } catch (\Throwable $exception) {
      $this->messenger()->addError($this->t($exception->getMessage()));
      return;
    }

    if(sizeof($response["errors"]) > 0 ) {
      $this->messenger()->addError($this->t('Unable to cancel agreement %arg', ['%arg' => $entity->label()]));
      return;
    }

    $this->messenger()->addMessage($this->t('Agreement %arg canceled', ['%arg' => $entity->label()]));
    $entity->set('agreement_status', 'STOPPED');
    $entity->save();
  }

  /**
   * {@inheritdoc}
   */
  public function getConfirmText() {
    return $this->t('Cancel Agreement');
  }
}
