<?php

namespace Drupal\vipps_recurring_payments\Entity;

use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Entity\EditorialContentEntityBase;
use Drupal\Core\Entity\RevisionableInterface;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityPublishedTrait;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\user\EntityOwnerInterface;
use Drupal\user\UserInterface;

/**
 * Defines the Vipps agreements entity.
 *
 * @ingroup vipps_recurring_payments
 *
 * @ContentEntityType(
 *   id = "vipps_agreements",
 *   label = @Translation("Vipps agreements"),
 *   handlers = {
 *     "storage" = "Drupal\vipps_recurring_payments\VippsAgreementsStorage",
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\vipps_recurring_payments\VippsAgreementsListBuilder",
 *     "views_data" = "Drupal\vipps_recurring_payments\Entity\VippsAgreementsViewsData",
 *
 *     "form" = {
 *       "default" = "Drupal\vipps_recurring_payments\Form\VippsAgreementsForm",
 *       "add" = "Drupal\vipps_recurring_payments\Form\VippsAgreementsForm",
 *       "edit" = "Drupal\vipps_recurring_payments\Form\VippsAgreementsForm",
 *       "cancel" = "Drupal\vipps_recurring_payments\Form\VippsAgreementsCancelForm",
 *       "delete" = "Drupal\vipps_recurring_payments\Form\VippsAgreementsDeleteForm",
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\vipps_recurring_payments\VippsAgreementsHtmlRouteProvider",
 *     },
 *     "access" = "Drupal\vipps_recurring_payments\VippsAgreementsAccessControlHandler",
 *   },
 *   base_table = "vipps_agreements",
 *   revision_table = "vipps_agreements_revision",
 *   revision_data_table = "vipps_agreements_field_revision",
 *   translatable = FALSE,
 *   admin_permission = "administer vipps agreements entities",
 *   entity_keys = {
 *     "id" = "id",
 *     "revision" = "vid",
 *     "uid" = "uid",
 *     "agreement_id" = "agreement_id",
 *     "label" = "mobile",
 *     "agreement_status" = "agreement_status",
 *     "price" = "price",
 *     "published" = "status",
 *     "agreement_intervals" = "agreement_intervals",
 *   },
 *   links = {
 *     "canonical" = "/admin/config/vipps/vipps-agreements/{vipps_agreements}",
 *     "add-form" = "/admin/config/vipps/vipps-agreements/add",
 *     "edit-form" = "/admin/config/vipps/vipps-agreements/{vipps_agreements}/edit",
 *     "delete-form" = "/admin/config/vipps/vipps-agreements/{vipps_agreements}/delete",
 *     "cancel-form" = "/admin/config/vipps/vipps-agreements/{vipps_agreements}/cancel",
 *     "version-history" = "/admin/config/vipps/vipps-agreements/{vipps_agreements}/revisions",
 *     "revision" = "/admin/config/vipps/vipps-agreements/{vipps_agreements}/revisions/{vipps_agreements_revision}/view",
 *     "revision_revert" = "/admin/config/vipps/vipps-agreements/{vipps_agreements}/revisions/{vipps_agreements_revision}/revert",
 *     "revision_delete" = "/admin/config/vipps/vipps-agreements/{vipps_agreements}/revisions/{vipps_agreements_revision}/delete",
 *     "collection" = "/admin/config/vipps/vipps-agreements",
 *   },
 *   field_ui_base_route = "vipps_agreements.settings"
 * )
 */
class VippsAgreements extends EditorialContentEntityBase implements VippsAgreementsInterface {

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
  public function getAgreementId() {
    return $this->get('agreement_id')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setAgreementId($agreementId) {
    $this->set('agreement_id', $agreementId);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getMobile() {
    return $this->get('mobile')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setMobile($mobile) {
    $this->set('mobile', $mobile);
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
  public function getStatus() {
    return $this->get('agreement_status')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setStatus($agreementStatus) {
    $this->set('agreement_status', $agreementStatus);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getIntervals() {
    return $this->get('agreement_intervals')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setIntervals($agreementIntervals) {
    $this->set('agreement_intervals', $agreementIntervals);
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
   * @inheritDoc
   */
  public function getOwner() {
    return $this->get('uid')->entity;
  }

  /**
   * @inheritDoc
   */
  public function setOwner(UserInterface $account) {
    $this->set('uid', $account->id());
    return $this;
  }

  /**
   * @inheritDoc
   */
  public function getOwnerId() {
    return $this->getEntityKey('owner');
  }

  /**
   * @inheritDoc
   */
  public function setOwnerId($uid) {
    $this->set('uid', $uid);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields = parent::baseFieldDefinitions($entity_type);

    // Add the published field.
    $fields += static::publishedBaseFieldDefinitions($entity_type);

    $fields['uid'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Owner'))
      ->setDescription(t('The agreement owner.'))
      ->setSetting('target_type', 'user')
      ->setSetting('handler', 'default')
      ->setDefaultValueCallback('Drupal\vipps_recurring_payments\Entity\VippsAgreements::getCurrentUserId')
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'author',
        'weight' => 0,
      ])
      ->setDisplayConfigurable('view', TRUE);

    $fields['agreement_id'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Agreement ID'))
      ->setDescription(t('Agreements unique identifier.'))
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

    $fields['mobile'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Mobile number'))
      ->setDescription(t('Users mobile number.'))
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

    $fields['status']->setDescription(t('A boolean indicating whether the Vipps agreements is published.'))
      ->setDisplayOptions('form', [
        'type' => 'boolean_checkbox',
        'weight' => -3,
      ]);

    $fields['price'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Price'))
      ->setDescription(t('Amount/price of the agreement.'))
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


    $fields['agreement_status'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Agreement status'))
      ->setDescription(t('Agreement status. Possible values: PENDING, ACTIVE, STOPPED, EXPIRED'))
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

    $fields['agreement_intervals'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Agreement intervals'))
      ->setDescription(t('How often agreement runs? MONTHLY, WEEKLY, YEARLY or DAILY'))
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

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Created'))
      ->setDescription(t('The time that the entity was created.'));

    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(t('Changed'))
      ->setDescription(t('The time that the entity was last edited.'));

    return $fields;
  }

  /**
   * Default value callback for 'uid' base field definition.
   *
   * @see ::baseFieldDefinitions()
   *
   * @return array
   *   An array of default values.
   */
  public static function getCurrentUserId() {
    return [\Drupal::currentUser()->id()];
  }
}
