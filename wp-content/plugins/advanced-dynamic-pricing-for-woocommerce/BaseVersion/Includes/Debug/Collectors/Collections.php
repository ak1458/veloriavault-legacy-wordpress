<?php

namespace ADP\BaseVersion\Includes\Debug\Collectors;

use ADP\BaseVersion\Includes\Context;

defined('ABSPATH') or exit;

class Collections
{
    /**
     * @var Context
     */
    protected $context;

    /**
     * @param null $deprecated
     */
    public function __construct($deprecated = null)
    {
        $this->context = adp_context();
    }

    public function withContext(Context $context)
    {
        $this->context = $context;
    }

    /**
     * @return array
     */
    public function collect()
    {
        return [];
    }
}
