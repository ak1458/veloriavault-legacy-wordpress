<?php

namespace ADP\BaseVersion\Includes\VolumePricingTable;

use ADP\BaseVersion\Includes\Context;
use ADP\BaseVersion\Includes\Core\RuleProcessor\BulkDiscount\BulkMeasurementEnum;
use ADP\BaseVersion\Includes\TemplateLoader;

defined('ABSPATH') or exit;

class Table
{
    /**
     * @var Context
     */
    protected $context;

    /**
     * @var string
     */
    protected $tableHeader;

    /**
     * @var array
     */
    protected $columns;

    /**
     * @var array[]
     */
    protected $rows;

    /**
     * @var array[]
     */
    protected $dataRows;

    /**
     * @var string
     */
    protected $tableFooter;

    /**
     * @var BulkMeasurementEnum
     */

    protected $measurement;

    /**
     * @var string
     */

    protected $layout;

    /**
     * @param null $deprecated
     */
    public function __construct($deprecated = null)
    {
        $this->context = adp_context();

        $this->tableHeader = '';
        $this->columns     = array();
        $this->rows        = array();
        $this->dataRows   = array();
        $this->tableFooter = '';
        $this->measurement = '';
        $this->layout      = '';
    }

    public function getHtml()
    {
        // fill not existing and remove redundant cells
        foreach ($this->rows as $index => $row) {
            $filteredRow = array();
            foreach (array_keys($this->columns) as $key) {
                $filteredRow[$key] = isset($row[$key]) ? $row[$key] : '';
            }

            if (count(array_filter($filteredRow)) > 0) {
                $this->rows[$index] = $filteredRow;
            } else {
                unset($this->rows[$index]);
            }
        }
        $this->rows = array_values($this->rows);

        $args = array(
            'header_html'  => $this->tableHeader,
            'table_header' => $this->columns,
            'rows'         => $this->rows,
            'data_rows'    => $this->dataRows,
            'footer_html'  => $this->tableFooter,
            'measurement'  => $this->measurement,
            'layout'       => $this->layout
        );

        ob_start();
        // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
        echo TemplateLoader::wdpGetTemplate("bulk-table.php", $args);

        return ob_get_clean();
    }

    public function setTableHeader($text)
    {
        if (is_string($text)) {
            $this->tableHeader = $text;
        }

        return $this;
    }

    public function addColumn($key, $title)
    {
        $this->columns[$key] = $title;

        return $this;
    }

    /**
     * @param array $row
     */
    public function addRow($row)
    {
        $this->rows[] = $row;
    }

    /**
     * @param array $data_row
     */
    public function addDataRow($data_row, $key=null)
    {
        if(is_null($key)) {
            $this->dataRows[] = $data_row;
        } else {
            $this->dataRows[$key] = $data_row;
        }
    }

    /**
     * @param BulkMeasurementEnum $measurement
     */

    public function setMeasurement($measurement) {
        $this->measurement = $measurement;
    }

    /**
     * @param string $layout
     */

    public function setLayout($layout) {
        $this->layout = $layout;
    }

    /**
     * @return array[]
     */
    public function getRows()
    {
        return $this->rows;
    }

    public function removeAllRows()
    {
        $this->rows = array();
    }

    public function removeAllColumns()
    {
        $this->columns = array();
    }

    public function setTableFooter($text)
    {
        if (is_string($text)) {
            $this->tableFooter = $text;
        }


        return $this;
    }
}
