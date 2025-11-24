<?php
namespace Drupal\article_review_workflow\Service;

use Drupal\Core\Mail\MailManagerInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\node\NodeInterface;

class ArticleNotificationService {

  protected $mailManager;
  protected $currentUser;
  protected $configFactory;

  public function __construct(MailManagerInterface $mailManager, AccountProxyInterface $currentUser, ConfigFactoryInterface $config_factory) {
    $this->mailManager = $mailManager;
    $this->currentUser = $currentUser;
    $this->configFactory = $config_factory;
  }

  /**
   * Sends notification for "needs review" state.
   * 
   * @param \Drupal\node\NodeInterface $node
   *   The article node.
   * 
   * @return bool
   *   TRUE if email sent successfully, FALSE otherwise.
   */
  public function sendNotification(NodeInterface $node) {
    return $this->sendReviewNotification($node);
  }

  /**
   * Sends "needs review" notification to editors.
   * 
   * @param \Drupal\node\NodeInterface $node
   *   The article node.
   * 
   * @return bool
   *   TRUE if email sent successfully, FALSE otherwise.
   */
  public function sendReviewNotification(NodeInterface $node) {
    $config = $this->configFactory->get('article_review_workflow.settings');
    $to = $config->get('article_review_notification_email') ?: 'editor@example.com';
    
    // Debug log
    \Drupal::logger('article_review_workflow')->info('Attempting to send REVIEW notification to: @email for article: @title', [
      '@email' => $to,
      '@title' => $node->label(),
    ]);
    
    $params = [
      'subject' => \Drupal::translation()->translate('Article Needs Review: @title', ['@title' => $node->label()]),
      'message' => \Drupal::translation()->translate('The article "@title" submitted by @username needs review. View it at: @url', [
        '@title' => $node->label(),
        '@username' => $this->currentUser->getDisplayName(),
        '@url' => $node->toUrl('canonical', ['absolute' => TRUE])->toString(),
      ]),
    ];
    
    $langcode = $this->currentUser->getPreferredLangcode();
    $result = $this->mailManager->mail('article_review_workflow', 'review_notify', $to, $langcode, $params);
    
    // Log the result
    if ($result['result']) {
      \Drupal::logger('article_review_workflow')->info('REVIEW email sent successfully to: @email', ['@email' => $to]);
    } else {
      \Drupal::logger('article_review_workflow')->error('Failed to send REVIEW email to: @email', ['@email' => $to]);
    }
    
    return $result['result'];
  }

  /**
   * Sends "published" notification to article author.
   * 
   * @param \Drupal\node\NodeInterface $node
   *   The article node.
   * 
   * @return bool
   *   TRUE if email sent successfully, FALSE otherwise.
   */
  public function sendPublishedNotification(NodeInterface $node) {
    // Get the article author's email
    $author = $node->getOwner();
    $to = $author->getEmail();
    
    if (empty($to)) {
      \Drupal::logger('article_review_workflow')->warning('Cannot send published notification: Author has no email address for article: @title', [
        '@title' => $node->label(),
      ]);
      return FALSE;
    }
    
    // Debug log
    \Drupal::logger('article_review_workflow')->info('Attempting to send PUBLISHED notification to author: @email for article: @title', [
      '@email' => $to,
      '@title' => $node->label(),
    ]);
    
    $params = [
      'subject' => \Drupal::translation()->translate('Your Article Has Been Published: @title', ['@title' => $node->label()]),
      'message' => \Drupal::translation()->translate('Great news! Your article "@title" has been reviewed and published by @editor. You can view it at: @url', [
        '@title' => $node->label(),
        '@editor' => $this->currentUser->getDisplayName(),
        '@url' => $node->toUrl('canonical', ['absolute' => TRUE])->toString(),
      ]),
    ];
    
    $langcode = $author->getPreferredLangcode();
    $result = $this->mailManager->mail('article_review_workflow', 'published_notify', $to, $langcode, $params);
    
    // Log the result
    if ($result['result']) {
      \Drupal::logger('article_review_workflow')->info('PUBLISHED email sent successfully to author: @email', ['@email' => $to]);
    } else {
      \Drupal::logger('article_review_workflow')->error('Failed to send PUBLISHED email to author: @email', ['@email' => $to]);
    }
    
    return $result['result'];
  }
}
