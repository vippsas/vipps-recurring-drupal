<?php

namespace Drupal\vipps_recurring_payments\Entity;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\RevisionLogInterface;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\Core\Entity\EntityPublishedInterface;

/**
 * Provides an interface for defining Periodic charges entities.
 *
 * @ingroup vipps_recurring_payments
 */
interface PeriodicChargesInterface extends ContentEntityInterface, RevisionLogInterface, EntityChangedInterface, EntityPublishedInterface {

  /**
   * Add get/set methods for your configuration properties here.
   */

  /**
   * Gets the Periodic charges ID.
   *
   * @return string
   *   Name of the Periodic charges.
   */
  public function getChargeId();

  /**
   * Sets the Periodic charges ID.
   *
   * @param string $id
   *   The Periodic charges ID.
   *
   * @return PeriodicChargesInterface
   *   The called Periodic charges entity.
   */
  public function setChargeId($id);

  /**
   * Gets the Periodic charges price.
   *
   * @return string
   *   Name of the Monthly charge price.
   */
  public function getPrice();

  /**
   * Sets the Periodic charges price.
   *
   * @param string $price
   *   The Periodic charges price.
   *
   * @return PeriodicChargesInterface
   *   The called Periodic charges entity.
   */
  public function setPrice($price);

  /**
   * Gets the Periodic charges price.
   *
   * @return string
   *   Name of the Monthly charge parent ID which is agreement.
   */
  public function getParentId();

  /**
   * Sets the Periodic charges parent.
   *
   * @param string $parent
   *   The Periodic charges parent ID.
   *
   * @return PeriodicChargesInterface
   *   The called Periodic charges entity.
   */
  public function setParentId($parent);

  /**
   * Gets the Periodic charges description.
   *
   * @return string
   *   Name of the Monthly charge parent ID which is agreement.
   */
  public function getDescription();

  /**
   * Sets the Periodic charges description.
   *
   * @param string $description
   *   The Periodic charges description.
   *
   * @return PeriodicChargesInterface
   *   The called Periodic charges entity.
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
   * Gets the Periodic charges creation timestamp.
   *
   * @return int
   *   Creation timestamp of the Periodic charges.
   */
  public function getCreatedTime();

  /**
   * Sets the Periodic charges creation timestamp.
   *
   * @param int $timestamp
   *   The Periodic charges creation timestamp.
   *
   * @return PeriodicChargesInterface
   *   The called Periodic charges entity.
   */
  public function setCreatedTime($timestamp);

  /**
   * Gets the Periodic charges revision creation timestamp.
   *
   * @return int
   *   The UNIX timestamp of when this revision was created.
   */
  public function getRevisionCreationTime();

  /**
   * Sets the Periodic charges revision creation timestamp.
   *
   * @param int $timestamp
   *   The UNIX timestamp of when this revision was created.
   *
   * @return PeriodicChargesInterface
   *   The called Periodic charges entity.
   */
  public function setRevisionCreationTime($timestamp);

  /**
   * Gets the Periodic charges revision author.
   *
   * @return \Drupal\user\UserInterface
   *   The user entity for the revision author.
   */
  public function getRevisionUser();

  /**
   * Sets the Periodic charges revision author.
   *
   * @param int $uid
   *   The user ID of the revision author.
   *
   * @return PeriodicChargesInterface
   *   The called Periodic charges entity.
   */
  public function setRevisionUserId($uid);

}
