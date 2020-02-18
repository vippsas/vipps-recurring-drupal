<?php

declare(strict_types=1);

namespace Drupal\vipps_recurring_payments_webform\Repository;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\webform\WebformSubmissionInterface;
use League\Container\Exception\NotFoundException;

class WebformSubmissionRepository
{
  protected $storage;

  public function __construct(EntityTypeManagerInterface $entityTypeManager)
  {
    $this->storage = $entityTypeManager->getStorage('webform_submission');
  }

  public function getById(int $id): WebformSubmissionInterface
  {
    /* @var $submission WebformSubmissionInterface */
    $submission = $this->storage->load($id);

    if(is_null($submission)) {
      throw new NotFoundException();
    }

    return $submission;
  }

  public function setStatus(WebformSubmissionInterface $entity, string $status): void
  {
    $entity->setElementData('agreement_status', $status);
    $entity->resave();
  }

  public function setAgreementId(WebformSubmissionInterface $entity, string $agreementId): void
  {
    $entity->setElementData('agreement_id', $agreementId);
    $entity->resave();
  }

}
