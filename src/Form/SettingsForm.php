<?php

namespace Drupal\deku\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\jsonapi\JsonApiResource\Data;
use Symfony\Component\VarDumper\Cloner\Data as ClonerData;

/**
 * Configure deku settings for this site.
 */
class SettingsForm extends ConfigFormBase {

  /**
   * Count all created table on the page.
   *
   * @var int
   */
  public $countTable = 1;

  /**
   * Count rows in one table.
   *
   * @var int
   */
  public $countRows = 1;

  /**
   * All headers table with keys.
   *
   * @var string[]
   */
  private array $headersTable = [
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

  /**
   * Data entry cells.
   *
   * @var string[]
   */
  private array $cellData = [
    'jan', 'feb', 'mar',
    'apr', 'may', 'jun',
    'jul', 'aug', 'sep',
    'oct', 'nov', 'dec',
  ];

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'deku_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['deku.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['#prefix'] = '<div id="deku-form">';
    $form['#suffix'] = '</div>';
    $form['addYear'] = [
      '#type' => 'submit',
      '#value' => $this->t('Add year'),
      '#submit' => ['::addYears'],
    ];
    $form['addTable'] = [
      '#type' => 'submit',
      '#submit' => ['::addTable'],
      '#value' => $this->t('Add table'),
    ];





    $form['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Submit')
    ];

  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    foreach () {

    }
  }

  /**
   * Create table from headers and rows.
   */
  public function createTable(array &$form, FormStateInterface $form_state) {
    for ($i = 1; $i <= $this->countTable; $i++) {
      $tableKey = 'table-' . $i;
      $form[] = [
        '#type' => 'table',
        '#header' => $this->headersTable,
      ];
      $this->createYears($i, $form['table-'.], $form_state);
    }
  }

  public function addTable(array &$form, FormStateInterface $form_state) {
    $this->countTable++;
    $form_state->setRebuild();
    return $form;
  }

  public function createYears(array &$form, FormStateInterface $form_state) {
    for ($i = 0; $i <= $this->countRows; $i++) {
      $form[$i]['year']['#default_value'] = date('Y') - $i;
      foreach ($this->headersTable as $rowKey => $rowName) {
        $form[$i][$rowKey] = [
          '#type' => 'number',
        ];
        if (!isset($this->cellData[$rowKey])) {
          $defaultValue = 0;
          $form[$i][$rowKey] = [
            '#disabled' => TRUE,
            '#default_value' => round($defaultValue, 2),
          ];
        }
      }
    }
  }

  public function addYears(array $form, FormStateInterface $form_state) {
    $this->countRows++;
    $form_state->setRebuild();
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $valuesTable = $form_state->getValues();
    foreach(){
      foreach ($variable as $key => $value) {
        $q1 = ($row['jan'] + $row['feb'] + $row['mar'] + 1) / 3;
        $q2 = ($row['apr'] + $row['may'] + $row['jun'] + 1) / 3;
        $q3 = ($row['jul'] + $row['aug'] + $row['sep'] + 1) / 3;
        $q4 = ($row['oct'] + $row['nov'] + $row['dec'] + 1) / 3;
        $ytd = ($q1 + $q2 + $q3 + $q4 + 1) / 4;
      }
    }
    $this->config('deku.settings')
      ->set('example', $form_state->getValue('example'))
      ->save();

  }

  public function reloadAjaxTable(array &$form, FormStateInterface $form_state) {
    return $form;
  }

}
