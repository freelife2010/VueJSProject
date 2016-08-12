<?php
return array(
    // set your paypal credential
    'client_id' => 'AQG-Mp4b7Igcclb9qzrD5zbTVgaCQ4MSFbc3gWAso4A6K03XJMk7smG68W0-s53HolUzEgS-ysqVu6xC',
    'secret' => 'ECbkWvlDRaFeiq8XGmQQInIwXsYfg4v34QiASSwVeBBt6FLU-Mbv_hcSDPyaEmQ6wtpcAG7cROWgHxIt',

    /**
     * SDK configuration
     */
    'settings' => array(
        /**
         * Available option 'sandbox' or 'live'
         */
        'mode' => 'sandbox',

        /**
         * Specify the max request time in seconds
         */
        'http.ConnectionTimeOut' => 30,

        /**
         * Whether want to log to a file
         */
        'log.LogEnabled' => true,

        /**
         * Specify the file that want to write on
         */
        'log.FileName' => storage_path() . '/logs/paypal.log',

        /**
         * Available option 'FINE', 'INFO', 'WARN' or 'ERROR'
         *
         * Logging is most verbose in the 'FINE' level and decreases as you
         * proceed towards ERROR
         */
        'log.LogLevel' => 'FINE'
    ),
);