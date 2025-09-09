<?php

namespace Drupal\product_catalog_plus\Plugin\views\field;

use Drupal\views\Plugin\views\field\FieldPluginBase;
use Drupal\views\ResultRow;

/**
 * Field handler to display product inquiry count.
 *
 * @ViewsField("product_inquiry_count")
 */
class ProductInquiryCount extends FieldPluginBase {

  /**
   * {@inheritdoc}
   */
  public function defineOptions() {
    $options = parent::defineOptions();
    $options['label'] = ['default' => $this->t('Inquiry Count')];
    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function render(ResultRow $values) {
    $node = $values->_entity;
    
    if ($node && $node->bundle() === 'product') {
      $count = \Drupal::database()
        ->select('product_inquiry', 'pi')
        ->condition('pi.product_id', $node->id())
        ->countQuery()
        ->execute()
        ->fetchField();

      return [
        '#markup' => $count,
      ];
    }

    return [
      '#markup' => '0',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function query() {
    // This is a computed field, so we don't add anything to the query.
    // The field value is computed in the render method.
  }

} 