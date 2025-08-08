<?php
namespace Drupal\article_review_workflow\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\node\Entity\Node;
use Drupal\Core\Url;
use Drupal\Core\Link;
use Drupal\content_moderation\Entity\ContentModerationState;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\node\NodeInterface;

class NeedsReviewDashboardController extends ControllerBase {

  /**
   * The entity type manager.
   */
  protected $entityTypeManager;

  /**
   * {@inheritdoc}
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager) {
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager')
    );
  }

  /**
   * Dashboard page callback.
   */
  public function dashboard() {
    $header = [
      'title' => 'Title',
      'author' => 'Author',
      'updated' => 'Last Updated',
      'operations' => 'Operations',
    ];

    $rows = [];
    
    // Query content moderation states to find articles in 'needs_review'
    $moderation_state_ids = $this->entityTypeManager
      ->getStorage('content_moderation_state')
      ->getQuery()
      ->condition('content_entity_type_id', 'node')
      ->condition('moderation_state', 'needs_review')
      ->accessCheck(FALSE)
      ->execute();

    $node_ids = [];
    if (!empty($moderation_state_ids)) {
      $moderation_states = $this->entityTypeManager
        ->getStorage('content_moderation_state')
        ->loadMultiple($moderation_state_ids);
      
      foreach ($moderation_states as $mod_state) {
        // Get the node ID and check if it's an article
        /** @var \Drupal\content_moderation\Entity\ContentModerationStateInterface $mod_state */
        $content_entity_id = $mod_state->get('content_entity_id')->value;
        /** @var \Drupal\node\NodeInterface $node */
        $node = $this->entityTypeManager
          ->getStorage('node')
          ->load($content_entity_id);
        
        if ($node && $node->bundle() === 'article') {
          $node_ids[] = $content_entity_id;
        }
      }
    }

    if (!empty($node_ids)) {
      $nodes = $this->entityTypeManager
        ->getStorage('node')
        ->loadMultiple($node_ids);
      foreach ($nodes as $node) {
        /** @var \Drupal\node\NodeInterface $node */
        $link = Link::fromTextAndUrl($node->label(), $node->toUrl())->toString();
        $author = $node->getOwner()->getDisplayName();
        $updated = date('Y-m-d H:i', $node->getChangedTime());
        $edit_link = Link::fromTextAndUrl('Edit', Url::fromRoute('entity.node.edit_form', ['node' => $node->id()]))->toString();

        $rows[] = [
          'title' => $link,
          'author' => $author,
          'updated' => $updated,
          'operations' => $edit_link,
        ];
      }
    }

    return [
      '#type' => 'table',
      '#header' => $header,
      '#rows' => $rows,
      '#empty' => 'No articles in Needs Review.',
    ];
  }
}