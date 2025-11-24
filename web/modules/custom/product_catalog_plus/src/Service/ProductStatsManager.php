<?php
namespace Drupal\product_catalog_plus\Service;

use Drupal\Core\Database\Connection;
use Drupal\node\Entity\Node;

class ProductStatsManager {

  protected $database;

  public function __construct(Connection $database) {
    $this->database = $database;
  }

  public function getTotalProducts(): int {
    return (int) \Drupal::entityQuery('node')
      ->condition('type', 'product')
      ->accessCheck(FALSE)
      ->count()
      ->execute();
  }

  public function getTotalInquiries(): int {
    return (int) $this->database->select('product_inquiry', 'pi')
      ->countQuery()
      ->execute()
      ->fetchField();
  }

  public function getRecentProducts($limit = 5): array {
    $nids = \Drupal::entityQuery('node')
      ->condition('type', 'product')
      ->accessCheck(FALSE)
      ->sort('created', 'DESC')
      ->range(0, $limit)
      ->execute();
    return Node::loadMultiple($nids);
  }

}
