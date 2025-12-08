<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Commission Roles
    |--------------------------------------------------------------------------
    |
    | Configure default base commission percent for each role type. Values are
    | used as sensible defaults and can be overridden per assignment.
    |
    */
    'roles' => [
        'acquirer'               => 3.0,
        'temp_relationship_owner'=> 1.0,
        'relationship_owner'     => 4.0,
        'closer'                 => 5.0,
        'pre_sales'              => 2.0,
        'execution_owner'        => 3.5,
    ],

    /*
    |--------------------------------------------------------------------------
    | Levels
    |--------------------------------------------------------------------------
    |
    | Level-based multipliers let you tune payouts for A/B/C tiers. These are
    | applied on top of the base percent when you calculate final commission.
    |
    */
    'levels' => [
        'A' => 1.2,
        'B' => 1.0,
        'C' => 0.8,
    ],

    /*
    |--------------------------------------------------------------------------
    | Level Priority
    |--------------------------------------------------------------------------
    |
    | Determines which level is picked first when multiple assignments exist
    | for the same role and no explicit ordering is provided.
    |
    */
    'level_order' => ['A', 'B', 'C'],
];
