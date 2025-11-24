<?php

namespace Drupal\product_catalog_plus\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Form\FormBuilderInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;

/**
 * Provides an Inquiry Form block.
 *
 * @Block(
 *   id = "product_inquiry_block",
 *   admin_label = @Translation("Product Inquiry Form")
 * )
 */
class ProductInquiryBlock extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * The form builder.
   *
   * @var \Drupal\Core\Form\FormBuilderInterface
   */
  protected $formBuilder;

  /**
   * Constructs a ProductInquiryBlock object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Form\FormBuilderInterface $form_builder
   *   The form builder.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, FormBuilderInterface $formBuilder) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->formBuilder = $formBuilder;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('form_builder')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $node = \Drupal::routeMatch()->getParameter('node');    
    
    if ($node instanceof \Drupal\node\NodeInterface && $node->bundle() === 'product') {
      $form = $this->formBuilder->getForm('\\Drupal\\product_catalog_plus\\Form\\ProductInquiryForm', $node->id());
      
      // Add our custom library for styling and JavaScript.
      $form['#attached']['library'][] = 'product_catalog_plus/product_inquiry_form';
      
      // Add a wrapper class for styling.
      $form['#attributes']['class'][] = 'product-inquiry-form';
      
      return $form;
    }
    
    return [];
  }

}
