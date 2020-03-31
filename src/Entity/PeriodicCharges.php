<?php

namespace Drupal\vipps_recurring_payments\Entity;

use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Entity\EditorialContentEntityBase;
use Drupal\Core\Entity\RevisionableInterface;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityPublishedTrait;
use Drupal\Core\Entity\EntityTypeInterface;

/**
 * Defines the Periodic charges entity.
 *
 * @ingroup vipps_recurring_payments
 *
 * @ContentEntityType(
 *   id = "periodic_charges",
 *   label = @Translation("Periodic charges"),
 *   handlers = {
 *     "storage" = "Drupal\vipps_recurring_payments\PeriodicChargesStorage",
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\vipps_recurring_payments\PeriodicChargesListBuilder",
 *     "views_data" = "Drupal\vipps_recurring_payments\Entity\PeriodicChargesViewsData",
 *
 *     "form" = {
 *       "default" = "Drupal\vipps_recurring_payments\Form\PeriodicChargesForm",
 *       "add" = "Drupal\vipps_recurring_payments\Form\PeriodicChargesForm",
 *       "edit" = "Drupal\vipps_recurring_payments\Form\PeriodicChargesForm",
 *       "delete" = "Drupal\vipps_recurring_payments\Form\PeriodicChargesDeleteForm",
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\vipps_recurring_payments\PeriodicChargesHtmlRouteProvider",
 *     },
 *     "access" = "Drupal\vipps_recurring_payments\PeriodicChargesAccessControlHandler",
 *   },
 *   base_table = "periodic_charges",
 *   revision_table = "periodic_charges_revision",
 *   revision_data_table = "periodic_charges_field_revision",
 *   translatable = FALSE,
 *   admin_permission = "administer periodic charges entities",
 *   entity_keys = {
 *     "id" = "id",
 *     "revision" = "vid",
 *     "published" = "status",
 *     "charge_id" = "charge_id",
 *     "charge_status" = "charge_status",
 *     "label" = "parent_id",
 *     "price" = "price",
 *     "description" = "description"
 *   },
 *   links = {
 *     "canonical" = "/admin/vipps/periodic_charges/{periodic_charges}",
 *     "add-form" = "/admin/vipps/periodic_charges/add",
 *     "edit-form" = "/admin/vipps/periodic_charges/{periodic_charges}/edit",
 *     "delete-form" = "/admin/vipps/periodic_charges/{periodic_charges}/delete",
 *     "version-history" = "/admin/vipps/periodic_charges/{periodic_charges}/revisions",
 *     "revision" = "/admin/vipps/periodic_charges/{periodic_charges}/revisions/{periodic_charges_revision}/view",
 *     "revision_revert" = "/admin/vipps/periodic_charges/{periodic_charges}/revisions/{periodic_charges_revision}/revert",
 *     "revision_delete" = "/admin/vipps/periodic_charges/{periodic_charges}/revisions/{periodic_charges_revision}/delete",
 *     "collection" = "/admin/vipps/periodic_charges",
 *   },
 *   field_ui_base_route = "periodic_charges.settings"
 * )
 */
class PeriodicCharges extends EditorialContentEntityBase implements PeriodicChargesInterface {

  use EntityChangedTrait;
  use EntityPublishedTrait;

  /**
   * {@inheritdoc}
   */
  protected function urlRouteParameters($rel) {
    $uri_route_parameters = parent::urlRouteParameters($rel);

    if ($rel === 'revision_revert' && $this instanceof RevisionableInterface) {
      $uri_route_parameters[$this->getEntityTypeId() . '_revision'] = $this->getRevisionId();
    }
    elseif ($rel === 'revision_delete' && $this instanceof RevisionableInterface) {
      $uri_route_parameters[$this->getEntityTypeId() . '_revision'] = $this->getRevisionId();
    }

    return $uri_route_parameters;
  }

  /**
   * {@inheritdoc}
   */
  public function preSave(EntityStorageInterface $storage) {
    parent::preSave($storage);

  }

  /**
   * {@inheritdoc}
   */
  public function getChargeId() {
    return $this->get('charge_id')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setChargeId($id) {
    $this->set('charge_id', $id);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getPrice() {
    return $this->get('price')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setPrice($price) {
    $this->set('price', $price);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getParentId() {
    return $this->get('parent_id')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setParentId($parentId) {
    $this->set('parent_id', $parentId);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    return $this->get('description')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setDescription($description) {
    $this->set('description', $description);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getStatus() {
    return $this->get('charge_status')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setStatus($chargeStatus) {
    $this->set('charge_status', $chargeStatus);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getCreatedTime() {
    return $this->get('created')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setCreatedTime($timestamp) {
    $this->set('created', $timestamp);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields = parent::baseFieldDefinitions($entity_type);

    // Add the published field.
    $fields += static::publishedBaseFieldDefinitions($entity_type);

    $fields['parent_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Parent id'))
      ->setSetting('target_type', 'vipps_agreements')
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'entity_reference_entity_id',
        'weight' => -4,
      ])
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => -4,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE)
      ->setRequired(TRUE);

    // Charge ID
    $fields['charge_id'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Charge ID'))
      ->setDescription(t('The name of the Periodic charges entity.'))
      ->setRevisionable(TRUE)
      ->setSettings([
        'max_length' => 50,
        'text_processing' => 0,
      ])
      ->setDefaultValue('')
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'string',
        'weight' => -4,
      ])
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => -4,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE)
      ->setRequired(TRUE);

    $fields['charge_status'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Charge status'))
      ->setDescription(t('Charge status. Possible values: PENDING, DUE, CHARGED, FAILED, REFUNDED, PARTIALLY_REFUNDED, RESERVED'))
      ->setRevisionable(TRUE)
      ->setSettings([
        'max_length' => 10,
        'text_processing' => 0,
      ])
      ->setDefaultValue('')
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'string',
        'weight' => -4,
      ])
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => -4,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE)
      ->setRequired(TRUE);

    $fields['price'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Price'))
      ->setDescription(t('Amount/price of the charge.'))
      ->setRevisionable(TRUE)
      ->setSettings([
        'max_length' => 8,
        'text_processing' => 0,
      ])
      ->setDefaultValue('')
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'number_integer',
        'weight' => -4,
      ])
      ->setDisplayOptions('form', [
        'type' => 'number',
        'weight' => -4,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE)
      ->setRequired(TRUE);


    $fields['description'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Description'))
      ->setDescription(t('Description to send to end users Vipps app with charge.'))
      ->setRevisionable(TRUE)
      ->setSettings([
        'max_length' => 64,
        'text_processing' => 0,
      ])
      ->setDefaultValue('')
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'string',
        'weight' => -4,
      ])
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => -4,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE)
      ->setRequired(TRUE);

    $fields['status']->setDescription(t('A boolean indicating whether the Periodic charges is published.'))
      ->setDisplayOptions('form', [
        'type' => 'boolean_checkbox',
        'weight' => -3,
      ]);

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Created'))
      ->setDescription(t('The time that the entity was created.'));

    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(t('Changed'))
      ->setDescription(t('The time that the entity was last edited.'));

    return $fields;
  }

}
