<?php


class  MVVWB_Pricing_rules
{

    private $bookingData;
    private $config;

    public function __construct($bookingData, $config)
    {
        $this->bookingData = $bookingData;
        $this->config = $config;
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

    public function priceByRules()
    {

        $rules = $this->config['cost']['rules'];

        usort($rules, function ($a, $b) {
            if ($a['priority'] == $b['priority']) return 0;
            return (intval($a['priority']) < intval($b['priority'])) ? -1 : 1;
        });
        $default = $this->config['cost'];

        $start = $this->bookingData['start'];
        $end = $this->bookingData['end'];
        $perBlockDuration = $this->config['general']['blockDuration'];
        $interval = DateInterval::createFromDateString($perBlockDuration . ' ' . $this->getUnit());
        $period = new DatePeriod($start, $interval, $end);
        $costs = [];

        foreach ($period as $i => $dt) {
            $_dt = $dt->getTimestamp();
            $costs[$_dt] = $defaultSet = [
                'ruleApplied' => false,
                'unit' => $default['perUnit'],
                'fixed' => $default['fixedPrice']['price'],
                'perPerson' => $default['perPerson'],
                'personTypes' => $default['personTypes'],
            ];

            foreach ($rules as $rule) {
                if ($rule['type'] === 'dateRange' || $rule['type'] === 'dateTimeRange') {

                    $ruleStart = $rule['start'] * 1;
                    $ruleEnd = $rule['end'] * 1;
                    if ($rule['type'] === 'dateRange') {
                        // set $ruleEnd end of the day
                        $ruleEnd = $rule['end'] + 86400;
                    }

                    if ($_dt >= $ruleStart && $_dt <= $ruleEnd) {
                        $costs[$_dt] = $this->updateCostBasedOnRule($defaultSet, $rule['cost']);
                        $costs[$_dt]['ruleApplied'] = $i;
                    }
                } else if ($rule['type'] === 'weekDays') {
                    $days = $rule['days'];
                    if (empty($days)) {
                        continue;
                    }

                    if (in_array($dt->format('N'), $days)) {
                        $costs[$_dt] = $this->updateCostBasedOnRule($defaultSet, $rule['cost']);
                        $costs[$_dt]['ruleApplied'] = $i;
                    }
                } else if ($rule['type'] === 'timeRange' && $this->isUnitType(['hours', 'minutes'])) {
                    // extract  minutes from $dt
                    $dtTime = $dt->format('H') * 60 + $dt->format('i');
                    $ruleStart = $rule['start'] * 1;
                    $ruleEnd = $rule['end'] * 1;

                    if ($dtTime >= $ruleStart && $dtTime <= $ruleEnd) {
                        $costs[$_dt] = $this->updateCostBasedOnRule($defaultSet, $rule['cost']);
                        $costs[$_dt]['ruleApplied'] = $i;
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
                        if ($dtTime >= $ruleStart && $dtTime <= $ruleEnd) {
                            $costs[$_dt] = $this->updateCostBasedOnRule($defaultSet, $rule['cost']);
                            $costs[$_dt]['ruleApplied'] = $i;
                        }
                    }
                }
            }

        }

        $costsReduced = array_reduce($costs, function ($carry, $item) {
            if ($carry == null) {
                return $item;
            }
            return [
                'unit' => $carry['unit'] + $item['unit'],
                'fixed' => max($carry['fixed'],$item['fixed']),
                'perPerson' => $carry['perPerson'] + $item['perPerson'],
                'personTypes' => [
                    0 => array_merge($carry['personTypes'][0], ['cost' => $carry['personTypes'][0]['cost'] + $item['personTypes'][0]['cost']]),
                    1 => array_merge($carry['personTypes'][1], ['cost' => $carry['personTypes'][1]['cost'] + $item['personTypes'][1]['cost']]),
                ],
            ];
        });
        $costs = array_map(function($item){
            return [
                'unit' => $item['unit'],
                'fixed' => $item['fixed'],
                'perPerson' => $item['perPerson'],
                'personTypes' => [
                    0 => array_merge(['cost' => $item['personTypes'][0]['cost']]),
                    1 => array_merge(['cost' => $item['personTypes'][1]['cost']]),
                ],
            ];
        },$costs);

        return ['costs'=>$costsReduced,'breakDown'=>$costs];
    }


    public function updateCostBasedOnRule($cost, $rule)
    {
        if (isset($cost['unit'])) {
            $cost['unit'] = $this->evalRule($rule['unit'], $cost['unit']);//
        }
        if (isset($cost['fixed'])) {
            $cost['fixed'] = $this->evalRule($rule['fixed'], $cost['fixed']);//
        }
        if (isset($cost['perPerson'])) {
            $cost['perPerson'] = $this->evalRule($rule['perPerson'], $cost['perPerson']);//
        }
        if (isset($cost['personTypes']) && is_array($cost['personTypes']) && isset($rule['personTypes']) && is_array($rule['personTypes'])) {
            foreach ($cost['personTypes'] as $i => $personType) {
                $cost['personTypes'][$i]['cost'] = $this->evalRule($rule['personTypes'][$i], $cost['personTypes'][$i]['cost']);
            }
        }
        return $cost;
    }

    public function evalRule($rule, $price)
    {
        switch ($rule['operation']) {
            case '+':
                $price = $price + floatval($rule['value']);
                break;
            case '-':
                $price = $price - floatval($rule['value']);
                break;
            case '=':
                $price = floatval($rule['value']);
                break;
            case 'x':
                $price = $price * floatval($rule['value']);
                break;
            case '/':
                $price = $price / floatval($rule['value']);
                break;
            case '+%':
                $price = $price + $price * floatval($rule['value']) / 100;
                break;
            case '-%':
                $price = $price - $price * floatval($rule['value']) / 100;
                break;
        }
        return $price;
    }
}
