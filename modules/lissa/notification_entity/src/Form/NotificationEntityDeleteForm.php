<?php

/**
 * @file
 * Contains \Drupal\notification_entity\Form\NotificationEntityDeleteForm.
 */

namespace Drupal\notification_entity\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\Core\Entity\ContentEntityConfirmFormBase;

/**
 * Provides a deletion confirmation form for a notification entity.
 */
class NotificationEntityDeleteForm extends ContentEntityConfirmFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'notification_entity_confirm_delete';
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return $this->t('Are you sure you want to delete the notification %title?', array('%title' => $this->entity->getTitle()));
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    return new Url('entity.notification_entity.list');
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    return $this->t('This action cannot be undone.');
  }

  /**
   * {@inheritdoc}
   */
  public function getConfirmText() {
    return $this->t('Delete');
  }

  /**
   * Deletes the notification and notifies the user about this.
   *
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->entity->delete();

    drupal_set_message($this->t('Deleted notification %title.', array('%title' => $this->entity->getTitle())));
    $this->logger('notification_entity')->notice('Deleted notification %title.', array('%title' => $this->entity->getTitle()));
    $form_state->setRedirectUrl($this->getCancelUrl());
  }

}
