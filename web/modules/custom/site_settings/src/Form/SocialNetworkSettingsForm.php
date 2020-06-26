<?php

namespace Drupal\site_settings\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class SocialNetworkSettingsForm.
 */
class SocialNetworkSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'social_network_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'social_network.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('social_network.settings');

    $form['add_this'] = [
      '#type' => 'details',
      '#title' => 'AddThis',
      '#open' => TRUE
    ];
    $form['add_this']['add_this_id'] = [
      '#type' => 'textfield',
      '#title' => 'ID',
      '#default_value' => $config->get('add_this_id')
    ];

    $form['facebook_api'] = [
      '#type' => 'details',
      '#title' => 'Facebook API',
      '#open' => TRUE
    ];
    $form['facebook_api']['facebook_app_id'] = [
      '#type' => 'textfield',
      '#title' => 'App ID',
      '#default_value' => $config->get('facebook_app_id')
    ];
    $form['facebook_api']['facebook_app_secret'] = [
      '#type' => 'textfield',
      '#title' => 'App secret',
      '#default_value' => $config->get('facebook_app_secret')
    ];

    $form['twitter_api'] = [
      '#type' => 'details',
      '#title' => 'Twitter API',
      '#open' => TRUE
    ];
    $form['twitter_api']['twitter_consumer_key'] = [
      '#type' => 'textfield',
      '#title' => 'Consumer Key',
      '#default_value' => $config->get('twitter_consumer_key')
    ];
    $form['twitter_api']['twitter_consumer_secret'] = [
      '#type' => 'textfield',
      '#title' => 'Consumer Secret',
      '#default_value' => $config->get('twitter_consumer_secret')
    ];
    $form['twitter_api']['twitter_access_token'] = [
      '#type' => 'textfield',
      '#title' => 'Access Token',
      '#default_value' => $config->get('twitter_access_token')
    ];
    $form['twitter_api']['twitter_access_token_secret'] = [
      '#type' => 'textfield',
      '#title' => 'Access Token Secret',
      '#default_value' => $config->get('twitter_access_token_secret')
    ];

    return parent::buildForm($form, $form_state);
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
    $config = $this->config('social_network.settings');
    $config->set('add_this_id', $form_state->getValue('add_this_id'));

    $config->set('facebook_app_id', $form_state->getValue('facebook_app_id'));
    $config->set('facebook_app_secret', $form_state->getValue('facebook_app_secret'));

    $config->set('twitter_consumer_key', $form_state->getValue('twitter_consumer_key'));
    $config->set('twitter_consumer_secret', $form_state->getValue('twitter_consumer_secret'));
    $config->set('twitter_access_token', $form_state->getValue('twitter_access_token'));
    $config->set('twitter_access_token_secret', $form_state->getValue('twitter_access_token_secret'));

    $config->save();
    drupal_set_message('Settings updated.');
  }

}
