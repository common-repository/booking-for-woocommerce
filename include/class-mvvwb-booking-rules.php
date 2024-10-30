<?php


class  MVVWB_Booking_rules
{

    private $bookingData;
    private $config;

    public function __construct($bookingData, $config)
    {
        $this->bookingData = $bookingData;
        $this->config = $config;
    }

    public function popDatesByDays($dates, $days)
    {
        if (count($dates)) {
            $dates = array_filter($dates, function ($va) use ($days) {
                if (in_array(date('D', $va), $days)) {
                    return false;
                }
                return true;
            });
        }
        return $dates;
    }

    public function popDates(&$dates, $start, $end)
    {
        if (count($dates)) {
            $dates = array_filter($dates, function ($va) use ($start, $end) {
                if ($va <= $end && $va >= $start) {
                    return false;
                }
                return true;
            });
        }
    }

    public function pushDates(&$dates, $start, $end)
    {
        for ($s = $start; $s <= ($end); $s = ($s + 86400)) { // add seconds
            $dates[] = $s;
        }
    }
    public function getUnit()
    {
        return $this->config['general']['durationUnit'];
    }
    public function isUnitType($type)
    {
        if (is_array($type)) {
            if (in_array($this->config['general']['durationUnit'], $type)) {
                return true;
            }
        } else if ($type === $this->config['general']['durationUnit']) {
            return true;
        }
        return false;
    }
    public function checkRule()
    {

        // need to check
        $rules = $this->config['availability']['rules'];
        usort($rules, function ($a, $b) {
            if ($a['priority'] == $b['priority']) return 0;
            return (intval($a['priority']) < intval($b['priority'])) ? -1 : 1;
        });


        if ($this->isUnitType(['hours', 'minutes'])) {
            $perBlockDuration = $this->config['general']['blockDuration'];
            $interval = DateInterval::createFromDateString($perBlockDuration . ' ' . $this->getUnit());
        } else {
            $interval = DateInterval::createFromDateString('1 day');
        }

        $period = new DatePeriod($this->bookingData['start'], $interval, $this->bookingData['end']);
        $isValid = true;
        foreach ($period as $i => $dt) {
            foreach ($rules as $rule) {
                if ($rule['type'] === 'dateRange' || $rule['type'] === 'dateTimeRange') {
                    $_dt = $dt->getTimestamp();

                    $ruleStart = $rule['start'] * 1;
                    $ruleEnd = $rule['end'] * 1;
                    if ($rule['type'] === 'dateRange') {
                        // set $ruleEnd end of the day
                        $ruleEnd = $rule['end'] + 86400;
                    }

                    if (
                        $rule['bookable'] === 'yes'
                        && $_dt >= $ruleStart
                        && $_dt <= $ruleEnd
                    ) {
                        $isValid = true;
                    } else if (
                        $rule['bookable'] === 'no' &&
                        $_dt >= $ruleStart &&
                        $_dt <= $ruleEnd
                    ) {
                        $isValid = false;
                    } else if (
                        $rule['bookable'] === 'holiday'  &&
                        $_dt >= $ruleStart &&
                        $_dt <= $ruleEnd
                    ) {

                        if ($i == 0) {
                            $isValid = false;
                        } else {
                            $isValid = true;
                        }
                    }
                } else if ($rule['type'] === 'weekDays') {
                    $days = $rule['days'];
                    if (empty($days)) {
                        continue;
                    }

                    if (in_array($dt->format('N'), $days)) {
                        if ($rule['bookable'] === 'yes') {
                            $isValid = true;
                        } else if ($rule['bookable'] === 'no') {
                            $isValid = false;
                        } else if ($rule['bookable'] === 'holiday') {
                            if ($i == 0) {
                                $isValid = false;
                            } else {
                                $isValid = true;
                            }
                        }
                    }
                } else if ($rule['type'] === 'timeRange' && $this->isUnitType(['hours', 'minutes'])) {
                    // extract  minutes from $dt
                    $dtTime = $dt->format('H') * 60 + $dt->format('i');
                    $ruleStart = $rule['start'] * 1;
                    $ruleEnd = $rule['end'] * 1;

                    if (
                        $rule['bookable'] === 'yes'
                        && $dtTime >= $ruleStart
                        && $dtTime <= $ruleEnd
                    ) {
                        $isValid = true;
                    } else if (
                        $rule['bookable'] === 'no' &&
                        $dtTime >= $ruleStart &&
                        $dtTime <= $ruleEnd
                    ) {
                        $isValid = false;
                    } else if (
                        $rule['bookable'] === 'holiday'  &&
                        $dtTime >= $ruleStart &&
                        $dtTime <= $ruleEnd
                    ) {

                        if ($i == 0) {
                            $isValid = false;
                        } else {
                            $isValid = true;
                        }
                    }
                } else if ($rule['type'] === 'timeWeekDays' && $this->isUnitType(['hours', 'minutes'])) {


                    $days = $rule['days'];
                    if (empty($days)) {
                        continue;
                    }
                    if (in_array($dt->format('N'), $days)) {
                        $dtTime = $dt->format('H') * 60 + $dt->format('i');
                        $ruleStart = $rule['timeRange']['start'] * 1;
                        $ruleEnd = $rule['timeRange']['end'] * 1;
                        if (
                            $rule['bookable'] === 'yes'
                            && $dtTime >= $ruleStart
                            && $dtTime <= $ruleEnd
                        ) {
                            $isValid = true;
                        } else if (
                            $rule['bookable'] === 'no' &&
                            $dtTime >= $ruleStart &&
                            $dtTime <= $ruleEnd
                        ) {
                            $isValid = false;
                        } else if (
                            $rule['bookable'] === 'holiday'  &&
                            $dtTime >= $ruleStart &&
                            $dtTime <= $ruleEnd
                        ) {

                            if ($i == 0) {
                                $isValid = false;
                            } else {
                                $isValid = true;
                            }
                        }
                    }
                }
            }

            if ($isValid === false) {
                break;
            }
        }

        return $isValid;

    }
}
