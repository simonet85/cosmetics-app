<?php

return [
    /*
     * How many codes one e-mail address, and one IP address, may request per hour.
     * E-mail costs nothing to send, so the limits are looser than for SMS; they still stop
     * the form from being used to flood someone's inbox.
     */
    'email_hourly_limit' => (int) env('EMAIL_OTP_EMAIL_HOURLY_LIMIT', 5),
    'ip_hourly_limit' => (int) env('EMAIL_OTP_IP_HOURLY_LIMIT', 20),

    /* Seconds an address must wait between two code requests. */
    'cooldown_seconds' => (int) env('EMAIL_OTP_COOLDOWN_SECONDS', 60),

    /* How long the code screen may stay open before the customer has to start again. */
    'pending_ttl_minutes' => (int) env('EMAIL_OTP_PENDING_TTL_MINUTES', 10),
];
