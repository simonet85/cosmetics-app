<?php

return [
    /*
     * How many codes one phone number, and one IP address, may request per hour.
     * The IP limit is the one to raise behind mobile carrier NAT.
     */
    'phone_hourly_limit' => (int) env('SMS_OTP_PHONE_HOURLY_LIMIT', 3),
    'ip_hourly_limit' => (int) env('SMS_OTP_IP_HOURLY_LIMIT', 5),

    /* Seconds a phone number must wait between two code requests. */
    'cooldown_seconds' => (int) env('SMS_OTP_COOLDOWN_SECONDS', 60),

    /* How long the code screen may stay open before the customer has to start again. */
    'pending_ttl_minutes' => (int) env('SMS_OTP_PENDING_TTL_MINUTES', 10),
];
