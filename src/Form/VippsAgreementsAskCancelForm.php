<?php

namespace Drupal\vipps_recurring_payments\Form;

use Drupal\Core\Entity\ContentEntityConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\vipps_recurring_payments\ResponseApiData\CancelAgreementResponse;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * Provides a form for deleting Vipps agreements entities.
 *
 * @ingroup vipps_recurring_payments
 */
class VippsAgreementsAskCancelForm extends ContentEntityConfirmFormBase {

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
    $entity = $this->entity;
    return new Url('entity.vipps_agreements.collection');
  }

  /**
   * @inheritDoc
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $entity = $this->entity;
    $form_state->setRedirect('entity.vipps_agreements.canonical', ['vipps_agreements' => $entity->id()]);

    $this->messenger()->addMessage($this->t('Thank you. Cancellation is pending administrator approval'));
    $entity->set('agreement_status', 'Pending Cancellation');
    $entity->save();
  }

  /**
   * {@inheritdoc}
   */
  public function getConfirmText() {
    return $this->t('Cancel Agreement');
  }
}
