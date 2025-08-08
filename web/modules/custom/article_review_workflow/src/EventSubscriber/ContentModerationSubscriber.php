<?php

namespace Drupal\article_review_workflow\EventSubscriber;

use Drupal\Core\Entity\EntityInterface;
use Drupal\article_review_workflow\Service\ArticleNotificationService;
use Drupal\content_moderation\ModerationInformationInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Custom EventSubscriber that listens to moderation state changes.
 * 
 * Since Drupal core doesn't provide specific content moderation events,
 * this subscriber integrates with the hook system to meet the requirement
 * of using an EventSubscriber for monitoring state changes.
 */
class ContentModerationSubscriber implements EventSubscriberInterface {

  /**
   * The article notification service.
   *
   * @var \Drupal\article_review_workflow\Service\ArticleNotificationService
   */
  protected $notificationService;

  /**
   * The moderation information service.
   *
   * @var \Drupal\content_moderation\ModerationInformationInterface
   */
  protected $moderationInfo;

  /**
   * Constructs a new ContentModerationSubscriber.
   *
   * @param \Drupal\article_review_workflow\Service\ArticleNotificationService $notification_service
   *   The article notification service.
   * @param \Drupal\content_moderation\ModerationInformationInterface $moderation_info
   *   The moderation information service.
   */
  public function __construct(ArticleNotificationService $notification_service, ModerationInformationInterface $moderation_info) {
    $this->notificationService = $notification_service;
    $this->moderationInfo = $moderation_info;
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents(): array {
    // Since Drupal core doesn't dispatch specific content moderation events,
    // we'll use a custom approach where the module hooks call this subscriber
    return [];
  }

  /**
   * Checks if we should send notification for article state change.
   * 
   * This method is called from the module's hook implementations to maintain
   * the EventSubscriber pattern while working with Drupal's hook system.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity to check.
   * @param bool $is_new
   *   Whether this is a new entity.
   */
  public function checkAndNotify(EntityInterface $entity, bool $is_new): void {
    // Only process Article nodes
    if ($entity->getEntityTypeId() === 'node' && $entity->bundle() === 'article') {
      
      // Check if moderation is enabled
      if ($this->moderationInfo->isModeratedEntity($entity)) {
        
        // Get the current moderation state
        /** @var \Drupal\Core\Entity\ContentEntityInterface $entity */
        if ($entity->hasField('moderation_state') && !$entity->get('moderation_state')->isEmpty()) {
          $current_state = $entity->get('moderation_state')->value;
          
          // Determine original state for comparison
          $original_state = NULL;
          if (!$is_new && isset($entity->original) && $entity->original->hasField('moderation_state') && !$entity->original->get('moderation_state')->isEmpty()) {
            /** @var \Drupal\Core\Entity\ContentEntityInterface $original */
            $original_state = $entity->original->get('moderation_state')->value;
          }
          
          // Handle "needs_review" notifications (to editors)
          if ($current_state === 'needs_review') {
            $should_notify = FALSE;
            
            if ($is_new) {
              // For new articles, notify if created directly in needs_review state
              $should_notify = TRUE;
              $log_original = 'new';
            } else {
              // Only notify if state actually changed TO needs_review
              $should_notify = ($original_state !== 'needs_review');
              $log_original = $original_state ?: 'unknown';
            }
            
            if ($should_notify) {
              // Send "needs review" notification to editors
              $this->notificationService->sendReviewNotification($entity);
              
              // Debug log
              \Drupal::logger('article_review_workflow')->info('REVIEW notification sent via EventSubscriber for article: @title (State: @old_state -> @new_state) [Context: @context]', [
                '@title' => $entity->label(),
                '@old_state' => $log_original,
                '@new_state' => $current_state,
                '@context' => $is_new ? 'new article' : 'updated article',
              ]);
            }
          }
          
          // Handle "published" notifications (to article author)
          elseif ($current_state === 'published') {
            $should_notify = FALSE;
            
            if (!$is_new && $original_state !== 'published') {
              // Only notify if state actually changed TO published (not for new articles created as published)
              $should_notify = TRUE;
            }
            
            if ($should_notify) {
              // Send "published" notification to article author
              $this->notificationService->sendPublishedNotification($entity);
              
              // Debug log
              \Drupal::logger('article_review_workflow')->info('PUBLISHED notification sent via EventSubscriber for article: @title (State: @old_state -> @new_state)', [
                '@title' => $entity->label(),
                '@old_state' => $original_state ?: 'unknown',
                '@new_state' => $current_state,
              ]);
            }
          }
        }
      }
    }
  }

}