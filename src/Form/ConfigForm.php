<?php

namespace Drupal\hsbxl_members\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;


/**
 * Class DefaultForm.
 */
class ConfigForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'config_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    $config = $this->config('simplified_bookkeeping.settings');

    $form['high_statement_repeat_membership'] = array(
      '#title' => t('Repeat membership months on high statement amount.'),
      '#type' => 'checkbox',
      '#description' => t('How are statements higher then the minimum membership fee handled? The rest is a donation, or create multiple membership months, if possible.'),
      '#default_value' => $config->get('membership_payment_received_email'),
    );

    $form['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Save'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    \Drupal::configFactory()->getEditable('simplified_bookkeeping.settings')
      ->set('membership_payment_received_email', $form_state->getValue('membership_payment_received_email'))
      ->save();
  }
}
