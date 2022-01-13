<?php

    namespace AmaranthNetwork\Database\Builder;

    use AmaranthNetwork\Enums\Enum;

    /**
     * Class SQL_Type
     *
     * @package AmaranthNetwork\Database\Builder
     */
    class SQL_Type extends Enum {
        /** @var string */
        CONST SELECT = 'SELECT';
        /** @var string */
        CONST INSERT = 'INSERT';
        CONST INSERT_IGNORE = 'INSERT_IGNORE';
        CONST INSERT_ON_DUPLICATE = 'INSERT_ON_DUPLICATE';
        CONST INSERT_IGNORE_RETURN = 'INSERT_IGNORE_RETURN';
        /** @var string */
        CONST DELETE = 'DELETE';
        /** @var string */
        CONST SHOW   = 'SHOW';
        /** @var string */
        CONST UPDATE = 'UPDATE';
        CONST UPDATE_IGNORE_RETURN = 'UPDATE_IGNORE_RETURN';
    }