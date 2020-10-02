<?php

    namespace AmaranthNetwork\Database\Builder;

    use AmaranthNetwork\Enums\Enum;

    /**
     * Class SQL_JOIN_TYPE
     *
     * @package AmaranthNetwork\Database\Builder
     */
    class SQL_JOIN_TYPE extends Enum{
        /** @var string */
        const LEFT  = 'LEFT';
        /** @var string */
        const RIGHT = 'RIGHT';
        /** @var string */
        const INNER = 'INNER';
        /** @var string */
        const OUTER = 'OUTER';
    }