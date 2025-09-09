<?php

namespace Drupal\product_catalog_plus\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\product_catalog_plus\Service\ProductStatsManager;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Returns the Product Dashboard page.
 */
class DashboardController extends ControllerBase {

  /**
   * The product stats manager service.
   *
   * @var \Drupal\product_catalog_plus\Service\ProductStatsManager
   */
  protected $statsManager;

  /**
   * Constructs a DashboardController object.
   *
   * @param \Drupal\product_catalog_plus\Service\ProductStatsManager $stats_manager
   *   The product stats manager service.
   */
  public function __construct(ProductStatsManager $stats_manager) {
    $this->statsManager = $stats_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('product_catalog_plus.stats_manager')
    );
  }

  /**
   * Returns the dashboard page.
   *
   * @return array
   *   A render array for the dashboard page.
   */
  public function dashboard() {
    // Get statistics using the injected service.
    $total_products = $this->statsManager->getTotalProducts();
    $total_inquiries = $this->statsManager->getTotalInquiries();
    $recent_products = $this->statsManager->getRecentProducts();

    $build = [
      '#type' => 'container',
      '#attributes' => ['class' => ['product-dashboard']],
      'welcome' => [
        '#type' => 'html_tag',
        '#tag' => 'h2',
        '#value' => $this->t('Product Dashboard'),
      ],
      'stats' => [
        '#type' => 'container',
        '#attributes' => ['class' => ['dashboard-stats']],
        'products' => [
          '#type' => 'html_tag',
          '#tag' => 'div',
          '#value' => $this->t('Total Products: @count', ['@count' => $total_products]),
        ],
        'inquiries' => [
          '#type' => 'html_tag',
          '#tag' => 'div',
          '#value' => $this->t('Total Inquiries: @count', ['@count' => $total_inquiries]),
        ],
      ],
      'recent_products' => [
        '#type' => 'container',
        '#attributes' => ['class' => ['recent-products']],
        'title' => [
          '#type' => 'html_tag',
          '#tag' => 'h3',
          '#value' => $this->t('Recent Products'),
        ],
        'list' => [
          '#theme' => 'item_list',
          '#items' => array_map(function($node) {
            return $node->label();
          }, $recent_products),
        ],
      ],
    ];

    return $build;
  }

}
