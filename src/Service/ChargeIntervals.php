<?php

namespace Drupal\vipps_recurring_payments\Service;


class ChargeIntervals
{
  public function getIntervals(string $interval = 'monthly'): array
  {
    switch ($interval) {
      case 'yearly':
        $intervals = array(
          'base_interval'         => 'MONTH',
          'base_interval_count'   => 12
        );
        break;
      case 'monthly':
        $intervals = array(
          'base_interval'         => 'MONTH',
          'base_interval_count'   => 1
        );
        break;
      case 'weekly':
        $intervals = array(
          'base_interval'         => 'WEEK',
          'base_interval_count'   => 1
        );
        break;
      case 'daily':
        $intervals = array(
          'base_interval'         => 'DAY',
          'base_interval_count'   => 1
        );
        break;
      default:
        throw new \Exception('Unsupported interval');
    }

    return $intervals;
  }
}
