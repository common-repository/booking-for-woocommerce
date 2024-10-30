<?php

if (!defined('ABSPATH'))
    exit;

class MVVWB_Booking_Item_Base
{

    public $isValid = false;
    public $config = false;
    public $item_id;
    public $timeZoneOffset = '0';
    public $timeZoneName = 'UTC';
    public $timeZone = false;
    public $bookingData = false;
    public $priceByRules = false;
    public $labels;
    public $statuses = false;
    public $isVariable = false;
    private $product_id;

    function __construct($product_id = false)
    {
        if ($product_id) {


            $this->product_id = $product_id;


            $product = wc_get_product($product_id); //it can be id of a variable item
            $parent_id = $product->get_parent_id();
            if ($parent_id === 0 || $parent_id === false) {
                $parent_id = $product_id;
                $this->isVariable = false;
            } else {
                $this->isVariable = true;
            }

            $mvv_bookable = ('yes' === get_post_meta($parent_id, '_mvvwb_bookable', true)) ? true : false;
            $item_id = get_post_meta($parent_id, '_mvvwb_product_meta', true);
            if (!$mvv_bookable || !$item_id) {
                return false;
            }

            $config = get_post_meta($item_id, '_mvvwb_config', true);

            if (!$config || empty($config)) {
                return false;
            }
            if (isset($config['cost']['perPerson']['cost'])) { // comparability with old version
                $config['cost']['perPerson'] = $config['cost']['perPerson']['cost'];
                $config['cost']['freePersonsCount'] = $config['cost']['perPerson']['after'];
            }

            if (isset($config['availability']['timeRange']['end']) && empty($config['availability']['timeRange']['end'])) { // comparability with old version
                $config['availability']['timeRange']['end'] = 1440;
            }

//            $config['general']['checkIn']=0;
//            $config['general']['checkOut']=1440;

            if (!isset($config['general']['timePickerFormat'])) { // comparability with old version
                $config['general']['timePickerFormat'] = mvvwb_getConfig('timePicker.format', '12');
            }
            if (!isset($config['general']['allDayBooking'])) { // comparability with old version
                $config['general']['allDayBooking'] = false;
            }
            $is_custom_price = get_post_meta($parent_id, '_mvvwb_is_custom_price', true);

            if ($is_custom_price == 'yes') {
                $custom_price = get_post_meta($parent_id, '_mvvwb_custom_price', true);
                if (isset($custom_price['perUnit']) && $custom_price['perUnit'] !== '') {
                    $config['cost']['perUnit'] = $custom_price['perUnit'];
                }

                if (isset($custom_price['perPerson']) && $custom_price['perPerson'] !== '') {
                    $config['cost']['perPerson'] = $custom_price['perPerson'];
                }
                if (isset($custom_price['fixedPrice']) && $custom_price['fixedPrice'] !== '') {
                    $config['cost']['fixedPrice']['price'] = $custom_price['fixedPrice'];
                }
                if (isset($custom_price['personType'])) {
                    $override = $custom_price['personType'];
                    $config['cost']['personTypes'] = array_map(function ($t) use ($override) {
                        if (isset($override[$t['type']]) && $override[$t['type']] !== '') {
                            $t['cost'] = $override[$t['type']];
                        }
                        return $t;
                    }, $config['cost']['personTypes']);
                }
            }


            if ((empty($config['cost']['perUnit']) || $config['cost']['perUnit'] == 0) && (empty($config['cost']['fixedPrice']['price']) || $config['cost']['fixedPrice']['price'] == 0) && (empty($config['cost']['perPerson']) || $config['cost']['perPerson'] == 0)

            ) {

                $config['cost']['perUnit'] = $product->get_price('edit'); // using 'edit' mode to ensure currency converters not converted it initially
            }

            if (apply_filters('mvvwb_force_inline_calendar', false, $item_id) && !isset($config['general']['forceInline'])) {
                $config['general']['forceInline'] = true;
            }


            $this->timeZoneOffset = _(get_option('gmt_offset'));
            $this->timeZone = mvvwb_getTimeZone();
            $this->timeZoneName = $this->timeZone->getName();

            $this->item_id = $item_id;
            $this->config = $config;
            $this->isValid = true;

            $this->labels = mvvwb_getAllConfig('labels');

            $statuses = array(
                'unpaid' => __('Unpaid', ''),
                'pending-confirmation' => __('Pending Confirmation', ''),
                'confirmed' => __('Confirmed', ''),
                'paid' => __('Paid', ''),
                'cancelled' => __('Cancelled', ''),
                'complete' => __('Complete', ''),
            );

            //

        }

        //

    }


    public function isVariable()
    {
        return $this->isVariable;
    }

    public function getPrice()
    {
        $fixed = 0;
        if (
            isset($this->config['cost']['fixedPrice']['price']) &&
            !empty($this->config['cost']['fixedPrice']['price']) && $this->config['cost']['fixedPrice']['price'] > 0
        ) {
            $fixed = $this->config['cost']['fixedPrice']['price'];
        }

        return ($this->config['cost']['perUnit'] + $fixed);
    }

    public function haveResources()
    {
        $terms = wp_get_post_terms($this->item_id, MVVWB_RESOURCE_TAX);
        if ($terms && $terms !== false) {
            return true;
        }
        return false;
    }

    public function getResources($type = false)
    {
        $resources = [];
        $terms = wp_get_post_terms($this->item_id, MVVWB_RESOURCE_TAX);
        if ($terms) {
            foreach ($terms as $term) {
                $priceText = '';
                $conf = [];

                $hidden = get_term_meta($term->term_id, 'mvvwb_hidden', true);
                if ($type == 'frontEnd' && $hidden && $hidden == true) {
                    continue;
                }

                $conf['optional'] = get_term_meta($term->term_id, 'mvvwb_optional', true);
                $conf['hidden'] = $hidden ? true : false;
                $price = get_term_meta($term->term_id, 'mvvwb_price', true);
                $conf['multiplyByUnit'] = get_term_meta($term->term_id, 'mvvwb_multiplyByUnit', true);
                $conf['multiplyByPerson'] = get_term_meta($term->term_id, 'mvvwb_multiplyByPerson', true);
                $conf['enableQuantity'] = get_term_meta($term->term_id, 'mvvwb_enableQuantity', true);
                $conf['maxQuantity'] = get_term_meta($term->term_id, 'mvvwb_maxQuantity', true);
                $conf['minQuantity'] = get_term_meta($term->term_id, 'mvvwb_minQuantity', true);
                $conf['availableNumber'] = get_term_meta($term->term_id, 'mvvwb_availableNumber', true);
                $conf['maxCapacity'] = get_term_meta($term->term_id, 'mvvwb_maxCapacity', true);
                $conf['sharable'] = get_term_meta($term->term_id, 'mvvwb_sharable', true);
                $conf['limitedResource'] = get_term_meta($term->term_id, 'mvvwb_limitedResource', true);
                $costPersonType = get_term_meta($term->term_id, 'mvvwb_costPersonType', true);
                if (empty($price) && $costPersonType['adult'] == '' && $costPersonType['child'] == '') {
                    $priceText = '';
                } else {
                    $multiCurrency = new MVVWB_Compatibility_MC();
                    $price = $multiCurrency->convert($price);
                    if ($costPersonType['adult'] !== '') {
                        $costPersonType['adult'] = $multiCurrency->convert($costPersonType['adult']);
                    }
                    if ($costPersonType['child'] !== '') {
                        $costPersonType['child'] = $multiCurrency->convert($costPersonType['child']);
                    }
                    if ($costPersonType['adult'] == '' && $costPersonType['child'] == '') {
                        $priceText = wc_price($price);
                    } else if (!$costPersonType['adult'] == '' && !$costPersonType['child'] == '') {
                        $priceText = wc_format_price_range($costPersonType['child'], $costPersonType['adult']);
                    } else {
                        $priceText = wc_format_price_range(($costPersonType['child'] == '') ? $price : $costPersonType['child'], ($costPersonType['adult'] == '') ? $price : $costPersonType['adult']);
                    }
                }
                if ($conf['multiplyByUnit'] && $conf['multiplyByPerson'] && $this->isPersonsEnabled()) {
                    $priceText .= ' <span class="mvvwb_price_text">' . mvvwb_getConfig('resource.resPriPerPpU', '') . '</span>';
                } else if ($conf['multiplyByUnit']) {
                    $priceText .= ' <span class="mvvwb_price_text">' . mvvwb_getConfig('resource.resPriPerU', 'Per Unit') . '</span>';
                } else if ($conf['multiplyByPerson']) {
                    $priceText .= ' <span class="mvvwb_price_text">' . mvvwb_getConfig('resource.resPriPerP', 'Per Person') . '</span>';
                } else {
                    $priceText .= ' <span class="mvvwb_price_text">' . mvvwb_getConfig('resource.resPriOnce', 'One time') . '</span>';
                }

                $resources[] = [
                    'term_id' => $term->term_id,
                    'name' => $term->name,
                    'desc' => $term->desciption,
                    'priceText' => $priceText,
                    'conf' => $conf
                ];
            }
        }
        return $resources;
    }

    public function isPersonsEnabled()
    {
        return $this->config['general']['enablePerson'];
    }

    public function can_be_cancelled()
    {
        return $this->config['general']['canCancel'] && isset($this->config['general']['cancelBefore']);
    }

    public function get_cancel_limit($format)
    {
        if (isset($this->config['general']['canCancel']) && $this->config['general']['canCancel']) {
            $dt = new DateTime();
            $dt->setTimezone(mvvwb_getTimeZone());
            $dt->modify('+ ' . $this->config['general']['cancelBefore']['value'] . ' ' . $this->config['general']['cancelBefore']['unit']);
            return $dt->format($format);
        }

        return false;

    }

    public function getConfig($type = 'frontEnd')
    {
        if ($type == 'frontEnd') {

            $config = $this->config;
            foreach ($config['availability']['rules'] as $k => $rule) {
                if ($rule['type'] === 'dateRange') {
                    $start = new DateTime('@' . (!empty($rule['start']) ? $rule['start'] : time()));
                    $start->setTimezone(mvvwb_getTimeZone());
                    $end = new DateTime('@' . (!empty($rule['end']) ? $rule['end'] : time()));
                    $end->setTimezone(mvvwb_getTimeZone());
                    $config['availability']['rules'][$k]['start'] = array_combine(
                        ['d', 'm', 'y'],
                        explode('-', $start->format('d-m-Y'))
                    );
                    $config['availability']['rules'][$k]['end'] = array_combine(
                        ['d', 'm', 'y'],
                        explode('-', $end->format('d-m-Y'))
                    );
                } else if ($rule['type'] === 'dateTimeRange') {
                    $start = new DateTime('@' . (!empty($rule['start']) ? $rule['start'] : time()));
                    $start->setTimezone(mvvwb_getTimeZone());
                    $end = new DateTime('@' . (!empty($rule['end']) ? $rule['end'] : time()));
                    $end->setTimezone(mvvwb_getTimeZone());
                    $config['availability']['rules'][$k]['start'] = array_combine(
                        ['d', 'm', 'y'],
                        explode('-', $start->format('d-m-Y'))
                    );
                    $config['availability']['rules'][$k]['start']['t'] = $start->format('H') * 60 + $start->format('i');
                    $config['availability']['rules'][$k]['end'] = array_combine(
                        ['d', 'm', 'y'],
                        explode('-', $end->format('d-m-Y'))
                    );
                    $config['availability']['rules'][$k]['end']['t'] = $end->format('H') * 60 + $end->format('i');
                }
            }
            return $config;
        } else {
            return $this->config;
        }
    }

    public function getTimeZone()
    {
        return $this->timeZone;
    }

    public function requireConfirmation()
    {
        return $this->config['general']['requireConfirmation'];
    }

    public function getUnAvailableSlotes()
    {
        return [];
    }

    public function isDateRangeEnabled()
    {

        return (!$this->config['general']['isFixed']
            && $this->config['general']['blockDuration'] === 1 &&
            $this->config['general']['durationUnit'] === 'days' &&
            $this->config['general']['showDateRangePicker'] === true);
    }

    public function getMaxCountPerDay()
    {
        if ($this->getUnit() == 'days') {
            $maxCount = $this->config['general']['maxBookingPerBlock'];
            return $maxCount;
        } else {
            $buffer = 0;
            if ($this->config['availability']['bufferPeriod']['unit'] == 'hours') {
                $buffer = $this->config['availability']['bufferPeriod']['value'] * 60;
            } else {
                $buffer = $this->config['availability']['bufferPeriod']['value'];
            }
            $perBlockDuration = $this->config['general']['blockDuration'];
            $unitInMinutes = $this->getUnit() === 'hours' ? $perBlockDuration * 60 : $perBlockDuration;
            $maxBlocks = ($this->config['availability']['timeRange']['end'] - $this->config['availability']['timeRange']['start']) / ($unitInMinutes + $buffer) + 1;

            return $this->config['general']['maxBookingPerBlock'] * $maxBlocks;
        }
    }

    public function getUnit()
    {
        return $this->config['general']['durationUnit'];
    }

    public function getPerBlockMinutes()
    {
        $perBlockDuration = $this->config['general']['blockDuration'];
        return $perBlockDuration * ($this->getUnit() === 'days' ? 24 * 60 : ($this->getUnit() === 'hours' ? 60 : 1));
    }

    public function checkRules()
    {

        $rules = $this->config['availability']['rules'];
        $checkRules = new MVVWB_Booking_rules($this->bookingData, $this->config);
        return $checkRules->checkRule();
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

    public function checkResources($exclude = false, $excludeSameSession = false)
    {
        $startOn = clone $this->bookingData['start'];

        $startOn->modify('-' . $this->config['availability']['bufferPeriod']['value'] . ' ' . $this->config['availability']['bufferPeriod']['unit'])
            ->modify('+1 second');
        $endOn = clone $this->bookingData['end'];
        $endOn->modify('+' . $this->config['availability']['bufferPeriod']['value'] . ' ' . $this->config['availability']['bufferPeriod']['unit'])
            ->modify('-1 second');

        if (isset($this->bookingData['resources']) && $this->bookingData['resources'] && is_array($this->bookingData['resources'])) {
            foreach ($this->bookingData['resources'] as $res) {
                if ($res['limitedResource']) {
                    if ($this->bookingData['duration'] > 1) { // check the booking per block. Checking directly start to end can cause errors
                        $perBlockDuration = $this->config['general']['blockDuration'];
                        $interval = DateInterval::createFromDateString($perBlockDuration . ' ' . $this->getUnit());
                        $period = new DatePeriod($startOn, $interval, $endOn);
                        $status = false;
                        $_start = false;

                        foreach ($period as $dt) {
                            if ($_start) {
                                $status = $this->checkResourcePerBlock($res, $_start, $dt, $exclude, $excludeSameSession);
                                if ($status === false || (isset($status['status']) && $status['status'] === false)) {
                                    return $status;
                                }
                            }
                            $_start = clone $dt;
                        }
                        $status = $this->checkResourcePerBlock($res, $_start, $endOn, $exclude, $excludeSameSession);
                        if ($status === false || (isset($status['status']) && $status['status'] === false)) {
                            return $status;
                        }


                    } else {
                        $status = $this->checkResourcePerBlock($res, $startOn, $endOn, $exclude, $excludeSameSession);
                        if ($status === false || (isset($status['status']) && $status['status'] === false)) {
                            return $status;
                        }
                    }
                }
            }
        }
        return true;


    }

    public function checkResourcePerBlock($resource, $startOn, $endOn, $exclude = false, $excludeSameSession = false)
    {
        $count = [];
        $temp = new MVVWB_Temp();
        $term_id = $resource['term_id'];
        $tempBookings = $temp->checkResource($term_id, ['start' => $startOn, 'end' => $endOn], $excludeSameSession);

        foreach ($tempBookings as $b) {
            if ($exclude && is_array($exclude) && in_array($b, $exclude)) {
                continue;
            }

            $pId = $b['pId'];
            if (!isset($count[$pId])) {
                $count[$pId] = 0;
            }
            $count[$pId] += $b['count'] * ($b['resource']['quantity'] ? $b['resource']['quantity'] : 1);
            //get_post_meta('_mvvwb_')
        }

        $bookings = MVVWB_Booking::getBookingIdsByResource($term_id, [
            'status' => ['unpaid',
                'pending-confirmation',
                'confirmed',
                'paid',
                'complete'],
            'date_between' => [
                'start' => $startOn,
                'end' => $endOn
            ]

        ]);

        foreach ($bookings as $b) {
            if ($exclude && is_array($exclude) && in_array($b, $exclude)) {
                continue;
            }
            $booking = new MVVWB_Booking($b);

            $pId = $booking->getProductId();
            if (!isset($count[$pId])) {
                $count[$pId] = 0;
            }
            $count[$pId] += $booking->getResourcesCount($term_id);

            //get_post_meta('_mvvwb_')
        }


        $slotNeeded = 1;
        if ($this->isPersonsAsBooking()) {
            $slotNeeded = $this->personsCount();
        }
        if ($this->isQuantityEnabled()) {
            $slotNeeded = $slotNeeded * $this->bookingData['quantity'];
        }
        $slotNeeded = $slotNeeded * ($resource['quantity'] ? $resource['quantity'] : 1);

        $availableNumber = get_term_meta($term_id, 'mvvwb_availableNumber', true);
        $sharable = get_term_meta($term_id, 'mvvwb_sharable', true);
        $maxCapacity = get_term_meta($term_id, 'mvvwb_maxCapacity', true);
        $maxCapacity = ($maxCapacity) ? $maxCapacity : 1;
        // $slotNeeded = ceil($slotNeeded/$maxCapacity);
        if ($sharable) {
            $unUsedCount = $availableNumber;// Un used resouces count
            $freeResCount = $unUsedCount * $maxCapacity;
            foreach ($count as $pId => $c) {
                $freeResCount = $freeResCount - $c;

            }

        } else {
            $unUsedCount = $availableNumber;// Un used resouces count
            foreach ($count as $pId => $c) {
                $unUsedCount = $unUsedCount - ceil($c / $maxCapacity);
            }
            $freeResCount = $unUsedCount * $maxCapacity;

            if (isset($count[$this->product_id])) {
                $freeResCount = $freeResCount + $maxCapacity - $count[$this->product_id] % $maxCapacity;
            }

        }

        if ($freeResCount < $slotNeeded) {
            if (!$resource['hidden']) {
                return ['status' => false, 'message' => sprintf(mvvwb_getConfig('messages.resouceNotAvailable', 'Resource %s Not Available'), $resource['name'])];
            } else {
                return false;
            }
        }
        return true;
    }

    public function isPersonsAsBooking()
    {
        return $this->config['general']['enablePerson'] && $this->config['general']['personsAsBooking'];
    }

    public function personsCount()
    {
        if (isset($this->config['general']['enablePersonType']) && $this->config['general']['enablePersonType']) {
            $count = 0;
            if (isset($this->bookingData['adult']) && !empty($this->bookingData['adult'])) {
                $count += $this->bookingData['adult'];
            }
            if (isset($this->bookingData['children']) && !empty($this->bookingData['children'])) {
                $count += $this->bookingData['children'];
            }
        } else {
            $count = $this->bookingData['persons'];
        }
        return $count;
    }

    public function isQuantityEnabled()
    {
        return isset($this->config['general']['enableQuantity']) ? $this->config['general']['enableQuantity'] : false;
    }

    public function checkBookings($exclude = false, $excludeSameSession = false)
    {


        $startOn = clone $this->bookingData['start'];

        $startOn->modify('-' . $this->config['availability']['bufferPeriod']['value'] . ' ' . $this->config['availability']['bufferPeriod']['unit'])
            ->modify('+1 second');
        $endOn = clone $this->bookingData['end'];
        $endOn->modify('+' . $this->config['availability']['bufferPeriod']['value'] . ' ' . $this->config['availability']['bufferPeriod']['unit'])
            ->modify('-1 second');

        if ($this->bookingData['duration'] > 1) { // check the booking per block. Checking directly start to end can cause errors
            $perBlockDuration = $this->config['general']['blockDuration'];
            $interval = DateInterval::createFromDateString($perBlockDuration . ' ' . $this->getUnit());
            $period = new DatePeriod($startOn, $interval, $endOn);
            $status = false;
            $_start = false;

            foreach ($period as $dt) {
                if ($_start) {
                    $status = $this->checkBookingPerBlock($_start, $dt, $exclude, $excludeSameSession);
                    if ($status === false || (isset($status['status']) && $status['status'] === false)) {
                        return $status;
                    }
                }
                $_start = clone $dt;
            }
            $status = $this->checkBookingPerBlock($_start, $endOn, $exclude, $excludeSameSession);

            return $status;


        } else {
            return $this->checkBookingPerBlock($startOn, $endOn, $exclude, $excludeSameSession);
        }

    }

    public function checkBookingPerBlock($startOn, $endOn, $exclude = false, $excludeSameSession = false)
    {
        $count = 0;
        $temp = new MVVWB_Temp($this->product_id);
        $tempBookings = $temp->checkBooking(['start' => $startOn, 'end' => $endOn], $excludeSameSession);


        foreach ($tempBookings as $b) {
            if ($exclude && is_array($exclude) && in_array($b, $exclude)) {
                continue;
            }

            $count += $b['count'];
            //get_post_meta('_mvvwb_')
        }

        $bookings = MVVWB_Booking::getBookingIdsByProduct($this->product_id, [
            'status' => mvvwb_booking_status('reserve'),
            'date_between' => [
                'start' => $startOn,
                'end' => $endOn
            ]

        ]);

        foreach ($bookings as $b) {
            if ($exclude && is_array($exclude) && in_array($b, $exclude)) {
                continue;
            }
            $booking = new MVVWB_Booking($b);
            $count += $booking->getBookingCount();

            //get_post_meta('_mvvwb_')
        }


        $slotNeeded = 1;
        if ($this->isPersonsAsBooking()) {
            $slotNeeded = $this->personsCount();
        }
        if ($this->isQuantityEnabled()) {
            $slotNeeded = $slotNeeded * $this->bookingData['quantity'];
        }
        $days = $this->config['general']['maxBookingPerBlock'] - $count;
        if ($days < $slotNeeded) {
            if ($this->isQuantityEnabled()) {
                return ['status' => false, 'message' => ($days > 1 ? sprintf(mvvwb_getConfig('messages.availQuantityP'), $days) : sprintf(mvvwb_getConfig('messages.availQuantityS'), $days))];
            } else {
                return false;
            }
        }
        return true;
    }

    public function isPersonsTypeEnabled()
    {
        return $this->config['general']['enablePerson'] && $this->config['general']['enablePersonType'];
    }

    public function isFixed()
    {
        return $this->config['general']['isFixed'];
    }

    public function getPriceByRules()
    {


        $default = $this->config['cost'];
        $pTypes = false;
        if (isset($default['personTypes'])) {
            if (is_array($default['personTypes'])) {
                $pTypes = array_map(function ($a) {
                    $a['cost'] = $this->bookingData['duration'] * $a['cost'];
                    return $a;
                }, $default['personTypes']);
            }
        }
        if ($this->bookingData === false
            || !isset($this->config['cost']['rules'])
            || count($this->config['cost']['rules']) == 0
            || $this->bookingData['start'] === false
            || $this->bookingData['end'] === false) {

            return [
                'costs'=>[
                    'unit' => $default['perUnit'] * $this->bookingData['duration'],
                    'fixed' => $default['fixedPrice']['price'],
                    'perPerson' => $default['perPerson'] * $this->bookingData['duration'],
                    'personTypes' => $pTypes],
                'breakDown'=>[]

            ];

        }


        if ($this->priceByRules === false) {
            $checkRules = new MVVWB_Pricing_rules($this->bookingData, $this->config);
            $this->priceByRules = $checkRules->priceByRules();
        }

        return $this->priceByRules;

    }



    public function checkMinMax()
    {

        switch ($this->config['availability']['minAdvanceBooking']['unit']) {
            case 'days':

                // here we can set time as 0:0:0 inorder to compare the days only
                $now = new DateTime("today +" . $this->config['availability']['minAdvanceBooking']['value'] . " days", $this->timeZone);
                $_start = clone $this->bookingData['start'];
                $_start->setTime(0, 0, 0);

                if ($_start < $now) {
                    return false;
                }
                break;
            case 'hours':
                $now = new DateTime("+" . $this->config['availability']['minAdvanceBooking']['value'] . " hours", $this->timeZone);
                //                $now->setTime(0, 0, 0);
                if ($this->bookingData['start'] < $now) {
                    return false;
                }
                break;
            case 'minutes':
                $now = new DateTime("+" . $this->config['availability']['minAdvanceBooking']['value'] . " minutes", $this->timeZone);
                //                $now->setTime(0, 0, 0);
                if ($this->bookingData['start'] < $now) {
                    return false;
                }
                break;
        }

        switch ($this->config['availability']['maxAdvanceBooking']['unit']) {
            case 'days':
                $now = new DateTime("+" . $this->config['availability']['maxAdvanceBooking']['value'] . " days", $this->timeZone);
                if ($this->bookingData['end'] > $now) {
                    return false;
                }
                break;
            case 'hours':
                $now = new DateTime("+" . $this->config['availability']['maxAdvanceBooking']['value'] . " hours", $this->timeZone);
                if ($this->bookingData['end'] > $now) {
                    return false;
                }
                break;
            case 'minutes':
                $now = new DateTime("+" . $this->config['availability']['maxAdvanceBooking']['value'] . " minutes", $this->timeZone);
                if ($this->bookingData['end'] > $now) {
                    return false;
                }
                break;
        }

        if ($this->isUnitType('hours', 'minutes')) {
            $this->config['availability']['timeRange']['start'];
            $this->config['availability']['timeRange']['end'];
            $startTime = $this->bookingData['start']->format('H') * 60 + $this->bookingData['start']->format('i');
            $endTime = $this->bookingData['end']->format('H') * 60 + $this->bookingData['end']->format('i');

            if ($startTime < $this->config['availability']['timeRange']['start']) {
                return false;
            }
            if ($endTime > $this->config['availability']['timeRange']['end']) {
                return false;
            }
        }
        if ($this->bookingData['duration'] < $this->config['general']['minBlocksBookable']) {
            return ['status' => false,
                'message' => sprintf(mvvwb_getConfig('messages.minBookable'), $this->config['general']['minBlocksBookable'])
            ];
        }
        if ($this->bookingData['duration'] > $this->config['general']['maxBlocksBookable']) {
            return ['status' => false,
                'message' => sprintf(mvvwb_getConfig('messages.maxBookable'), $this->config['general']['maxBlocksBookable'])
            ];
        }
        return true;
    }







    // End enqueue_scripts ()
    // End instance()
}
