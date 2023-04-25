<?php

function getConfig()
{
    return [

        // -------------------------------------------------------------------------------------------------------------
        // Web PKI Configuration
        // -------------------------------------------------------------------------------------------------------------
        'webPki' => [

            // Base64-encoded binary license for the Web PKI. This value is passed to Web PKI component's constructor.
            'license' => null
        ],

        // -------------------------------------------------------------------------------------------------------------
        // REST PKI Configuration
        // -------------------------------------------------------------------------------------------------------------
        'restPki' => [

            // =================================================
            //     >>>> PASTE YOU ACCESS TOKEN BELOW <<<<
            // =================================================
            'accessToken' => 'sDR6bSgXPFeLo93hTSU7werwZrXlM0mEND_oUMFf7cYNkCjC0sNthRgRK7lRJmJXMrrxClb9CGDEhB_CoYmUKWg98kYRbd1_DSr8qWFwuTXLew7X3p7yAVsv7LaPSwgm-U7b5PpXIj3CujaCOiKRAqsGxmBs6MhS52CzixKv8gaWiO1lI3fLiPzhCaBlK-FTb7PUB1zPNh6I4BWPXSU7dws0lRxlv1ENl5k3ILjO1LQU4sVMWph75tfvbLg8os7JWc2FLlfA-7Lu5vYvHBS5v_317WQEx4ztjYGxGCWIzu2_XZP9LZAW5St84dH0MU22B_DRP08V8-JeCOHw3MkPMVo_IOnZRQoA7qkBpaxAvPHhfI7nkXmxF5Snpcgj7qWkddOedD696RRypPQzu6QDlCOdUzcYsyztO-apQWjVRU8haxLYiHBD7VLTI4gJNg-SMXbQX4-yPt_wG9QYQKy3b45G7YaWFoS3cAwykFVnG0XBj9WSFnrAGHEJrzZGIxCvDkssew',

            // Address of your Rest PKI installation (with the trailing '/' character)
            "endpoint" => null
        ]
    ];
}