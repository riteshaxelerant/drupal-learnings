<?php

namespace Drupal\article_review_workflow\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Configuration form for Article Workflow settings.
 */
class SettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'article_review_workflow_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['article_review_workflow.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('article_review_workflow.settings');

    $form['article_review_notification_email'] = [
      '#type' => 'email',
      '#title' => $this->t('Notification Email'),
      '#description' => $this->t('Enter email address to receive notifications for articles needing review.'),
      '#default_value' => $config->get('article_review_notification_email'),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->config('article_review_workflow.settings')
      ->set('article_review_notification_email', $form_state->getValue('article_review_notification_email'))
      ->save();

    parent::submitForm($form, $form_state);
  }

}