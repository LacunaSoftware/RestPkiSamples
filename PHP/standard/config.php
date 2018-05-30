<?php

function getConfig()
{
    return [

        // Web PKI Configuration
        // -------------------------------------------------------------------------------------------------------------

        'webPki' => [

            // Base64-encoded binary license for the Web PKI. This value is passed to Web PKI component's constructor.
            'license' => null
        ],

        // REST PKI Configuration
        // -------------------------------------------------------------------------------------------------------------

        'restPki' => [

            // =================================================
            //     >>>> PASTE YOU ACCESS TOKEN BELOW <<<<
            // =================================================
            'accessToken' => 'YOUR API ACCESS TOKEN HERE',

            // Address of your Rest PKI installation (with the trailing '/' character)
            "endpoint" => null
        ]
    ];
}