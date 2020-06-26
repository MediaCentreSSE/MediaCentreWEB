<?php
/**
 * @file
 * Contains \Drupal\brochureq\Form\BrochureqForm
 */

namespace Drupal\brochureq\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;



class BrochureqForm extends FormBase {

    private $data;

    public function setData($data) {
        $this->data = $data;
    }

    /**
    * {@inheritdoc}
    */
    public function getFormId() {
        return 'brochureqform';
    }

    /**
    * {@inheritdoc}
    */
    public function buildForm(array $form, FormStateInterface $form_state) {
        $form['name'] = array(
            '#type' => 'textfield',
            '#title' => t('Your name') . '<small class="text-danger">*</small>',
            '#required' => true,
            '#label_attributes' => array('class' => array('inputblock-label')),
            '#prefix' => '<div class="inputblock mb-4">',
            '#suffix' => '</div>'
        );
        $form['last_name'] = array(
            '#type' => 'textfield',
            '#title' => t('Your last name') . '<small class="text-danger">*</small>',
            '#required' => true,
            '#label_attributes' => array('class' => array('inputblock-label')),
            '#prefix' => '<div class="inputblock mb-4">',
            '#suffix' => '</div>'
        );
        $form['company'] = array(
            '#type' => 'textfield',
            '#title' => t('Company'),
            '#label_attributes' => array('class' => array('inputblock-label')),
            '#prefix' => '<div class="inputblock mb-4">',
            '#suffix' => '</div>'
        );
        $form['position'] = array(
            '#type' => 'textfield',
            '#title' => t('Your position'),
            '#label_attributes' => array('class' => array('inputblock-label')),
            '#prefix' => '<div class="inputblock mb-4">',
            '#suffix' => '</div>'
        );
        $form['country'] = array(
            '#type' => 'textfield',
            '#title' => t('Your country'),
            '#label_attributes' => array('class' => array('inputblock-label')),
            '#prefix' => '<div class="inputblock mb-4">',
            '#suffix' => '</div>'
        );
        $form['email'] = array(
            '#type' => 'textfield',
            '#title' => t('Your email') . '<small class="text-danger">*</small>',
            '#required' => true,
            '#label_attributes' => array('class' => array('inputblock-label')),
            '#prefix' => '<div class="inputblock mb-4">',
            '#suffix' => '</div>'

        );
        $form['phone'] = array(
            '#type' => 'tel',
            '#title' => t('Your phone') . '<small class="text-danger">*</small>',
            '#required' => true,
            '#label_attributes' => array('class' => array('inputblock-label')),
            '#prefix' => '<div class="inputblock mb-4">',
            '#suffix' => '</div>'
        );
        $form['agreement'] = array(
            '#type' => 'checkbox',
            '#title' => '<a href="' . $this->data['agreement'] . '">' . t('Agree to the Privacy Policy') . '</a>' . '<small class="text-danger">*</small>',
            '#required' => true,
            '#label_attributes' => array('class' => array('inputblock-label ml-4')),
            '#attributes' => array('class' => array('float-left')),
            '#prefix' => '<div class="mb-4">',
            '#suffix' => '</div>'
        );

        $form['submit'] = array(
            '#type' => 'submit',
            '#value' => t('Submit'),
            '#attributes' => array('class' => array('btn-primary')),
            '#prefix' => '<div class="clearfix">',
            '#suffix' => '</div>'
        );
        return $form;
    }

    /**
     * Form submission handler.
     *
     * @param array $form
     *   An associative array containing the structure of the form.
     * @param \Drupal\Core\Form\FormStateInterface $form_state
     *   The current state of the form.
     */
    public function submitForm(array &$form, FormStateInterface $form_state) {
      //we don't need to do anything here
    }

    public function validateForm(array &$form, FormStateInterface $form_state) {

        //I don't know why this stupid thing is not displaying errors set with setErrorByName
        //So I'll display them with description

        if(!filter_var($form_state->getValue('email'), FILTER_VALIDATE_EMAIL)) {
            $error = t('Email is incorrect');
            $form_state->setErrorByName('email', $error);
            $form['email']['#description'] = '<small class="text-danger">' . $error . '</small>';
        }
        if(!is_numeric($form_state->getValue('phone'))) {
            $error = t('Phone number must contain only numbers');
            $form_state->setErrorByName('phone', $error);
            $form['phone']['#description'] = '<small class="text-danger">' . $error . '</small>';
        }
        if(empty($form_state->getValue('name'))) {
            $error = t('Please specify name');
            $form_state->setErrorByName('name', $error);
            $form['name']['#description'] = '<small class="text-danger">' . $error . '</small>';
        }
        if(empty($form_state->getValue('last_name'))) {
            $error = t('Please specify last name');
            $form_state->setErrorByName('last_name', $error);
            $form['last_name']['#description'] = '<small class="text-danger">' . $error . '</small>';
        }

        if(!$form_state->getErrors()) {
            \Drupal::request()->getSession()->set($this->data['session_key'], true);
            sendEmail($form, $form_state, $this->data['emails']);
        }
    }
}


