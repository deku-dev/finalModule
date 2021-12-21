<?php

namespace Drupal\dekufinal\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\ConfigFormBase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\HtmlCommand;

/**
 * Configure deku settings for this site.
 */
class SettingsForm extends ConfigFormBase {

  /**
   * Count all created table on the page.
   *
   * @var int
   */
  public int $countTable = 0;

  /**
   * Count rows in one table.
   *
   * @var int
   */
  public int $countRows = 1;

  /**
   * All headers table with keys.
   *
   * @var string[]
   */
  protected array $headersTable;

  /**
   * Array for cell with start data and end data.
   *
   * @var float[]
   */
  private array $arrData;

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
  protected function getEditableConfigNames() {
    return [
      'your_module.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['#prefix'] = '<div id="deku-form">';
    $form['#suffix'] = '</div>';

    $form['result'] = [
      '#markup' => '<div id="deku-result"></div>'
    ];

    $form['rowCount'] = [
      '#type' => 'hidden',
      '#value' => $this->countRows,
    ];
    $form['tableCount'] = [
      '#type' => 'hidden',
      '#value' => $this->countTable,
    ];

    $form['addYear'] = [
      '#type' => 'submit',
      '#value' => $this->t('Add year'),
      '#submit' => ['::addYears'],
      '#ajax' => [
        'callback' => '::reloadAjaxTable',
        'wrapper' => 'deku-form',
      ]
    ];
    $form['addTable'] = [
      '#type' => 'submit',
      '#value' => $this->t('Add table'),
      '#submit' => ['::addTable'],
      '#ajax' => [
        'callback' => '::reloadAjaxTable',
        'wrapper' => 'deku-form',
      ]
    ];


    // Set value to the variables.
    // $this->generateHeaderTable();

    $this->createTable($form, $form_state);

    $form['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Submit'),
      '#ajax' => [
        'callback' => '::validateCell',
        'wrapper' => 'deku-form',
      ]
    ];
    return $form;

  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {

  }

  public function validateCell(array &$form, FormStateInterface $form_state) {
    $response = new AjaxResponse();

    $arrCell = [];
    $arrData = [];
    $arrNull = [];
    for ($table = 0; $table <= $this->countTable; $table++) {
      for ($row = 0; $row <= $this->countRows; $row++) {
        foreach($this->cellData as $colKey) {
          $key = 'col-' . $colKey . '-row-' . $row . '-from-' . $table;
          $cellValue = $form_state->hasValue($key);
          if (!$table) {
            
          }
          else {
            $arrCell[$key] = !$cellValue ? NULL : TRUE;
          }

        }
      }

    }
    return $response->addCommand(new HtmlCommand('#deku-result', var_dump($arrData)));
  }

  /**
   * Create table from headers and rows.
   */
  public function createTable(array &$form, FormStateInterface $form_state) {
    // Set value to the variables.
    $this->generateHeaderTable();
    for ($id = 0; $id < $this->countTable; $id++) {
      $tableKey = 'table-' . $id;
      $form[$tableKey] = [
        '#type' => 'table',
        '#tree' => FALSE,
        '#header' => $this->headersTable,
      ];
      $this->createYears($id, $form[$tableKey], $form_state);
    }
  }

  public function addTable(array &$form, FormStateInterface $form_state): array {
    $this->countTable = $form_state->getValue('tableCount') + 1;
    $form_state->setRebuild();
    return $form;
  }

  public function createYears(int $tableKey, array &$table, FormStateInterface $form_state) {
    for ($row = 0; $row < $this->countRows; $row++) {

      foreach ($this->headersTable as $colKey => $colName) {

        $cellKey = 'col-' . $colKey . '-row-' . $row . '-from-' . $tableKey;
        $table[$row][$cellKey] = [
          '#type' => 'number',
          '#step' => '0.01',
        ];
        if (!in_array($colKey, $this->cellData)) {
          $defaultValue = 1;
          $table[$row][$cellKey] = [
            '#type' => 'number',
            '#disabled' => TRUE,
            '#default_value' => round($defaultValue, 2),
          ];
        }
        if ($colKey == 'year') {
          $table[$row][$cellKey]['#default_value'] = date('Y') - $row;
        }

      }

    }
  }

  public function addYears(array $form, FormStateInterface $form_state): array {
    $this->countRows = $form_state->getValue('rowCount') + 1;
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
