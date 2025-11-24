<?php

namespace Drupal\product_catalog_plus\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\node\Entity\Node;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\product_catalog_plus\Service\ProductInquiryManager;

/**
 * Provides a Product Inquiry form.
 */
class ProductInquiryForm extends FormBase {

  /**
   * The inquiry manager service.
   *
   * @var \Drupal\product_catalog_plus\Service\ProductInquiryManager
   */
  protected $inquiryManager;

  /**
   * Constructs a ProductInquiryForm object.
   *
   * @param \Drupal\product_catalog_plus\Service\ProductInquiryManager $inquiry_manager
   *   The inquiry manager service.
   */
  public function __construct(ProductInquiryManager $inquiryManager) {
    $this->inquiryManager = $inquiryManager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('product_catalog_plus.inquiry_manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'product_catalog_plus_inquiry_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $product_id = NULL) {
    // Get product ID from route parameter or passed parameter.
    if (!$product_id) {
      $node = \Drupal::routeMatch()->getParameter('node');
      $product_id = $node instanceof Node ? $node->id() : NULL;
    }

    // Validate that we have a valid product.
    if (!$product_id || !Node::load($product_id)) {
      $form['error'] = [
        '#markup' => $this->t('Product not found.'),
      ];
      return $form;
    }

    $form['name'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Your Name'),
      '#required' => TRUE,
      '#maxlength' => 255,
      '#attributes' => [
        'placeholder' => $this->t('Enter your full name'),
      ],
    ];

    $form['email'] = [
      '#type' => 'email',
      '#title' => $this->t('Your Email'),
      '#required' => TRUE,
      '#maxlength' => 255,
      '#attributes' => [
        'placeholder' => $this->t('Enter your email address'),
      ],
    ];

    $form['message'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Message'),
      '#required' => TRUE,
      '#rows' => 5,
      '#attributes' => [
        'placeholder' => $this->t('Enter your inquiry message'),
      ],
    ];

    $form['product_id'] = [
      '#type' => 'value',
      '#value' => $product_id,
    ];

    $form['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Send Inquiry'),
      '#ajax' => [
        'callback' => '::ajaxSubmit',
        'wrapper' => 'inquiry-form-wrapper',
        'progress' => [
          'type' => 'throbber',
          'message' => $this->t('Sending inquiry...'),
        ],
      ],
    ];

    $form['#prefix'] = '<div id="inquiry-form-wrapper">';
    $form['#suffix'] = '</div>';

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);

    // Validate email format.
    $email = $form_state->getValue('email');
    if (!empty($email) && !\Drupal::service('email.validator')->isValid($email)) {
      $form_state->setError($form['email'], $this->t('Please enter a valid email address.'));
    }

    // Validate message length.
    $message = $form_state->getValue('message');
    if (!empty($message) && strlen($message) < 10) {
      $form_state->setError($form['message'], $this->t('Message must be at least 10 characters long.'));
    }

    // Validate product exists.
    $product_id = $form_state->getValue('product_id');
    if (!Node::load($product_id)) {
      $form_state->setErrorByName('product_id', $this->t('Invalid product selected.'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    try {
      $values = $form_state->getValues();
      
      // Call the service to handle the inquiry.
      $result = $this->inquiryManager->handleInquiry($values);
      
      if ($result) {
        // Clear form values for AJAX submission.
        $form_state->setValues([]);
        $form_state->setRebuild(TRUE);
        
        // Add success message.
        $this->messenger()->addStatus($this->t('Your inquiry has been sent successfully.'));
      }
      else {
        // Service returned FALSE, error message already set by service.
        $form_state->setRebuild(TRUE);
      }
    }
    catch (\Exception $e) {
      $this->messenger()->addError($this->t('An error occurred while sending your inquiry. Please try again.'));
      \Drupal::logger('product_catalog_plus')->error('Inquiry submission failed: @error', ['@error' => $e->getMessage()]);
      $form_state->setRebuild(TRUE);
    }
  }

  /**
   * AJAX callback for form submission.
   */
  public function ajaxSubmit(array &$form, FormStateInterface $form_state) {
    // If there are validation errors, return the form with errors.
    if ($form_state->hasAnyErrors()) {
      return $form;
    }

    // Get all messages that were set during form submission.
    $messages = $this->messenger()->all();
    
    // Check for success or error messages.
    $has_success = !empty($messages['status']);
    $has_error = !empty($messages['error']);

    if ($has_success) {
      // Return a success message.
      return [
        '#type' => 'container',
        '#attributes' => ['id' => 'inquiry-form-wrapper'],
        'message' => [
          '#type' => 'html_tag',
          '#tag' => 'div',
          '#attributes' => ['class' => ['messages', 'messages--status']],
          '#value' => $this->t('Thank you! Your inquiry has been sent successfully.'),
        ],
      ];
    }
    elseif ($has_error) {
      // Return error message.
      return [
        '#type' => 'container',
        '#attributes' => ['id' => 'inquiry-form-wrapper'],
        'message' => [
          '#type' => 'html_tag',
          '#tag' => 'div',
          '#attributes' => ['class' => ['messages', 'messages--error']],
          '#value' => implode(' ', $messages['error']),
        ],
      ];
    }
    else {
      // No specific messages, return the form as is.
      return $form;
    }
  }

}
