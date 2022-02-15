<?php

namespace Package\SwooleBundle\Cron;

interface CronInterface
{
    /**
     * Cron Expression.
     *
     * Predefined Scheduling
     *
     * '@yearly'    => '0 0 1 1 *',
     * '@annually'  => '0 0 1 1 *',
     * '@monthly'   => '0 0 1 * *',
     * '@weekly'    => '0 0 * * 0',
     * '@daily'     => '0 0 * * *',
     * '@hourly'    => '0 * * * *',
     * '@EveryMinute'    => 'w* * * * *',
     * "@EveryMinute5'  => '*\/5 * * * *',
     * '@EveryMinute10'  => '*\/10 * * * *',
     * '@EveryMinute15'  => '*\/15 * * * *',
     * '@EveryMinute30'  => '*\/30 * * * *',
     *
     * @see https://crontab.guru
     */
    public const TIME = '';

    /**
     * Cron is Enable|Disable.
     */
    public const ENABLE = true;

    public function __invoke(): void;
}
