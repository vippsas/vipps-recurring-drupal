<?php

namespace Drupal\vipps_recurring_payments\Form;

use Drupal\Core\Entity\ContentEntityConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a form for deleting Vipps agreements entities.
 *
 * @ingroup vipps_recurring_payments
 */
class VippsAgreementsCancelForm extends ContentEntityConfirmFormBase {

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
    return $this->t('Are you sure you want to cancel the agreement?', ['%arg' => $this->entity->label()]);
  }

  /**
   * @inheritDoc
   */
  public function getCancelUrl() {
    //return new Url('entity.vipps_agreements.canonical');
  }

  /**
   * @inheritDoc
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // TODO: Implement submitForm() method.
  }

  /**
   * {@inheritdoc}
   */
  public function getConfirmText() {
    return $this->t('Cancel Agreement');
  }
}
