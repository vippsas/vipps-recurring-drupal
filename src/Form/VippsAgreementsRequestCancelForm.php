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
class VippsAgreementsRequestCancelForm extends ContentEntityConfirmFormBase {

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

    $this->messenger()->addMessage($this->t('Thank you. Cancellation is pending administrator approval'));
    $entity->set('agreement_status', 'Pending');
    $entity->save();

    $send_mail = new \Drupal\Core\Mail\Plugin\Mail\PhpMail(); // this is used to send HTML emails
    $from = 'drupal@frontkom.com';
    $to = 'cristina@frontkom.com';
    $message['headers'] = array(
      'content-type' => 'text/html',
      'MIME-Version' => '1.0',
      'reply-to' => $from,
      'from' => 'sender name <'.$from.'>'
    );
    $message['to'] = $to;
    $message['subject'] = "Subject Goes here !!!!!";

    $message['body'] = 'Hello,
    Thank you for reading this blog.';

    $send_mail->mail($message);
  }

  /**
   * {@inheritdoc}
   */
  public function getConfirmText() {
    return $this->t('Cancel Agreement');
  }
}
