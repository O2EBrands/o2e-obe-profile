<?php

namespace Drupal\o2e_obe_salesforce\Form;

use Drupal\Core\Messenger\MessengerInterface;
use Symfony\Component\HttpFoundation\Request;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\o2e_obe_salesforce\UnsubscribeService;

/**
 * Custom Unsubscribe Form.
 */
class UnsubscribeForm extends FormBase {

  /**
   * The current request.
   *
   * @var \Symfony\Component\HttpFoundation\Request
   */
  protected $request;

  /**
   * The Unsubscriber Manager.
   *
   * @var \Drupal\o2e_obe_salesforce\UnsubscribeService
   */
  protected $unsubscribeService;

  /**
   * Messenger Object.
   *
   * @var \Drupal\Core\Messenger\MessengerInterface
   */
  protected $messenger;

  /**
   * Constructs the object.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The import transformer service.
   * @param \Drupal\o2e_obe_salesforce\UnsubscribeService $unsubscribeService
   *   The Unsubscribe API Service.
   * @param \Drupal\Core\Messenger\MessengerInterface $messenger
   *   The Messenger Service.
   */
  public function __construct(Request $request, UnsubscribeService $unsubscribeService, MessengerInterface $messenger) {
    $this->request = $request;
    $this->unsubscribeService = $unsubscribeService;
    $this->messenger = $messenger;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
    $container->get('request_stack')->getCurrentRequest(),
    $container->get('o2e_obe_salesforce.unsubscribe'),
    $container->get('messenger'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'unsubscribe_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $email = '';
    if ($this->request->query->has('email')) {
      $email = $this->request->query->has('email');
      $email = str_replace(" ", "", $email);
    }
    $form['unsubscribeFormOpen']['#markup'] = '<div class="obeHeaderIntro large-12 columns">' . $this->t('Unsubscribe from mailing list') . '<p>' . $this->t('To unsubscribe from promotional email communications, enter your email address below and click Unsubscribe.') . '</p>';
    $form['email'] = [
      '#type' => 'email',
      '#title' => $this->t('Email'),
      '#name' => 'email',
      '#required' => TRUE,
      '#maxlength' => 80,
      '#size' => 40,
      '#default_value' => $email,
      '#required_error' => $this->t('Email is required'),
    ];
    $form['actions']['#type'] = 'actions';
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Unsubscribe'),
      '#button_type' => 'primary',
    ];
    $form['unsubscribeFormClose']['#markup'] = '</div>';
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    // Validate email address.
    if (!empty($form_state->getValue('email'))) {
      $email_subject = $form_state->getValue('email');
      if (filter_var($email_subject, FILTER_VALIDATE_EMAIL) === FALSE) {
        $form_state->setErrorByName('email', $this->t('Please enter a valid email address (max. 80 characters).'));
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $email = $form_state->getValue('email');
    if (!empty($email)) {
      $unsubscribeObject = [
        'email_address' => $email,
      ];
    }
    $response = $this->unsubscribeService->unsubscribe($unsubscribeObject);
    if (!empty($response) && $response == 200) {
      $this->messenger()->addMessage($this->t('You have successfully unsubscribed.'));
    }
    else {
      $this->messenger()->addError($this->t('We are unable to unsubscribe your email now.'));
    }
  }

}
