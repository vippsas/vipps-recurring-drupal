<?php

namespace Drupal\vipps_recurring_payments\Entity;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\RevisionLogInterface;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\Core\Entity\EntityPublishedInterface;

/**
 * Provides an interface for defining Monthly charges entities.
 *
 * @ingroup vipps_recurring_payments
 */
interface MonthlyChargesInterface extends ContentEntityInterface, RevisionLogInterface, EntityChangedInterface, EntityPublishedInterface {

  /**
   * Add get/set methods for your configuration properties here.
   */

  /**
   * Gets the Monthly charges ID.
   *
   * @return string
   *   Name of the Monthly charges.
   */
  public function getChargeId();

  /**
   * Sets the Monthly charges ID.
   *
   * @param string $id
   *   The Monthly charges ID.
   *
   * @return MonthlyChargesInterface
   *   The called Monthly charges entity.
   */
  public function setChargeId($id);

  /**
   * Gets the Monthly charges price.
   *
   * @return string
   *   Name of the Monthly charge price.
   */
  public function getPrice();

  /**
   * Sets the Monthly charges price.
   *
   * @param string $price
   *   The Monthly charges price.
   *
   * @return MonthlyChargesInterface
   *   The called Monthly charges entity.
   */
  public function setPrice($price);

  /**
   * Gets the Monthly charges price.
   *
   * @return string
   *   Name of the Monthly charge parent ID which is agreement.
   */
  public function getParentId();

  /**
   * Sets the Monthly charges parent.
   *
   * @param string $parent
   *   The Monthly charges parent ID.
   *
   * @return MonthlyChargesInterface
   *   The called Monthly charges entity.
   */
  public function setParentId($parent);

  /**
   * Gets the Monthly charges description.
   *
   * @return string
   *   Name of the Monthly charge parent ID which is agreement.
   */
  public function getDescription();

  /**
   * Sets the Monthly charges description.
   *
   * @param string $description
   *   The Monthly charges description.
   *
   * @return MonthlyChargesInterface
   *   The called Monthly charges entity.
   */
  public function setDescription($description);


  /**
   * Gets the status of the charge.
   *
   * @return string
   *   Charge status
   */
  public function getStatus();

  /**
   * Sets the status of the charge.
   *
   * @param string $status
   *   Charge status.
   *
   * Possible values:
   * - PENDING
   * - DUE
   * - CHARGED
   * - FAILED
   * - REFUNDED
   * - PARTIALLY_REFUNDED
   * - RESERVED
   *
   * @return VippsAgreementsInterface
   */
  public function setStatus($status);

  /**
   * Gets the Monthly charges creation timestamp.
   *
   * @return int
   *   Creation timestamp of the Monthly charges.
   */
  public function getCreatedTime();

  /**
   * Sets the Monthly charges creation timestamp.
   *
   * @param int $timestamp
   *   The Monthly charges creation timestamp.
   *
   * @return MonthlyChargesInterface
   *   The called Monthly charges entity.
   */
  public function setCreatedTime($timestamp);

  /**
   * Gets the Monthly charges revision creation timestamp.
   *
   * @return int
   *   The UNIX timestamp of when this revision was created.
   */
  public function getRevisionCreationTime();

  /**
   * Sets the Monthly charges revision creation timestamp.
   *
   * @param int $timestamp
   *   The UNIX timestamp of when this revision was created.
   *
   * @return MonthlyChargesInterface
   *   The called Monthly charges entity.
   */
  public function setRevisionCreationTime($timestamp);

  /**
   * Gets the Monthly charges revision author.
   *
   * @return \Drupal\user\UserInterface
   *   The user entity for the revision author.
   */
  public function getRevisionUser();

  /**
   * Sets the Monthly charges revision author.
   *
   * @param int $uid
   *   The user ID of the revision author.
   *
   * @return MonthlyChargesInterface
   *   The called Monthly charges entity.
   */
  public function setRevisionUserId($uid);

}
