<?php

return [


    'content' => [

        1 => [
            'Parties' => [
                "this contract is between **:workspace_name** also will be referred to as first party and **:username** as **:role** also will be referred as the second party"
            ]
        ],
        2 => [
            'Contract period' => [
                "This contract starts on **:start_at** and ends on **:end_at**"
            ]
        ],

        3 => [
            'Presence hours (minimum)' => [
                "The second party agrees to be present on the **:workspace_name** for a minimum of **:min_hours** hours",
                "If the working hours are less than the minimum hours of the contract, no payment will be made.",
            ]
        ],


        4 => [
            'Presence hours (maximum)' => [
                "The second party agrees to be present on the **:workspace_name** for a maximum of **:max_hours** hours",
                "If the working hours are more than the contract hours, payment will be made according to the maximum working hours.",
            ]
        ],

        5 => [
            'Payment Details' => [
                "The first party agrees to deposit **:per_hour** USDT per hour of the second party's work to the account introduced by the second party.",
                "The payment method is **:payment_method** , the second party will pay **:payment_period** to first party on payment address entered by first party **(:payment_address)**",
                "The first party will deposit the contract amount to the account of the second party no later than 7 days after the completion of the contract",

            ]
        ],

        6 => [
            'Renewal' => [
                "This contract will renew for another **:renewal_count** **:renew_time_period_type** automatically",
                "If neither of the two parties has any objection to the contract extension until **:renew_notice** days before the end of the contract.",
                "This means that any party that does not wish to renew the contract must notify the other party at least 10 days before the end of the contract."
            ]
        ],
        7 => [
            "Counting times" => [
                "The first party must be online on schedule times added to contract and only times count in schedule",
                "The first party must has jobs to count times in workspace",
            ]
        ],
        8 => [
            "Disclosure" => [
                "This agreement is not confidential and the parties are allowed to publicly disclose its provisions."
            ]
        ],


    ],


];
