<?php

namespace Drupal\vipps_recurring_payments\Form;

use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a form for deleting a Monthly charges revision.
 *
 * @ingroup vipps_recurring_payments
 */
class MonthlyChargesRevisionDeleteForm extends ConfirmFormBase {

  /**
   * The Monthly charges revision.
   *
   * @var \Drupal\vipps_recurring_payments\Entity\MonthlyChargesInterface
   */
  protected $revision;

  /**
   * The Monthly charges storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $monthlyChargesStorage;

  /**
   * The database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $connection;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    $instance = parent::create($container);
    $instance->monthlyChargesStorage = $container->get('entity_type.manager')->getStorage('monthly_charges');
    $instance->connection = $container->get('database');
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'monthly_charges_revision_delete_confirm';
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return $this->t('Are you sure you want to delete the revision from %revision-date?', [
      '%revision-date' => format_date($this->revision->getRevisionCreationTime()),
    ]);
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    return new Url('entity.monthly_charges.version_history', ['monthly_charges' => $this->revision->id()]);
  }

  /**
   * {@inheritdoc}
   */
  public function getConfirmText() {
    return $this->t('Delete');
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $monthly_charges_revision = NULL) {
    $this->revision = $this->MonthlyChargesStorage->loadRevision($monthly_charges_revision);
    $form = parent::buildForm($form, $form_state);

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->MonthlyChargesStorage->deleteRevision($this->revision->getRevisionId());

    $this->logger('content')->notice('Monthly charges: deleted %title revision %revision.', ['%title' => $this->revision->label(), '%revision' => $this->revision->getRevisionId()]);
    $this->messenger()->addMessage(t('Revision from %revision-date of Monthly charges %title has been deleted.', ['%revision-date' => format_date($this->revision->getRevisionCreationTime()), '%title' => $this->revision->label()]));
    $form_state->setRedirect(
      'entity.monthly_charges.canonical',
       ['monthly_charges' => $this->revision->id()]
    );
    if ($this->connection->query('SELECT COUNT(DISTINCT vid) FROM {monthly_charges_field_revision} WHERE id = :id', [':id' => $this->revision->id()])->fetchField() > 1) {
      $form_state->setRedirect(
        'entity.monthly_charges.version_history',
         ['monthly_charges' => $this->revision->id()]
      );
    }
  }

}
