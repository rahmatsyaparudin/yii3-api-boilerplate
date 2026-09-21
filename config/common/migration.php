<?php

declare(strict_types=1);

return [
    // Required mapping: module (src/Migration/<Module>) => connection name
    // (db.<name>.* env keys). migrate:module fails without an entry.
    'moduleConnections' => [
        'Example' => 'default',
        'Auditable' => 'audit',
    ],
];
