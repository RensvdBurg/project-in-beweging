<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Meldingen op de telefoon
    |--------------------------------------------------------------------------
    |
    | null  = automatisch: aan in de NativePHP-app op een toestel, uit in de browser.
    | true  = altijd aan (bijv. in tests met een nep-plugin).
    | false = altijd uit.
    |
    */

    'phone_notifications' => env('UPMOVE_PHONE_NOTIFICATIONS'),

];
