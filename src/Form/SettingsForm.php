<?php

namespace Drupal\dekufinal\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\ConfigFormBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Configure deku settings for this site.
 */
class SettingsForm extends ConfigFormBase {

  /**
   * Count all created table on the page.
   *
   * @var int
   */
  public int $countTable = 1;

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
   * Array with non-empty key cells for first table.
   *
   * @var string[]
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

    // Key cell for calculated data.
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
  public function getFormId() {
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

    $this->createTable($form, $form_state);

    $form['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Submit'),
      '#ajax' => [
        'callback' => '::reloadAjaxTable',
        'wrapper' => 'deku-form',
      ]
    ];

    $form['#attached']['library'][] = 'dekufinal/dekufinal';
    return $form;

  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {

    // Save cells from the first not empty to end.
    $arrCell = [];
    $firstDataCell = FALSE;

    for ($table = 0; $table < $this->countTable; $table++) {
      // Array with not empty cell names.
      $tableTrue = [];
      for ($row = 0; $row < $this->countRows; $row++) {
        foreach($this->cellData as $colKey) {

          // Key without table number.
          $key = 'col-' . $colKey . '-row-' . $row . '-from-';

          // Using the function hasValue() does not produce a good result.
          $cellValue = !empty($form_state->getValue($key . $table));


          if ($cellValue) {
            $tableTrue[] = $key;
          }

          if ($firstDataCell) {
            $arrCell[$key . $table] = $cellValue;
          }
          // Search first data cell.
          if (!$firstDataCell && $cellValue) {
            $arrCell[$key . $table] = TRUE;
            $firstDataCell = TRUE;
          }
        }
      }
      // If the first non-empty value is not found, then there is no data.
      if (!$firstDataCell) {
        $this->messenger->addWarning('The table cannot be empty.');
        break;
      }

      // Search empty cells in array between not empty cells.
      if ($table == 0) {
        $emptyCells = $this->filterArrayCell($arrCell);
        $this->arrData = $tableTrue;
        foreach ($emptyCells as $keyCell) {
          $form_state->setErrorByName(
            $keyCell,
            'Table should not contain breaks.'
          );
        }
      }

      // Finding array difference in tables.
      if ($table != 0) {
        $different = array_diff($this->arrData, $tableTrue);
        foreach ($different as $nameCell) {
          $nameCell = $nameCell . $table;
          $form_state->setErrorByName($nameCell, 'Tables should be similar.');
        }
      }
    }
  }

  /**
   * Filtering empty and non-empty cells into different arrays.
   *
   * @param array $arrCell
   *   Array cells with not null first cell.
   *
   * @return string[]
   *   Two arrays with empty and non-empty values.
   */
  protected function filterArrayCell(array $arrCell) {

    $endDataKey = array_search(TRUE, array_reverse($arrCell));
    $arrFalse = [];
    foreach ($arrCell as $key => $value) {
      if (!$value) {
        $arrFalse[] = $key;
      }
      if ($key == $endDataKey) {
        return $arrFalse;
      }
    }

  }

  /**
   * Create table from headers and rows.
   */
  public function createTable(array &$form, FormStateInterface $form_state) {

    $this->generateHeaderTable();
    for ($id = 0; $id < $this->countTable; $id++) {
      $tableKey = 'table-' . $id;
      $form[$tableKey] = [
        '#type' => 'table',
        '#tree' => FALSE,
        '#header' => $this->headersTable,
        '#attributes' => [
          'class' => [
            'table_form',
          ],
        ],
      ];
      $this->createYears($id, $form[$tableKey], $form_state);
    }
  }

  /**
   * Render rows tables.
   */
  public function createYears(int $tableKey, array &$table, FormStateInterface $form_state) {
    for ($row = 0; $row < $this->countRows; $row++) {

      foreach ($this->headersTable as $colKey => $colName) {

        $cellKey = 'col-' . $colKey . '-row-' . $row . '-from-' . $tableKey;
        $table[$row][$cellKey] = [
          '#type' => 'number',
          '#step' => '0.01',
          '#attributes' => [
            'class' => [
              'cell_table',
            ],
          ],
        ];

        // For calculated values.
        if (!in_array($colKey, $this->cellData)) {
          $defaultValue = $form_state->getValue($cellKey, 0);
          $table[$row][$cellKey]['#disabled'] = TRUE;
          $table[$row][$cellKey]['#default_value'] = round($defaultValue, 2);
        }

        if ($colKey == 'year') {
          $table[$row][$cellKey]['#default_value'] = date('Y') - $row;
        }
      }
    }
  }

  /**
   * Increment count rows in tables.
   */
  public function addYears(array $form, FormStateInterface $form_state) {
    $this->countRows = $form_state->getValue('rowCount') + 1;
    $form_state->setRebuild();
    return $form;
  }

  /**
   * Increment count tables
   */
  public function addTable(array &$form, FormStateInterface $form_state) {
    $this->countTable = $form_state->getValue('tableCount') + 1;
    $form_state->setRebuild();
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    for ($table = 0; $table <= $this->countTable; $table++) {
      for ($row = 0; $row <= $this->countRows; $row++) {

        $keyPart2 = '-row-' . $row . '-from-' . $table;
        // Calculate result in other function.
        $calcRes = $this->calculateCells($keyPart2, $form, $form_state);

        // Save result values to form cell.
        foreach ($calcRes as $key => $res) {
          $form_state->setValue('col-' . $key . $keyPart2, $res);
        }
      }
    }
    $this->messenger->addStatus('Valid.');
    $form_state->setRebuild();
  }

  /**
   * Calculate value cells.
   *
   * @param string $keyTableRow
   *   Two part key name cell without month.
   *
   * @return float[]
   *   Result calculated value cells.
   */
  protected function calculateCells(string $keyTableRow, array &$form, FormStateInterface $form_state) {

    // Array values with key month.
    $cell = [];
    // Add month key and get value from cell.
    foreach ($this->cellData as $month) {
      $keyFull = 'col-' . $month . $keyTableRow;
      $cell[$month] = (int) $form_state->getValue($keyFull);
    }

    $q1 = ($cell['jan'] + $cell['feb'] + $cell['mar'] + 1) / 3;
    $q2 = ($cell['apr'] + $cell['may'] + $cell['jun'] + 1) / 3;
    $q3 = ($cell['jul'] + $cell['aug'] + $cell['sep'] + 1) / 3;
    $q4 = ($cell['oct'] + $cell['nov'] + $cell['dec'] + 1) / 3;

    return [
      'q1' => $q1,
      'q2' => $q2,
      'q3' => $q3,
      'q4' => $q4,
      'ytd' => ($q1 + $q2 + $q3 + $q3 + 1) / 4,
    ];
  }

  /**
   * Reload table form via ajax.
   */
  public function reloadAjaxTable(array &$form, FormStateInterface $form_state) {
    return $form;
  }

}
