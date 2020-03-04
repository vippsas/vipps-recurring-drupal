<?php

namespace Drupal\vipps_recurring_payments\Entity;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\RevisionLogInterface;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\Core\Entity\EntityPublishedInterface;

/**
 * Provides an interface for defining Vipps agreements entities.
 *
 * @ingroup vipps_recurring_payments
 */
interface VippsAgreementsInterface extends ContentEntityInterface, RevisionLogInterface, EntityChangedInterface, EntityPublishedInterface {

  /**
   * Add get/set methods for your configuration properties here.
   */

  /**
   * Gets the Vipps agreements agreement_id.
   *
   * @return string
   *   Vipps Agreement_id.
   */
  public function getAgreementId();

  /**
   * Sets the Vipps agreements agreement_id.
   *
   * @param string $agreement_id
   *   Vipsp agreement_id.
   *
   * @return VippsAgreementsInterface
   *   The called Vipps agreements entity.
   */
  public function setAgreementId($agreement_id);

  /**
   * Gets the Vipps mobile number.
   *
   * @return integer
   *   Users mobile number used for signing agreement.
   */
  public function getMobile();

  /**
   * Sets the Vipps mobile number.
   *
   * @param integer $mobile
   *   Users mobile number used for signing agreement.
   *
   * @return VippsAgreementsInterface
   */
  public function setMobile($mobile);

  /**
   * Gets the Amount (price) of the agreement.
   *
   * @return integer
   *   Agreements amount/price
   */
  public function getPrice();

  /**
   * Sets the Amount (price) of the agreement.
   *
   * @param integer $price
   *   Users price number used for signing agreement.
   *
   * @return VippsAgreementsInterface
   */
  public function setPrice($price);

  /**
   * Gets the status of the agreement.
   *
   * @return string
   *   Agreements status
   */
  public function getStatus();

  /**
   * Sets the status of the agreement.
   *
   * @param string $status
   *   Agreements status.
   *
   * Possible values:
   * - PENDING
   * - ACTIVE
   * - STOPPED
   * - EXPIRED
   *
   * @return VippsAgreementsInterface
   */
  public function setStatus($status);

  /**
   * Gets the Vipps agreements creation timestamp.
   *
   * @return int
   *   Creation timestamp of the Vipps agreements.
   */
  public function getCreatedTime();

  /**
   * Sets the Vipps agreements creation timestamp.
   *
   * @param int $timestamp
   *   The Vipps agreements creation timestamp.
   *
   * @return VippsAgreementsInterface
   *   The called Vipps agreements entity.
   */
  public function setCreatedTime($timestamp);

  /**
   * Gets the Vipps agreements revision creation timestamp.
   *
   * @return int
   *   The UNIX timestamp of when this revision was created.
   */
  public function getRevisionCreationTime();

  /**
   * Sets the Vipps agreements revision creation timestamp.
   *
   * @param int $timestamp
   *   The UNIX timestamp of when this revision was created.
   *
   * @return VippsAgreementsInterface
   *   The called Vipps agreements entity.
   */
  public function setRevisionCreationTime($timestamp);

  /**
   * Gets the Vipps agreements revision author.
   *
   * @return \Drupal\user\UserInterface
   *   The user entity for the revision author.
   */
  public function getRevisionUser();

  /**
   * Sets the Vipps agreements revision author.
   *
   * @param int $uid
   *   The user ID of the revision author.
   *
   * @return VippsAgreementsInterface
   *   The called Vipps agreements entity.
   */
  public function setRevisionUserId($uid);

}
