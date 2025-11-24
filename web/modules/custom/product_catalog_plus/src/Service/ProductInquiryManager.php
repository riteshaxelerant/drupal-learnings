<?php

namespace Drupal\product_catalog_plus\Service;

use Drupal\Core\Database\Connection;
use Drupal\Core\Mail\MailManagerInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Service for handling product inquiries.
 */
class ProductInquiryManager {

  use StringTranslationTrait;

  /**
   * The database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $db;

  /**
   * The mail manager.
   *
   * @var \Drupal\Core\Mail\MailManagerInterface
   */
  protected $mailManager;

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $config;

  /**
   * The messenger.
   *
   * @var \Drupal\Core\Messenger\MessengerInterface
   */
  protected $messenger;

  /**
   * The logger factory.
   *
   * @var \Drupal\Core\Logger\LoggerChannelFactoryInterface
   */
  protected $loggerFactory;

  /**
   * Constructs a ProductInquiryManager object.
   *
   * @param \Drupal\Core\Database\Connection $db
   *   The database connection.
   * @param \Drupal\Core\Mail\MailManagerInterface $mail_manager
   *   The mail manager.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   * @param \Drupal\Core\Messenger\MessengerInterface $messenger
   *   The messenger.
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $logger_factory
   *   The logger factory.
   */
  public function __construct(
    Connection $db,
    MailManagerInterface $mail_manager,
    ConfigFactoryInterface $config_factory,
    MessengerInterface $messenger,
    LoggerChannelFactoryInterface $logger_factory
  ) {
    $this->db = $db;
    $this->mailManager = $mail_manager;
    $this->config = $config_factory;
    $this->messenger = $messenger;
    $this->loggerFactory = $logger_factory;
  }

  /**
   * Handles the product inquiry submission.
   *
   * @param array $values
   *   The form values.
   *
   * @return bool
   *   TRUE if successful, FALSE otherwise.
   */
  public function handleInquiry(array $values) {
    try {
      // Validate required fields.
      $required_fields = ['product_id', 'name', 'email', 'message'];
      foreach ($required_fields as $field) {
        if (empty($values[$field])) {
          throw new \Exception("Missing required field: $field");
        }
      }

      // Sanitize inputs.
      $data = [
        'product_id' => (int) $values['product_id'],
        'name' => trim($values['name']),
        'email' => trim($values['email']),
        'message' => trim($values['message']),
        'created' => \Drupal::time()->getCurrentTime(),
      ];

      // Log the attempt.
      $this->loggerFactory->get('product_catalog_plus')->info('Attempting to save inquiry: @data', ['@data' => json_encode($data)]);

      // Check if table exists.
      if (!$this->db->schema()->tableExists('product_inquiry')) {
        throw new \Exception('Product inquiry table does not exist. Please run database updates.');
      }

      // Save to database.
      $result = $this->db->insert('product_inquiry')
        ->fields($data)
        ->execute();

      if (!$result) {
        throw new \Exception('Failed to insert inquiry into database.');
      }

      $this->loggerFactory->get('product_catalog_plus')->info('Inquiry saved successfully with ID: @id', ['@id' => $result]);

      // Send email notification.
      $this->sendEmailNotification($data);

      // Notify user.
      $this->messenger->addStatus($this->t('Your inquiry has been sent successfully.'));

      return TRUE;
    }
    catch (\Exception $e) {
      $this->loggerFactory->get('product_catalog_plus')->error('Inquiry submission failed: @error', ['@error' => $e->getMessage()]);
      $this->messenger->addError($this->t('An error occurred while sending your inquiry: @error', ['@error' => $e->getMessage()]));
      return FALSE;
    }
  }

  /**
   * Sends email notification for the inquiry.
   *
   * @param array $data
   *   The inquiry data.
   */
  protected function sendEmailNotification(array $data) {
    try {
      $site_email = $this->config->get('system.site')->get('mail');
      
      $this->mailManager->mail(
        'product_catalog_plus',
        'inquiry_notification',
        $site_email,
        'en',
        [
          'message' => $data['message'],
          'name' => $data['name'],
          'email' => $data['email'],
          'product_id' => $data['product_id'],
        ]
      );

      $this->loggerFactory->get('product_catalog_plus')->info('Email notification sent for inquiry from @email', ['@email' => $data['email']]);
    }
    catch (\Exception $e) {
      $this->loggerFactory->get('product_catalog_plus')->error('Failed to send email notification: @error', ['@error' => $e->getMessage()]);
    }
  }

}
