<?php

if (!defined('ABSPATH'))
    exit;

class MVVWB_Booking_Item extends MVVWB_Booking_Item_Base
{

    public $isValid = false;
    private $product_id;


    function __construct($product_id = false, $variation_id = false)
    {
        if ($variation_id) {
            $this->product_id = $variation_id;
            parent::__construct($variation_id);
        } else if ($product_id) {
            $this->product_id = $product_id;
            parent::__construct($product_id);
        }
    }

    public function getTimeSlots($data, $status = false)
    {

        $start = new DateTime($data['dateStart'], $this->timeZone);
        $transKey = 'mvvwb_timeSlots_' . $this->product_id . '_' . $start->format('Y-m-d');

        $trans = get_transient($transKey);
        $perBlockDuration = $this->config['general']['blockDuration'];
        $unitInMinutes = $this->getUnit() === 'hours' ? $perBlockDuration * 60 : $perBlockDuration;
        if ($trans === false) {

            $start->setTime(0, 0, 0);
            $end = new DateTime($data['dateStart'], $this->timeZone);
            $end->setTime(23, 59, 59);


            $ids = MVVWB_Booking::getBookingIdsByProduct($this->product_id, [
                'status' => mvvwb_booking_status('reserve'),
                'date_between' => [
                    'start' => $start,
                    'end' => $end
                ]

            ]);
            $slotes = [];
            foreach ($ids as $b) {
                $booking = new MVVWB_Booking($b);
                $bookingData = $booking->getBookingData(false);
                if ($this->getUnit() == 'hours' || $this->getUnit() == 'minutes') {


                    $interval = DateInterval::createFromDateString($unitInMinutes . ' minutes');
                    $startOn = new DateTime($bookingData['start'], $this->timeZone);
                    $startOn->modify('-' . $this->config['availability']['bufferPeriod']['value'] . ' ' . $this->config['availability']['bufferPeriod']['unit'])
                        ->modify('+1 second');
                    $endOn = new DateTime($bookingData['end'], $this->timeZone);
                    $endOn->modify('+' . $this->config['availability']['bufferPeriod']['value'] . ' ' . $this->config['availability']['bufferPeriod']['unit'])
                        ->modify('-1 second');

                    $period = new DatePeriod($startOn, $interval, $endOn);
                    foreach ($period as $dt) {
                        $key = $dt->format('H') * 60 + $dt->format('i');
                        if (isset($slotes[$key])) {
                            $slotes[$key] += $bookingData['count'];
                        } else {
                            $slotes[$key] = intval($bookingData['count']);
                        }
                    }
                }
            }


            $trans = $slotes;


            $endOfDay = new DateTime('+10 minutes', $this->timeZone);

            MVVWB_Transient::setTransient($transKey, $trans, $this->product_id, $endOfDay->getTimestamp());
        }


        $temp = new MVVWB_Temp($this->product_id);
        $cartItems = $temp->getTransient();

        $cartItemsToday = array_filter($cartItems, function ($item) use ($start) {
            $t = new DateTime($item['start'], $this->timeZone);
            if ($t->format('Y-m-d') == $start->format('Y-m-d')) {
                return true;
            } else {
                return false;
            }
        });
        foreach ($cartItemsToday as $item) {
            if ($this->getUnit() == 'hours' || $this->getUnit() == 'minutes') {
                $interval = DateInterval::createFromDateString($unitInMinutes . ' minutes');
                $startOn = new DateTime($item['start'], $this->timeZone);
                $startOn->modify('-' . $this->config['availability']['bufferPeriod']['value'] . ' ' . $this->config['availability']['bufferPeriod']['unit'])
                    ->modify('+1 second');
                $endOn = new DateTime($item['end'], $this->timeZone);
                $endOn->modify('+' . $this->config['availability']['bufferPeriod']['value'] . ' ' . $this->config['availability']['bufferPeriod']['unit'])
                    ->modify('-1 second');

                $period = new DatePeriod($startOn, $interval, $endOn);
                foreach ($period as $dt) {
                    $key = $dt->format('H') * 60 + $dt->format('i');
                    if (isset($trans[$key])) {
                        $trans[$key] += $item['count'];
                    } else {
                        $trans[$key] = intval($item['count']);
                    }
                }
            }
        }
        $maxCount = $this->config['general']['maxBookingPerBlock'];
        if (!$status) { // as of now $status has no usage, $status can be used once it want to show the available count for slotes
            $trans = array_filter($trans, function ($slot) use ($maxCount) {
                return $slot >= $maxCount;
            });
        }
        $resources = $this->getResources();
        foreach ($resources as $res) {
            if ($res['conf']['limitedResource'] && !$res['conf']['optional']) {
                $trans = $trans + $this->getTimeSlotsByResource($res['term_id'], $data);
            }
        }

        return ($trans);
    }

    public function getTimeSlotsByResource($id, $data)
    {
        $start = new DateTime($data['dateStart'], $this->timeZone);
        $transKey = 'mvvwb_timeSlots_res_' . $id . '_' . $start->format('Y-m-d');
        $trans = get_transient($transKey);
        $perBlockDuration = $this->config['general']['blockDuration'];
        $unitInMinutes = $this->getUnit() === 'hours' ? $perBlockDuration * 60 : $perBlockDuration;
        if ($trans === false) {
            $start->setTime(0, 0, 0);
            $end = new DateTime($data['dateStart'], $this->timeZone);
            $end->setTime(23, 59, 59);
            $ids = MVVWB_Booking::getBookingIdsByResource($id, [
                'status' => ['unpaid',
                    'pending-confirmation',
                    'confirmed',
                    'paid',
                    'complete']
                ,
                'date_between' => [
                    'start' => $start,
                    'end' => $end
                ]

            ]);
            $slotes = [];
            foreach ($ids as $b) {
                $booking = new MVVWB_Booking($b);
                $bookingData = $booking->getBookingData(false);
                $bookingResources = $booking->getResources();
                $resQ = 1;
                foreach ($bookingResources as $br) {
                    if ($br['term_id'] == $id) {
                        $resQ = ($br['quantity'] ? $br['quantity'] : 1);
                    }
                }
                if ($this->getUnit() == 'hours' || $this->getUnit() == 'minutes') {


                    $interval = DateInterval::createFromDateString($unitInMinutes . ' minutes');
                    $startOn = new DateTime($bookingData['start'], $this->timeZone);
                    $startOn->modify('-' . $this->config['availability']['bufferPeriod']['value'] . ' ' . $this->config['availability']['bufferPeriod']['unit'])
                        ->modify('+1 second');
                    $endOn = new DateTime($bookingData['end'], $this->timeZone);
                    $endOn->modify('+' . $this->config['availability']['bufferPeriod']['value'] . ' ' . $this->config['availability']['bufferPeriod']['unit'])
                        ->modify('-1 second');

                    $period = new DatePeriod($startOn, $interval, $endOn);
                    foreach ($period as $dt) {
                        $key = $dt->format('H') * 60 + $dt->format('i');
                        if (isset($slotes[$key])) {
                            if (!isset($slotes[$key][$bookingData['product_id']])) {
                                $slotes[$key][$bookingData['product_id']] = 0;
                            }
                            $slotes[$key][$bookingData['product_id']] += $bookingData['count'] * $resQ;
                        } else {
                            $slotes[$key] = [$bookingData['product_id'] => intval($bookingData['count'] * $resQ)];
                        }
                    }
                }
            }


            $trans = $slotes;


            $endOfDay = new DateTime('+10 minutes', $this->timeZone);

            MVVWB_Transient::setTransient($transKey, $trans, $id, $endOfDay->getTimestamp(), true);
        }

        $temp = new MVVWB_Temp();
        $cartItems = $temp->getResTransient($id);
        $cartItemsToday = array_filter($cartItems, function ($item) use ($start) {
            $t = new DateTime($item['start'], $this->timeZone);
            if ($t->format('Y-m-d') == $start->format('Y-m-d')) {
                return true;
            } else {
                return false;
            }
        });


        foreach ($cartItemsToday as $item) {
            if ($this->getUnit() == 'hours' || $this->getUnit() == 'minutes') {
                $interval = DateInterval::createFromDateString($unitInMinutes . ' minutes');
                $startOn = new DateTime($item['start'], $this->timeZone);
                $startOn->modify('-' . $this->config['availability']['bufferPeriod']['value'] . ' ' . $this->config['availability']['bufferPeriod']['unit'])
                    ->modify('+1 second');
                $endOn = new DateTime($item['end'], $this->timeZone);
                $endOn->modify('+' . $this->config['availability']['bufferPeriod']['value'] . ' ' . $this->config['availability']['bufferPeriod']['unit'])
                    ->modify('-1 second');

                $period = new DatePeriod($startOn, $interval, $endOn);
                foreach ($period as $dt) {
                    $key = $dt->format('H') * 60 + $dt->format('i');
                    if (isset($trans[$key])) {
                        if (!isset($trans[$key][$item['pId']])) {
                            $trans[$key][$item['pId']] = 0;
                        }
                        $trans[$key][$item['pId']] += $item['count'] * ($item['resource']['quantity'] ? $item['resource']['quantity'] : 1);
                    } else {
                        $trans[$key] = [$item['pId'] => intval($item['count']) * ($item['resource']['quantity'] ? $item['resource']['quantity'] : 1)];
                    }

                }
            }
        }


        $maxCapacity = get_term_meta($id, 'mvvwb_maxCapacity', true);
        $availableNumber = get_term_meta($id, 'mvvwb_availableNumber', true);
        $maxCapacity = ($maxCapacity) ? $maxCapacity : 1;
        $sharable = get_term_meta($id, 'mvvwb_sharable', true);
        $maxCount = $this->getMaxCountPerDay() / $this->config['general']['maxBookingPerBlock'];

        $resMaxCount = $maxCount * get_term_meta($id, 'mvvwb_availableNumber', true);


        foreach ($trans as $t => $slot) {
            if ($sharable) {
                $unUsedCount = $availableNumber;// Un used resouces count
                $freeResCount = $unUsedCount * $maxCapacity;
                foreach ($slot as $pId => $c) {
                    $freeResCount = $freeResCount - $c;
                }
                $trans[$t] = $freeResCount;
            } else {
                $unUsedCount = $availableNumber;// Un used resouces count
                foreach ($slot as $pId => $c) {
                    $unUsedCount = $unUsedCount - ceil($c / $maxCapacity);
                }
                $freeResCount = $unUsedCount * $maxCapacity;
                if (isset($slot[$this->product_id])) {
                    $freeResCount = $freeResCount + $maxCapacity - $slot[$this->product_id] % $maxCapacity;
                }
                $trans[$t] = $freeResCount;

            }
        }

        $trans = array_filter($trans, function ($slot) {
            return $slot <= 0;

        });

        return $trans;
    }

    public function getSlotes($status = false)
    {

        $trans = get_transient('mvvwb_slots_' . $this->product_id);

        if ($trans === false) {
            $today = new DateTime('now', $this->timeZone);

            switch ($this->config['availability']['maxAdvanceBooking']['unit']) {
                case 'days':
                    $end = new DateTime("+" . $this->config['availability']['maxAdvanceBooking']['value'] . " days", $this->timeZone);
                    break;
                case 'hours':
                    $end = new DateTime("+" . $this->config['availability']['maxAdvanceBooking']['value'] . " hours", $this->timeZone);
                    break;
                default:
                    $end = new DateTime("+" . $this->config['availability']['maxAdvanceBooking']['value'] . " days", $this->timeZone);
                    break;
            }

            $ids = MVVWB_Booking::getBookingIdsByProduct($this->product_id, [
                'status' => mvvwb_booking_status('reserve'),
                'date_between' => [
                    'start' => $today,
                    'end' => $end
                ]

            ]);

            $slotes = [];
            foreach ($ids as $b) {
                $booking = new MVVWB_Booking($b);
                $bookingData = $booking->getBookingData(false);
                if ($this->getUnit() == 'days') {
                    $interval = DateInterval::createFromDateString('1 day');
                    $startOn = new DateTime($bookingData['start'], $this->timeZone);
                    $startOn->modify('-' . $this->config['availability']['bufferPeriod']['value'] . ' ' . $this->config['availability']['bufferPeriod']['unit'])
                        ->setTime(0, 0, 0);
                    $endOn = new DateTime($bookingData['end'], $this->timeZone);
                    $endOn->modify('+' . $this->config['availability']['bufferPeriod']['value'] . ' ' . $this->config['availability']['bufferPeriod']['unit']);
                    if ($endOn->format('Hi') !== '0000') {

                        $endOn->modify('tomorrow midnight');

                    }


                    $period = new DatePeriod($startOn, $interval, $endOn);

                    foreach ($period as $dt) {
                        $key = $dt->format('Y-m-d');
                        if (isset($slotes[$key])) {
                            $slotes[$key] += $bookingData['count'];
                        } else {
                            $slotes[$key] = intval($bookingData['count']);
                        }
                    }
                } else if ($this->isUnitType(['hours', 'minutes'])) {
                    $start = new DateTime($bookingData['start'], $this->timeZone);
                    $key = $start->format('Y-m-d');
                    if (isset($slotes[$key])) {
                        $slotes[$key] += $bookingData['count'];
                    } else {
                        $slotes[$key] = intval($bookingData['count']);
                    }
                }
            }

            $trans = $slotes;
            $endOfDay = new DateTime('tomorrow midnight', $this->timeZone); // today midnight be always past,
            // for 00:00 at today night we have to use "tomorrow"
            $_now = new DateTime('now', $this->timeZone);
            $interval = $endOfDay->getTimestamp() - $_now->getTimestamp();
            MVVWB_Transient::setTransient('mvvwb_slots_' . $this->product_id, $trans, $this->product_id, time() + $interval);
        }

        $temp = new MVVWB_Temp($this->product_id);
        $cartItems = $temp->getTransient();
        foreach ($cartItems as $item) {
            if ($this->getUnit() == 'days') {
                $interval = DateInterval::createFromDateString('1 day');
                $startOn = new DateTime($item['start'], $this->timeZone);
                $startOn->modify('-' . $this->config['availability']['bufferPeriod']['value'] . ' ' . $this->config['availability']['bufferPeriod']['unit'])
                    ->setTime(0, 0, 0);
                $endOn = new DateTime($item['end'], $this->timeZone);
                $endOn->modify('+' . $this->config['availability']['bufferPeriod']['value'] . ' ' . $this->config['availability']['bufferPeriod']['unit']);
                if ($endOn->format('Hi') !== '0000') {
                    $endOn->modify('tomorrow midnight');
                }

                $period = new DatePeriod($startOn, $interval, $endOn);
                foreach ($period as $dt) {
                    $key = $dt->format('Y-m-d');
                    if (isset($trans[$key])) {
                        $trans[$key] += $item['count'];
                    } else {
                        $trans[$key] = intval($item['count']);
                    }
                }
            } else if ($this->isUnitType(['hours', 'minutes'])) {
                $start = new DateTime($item['start'], $this->timeZone);
                $key = $start->format('Y-m-d');
                if (isset($trans[$key])) {
                    $trans[$key] += $item['count'];
                } else {
                    $trans[$key] = intval($item['count']);
                }
            }
        }

        $maxCount = $this->getMaxCountPerDay();
        if (!$status) {
            $trans = array_filter($trans, function ($slot) use ($maxCount) {
                return $slot >= $maxCount;
            });
        }
        $resources = $this->getResources();
        foreach ($resources as $res) {
            if ($res['conf']['limitedResource'] && !$res['conf']['optional']) {
                $trans = $trans + $this->getSlotsByResource($res['term_id']);
            }
        }
        return $trans;
    }

    public function getSlotsByResource($resId)
    {
        $transKey = 'mvvwb_slots_res_' . $resId;

        $trans = get_transient($transKey);

        if ($trans === false) {
            $today = new DateTime('now', $this->timeZone);
            switch ($this->config['availability']['maxAdvanceBooking']['unit']) {
                case 'days':
                    $end = new DateTime("+" . $this->config['availability']['maxAdvanceBooking']['value'] . " days", $this->timeZone);
                    break;
                case 'hours':
                    $end = new DateTime("+" . $this->config['availability']['maxAdvanceBooking']['value'] . " hours", $this->timeZone);
                    break;
                default:
                    $end = new DateTime("+" . $this->config['availability']['maxAdvanceBooking']['value'] . " days", $this->timeZone);
                    break;
            }

            $ids = MVVWB_Booking::getBookingIdsByResource($resId, [
                'status' => ['unpaid',
                    'pending-confirmation',
                    'confirmed',
                    'paid',
                    'complete']
                ,
                'date_between' => [
                    'start' => $today,
                    'end' => $end
                ]

            ]);

            $slotes = [];
            foreach ($ids as $b) {
                $booking = new MVVWB_Booking($b);
                $bookingData = $booking->getBookingData(false);
                $resQ = 1;
                $bookingResources = $booking->getResources();
                foreach ($bookingResources as $br) {
                    if ($br['term_id'] == $resId) {
                        $resQ = ($br['quantity'] ? $br['quantity'] : 1);
                    }
                }
                if ($this->getUnit() == 'days') {
                    $interval = DateInterval::createFromDateString('1 day');
                    $startOn = new DateTime($bookingData['start'], $this->timeZone);
                    $startOn->modify('-' . $this->config['availability']['bufferPeriod']['value'] . ' ' . $this->config['availability']['bufferPeriod']['unit'])
                        ->setTime(0, 0, 0);
                    $endOn = new DateTime($bookingData['end'], $this->timeZone);
                    $endOn->modify('+' . $this->config['availability']['bufferPeriod']['value'] . ' ' . $this->config['availability']['bufferPeriod']['unit']);
                    if ($endOn->format('Hi') !== '0000') {
                        $endOn->modify('tomorrow midnight');
                    }


                    $period = new DatePeriod($startOn, $interval, $endOn);

                    foreach ($period as $dt) {
                        $key = $dt->format('Y-m-d');
                        if (isset($slotes[$key])) {
                            if (isset($slotes[$key]['product_id'])) {
                                $slotes[$key]['product_id'] = 0;
                            }
                            $slotes[$key]['product_id'] += $bookingData['count'] * $resQ;
                        } else {
                            $slotes[$key] = [$bookingData['product_id'] => intval($bookingData['count']) * $resQ];
                        }
                    }
                } else if ($this->isUnitType(['hours', 'minutes'])) {
                    $start = new DateTime($bookingData['start'], $this->timeZone);
                    $key = $start->format('Y-m-d');
                    if (isset($slotes[$key])) {
                        if (isset($slotes[$key]['product_id'])) {
                            $slotes[$key]['product_id'] = 0;
                        }
                        $slotes[$key]['product_id'] += $bookingData['count'] * $resQ;
                    } else {
                        $slotes[$key] = [$bookingData['product_id'] => intval($bookingData['count']) * $resQ];
                    }
                }
            }

            $trans = $slotes;
            $endOfDay = new DateTime('tomorrow midnight', $this->timeZone); // today midnight be always past,
            // for 00:00 at today night we have to use "tomorrow"
            $_now = new DateTime('now', $this->timeZone);
            $interval = $endOfDay->getTimestamp() - $_now->getTimestamp();

            MVVWB_Transient::setTransient($transKey, $trans, $resId, time() + $interval, true);
        }

        $temp = new MVVWB_Temp();
        $cartItems = $temp->getResTransient($resId);
        foreach ($cartItems as $item) {
            if ($this->getUnit() == 'days') {
                $interval = DateInterval::createFromDateString('1 day');
                $startOn = new DateTime($item['start'], $this->timeZone);
                $startOn->modify('-' . $this->config['availability']['bufferPeriod']['value'] . ' ' . $this->config['availability']['bufferPeriod']['unit'])
                    ->setTime(0, 0, 0);
                $endOn = new DateTime($item['end'], $this->timeZone);
                $endOn->modify('+' . $this->config['availability']['bufferPeriod']['value'] . ' ' . $this->config['availability']['bufferPeriod']['unit']);
                if ($endOn->format('Hi') !== '0000') {
                    $endOn->modify('tomorrow midnight');
                }

                $period = new DatePeriod($startOn, $interval, $endOn);
                foreach ($period as $dt) {
                    $key = $dt->format('Y-m-d');
                    if (isset($trans[$key])) {
                        if (!isset($trans[$key][$item['pId']])) {
                            $trans[$key][$item['pId']] = 0;
                        }
                        $trans[$key][$item['pId']] += $item['count'] * ($item['resource']['quantity'] ? $item['resource']['quantity'] : 1);
                    } else {
                        $trans[$key] = [$item['pId'] => intval($item['count']) * ($item['resource']['quantity'] ? $item['resource']['quantity'] : 1)];
                    }
                }
            } else if ($this->isUnitType(['hours', 'minutes'])) {
                $start = new DateTime($item['start'], $this->timeZone);
                $key = $start->format('Y-m-d');
                if (isset($trans[$key])) {
                    if (!isset($trans[$key][$item['pId']])) {
                        $trans[$key][$item['pId']] = 0;
                    }
                    $trans[$key][$item['pId']] += $item['count'] * ($item['resource']['quantity'] ? $item['resource']['quantity'] : 1);
                } else {
                    $trans[$key] = [$item['pId'] => intval($item['count']) * ($item['resource']['quantity'] ? $item['resource']['quantity'] : 1)];
                }
            }
        }
        $maxCapacity = get_term_meta($resId, 'mvvwb_maxCapacity', true);
        $availableNumber = get_term_meta($resId, 'mvvwb_availableNumber', true);
        $maxCapacity = ($maxCapacity) ? $maxCapacity : 1;
        $sharable = get_term_meta($resId, 'mvvwb_sharable', true);
        $maxCount = $this->getMaxCountPerDay() / $this->config['general']['maxBookingPerBlock'];

        $resMaxCount = $maxCount * get_term_meta($resId, 'mvvwb_availableNumber', true);


        foreach ($trans as $t => $slot) {
            if ($sharable) {
                $unUsedCount = $availableNumber;// Un used resouces count
                $freeResCount = $unUsedCount * $maxCapacity;
                foreach ($slot as $pId => $c) {
                    $freeResCount = $freeResCount - $c;
                }
                $trans[$t] = $freeResCount;
            } else {
                $unUsedCount = $availableNumber;// Un used resouces count
                foreach ($slot as $pId => $c) {
                    $unUsedCount = $unUsedCount - ceil($c / $maxCapacity);
                }
                $freeResCount = $unUsedCount * $maxCapacity;
                if (isset($slot[$this->product_id])) {
                    $freeResCount = $freeResCount + $maxCapacity - $slot[$this->product_id] % $maxCapacity;
                }
                $trans[$t] = $freeResCount;
            }
        }
        $trans = array_filter($trans, function ($slot) {
            return $slot <= 0;

        });


        return $trans;// it returns how many res are free on each slot
    }

    public function getBookingData($summary = false)
    {
        if ($summary) {
            $summary = apply_filters('mvvwb_booking_summary_text', $this->bookingData['summary'], $this->bookingData);
            return $summary;
        }
        return $this->bookingData;
    }

    public function setBookingData($data)
    {

        $bookingCount = 1;
        if ($this->isPersonsAsBooking()) {
            $count = 0;
            if (isset($this->config['general']['enablePersonType']) && $this->config['general']['enablePersonType']) {

                if (isset($data['adult']) && !empty($data['adult'])) {
                    $count += $data['adult'];
                }
                if (isset($data['children']) && !empty($data['children'])) {
                    $count += $data['children'];
                }
            } else {

                if (isset($data['persons']) && $data['persons'] !== false) {
                    $count = $data['persons'];
                }
            }

            $bookingCount = ($count !== 0) ? $count : 1;
        }
        $data['count'] = $bookingCount * $data['quantity'];

        $this->bookingData = $data;
    }

    public function prepareData($data, $isPost = false, $dateFormat = false)
    {

        if ($dateFormat === false) {
            $dateFormat = 'Y-m-d'; //get_option('date_format');
        }
        $dateFormatView = get_option('date_format');
        $timeFormatView = get_option('time_format');
        if ($isPost) {
            $fields = [
                'mvvwb_start' => 'dateStart',
                'mvvwb_end' => 'dateEnd',
                'mvvwb_persons' => 'persons',
                'mvvwb_duration' => 'duration',
                'mvvwb_timeStart' => 'timeStart',
                'mvvwb_adult' => 'adult',
                'mvvwb_children' => 'children',
                'mvvwb_quantity' => 'quantity',
                'mvvwb_resources' => 'resources',
                'mvvwb_resources_quantity' => 'resourcesQ',
            ];
            $data = [];
            foreach ($fields as $key => $val) {
                if (isset($_REQUEST[$key])) {
                    if (is_array($_REQUEST[$key])) {
                        $data[$val] = [];
                        foreach ($_REQUEST[$key] as $k => $v) {
                            $data[$val][$k] = sanitize_text_field($v);
                        }
                    } else {
                        $data[$val] = sanitize_text_field($_REQUEST[$key]);
                    }
                }
            }
        }
        $start = false;
        $end = false;
        $perBlockDuration = $this->config['general']['blockDuration'];
        $isAllDayBooking = $this->config['general']['allDayBooking'];
        $this->bookingData = [];
        if (!isset($data['dateStart']) || empty($data['dateStart'])) {
            return false;
        }

        if ($this->isFixed()) {

            if ($this->getUnit() === 'days') {
                $start = DateTime::createFromFormat($dateFormat, $data['dateStart'], mvvwb_getTimeZone());
                $start->setTime(0, 0, 0);

                $end = clone $start; //DateTime::createFromFormat($dateFormat, $data['dateStart'], new DateTimeZone($timeZoneName));

                $end = $end->modify("+" . $perBlockDuration . ' days');

                if ($isAllDayBooking) {
                    $end = $end->modify("-1 second");
                }
//                else {
//                    $end = $end->modify("+" . $perBlockDuration . ' days');
//                }
//


                $days = mvvwb_daysDiff($start, $end, $isAllDayBooking);
                if ($start == $end) {
                    $booking_date = sprintf(_n('%1$s (%2$d day)', '%1$s (%2$d) days', $days, 'booking-for-woocommerce'), mvvwb_date($dateFormatView, $start->getTimeStamp()), $days);
                } else {
                    $booking_date = sprintf(_n('%1$s - %2$s (%3$d day)', '%1$s - %2$s (%3$d days)', $days, 'booking-for-woocommerce'), mvvwb_date($dateFormatView, $start->getTimeStamp()), mvvwb_date($dateFormatView, $end->getTimeStamp()), $days);
                }
                $this->bookingData['summary'] = $booking_date;
            } else if ($this->isUnitType(['hours', 'minutes'])) {
                if (!isset($data['timeStart']) || $data['timeStart'] === false) {
                    return false;
                }
                $unitInMinutes = $this->getUnit() === 'hours' ? $perBlockDuration * 60 : $perBlockDuration;
                $start = DateTime::createFromFormat($dateFormat, $data['dateStart'], mvvwb_getTimeZone());
                $start->setTime(0, 0, 0);
                $start->setTime(0, $data['timeStart'], 0);

                $end = clone $start; //
                //                $end = DateTime::createFromFormat($dateFormat, $data['dateStart'], new DateTimeZone($timeZoneName));
                $end->setTime(0, $data['timeStart'] + $unitInMinutes, 0);
                $this->bookingData['timeStart'] = $data['timeStart'];
                $booking_date = sprintf(__('%1$s  %2$s - %3$s', 'booking-for-woocommerce'), mvvwb_date($dateFormatView, $start->getTimeStamp()), mvvwb_date($timeFormatView, $start->getTimeStamp()), mvvwb_date($timeFormatView, $end->getTimeStamp()));
                $this->bookingData['summary'] = $booking_date;
            }
            $this->bookingData['duration'] = 1;
        } else if (!$this->isFixed()) {
            if ($this->getUnit() === 'days') {

                $start = DateTime::createFromFormat($dateFormat, $data['dateStart'], mvvwb_getTimeZone());
                $start->setTime(0, 0, 0);
                if ($this->isDateRangeEnabled()) {
                    if (isset($data['dateEnd']) && !empty($data['dateEnd']) && $data['dateEnd'] !== false) {
                        $end = DateTime::createFromFormat($dateFormat, $data['dateEnd'], mvvwb_getTimeZone());
                        $end->setTime(0, 0, 0);
                        $this->bookingData['duration'] = mvvwb_daysDiff($start, $end);
                    } else {
                        return false;
                    }
                } else {
                    if (isset($data['duration'])) {
                        $this->bookingData['duration'] = $data['duration'];
                        $end = clone $start; //
                        //                        $end = DateTime::createFromFormat($dateFormat, $data['dateStart'], new DateTimeZone($timeZoneName));
                        $end->modify("+" . $perBlockDuration * $data['duration'] . ' days');
                        if ($isAllDayBooking) {
                            $end->modify("-1 second");
                        }
//                        else {
//                            $end->modify("+" . $perBlockDuration * $data['duration'] . ' days');
//                        }

                    } else {
                        return false;
                    }
                }
                $days = mvvwb_daysDiff($start, $end, $isAllDayBooking);

                if ($start == $end) {
                    $booking_date = sprintf(_n('%1$s (%2$d day)', '%1$s (%2$d) days', $days, 'booking-for-woocommerce'), mvvwb_date($dateFormatView, $start->getTimeStamp()), $days);
                } else {
                    $booking_date = sprintf(_n('%1$s - %2$s (%3$d day)', '%1$s - %2$s (%3$d days)', $days, 'booking-for-woocommerce'), mvvwb_date($dateFormatView, $start->getTimeStamp()), mvvwb_date($dateFormatView, $end->getTimeStamp()), $days);
                }
                $this->bookingData['summary'] = $booking_date;
            } else if ($this->isUnitType(['hours', 'minutes'])) {
                if (!isset($data['timeStart']) || $data['timeStart'] === false) {
                    return false;
                }
                $unitInMinutes = $this->getUnit() === 'hours' ? $perBlockDuration * 60 : $perBlockDuration;
                $start = DateTime::createFromFormat($dateFormat, $data['dateStart'], mvvwb_getTimeZone());
                $start->setTime(0, $data['timeStart'], 0);
                if (isset($data['duration'])) {
                    $end = DateTime::createFromFormat($dateFormat, $data['dateStart'], mvvwb_getTimeZone());
                    $end->setTime(0, $data['timeStart'] + $unitInMinutes * $data['duration'], 0);

                    $this->bookingData['duration'] = $data['duration'];
                } else {
                    return false;
                }


                $this->bookingData['timeStart'] = $data['timeStart'];
                $booking_date = sprintf(__('%1$s  %2$s - %3$s', 'booking-for-woocommerce'), mvvwb_date($dateFormatView, $start->getTimeStamp()), mvvwb_date($timeFormatView, $start->getTimeStamp()), mvvwb_date($timeFormatView, $end->getTimeStamp()));
                $this->bookingData['summary'] = $booking_date;
            }
        }
        if (isset($this->config['general']['enableQuantity']) && $this->config['general']['enableQuantity']) {
            $this->bookingData['quantity'] = (isset($data['quantity']) && isset($data['quantity'])) ? $data['quantity'] : 1;
        } else {
            $this->bookingData['quantity'] = 1;
        }
        if ($this->config['general']['enablePerson']) {
            if (isset($this->config['general']['enablePersonType']) && $this->config['general']['enablePersonType']) {
                $flag = true;
                if (isset($data['adult']) && !empty($data['adult'])) {
                    $this->bookingData['adult'] = $data['adult'];
                    $flag = false;
                }
                if (isset($data['children']) && !empty($data['children'])) {
                    $this->bookingData['children'] = $data['children'];
                    $flag = false;
                }
                if ($flag) {
                    return false;
                }
            } else {
                if (isset($data['persons']) && !empty($data['persons'])) {
                    $this->bookingData['persons'] = $data['persons'];
                } else {
                    return false;
                }
            }
        }
        $itemResources = $this->getResources();
        if (!isset($data['resources'])) {
            $data['resources'] = [];
        }
        $this->bookingData['resources'] = [];
        if ($itemResources && count($itemResources)) {
            $this->bookingData['resources'] = [];
            foreach ($itemResources as $res) {
                if ($res['conf']['optional'] == false || in_array($res['term_id'], $data['resources'])) {
                    $quantity = false;
                    if ($res['conf']['enableQuantity']) {
                        $quantity = isset($data['resourcesQ'][$res['term_id']]) ? intval($data['resourcesQ'][$res['term_id']]) : $res['conf']['minQuantity'];
                        if ($quantity > $res['conf']['maxQuantity']) {
                            $quantity = $res['conf']['maxQuantity'];
                        }
                    }
                    $this->bookingData['resources'][] = [
                        'term_id' => $res['term_id'],
                        'name' => $res['name'],
                        'hidden' => $res['conf']['hidden'] ? true : false,
                        'limitedResource' => $res['conf']['limitedResource'] ? true : false,
                        'quantity' => $quantity
                    ];
                }
                //
            }
        }

        $this->bookingData['start'] = $start; // includes time also
        $this->bookingData['end'] = $end; // includes time also
        $bookingCount = 1;
        if ($this->isPersonsAsBooking()) {
            $bookingCount = $this->personsCount();
        }
        $this->bookingData['count'] = $bookingCount * $this->bookingData['quantity'];


        return true;
    }

    public function getCartData($excludeQuantity = false)
    {
        $bookingData = $this->bookingData;
        if ($excludeQuantity) {
            unset($bookingData['quantity']);
            unset($bookingData['count']);
        }

        return ['bookingData' => $bookingData,
            'labels' => $this->labels,
            'requireConf' => $this->requiresConfirmation()
        ];

        //        return [
        //            'start' => ['label' => $this->labels['start'], 'value' => $this->bookingData['start']->format('Y-m-d')],
        //            'end' => $this->isDateRangeEnabled() ? ['label' => $this->labels['end'], 'value' => $this->bookingData['end']->format('Y-m-d')] : false,
        //            'timeStart' => (isset($this->bookingData['timeStart']) && $this->bookingData['timeStart']) ? ['label' => $this->labels['timeStart'], 'value' => $this->bookingData['timeStart']] : false,
        //            'duration' => ['label' => $this->labels['duration'], 'value' => $this->bookingData['duration']],
        //            'persons' => (isset($this->bookingData['persons']) && $this->bookingData['persons']) ? ['label' => $this->labels['persons'], 'value' => $this->bookingData['persons']] : false
        //        ];
    }

    public function requiresConfirmation()
    {
        return $this->config['general']['requireConfirmation'];
    }

    public function checkAvailability($exclude = false, $isAdmin = false, $excludeSameSession = false)
    {
        // validate date

        $status = false;
        $minMax = $this->checkMinMax();
        if ($minMax === false || (!is_bool($minMax) && $minMax['status'] === false)) {
            return $this->checkAvailabilityFilter($minMax);
        }

        if (!$isAdmin) {
            $checkRules = $this->checkRules();
            if (!$checkRules) {

                return $this->checkAvailabilityFilter(false);
            }
        }


        $checkBookings = $this->checkBookings($exclude, $excludeSameSession);
        if ($checkBookings === false) {

            return $this->checkAvailabilityFilter(false);
        } else if (isset($checkBookings['status']) && $checkBookings['status'] === false) {

            return $this->checkAvailabilityFilter($checkBookings);
        }

        $checkResource = $this->checkResources($exclude, $excludeSameSession);
        if ($checkResource === false) {
            return $this->checkAvailabilityFilter(false);
        } else if (isset($checkResource['status']) && $checkResource['status'] === false) {
            return $this->checkAvailabilityFilter($checkResource);

        }

        //        $dateTimeObj['from'];
        //        if($this->config['general']['minTime']['unit']);
        return $this->checkAvailabilityFilter(true);

    }

    public function checkAvailabilityFilter($status)
    {
        return apply_filters('mvvwb_check_availability', $status, $this->bookingData, $this->product_id, $this->item_id);
    }

    public function processCost($cost){

        if (isset($cost['fixed']) && !empty($cost['fixed']) && $cost['fixed'] > 0) {
            if ( isset($this->config['cost']['fixedPrice']['multiplyByPerson']) &&
                $this->config['cost']['fixedPrice']['multiplyByPerson']  ) {

                $costs[] = [
                    'label' => mvvwb_getConfig('labels.fixedCharge', 'Fixed Charge'),
                    'price' => $cost['fixed'] * $this->bookingData['persons'],
                ];

            } else {
                $costs[] = [
                    'label' => mvvwb_getConfig('labels.fixedCharge', 'Fixed Charge'),
                    'price' => $cost['fixed'],
                ];
            }
        }


        if (isset($this->bookingData['persons']) && $this->config['cost']['multiplyByPerson']) {
            $costs[] = [
                'label' => mvvwb_getConfig('labels.bookingPrice', 'Booking Price'),
                'price' => $cost['unit'] * $this->bookingData['persons'],
            ];
        } else if (
            isset($this->bookingData['persons']) &&
            $cost['perPerson'] &&
            $cost['perPerson'] > 0 && ($this->bookingData['persons'] - $this->config['cost']['freePersonsCount']) > 0
        ) {
            $costs[] = [
                'label' => mvvwb_getConfig('labels.bookingPrice', 'Booking Price'),
                'price' => $cost['unit'],
            ];
            $costs[] = [
                'label' => mvvwb_getConfig('labels.bookingPricePersons', 'Booking Price Persons'),
                'price' => $cost['perPerson'] * ($this->bookingData['persons'] - $this->config['cost']['freePersonsCount']),
            ];
        } else if (
            isset($cost['personTypes']) && (isset($this->bookingData['adult']) || isset($this->bookingData['children']))

        ) {
            $costs[] = [
                'label' => mvvwb_getConfig('labels.bookingPrice', 'Booking Price'),
                'price' => $cost['unit'],
            ];
            $freeCount = $this->config['cost']['freePersonsCount'];
            if (
                isset($this->bookingData['adult']) && isset($cost['personTypes'][0]['cost']) &&
                $cost['personTypes'][0]['cost'] > 0 &&
                !empty($this->bookingData['adult']) && $this->bookingData['adult'] > 0
            ) {

                $personCount = ($this->bookingData['adult'] - $freeCount);
                if ($personCount > 0) {
                    $costs[] = [
                        'label' => mvvwb_getConfig('labels.bookingPriceAdult', 'Booking Price Adult'),
                        'price' => $cost['personTypes'][0]['cost'] * ($personCount),
                    ];
                    $freeCount = 0;
                } else {
                    $freeCount = $personCount * -1;
                }
            }

            if (
                isset($this->bookingData['children']) && isset($cost['personTypes'][1]['cost']) &&
                $cost['personTypes'][1]['cost'] > 0 &&
                !empty($this->bookingData['children']) && $this->bookingData['children'] > 0
            ) {
                $personCount = ($this->bookingData['children'] - $freeCount);
                if ($personCount > 0) {
                    $costs[] = [
                        'label' => mvvwb_getConfig('labels.bookingPriceChildren', 'Booking Price Children'),
                        'price' => $cost['personTypes'][1]['cost'] * ($personCount),
                    ];
                }
            }
        } else {
            $costs[] = [
                'label' => mvvwb_getConfig('labels.bookingPrice', 'Booking Price'),
                'price' => $cost['unit'],
            ];
        }


        if (isset($this->bookingData['resources']) && count($this->bookingData['resources'])) {
            $resPriceTotal = 0;
            foreach ($this->bookingData['resources'] as &$res) {
                $conf = [];
                $resPrice = 0;
                $price = get_term_meta($res['term_id'], 'mvvwb_price', true);
                $multiplyByUnit = get_term_meta($res['term_id'], 'mvvwb_multiplyByUnit', true);
                $multiplyByPerson = get_term_meta($res['term_id'], 'mvvwb_multiplyByPerson', true);

                if ($multiplyByPerson) {
                    if ($this->isPersonsTypeEnabled()) {
                        $costPersonType = get_term_meta($res['term_id'], 'mvvwb_costPersonType', true);
                        $adultPrice = ($costPersonType['adult'] == '') ? $price : $costPersonType['adult'];
                        $childPrice = ($costPersonType['child'] == '') ? $price : $costPersonType['child'];
                        $resPrice += $adultPrice * ((isset($this->bookingData['adult']) && $this->bookingData['adult']) ? $this->bookingData['adult'] : 0)
                            + ($childPrice * (isset($this->bookingData['children'])
                                && $this->bookingData['children']) ?
                                $this->bookingData['children'] : 0);
                    } else if ($this->isPersonsEnabled() && isset($this->bookingData['persons'])) {
                        $resPrice += $price * $this->bookingData['persons'];
                    } else {
                        $resPrice = $price;
                    }
                } else {
                    $resPrice = $price;
                }
                if ($res['quantity']) {
                    $resPrice = $resPrice * $res['quantity'];
                }

                if ($multiplyByUnit) {
                    $resPrice = $resPrice * $this->bookingData['duration'];
                }
                $res['price'] = $resPrice;
                $resPriceTotal += $resPrice;
                //
            }
            if ($resPriceTotal > 0) {
                $costs[] = [
                    'label' => mvvwb_getConfig('labels.resourcePrice', 'Resources Price'),
                    'price' => $resPriceTotal,
                ];
            }

        }
return $costs;
    }
    public function calculateCost($context = 'view', $includeQuantity = true)
    {
        $perBlockDuration = $this->config['general']['blockDuration'];
        $costs = [];
        $total = 0;
        $multiplySymb = '×';
        $costFull = $this->getPriceByRules();
        $cost = $costFull['costs'];
        $costBreakDown = $costFull['breakDown']; //  this data not using in current versions, will implement in future



        $total = 0;
        $multiCurrency = new MVVWB_Compatibility_MC();
        $costs = $this->processCost($cost);
        foreach ($costs as $k => $item) {

            if (($context === 'view')) {
                $price = $multiCurrency->convert($item['price']);
            } else {
                $price = $item['price'];
            }

            if ($this->isQuantityEnabled() && $includeQuantity) {
                $price = $price * $this->bookingData['quantity'];
                $costs[$k]['label'] = $costs[$k]['label'] . ' <b>' . $multiplySymb . ' ' . $this->bookingData['quantity'] . '</b>';
            }
            $total += $price;
            if (($context === 'view')) {
                $costs[$k]['price'] = wc_price($price);
                $costs[$k]['priceRaw'] = $price;
            } else {
                $costs[$k]['price'] = ($price);
            }
        }
        /* breakDown feature not providing now, commenting it now
        foreach ($costBreakDown as $dt=>$b){
            $_total = 0;
            $costBreakDown[$dt] = ['items'=>$this->processCost($b)];

            foreach ($costBreakDown[$dt]['items'] as $k => $item) {
                if (($context === 'view')) {
                    $price = $multiCurrency->convert($item['price']);
                } else {
                    $price = $item['price'];
                }

                if ($this->isQuantityEnabled() && $includeQuantity) {
                    $price = $price * $this->bookingData['quantity'];
                    $costBreakDown[$dt]['items'][$k]['label'] = $costBreakDown[$dt]['items'][$k]['label'] . ' <b>' . $multiplySymb . ' ' . $this->bookingData['quantity'] . '</b>';
                }
                $_total += $price;
                if (($context === 'view')) {
                    $costBreakDown[$dt]['items'][$k]['price'] = wc_price($price);
                    $costBreakDown[$dt]['items'][$k]['priceRaw'] = $price;
                } else {
                    $costBreakDown[$dt]['items'][$k]['price'] = ($price);
                }


            }
            if (($context === 'view')) {
                $costBreakDown[$dt]['total'] = wc_price($_total);
                $costBreakDown[$dt]['totalRaw'] = $_total;
            } else {
                $costBreakDown[$dt]['total'] = $_total;
            }

        }
*/
        if (($context === 'view')) {
                return [
                    'total' => wc_price($total),
                    'totalRaw'=>$total,
                    'items' => $costs];

        } else {
            return ['total' => $total, 'items' => $costs];
        }
    }

    public function addBooking($order_id, $order_item, $user = false)
    {
        // Gather post data.

        $my_post = array(
            'post_title' => 'Booking #',
            'post_content' => '',
            'post_status' => $this->requiresConfirmation() ? 'pending-confirmation' : 'unpaid',
            'post_parent' => $order_id,
            'post_author' => 1,
            'post_type' => MVVWB_BOOKING_PT
        );

        $post_id = wp_insert_post($my_post);
        if (!is_numeric($post_id)) {
            return false;
        }
        $user_id = get_current_user_id();
        wp_update_post(['ID' => $post_id, 'post_title' => 'Booking #' . $post_id]);


        update_post_meta($post_id, '_mvvwb_booking_customer_id', $user_id);
        //        update_post_meta($post_id, '_mvvwb_booking_status', 'pending');
        update_post_meta($post_id, '_mvvwb_product_id', $this->product_id);

        update_post_meta($post_id, '_mvvwb_order_id', $order_id);
        update_post_meta($post_id, '_mvvwb_order_item_id', $order_item->get_id());
        update_post_meta($post_id, '_mvvwb_all_day', 0);


        $this->updateBooking($post_id, $order_item);

        $temp = new MVVWB_Temp($this->product_id);
        $temp->clear();
        $booking = new MVVWB_Booking($post_id);
        $booking->scheduleEvents();
        do_action('mvv_booking_admin_new_booking', $post_id);
        return $post_id;
    }


    //    public function removeFromCart($cart_item_key)
    //    {
    //        $key = MVVWB_TRANSIENT_KEY . '_' . $this->product_id;
    //        $transient = get_transient($key);
    //        if (isset($transient[$cart_item_key])) {
    //            unset($transient[$cart_item_key]);
    //        }
    //        set_transient($key, $transient, 5 * 60); // 5 minutes
    //    }
    //
    //    public function addToCart($cart_item_key, $bookingData)
    //    {
    //
    //        $key = MVVWB_TRANSIENT_KEY . '_' . $this->product_id;
    //        $transient = get_transient($key);
    //        $transient[$cart_item_key] = $bookingData;
    //        set_transient($key, $transient, 5 * 60); // 5 minutes
    //    }

    public function updateBooking($bookingId, $order_item = false)
    {
        $saveAsGmt = mvvwb_getConfig('settings.save_as_gmt', true);
        $start = clone $this->bookingData['start'];
        $end = clone $this->bookingData['end'];
        $zoneOffSet = get_option('gmt_offset');
        if ($saveAsGmt) {
            $start->setTimezone(new DateTimeZone('GMT'));
            $end->setTimezone(new DateTimeZone('GMT'));
            $zoneOffSet = 0;
        }
        update_post_meta($bookingId, '_mvvwb_booking_start', $start->format('YmdHis'));
        update_post_meta($bookingId, '_mvvwb_booking_end', $end->format('YmdHis'));
        update_post_meta($bookingId, '_mvvwb_time_zone', $zoneOffSet);


        update_post_meta($bookingId, '_mvvwb_booking_duration', $this->bookingData['duration']);
        if ($order_item === false) {
            $order_item_id = get_post_meta($bookingId, '_mvvwb_order_item_id', true);
            $order_item = new WC_Order_Item_Product($order_item_id);
        }
        if ($order_item) {
            update_post_meta($bookingId, '_mvvwb_quantity', $order_item->get_quantity());
        }
        if (isset($this->bookingData['timeStart']) && $this->bookingData['timeStart'] !== false) {
            update_post_meta($bookingId, '_mvvwb_booking_timeStart', $this->bookingData['timeStart']);
        }
        if (isset($this->bookingData['persons']) && $this->bookingData['persons'] !== false) {
            update_post_meta($bookingId, '_mvvwb_booking_persons', $this->bookingData['persons']);
        }
        if (isset($this->bookingData['children']) && $this->bookingData['children'] !== false) {
            update_post_meta($bookingId, '_mvvwb_booking_children', $this->bookingData['children']);
        }
        if (isset($this->bookingData['adult']) && $this->bookingData['adult'] !== false) {
            update_post_meta($bookingId, '_mvvwb_booking_adult', $this->bookingData['adult']);
        }
        if (isset($this->bookingData['resources']) && $this->bookingData['resources'] !== false && $this->haveResources()) {
            update_post_meta($bookingId, '_mvvwb_resources', $this->bookingData['resources']);
            foreach ($this->bookingData['resources'] as $res) {
                add_post_meta($bookingId, '_mvvwb_resource_id', $res['term_id']);
            }
        }
        $bookingCount = 1;
        if ($this->isPersonsAsBooking()) {
            $bookingCount = $this->personsCount();
        }

        $quantity = get_post_meta($bookingId, '_mvvwb_quantity', true);
        if ($quantity) {
            $bookingCount = intval($quantity) * $bookingCount;
        }
        update_post_meta($bookingId, '_mvvwb_booking_count', $bookingCount);

        $item_data = [
            'durationUnit' => $this->config['general']['durationUnit'],
            'blockDuration' => $this->config['general']['blockDuration'],
            'requireConfirmation' => $this->config['general']['requireConfirmation'],
            'canCancel' => $this->config['general']['canCancel'],
        ];
        update_post_meta($bookingId, '_mvvwb_booking_item_data', $item_data);

        if (isset($this->bookingData['costs'])) {
            update_post_meta($bookingId, '_mvvwb_booking_costs', $this->bookingData['costs']);
        }

        $this->clearTrans($this->product_id);
    }

    static function clearTrans($p_id)
    {

        MVVWB_Transient::clearTransByProduct($p_id);

        $item = new MVVWB_Booking_Item($p_id);
        foreach ($item->getResources() as $res) {
            MVVWB_Transient::clearTransByResource($res['term_id']);
        }
    }
    // End enqueue_scripts ()
    // End instance()
}
