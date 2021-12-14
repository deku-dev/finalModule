<?php

namespace Drupal\dekufinal\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Form\FormBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Configure deku settings for this site.
 */
class SettingsForm extends FormBase {

  /**
   * Count all created table on the page.
   *
   * @var int
   */
  static int $countTable = 1;

  /**
   * Count rows in one table.
   *
   * @var int
   */
  static int $countRows = 1;

  /**
   * All headers table with keys.
   *
   * @var string[]
   */
  protected array $headersTable;

  /**
   * Data entry cells.
   *
   * @var string[]
   */
  protected array $cellData;

  /**
   * Set key and name headers table.
   */
  public function generateHeaderTable () {

    // Key cell of table.
    $this->headersTable = [
      'year' => $this->t('Year'),
      'jan' => $this->t('Jan'),
      'feb' => $this->t('Feb'),
      'mar' => $this->t('Mar'),
      'q1' => $this->t('Q1'),
      'apr' => $this->t('Apr'),
      'may' => $this->t('May'),
      'jun' => $this->t('Jun'),
      'q2' => $this->t('Q2'),
      'jul' => $this->t('Jul'),
      'aug' => $this->t('Aug'),
      'sep' => $this->t('Sep'),
      'q3' => $this->t('Q3'),
      'oct' => $this->t('Oct'),
      'nov' => $this->t('Nov'),
      'dec' => $this->t('Dec'),
      'q4' => $this->t('Q4'),
      'ytd' => $this->t('YTD'),
    ];

    // Key cell of calculated data.
    $this->cellData = [
      'jan', 'feb', 'mar',
      'apr', 'may', 'jun',
      'jul', 'aug', 'sep',
      'oct', 'nov', 'dec',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): SettingsForm {
    $instance = parent::create($container);
    $instance->setMessenger($container->get('messenger'));
    return $instance;
  }



  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'deku_settings';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    // $form['#prefix'] = '<div id="deku-form">';
    // $form['#suffix'] = '</div>';

    $form['addYear'] = [
      '#type' => 'submit',
      '#value' => $this->t('Add year'),
      '#ajax' => [
        'callback' => '::addYears',
        'wrapper' => 'deku-form',
      ]
    ];
    $form['addTable'] = [
      '#type' => 'submit',
      '#value' => $this->t('Add table'),
      '#ajax' => [
        'callback' => '::addTable',
        'wrapper' => 'deku-form',
      ]
    ];

    $form['result'] = [
      '#markup' => '<div id="deku-form"></div>'
    ];

    $this->createTable($form, $form_state);

    $form['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Submit'),
      '#ajax' => [
        'callback' => '::reloadAjaxTable',
        'wrapper' => 'deku-form',
      ]
    ];
    return $form;

  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    for ($i = 1; $i <= $this->countTable; $i++) {
      for ($i = 1; $i <= $this->countRows; $i++) {
        foreach($this->headersTable as $header) {
          $cell = $form_state->getValue();
        }
      }
    }
  }

  /**
   * Create table from headers and rows.
   */
  public function createTable(array &$form, FormStateInterface $form_state) {

    // Set value to the variables.
    $this->generateHeaderTable();

    for ($i = 1; $i <= $this->countTable; $i++) {
      $tableKey = 'table-' . $i;
      $form[$tableKey] = [
        '#type' => 'table',
        '#tree' => TRUE,
        '#header' => $this->headersTable,
      ];
      $this->createYears($form[$tableKey], $form_state);
    }
  }

  public function addTable(array &$form, FormStateInterface $form_state): array {
    $this->countTable++;
    $form_state->setRebuild();
    return $form;
  }

  public function createYears(array &$table, FormStateInterface $form_state) {
    for ($i = 1; $i <= $this->countRows; $i++) {

      foreach ($this->headersTable as $rowKey => $rowName) {
        $table[$i][$rowKey] = [
          '#type' => 'number',
          '#step' => '0.01',
        ];
        if (!array_key_exists($rowKey, $this->cellData)) {
          $defaultValue = 1;
          $table[$i][$rowKey] = [
            '#disabled' => TRUE,
            '#default_value' => round($defaultValue, 2),
          ];
        }
      }
      $table[$i]['year']['#default_value'] = date('Y') - $i;
    }
  }

  public function addYears(array $form, FormStateInterface $form_state): array {
    $this->countRows++;
    $form_state->setRebuild();
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $valuesTable = $form_state->getValues();
    foreach($valuesTable as $tableKey => $table){
      foreach ($table as $key => $row) {
        $q1 = ($row['jan'] + $row['feb'] + $row['mar'] + 1) / 3;
        $q2 = ($row['apr'] + $row['may'] + $row['jun'] + 1) / 3;
        $q3 = ($row['jul'] + $row['aug'] + $row['sep'] + 1) / 3;
        $q4 = ($row['oct'] + $row['nov'] + $row['dec'] + 1) / 3;
        $ytd = ($q1 + $q2 + $q3 + $q4 + 1) / 4;
      }
    }
    // $this->config('deku.settings')
    //   ->set('example', $form_state->getValue('example'))
    //   ->save();
    $this->messenger->addStatus('All cell is valid');


  }

  public function reloadAjaxTable(array &$form, FormStateInterface $form_state): array {
    return $form;
  }

}
