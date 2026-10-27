<?php

/*
|--------------------------------------------------------------------------
| School identity
|--------------------------------------------------------------------------
|
| Printed in the header of report cards. Set these in the .env file.
|
*/

return [

    'name' => env('SCHOOL_NAME', env('APP_NAME', 'Établissement scolaire')),

    'address' => env('SCHOOL_ADDRESS'),

    'phone' => env('SCHOOL_PHONE'),

    'email' => env('SCHOOL_EMAIL'),

    'head_title' => env('SCHOOL_HEAD_TITLE', 'Le Chef d\'établissement'),

];
