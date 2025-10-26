<?php
defined('MOODLE_INTERNAL') || die();

$capabilities = [
    'tool/idnumbergenerator:manage' => [
        'riskbitmask' => RISK_DATALOSS | RISK_CONFIG,
        'captype' => 'write',
        'contextlevel' => CONTEXT_SYSTEM,
        'archetypes' => [
            'manager' => CAP_ALLOW,
            'admin' => CAP_ALLOW
        ],
        'clonepermissionsfrom' => 'moodle/site:config' // inherits same security level as site config
    ],
];
