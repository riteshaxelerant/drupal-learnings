<?php
namespace Drupal\product_catalog_plus\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\Batch\BatchBuilder;

class ProductImportForm extends FormBase {

  public function getFormId() {
    return 'product_import_form';
  }

  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['csv_file'] = [
      '#type' => 'managed_file',
      '#title' => $this->t('Upload CSV File'),      
      '#upload_validators' => [
        'FileExtension' => ['extensions'=>'csv'],
        'FileSizeLimit' => ['fileLimit'=>1000000]
      ],
      '#required' => TRUE,
    ];
    $form['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Import Products'),
    ];
    return $form;
  }

  public function submitForm(array &$form, FormStateInterface $form_state) {
    $fids = $form_state->getValue('csv_file');
    if (!empty($fids)) {
        $file = \Drupal\file\Entity\File::load(reset($fids));
        $file->setPermanent();
        $file->save();

        $real_path = \Drupal::service('file_system')->realpath($file->getFileUri());
        $rows = array_map('str_getcsv', file($real_path));
        $data_rows = array_slice($rows, 1); // here we are ignoring the first row as that will be header.

        $batch = [
          'title' => $this->t('Importing Products...'),
          'operations' => [],
          'finished' => [get_class($this), 'batchFinished'],
        ];

        foreach ($data_rows as $row) {
          $batch['operations'][] = [
            [get_class($this), 'importRow'],
            [$row],
          ];
        }

        batch_set($batch);
    }
  }

  public static function importRow($row) {
    [$title, $desc, $price, $status] = $row;

    $node = \Drupal\node\Entity\Node::create([
      'type' => 'product',
      'title' => $title,
      'field_product_description' => ['value' => $desc],
      'field_price' => floatval($price),
      'status' => $status,
    ]);
    $node->save();
  }


  public function batchFinished($success, $results, $operations) {
    $this->messenger()->addMessage($this->t('Import completed.'));
  }
}
