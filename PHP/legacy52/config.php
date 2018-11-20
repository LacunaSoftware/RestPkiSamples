<?php

function getConfig()
{
    return array(

        // -------------------------------------------------------------------------------------------------------------
        // Web PKI Configuration
        // -------------------------------------------------------------------------------------------------------------
        'webPki' => array(

            // Base64-encoded binary license for the Web PKI. This value is passed to Web PKI component's constructor.
            'license' => null
        ),

        // -------------------------------------------------------------------------------------------------------------
        // REST PKI Configuration
        // -------------------------------------------------------------------------------------------------------------
        'restPki' => array(

            // =================================================
            //     >>>> PASTE YOU ACCESS TOKEN BELOW <<<<
            // =================================================
            'accessToken' => 'YOUR API ACCESS TOKEN HERE',

            // Address of your REST PKI installation (with the trailing '/' character)
            'endpoint' => null
        )
    );
}