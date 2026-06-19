<?php

return [
    'url' => env('ERPNEXT_URL', 'https://gmp.ayperp.net'),
    'api_key' => env('ERPNEXT_API_KEY'),
    'api_secret' => env('ERPNEXT_API_SECRET'),
    'timeout' => (int) env('ERPNEXT_TIMEOUT', 90),
    'use_dummy_data' => env('ERPNEXT_USE_DUMMY_DATA', false),
    'connection' => env('ERPNEXT_DB_CONNECTION', 'erpnext'),
    'db' => [
        'host' => env('ERPNEXT_DB_HOST', '127.0.0.1'),
        'port' => env('ERPNEXT_DB_PORT', '3306'),
        'database' => env('ERPNEXT_DB_DATABASE'),
        'username' => env('ERPNEXT_DB_USERNAME'),
        'password' => env('ERPNEXT_DB_PASSWORD'),
    ],
    'cache_ttl' => (int) env('DASHBOARD_CACHE_TTL', 600),
    'ceo_max_execution' => (int) env('CEO_MAX_EXECUTION', 180),
    'payroll_max_execution' => (int) env('PAYROLL_MAX_EXECUTION', 300),
    'ias_bs_gl_max_rows' => (int) env('IAS_BS_GL_MAX_ROWS', 6000),
    'ias_pl_gl_max_rows' => (int) env('IAS_PL_GL_MAX_ROWS', 4000),
    'ias_gl_page_size' => (int) env('IAS_GL_PAGE_SIZE', 500),
    'default_company' => env('ERPNEXT_DEFAULT_COMPANY', 'GMP Foods (Pvt.) Ltd'),
    'default_currency' => env('ERPNEXT_DEFAULT_CURRENCY', 'PKR'),
];
