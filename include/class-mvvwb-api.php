<?php

if (!defined('ABSPATH'))
    exit;

class MVVWB_Api
{


    private static $_instance = null;

    public $_version;


    public function __construct()
    {

        add_action(
            'rest_api_init',
            function () {
                register_rest_route(
                    MVVWB_TOKEN . '/v1',
                    '/save/(?P<id>\d+)',
                    array(
                        'methods' => 'POST',
                        'callback' => array($this, 'saveItem'),
                        'permission_callback' => array($this, 'get_permission')
                    )
                );
                register_rest_route(
                    MVVWB_TOKEN . '/v1',
                    '/save_resource/(?P<id>\d+)',
                    array(
                        'methods' => 'POST',
                        'callback' => array($this, 'saveResource'),
                        'permission_callback' => array($this, 'get_permission')
                    )
                );
                register_rest_route(
                    MVVWB_TOKEN . '/v1',
                    '/save_config',
                    array(
                        'methods' => 'POST',
                        'callback' => array($this, 'saveConfig'),
                        'permission_callback' => array($this, 'get_permission')
                    )
                );
                register_rest_route(
                    MVVWB_TOKEN . '/v1',
                    '/get_config',
                    array(
                        'methods' => 'GET',
                        'callback' => array($this, 'getConfig'),
                        'permission_callback' => array($this, 'get_permission')
                    )
                );
                register_rest_route(
                    MVVWB_TOKEN . '/v1',
                    '/delete/',
                    array(
                        'methods' => 'POST',
                        'callback' => array($this, 'deleteItem'),
                        'permission_callback' => array($this, 'get_permission')
                    )
                );
                register_rest_route(
                    MVVWB_TOKEN . '/v1',
                    '/deleteResource/',
                    array(
                        'methods' => 'POST',
                        'callback' => array($this, 'deleteResource'),
                        'permission_callback' => array($this, 'get_permission')
                    )
                );
                register_rest_route(
                    MVVWB_TOKEN . '/v1',
                    '/check/',
                    array(
                        'methods' => 'POST',
                        'callback' => array($this, 'checkBooking'),
                        'permission_callback' => function(){
                            return true;
                        }
                    )
                );
                register_rest_route(
                    MVVWB_TOKEN . '/v1',
                    '/items/',
                    array(
                        'methods' => 'GET',
                        'callback' => array($this, 'getItems'),
                        'permission_callback' => array($this, 'get_permission')
                    )
                );
                register_rest_route(
                    MVVWB_TOKEN . '/v1',
                    '/resources/',
                    array(
                        'methods' => 'GET',
                        'callback' => array($this, 'getResources'),
                        'permission_callback' => array($this, 'get_permission')
                    )
                );
                register_rest_route(
                    MVVWB_TOKEN . '/v1',
                    '/admin_check/(?P<id>\d+)',
                    array(
                        'methods' => 'POST',
                        'callback' => array($this, 'adminCheckBooking'),
                         'permission_callback' => array($this, 'get_permission')
                    )
                );
                register_rest_route(
                    MVVWB_TOKEN . '/v1',
                    '/updateBooking/(?P<id>\d+)',
                    array(
                        'methods' => 'POST',
                        'callback' => array($this, 'updateBooking'),
                        'permission_callback' => array($this, 'get_permission')
                    )
                );
                register_rest_route(
                    MVVWB_TOKEN . '/v1',
                    '/bookings(?:/(?P<page>[a-zA-Z0-9-]+))?(?:/(?P<filter>(.*)?))?',
                    array(
                        'methods' => 'GET',
                        'callback' => array($this, 'getBookings'),
                        'permission_callback' => array($this, 'get_permission')
                    )
                );
                register_rest_route(
                    MVVWB_TOKEN . '/v1',
                    '/bookings-calendar(?:/(?P<filter>(.*)?))?',
                    array(
                        'methods' => 'GET',
                        'callback' => array($this, 'getBookingsCalendar'),
                        'permission_callback' => array($this, 'get_permission')
                    )
                );
                register_rest_route(
                    MVVWB_TOKEN . '/v1',
                    '/item/(?P<id>\d+)/products',
                    array(
                        'methods' => 'GET',
                        'callback' => array($this, 'getItemProducts'),
                        'permission_callback' => array($this, 'get_permission')
                    )
                );
                register_rest_route(
                    MVVWB_TOKEN . '/v1',
                    '/resource_list/',
                    array(
                        'methods' => 'GET',
                        'callback' => array($this, 'getResourceList'),
                        'permission_callback' => array($this, 'get_permission')
                    )
                );
                register_rest_route(
                    MVVWB_TOKEN . '/v1',
                    '/booking_form/(?P<id>\d+)',
                    array(
                        'methods' => 'GET',
                        'callback' => array($this, 'getBookingForm'),
                        'permission_callback' => array($this, 'get_permission')
                    )
                );
                register_rest_route(
                    MVVWB_TOKEN . '/v1',
                    '/item/(?P<id>\d+)',
                    array(
                        'methods' => 'GET',
                        'callback' => array($this, 'getItem'),
                        'permission_callback' => array($this, 'get_permission')
                    )
                );
                register_rest_route(
                    MVVWB_TOKEN . '/v1',
                    '/resource/(?P<id>\d+)',
                    array(
                        'methods' => 'GET',
                        'callback' => array($this, 'getResource'),
                        'permission_callback' => array($this, 'get_permission')
                    )
                );
                register_rest_route(
                    MVVWB_TOKEN . '/v1',
                    '/booking/(?P<id>\d+)',
                    array(
                        'methods' => 'GET',
                        'callback' => array($this, 'getBooking'),
                        'permission_callback' => array($this, 'get_permission')
                    )
                );
                register_rest_route(
                    MVVWB_TOKEN . '/v1',
                    '/updateStatus/',
                    array(
                        'methods' => 'POST',
                        'callback' => array($this, 'updateStatus'),
                        'permission_callback' => array($this, 'get_permission')
                    )
                );
                register_rest_route(
                    MVVWB_TOKEN . '/v1',
                    '/product_meta/(?P<id>\d+)',
                    array(
                        'methods' => 'GET',
                        'callback' => array($this, 'getProMeta'),
                        'permission_callback' => array($this, 'get_permission')
                    )
                );

            }
        );
    }


    public static function instance($file = '', $version = '1.0.0')
    {
        if (is_null(self::$_instance)) {
            self::$_instance = new self($file, $version);
        }
        return self::$_instance;
    }

    function updateBooking($data)
    {
        if (!isset($data['id'])) {
            return new WP_REST_Response([], 400);
        }
        $params = $data->get_params();
        $bookingId = (int)$data['id'];
        $booking = new MVVWB_Booking($bookingId);
        $item = $booking->getBookingItem();
        if (!$item->isValid) {
            return new WP_REST_Response([], 400);
        }
        $config = $data->get_params();
        $prePare = $item->prepareData($config['data'], false, 'Y-m-d');
        $avail = $item->checkAvailability([$bookingId], true);
        $response = ['status' => false, 'message' => ''];
        if ($avail === true) {

            $item->calculateCost('edit', false); // calling this here to update the resources list and price

            $item->updateBooking($bookingId);
            $response = ['status' => true, 'message' => ''];
        }
        return new WP_REST_Response($response, 200);
    }

    function adminCheckBooking($data)
    {
        $response = ['status' => false, 'message' => '', 'price' => []];
        if (!isset($data['id'])) {
            return new WP_REST_Response([], 400);
        }
        $bookingId = (int)$data['id'];
        $booking = new MVVWB_Booking($bookingId);
        $item = $booking->getBookingItem();
        $bookingData = $booking->getBookingData();
        $response = ['status' => true, 'message' => '', 'price' => []];
        $config = $data->get_params();

        if (isset($bookingData['quantity']) && $bookingData['quantity'] != null) {
            $quantity = $bookingData['quantity'];
        } else {
            $quantity = 1;
        }
        $config['data']['quantity'] = $quantity;
        $prePare = $item->prepareData($config['data'], false, 'Y-m-d');
        if (!$prePare) {
            return false;
        }
        $avail = $item->checkAvailability([$bookingId], true);
//        if ($avail === false || (isset($avail['status']) && $avail['status'] === false)) {
//            $response['status'] = false;
//            $response['message'] = 'This range is not available';
//            $response['costs'] = false;
//        } else {
//            $response['costs'] = $item->calculateCost();
//        }
        if ($avail === false || (isset($avail['status']) && $avail['status'] === false)) {
            $response['status'] = false;
            $response['message'] = isset($avail['message']) ? $avail['message'] :
                mvvwb_getConfig('messages.notAvailable', 'This range is not available');
            $response['costs'] = false;
        } else {
            $response['status'] = true;
            $response['costs'] = $item->calculateCost();

        }

        return new WP_REST_Response($response, 200);
    }

    function checkBooking($data)
    {
        $response = ['status' => false, 'message' => ''];
        $config = $data->get_params();
        $product_id = $config['data']['postId'];
        $dateChanged = $config['data']['dateChanged'];
        //   $dateTimeObj = dateStringToObject($config['data']['dateStart'], $config['data']['dateEnd']);
        $booking = New MVVWB_Booking_Item($product_id);

        $prePare = $booking->prepareData($config['data']);
        if (($booking->getUnit() == 'hours' || $booking->getUnit() == 'minutes') && isset($config['data']['dateStart'])) {
            $bookedHourSlots = $booking->getTimeSlots($config['data']);
           
            $response['bookedHourSlots'] = $bookedHourSlots;
        } else if ($booking->isVariable()) {
            $getSlots = $booking->getSlotes($config['data']);
            $response['bookedSlots'] = $getSlots;
        }
        if (!$prePare) {
            return new WP_REST_Response($response, 200);
        }
        $avail = $booking->checkAvailability();
        if ($avail === false || (isset($avail['status']) && $avail['status'] === false)) {
            $response['message'] = isset($avail['message']) ? $avail['message'] : mvvwb_getConfig('messages.notAvailable', 'This range is not available');
            $response['costs'] = false;
        } else {
            $response['status'] = true;
            $response['costs'] = $booking->calculateCost();
            $response['bookingData'] = $booking->getBookingData();
            $response['summary'] = $booking->getBookingData(true);
        }

        return new WP_REST_Response($response, 200);
    }

    function getResourceList()
    {

        $terms = get_terms(MVVWB_RESOURCE_TAX, array(
            'hide_empty' => false,
        ));
        $posts = [];
        foreach ($terms as $term) {
            $posts[] = [
                'item_id' => $term->term_id,
                'title' => $term->name
            ];

        }
        return new WP_REST_Response($posts, 200);
    }

    function _getResource($id)
    {
        $term = get_term($id);
        $postData = [];
        if ($term) {
            $postData['title'] = $term->name;
            $postData['term_id'] = $term->term_id;
            $postData['description'] = $term->description;
            $postData['optional'] = get_term_meta($term->term_id, 'mvvwb_optional', true);
            $postData['price'] = get_term_meta($term->term_id, 'mvvwb_price', true);
            $postData['multiplyByUnit'] = get_term_meta($term->term_id, 'mvvwb_multiplyByUnit', true);
            $postData['multiplyByPerson'] = get_term_meta($term->term_id, 'mvvwb_multiplyByPerson', true);
            $postData['enableQuantity'] = get_term_meta($term->term_id, 'mvvwb_enableQuantity', true);
            $postData['maxQuantity'] = get_term_meta($term->term_id, 'mvvwb_maxQuantity', true);
            $postData['minQuantity'] = get_term_meta($term->term_id, 'mvvwb_minQuantity', true);
            $postData['costPersonType'] = get_term_meta($term->term_id, 'mvvwb_costPersonType', true);
            $postData['availableNumber'] = get_term_meta($term->term_id, 'mvvwb_availableNumber', true);
            $postData['maxCapacity'] = get_term_meta($term->term_id, 'mvvwb_maxCapacity', true);
            $postData['sharable'] = get_term_meta($term->term_id, 'mvvwb_sharable', true);
            $postData['limitedResource'] = (get_term_meta($term->term_id, 'mvvwb_limitedResource', true))?true:false;
            $postData['hidden'] = get_term_meta($term->term_id, 'mvvwb_hidden', true);

        }
        return $postData;
    }

    function getResources()
    {

        $terms = get_terms(MVVWB_RESOURCE_TAX, array(
            'hide_empty' => false,
        ));
        $posts = [];
        foreach ($terms as $term) {
//            $posts[] = [
//                'item_id' => $term->term_id,
//                'title' => $term->name
//            ];
            $posts[] = $this->_getResource($term->term_id);
        }
        return new WP_REST_Response($posts, 200);
    }

    function getItems()
    {
        $args = array(
            'numberposts' => -1,
            'post_type' => MVVWB_ITEMS_PT
        );
        $posts = [];
        $latest_items = get_posts($args);
        foreach ($latest_items as $post) {

            $postData = get_post_meta($post->ID, '_mvvwb_config', true);
            $posts[] = [
                'item_id' => $post->ID,
                'title' => $post->post_title,
                'blockDuration' => $postData['general']['blockDuration'] . ' ' . $postData['general']['durationUnit'],
                'type' => $postData['general']['isFixed'] ? 'Fixed' : 'Multiple',
                'unitPrice' => $postData['cost']['perUnit'],
                'fixedPrice' => $postData['cost']['fixedPrice']['price']

            ];
        }

        return new WP_REST_Response($posts, 200);
    }

    function getBookings($data)
    {
        $page = 1;
        if (isset($data['page'])) {
            $page = $data['page'];
        }
        $filter = false;
        if (isset($data['filter'])) {
//            $filter = json_decode(stripslashes($data['filter']));
            $filter = json_decode(stripslashes(urldecode($data['filter'])));

        }

//        if (isset($_GET['filter'])) {
//            $filter = json_decode($data->get_param('filter'));
//        }

        $status = ['unpaid', 'pending-confirmation', 'confirmed', 'paid', 'complete', 'cancelled'];
        if (isset($filter->status) && is_array($filter->status) && !empty($filter->status)) {
            $status = $filter->status;
        }


        $args = array(
            'numberposts' => 20,
            'offset' => ($page - 1) * 20,
            'post_status' => $status,
            'post_type' => MVVWB_BOOKING_PT
        );


        if (isset($filter->query) && !empty($filter->query)) {
            $args['s'] = $filter->query;

        }

        $latest_items = get_posts($args);
        $posts = [];
        foreach ($latest_items as $post) {

            $booking = new MVVWB_Booking($post->ID);

            $postData = $booking->getDetails(true, true, false);
            $posts[] = $postData;

        }

        return new WP_REST_Response(['page' => $page, 'items' => $posts], 200);
    }

    function getBookingsCalendar($data)
    {
        $filter = false;

        if (isset($data['filter'])) {
            $filter = json_decode(stripslashes(urldecode($data['filter'])));
        }
//        $status = ['unpaid', 'pending-confirmation', 'confirmed', 'paid', 'complete'];
//        if (isset($filter->status) && is_array($filter->status) && !empty($filter->status)) {
//            $status = $filter->status;
//        }
//
//
//
//
//        if (isset($filter->query) && !empty($filter->query)) {
//            $args['s'] = $filter->query;
//        }

        if (isset($filter->startDate)) {
            $start = new DateTime($filter->startDate);
        } else {
            $start = new DateTime();
        }
        $start->modify('first day of this month');
        $end = clone $start;
        $end->modify('+1 month');
        $latest_items = MVVWB_Booking::getBookingIdsByProduct(null,['date_between' => ['start' => $start, 'end' => $end]]);
        $posts = [];
        foreach ($latest_items as $id) {
            $booking = new MVVWB_Booking($id);

            $postData = $booking->getDetails(true, true, 'Y-m-d H:i:s');// this format is required for calendar
            $posts[] = $postData;
        }

        return new WP_REST_Response(['items' => $posts], 200);
    }

    function getBooking($data)
    {
        if (!isset($data['id'])) {
            return new WP_REST_Response([], 400);
        }
        $booking = new MVVWB_Booking($data['id']);
        $postData = $booking->getDetails(true);

        return new WP_REST_Response($postData, 200);
    }

    function getProMeta($data)
    {

        if (!isset($data['id'])) {
            return new WP_REST_Response([], 400);
        }
        $post = get_post($data['id']);
        $postData = false;
        if ($post) {
            $postData = get_post_meta($post->ID, '_mvvwb_config', true);

        }
        return new WP_REST_Response($postData, 200);
    }

    function getItemProducts($data)
    {
        if (!isset($data['id'])) {
            return new WP_REST_Response([], 400);
        }
        $itemId = $data['id'];
        $productIds = $this->_getItemProducts($itemId);
        $products = [];
        foreach ($productIds as $pId) {
            $products[] = ['id' => $pId, 'link' => get_edit_post_link($pId, 'edit'), 'title' => html_entity_decode(get_the_title($pId), ENT_QUOTES)];
        }
        return new WP_REST_Response($products, 200);

    }

    function _getItemProducts($itemId)
    {
        global $wpdb;
        $meta_keys = [];
        $query_select = "SELECT p.ID FROM {$wpdb->posts} p";

        $query_where = array('WHERE 1=1', "p.post_type = 'product'");

        $query_where[] = "_mvvwb_product_meta.meta_value = $itemId";
        $query_where[] = "p.post_status IN ('draft','publish')";
        $query_order = ' ORDER BY p.post_date desc';
        $meta_keys = array();
        $meta_keys = array_unique(['_mvvwb_product_meta']);
        $query_where = implode(' AND ', $query_where);
        foreach ($meta_keys as $index => $meta_key) {
            $key = esc_sql($meta_key);
            $query_select .= " LEFT JOIN {$wpdb->postmeta} {$key} ON p.ID = {$key}.post_id AND {$key}.meta_key = '{$key}'";
        }
        return array_filter(wp_parse_id_list($wpdb->get_col("{$query_select} {$query_where} {$query_order};")));
    }

    function getBookingForm($data)
    {
        if (!isset($data['id'])) {
            return new WP_REST_Response([], 400);
        }
        $booking = new MVVWB_Booking($data['id']);

        $item = $booking->getBookingItem();
        if (!$item->isValid) {
            return new WP_REST_Response([], 400);
        }
        $details = $booking->getDetails(true, false);

        $start = new DateTime($details['booking']['start']);
        $end = new DateTime($details['booking']['end']);
//                    $postData['availability']['rules']['start']
        $resourcesQ = [];
        $resources = [];
        if ($details['resources'] && is_array($details['resources'])) {
            foreach ($details['resources'] as $res) {
                $resourcesQ[$res['term_id']] = $res['quantity'];
                $resources[] = $res['term_id'];
            }
        }
        $data = [
            'config' => $item->getConfig('frontEnd'),
            'global' => mvvwb_getAllConfig(),
            'resources' => $item->getResources(),
            'details' => [
                'duration' => $details['booking']['duration'],
                'dateStart' => $start->format('Y-m-d'),
                'timeStart' => (isset($details['booking']['timeStart']) ? $details['booking']['timeStart'] : false),
                'dateEnd' => $end->format('Y-m-d'),
                'persons' => isset($details['booking']['persons']) ? $details['booking']['persons'] : false,
                'children' => isset($details['booking']['children']) ? $details['booking']['children'] : false,
                'adult' => isset($details['booking']['adult']) ? $details['booking']['adult'] : false,
                'resources' => $resources,
                'resourcesQ' => $resourcesQ,
                'postId' => ''
            ]
        ];
        return new WP_REST_Response($data, 200);
    }

    function getResource($data)
    {
        if (!isset($data['id'])) {
            return new WP_REST_Response([], 400);
        }

        $postData = $this->_getResource($data['id']);

        return new WP_REST_Response($postData, 200);
    }

    function getItem($data)
    {

        if (!isset($data['id'])) {
            return new WP_REST_Response([], 400);
        }
        $post = get_post($data['id']);
        $postData = [];
        if ($post) {
            $postData = get_post_meta($post->ID, '_mvvwb_config', true);

            foreach ($postData['availability']['rules'] as $k => $rule) {
                if ($rule['type'] === 'dateRange') {
                    $start = new DateTime('@' . (!empty($rule['start']) ? $rule['start'] : time()));
                    $start->setTimezone(mvvwb_getTimeZone());
                    $end = new DateTime('@' . (!empty($rule['end']) ? $rule['end'] : time()));
                    $end->setTimezone(mvvwb_getTimeZone());
//                    $postData['availability']['rules']['start']
                    $postData['availability']['rules'][$k]['start'] = array_combine(
                        ['d', 'm', 'y'],
                        explode('-', $start->format('d-m-Y')));
                    $postData['availability']['rules'][$k]['end'] = array_combine(
                        ['d', 'm', 'y'],
                        explode('-', $end->format('d-m-Y')));
                } else if ($rule['type'] === 'dateTimeRange') {
                    $start = new DateTime('@' . (!empty($rule['start']) ? $rule['start'] : time()));
                    $start->setTimezone(mvvwb_getTimeZone());
                    $end = new DateTime('@' . (!empty($rule['end']) ? $rule['end'] : time()));
                    $end->setTimezone(mvvwb_getTimeZone());
//                    $postData['availability']['rules']['start']
                    $postData['availability']['rules'][$k]['start'] = array_combine(
                        ['d', 'm', 'y'],
                        explode('-', $start->format('d-m-Y')));
                    $postData['availability']['rules'][$k]['start']['t'] = $start->format('H') * 60 + $start->format('i');
                    $postData['availability']['rules'][$k]['end'] = array_combine(
                        ['d', 'm', 'y'],
                        explode('-', $end->format('d-m-Y')));
                    $postData['availability']['rules'][$k]['end']['t'] = $end->format('H') * 60 + $end->format('i');
                }
            }
            foreach ($postData['cost']['rules'] as $k => $rule) {
                if ($rule['type'] === 'dateRange') {
                    $start = new DateTime('@' . (!empty($rule['start']) ? $rule['start'] : time()), mvvwb_getTimeZone());
                    $start->setTimezone(mvvwb_getTimeZone());
                    $end = new DateTime('@' . (!empty($rule['end']) ? $rule['end'] : time()), mvvwb_getTimeZone());
                    $end->setTimezone(mvvwb_getTimeZone());
//                    $postData['availability']['rules']['start']
                    $postData['cost']['rules'][$k]['start'] = array_combine(
                        ['d', 'm', 'y'],
                        explode('-', $start->format('d-m-Y')));

                    $postData['cost']['rules'][$k]['end'] = array_combine(
                        ['d', 'm', 'y'],
                        explode('-', $end->format('d-m-Y')));
                } else if ($rule['type'] === 'dateTimeRange') {
                    $start = new DateTime('@' . (!empty($rule['start']) ? $rule['start'] : time()));
                    $start->setTimezone(mvvwb_getTimeZone());
                    $end = new DateTime('@' . (!empty($rule['end']) ? $rule['end'] : time()));
                    $end->setTimezone(mvvwb_getTimeZone());
//                    $postData['availability']['rules']['start']
                    $postData['cost']['rules'][$k]['start'] = array_combine(
                        ['d', 'm', 'y'],
                        explode('-', $start->format('d-m-Y')));
                    $postData['cost']['rules'][$k]['start']['t'] = $start->format('H') * 60 + $start->format('i');
                    $postData['cost']['rules'][$k]['end'] = array_combine(
                        ['d', 'm', 'y'],
                        explode('-', $end->format('d-m-Y')));
                    $postData['cost']['rules'][$k]['end']['t'] = $end->format('H') * 60 + $end->format('i');
                }
            }
            $resources = get_the_terms($post->ID, MVVWB_RESOURCE_TAX);
            if ($resources) {
                $resources = array_map(function ($term) {
                    return $term->term_id;
                }, $resources);
                $postData['resources'] = $resources;
            } else {
                $postData['resources'] = [];
            }

            if (isset($postData['general'])) {
                $postData['general']['title'] = $post->post_title;
                if (!isset($postData['general']['showInlineCalendar'])) {
                    $postData['general']['showInlineCalendar'] = false;
                }
                if (!isset($postData['general']['cancelBefore'])) {
                    $postData['general']['cancelBefore'] = ['value' => 0, 'unit' => 'default'];
                }
            }

            if (isset($postData['availability'])) {
                if (!isset($postData['availability']['timeIncrDuration'])) {
                    $postData['availability']['timeIncrDuration'] = ['value' => $postData['general']['durationUnit']=='minutes'?15:$postData['general']['blockDuration'], 'unit' => 'default'];
                }
            }

        }


//        $postData = array(
//            'general' => [
//                'title' => $post->post_title,
//                'durationUnit' => 'days'
//
//            ],
//            'cost' => [
//                'title' => $post->post_title,
//                'durationUnit' => 'days'],
//            'availability' => [
//                'rules' => []]
//        );
        return new WP_REST_Response($postData, 200);
    }

    function deleteResource($data)
    {
        $params = $data->get_params();
        $itemId = (int)$params['resource_id'];
        $status = wp_delete_term($itemId, MVVWB_RESOURCE_TAX);

        return new WP_REST_Response(true, 200);
    }

    function deleteItem($data)
    {
        $params = $data->get_params();
        $itemId = (int)$params['item_id'];
        if (get_post_type($itemId) === MVVWB_ITEMS_PT) {
            wp_delete_post($itemId, true);
        }
        return new WP_REST_Response(true, 200);
    }

    function updateStatus($data)
    {
        $params = $data->get_params();
        $itemId = (int)$params['booking_id'];
        $status = $params['status'];
        if (get_post_type($itemId) === MVVWB_BOOKING_PT) {
            $booking = new MVVWB_Booking($itemId);
            $booking->update_status($status);
            $booking->syncOrderStatusWithBooking($itemId);

        }
        return new WP_REST_Response(["status" => true, 'booking_id' => $itemId, 'redirect' => true], 200);


    }

    function getConfig($data)
    {


        $postData = mvvwb_getAllConfig();
        if (!isset($postData['labels']['quantity'])) {
            $postData['labels']['quantity'] = 'Quantity';
        }
        if (!isset($postData['labels']['adult'])) {
            $postData['labels']['adult'] = 'Adults';
            $postData['labels']['children'] = 'Children';
        }
        if (!isset($postData['labels']['dateRange'])) {
            $postData['labels']['dateRange'] = 'Date Range';
        }
        if (!isset($postData['labels']['selectedRange'])) {
            $postData['labels']['selectedRange'] = 'Selected:';
        }
        if (!isset($postData['button']['bookNow'])) {
            $postData['button']['bookNow'] = 'Book Now';
            $postData['button']['checkAvail'] = 'Check Availability';
        }
        if (!isset($postData['messages'])) {
            $postData['messages'] = [
                'notAvailable' => 'Selected date range is not available',
                'invalidSelection' => 'Invalid Selection'
            ];
        }
        if (!isset($postData['messages']['confRequired'])) {
            $postData['messages']['confRequired'] = 'Confirmation Required';
        }

        if (!isset($postData['messages']['availQuantityS'])) {
            $postData['messages']['availQuantityS'] = 'Only %d Quantity available';
            $postData['messages']['availQuantityP'] = 'Only %d Quantities are available';
        }

        if (!isset($postData['timePicker'])) {
            $postData['timePicker']['format'] = 12;
        }

        return new WP_REST_Response($postData, 200);
    }

    function saveConfig($data)
    {


        $postData = $data->get_params();
        $postData = $postData['data'];
        if ($postData) {
            $newConf = [
                'labels' => [
                    'dateStart' => sanitize_text_field($postData['labels']['dateStart']),
                    'dateEnd' => sanitize_text_field($postData['labels']['dateEnd']),
                    'dateRange' => sanitize_text_field($postData['labels']['dateRange']),
                    'timeStart' => sanitize_text_field($postData['labels']['timeStart']),
                    'duration' => sanitize_text_field($postData['labels']['duration']),
                    'persons' => sanitize_text_field($postData['labels']['persons']),
                    'quantity' => sanitize_text_field($postData['labels']['quantity']),
                    'total' => sanitize_text_field($postData['labels']['total']),
                    'adult' => sanitize_text_field($postData['labels']['adult']),
                    'children' => sanitize_text_field($postData['labels']['children']),
                    'bookingPrice' => sanitize_text_field($postData['labels']['bookingPrice']),
                    'fixedCharge' => sanitize_text_field($postData['labels']['fixedCharge']),
                    'bookingPricePersons' => sanitize_text_field($postData['labels']['bookingPricePersons']),
                    'bookingPriceAdult' => sanitize_text_field($postData['labels']['bookingPriceAdult']),
                    'bookingPriceChildren' => sanitize_text_field($postData['labels']['bookingPriceChildren']),
                ],
                'resource' => [
                    'resourcesTitle' => sanitize_text_field($postData['resource']['resourcesTitle']),
                    'resPriPerU' => sanitize_text_field($postData['resource']['resPriPerU']),
                    'resPriPerP' => sanitize_text_field($postData['resource']['resPriPerP']),
                    'resPriPerPpU' => sanitize_text_field($postData['resource']['resPriPerPpU']),
                    'resPriOnce' => sanitize_text_field($postData['resource']['resPriOnce']),
                ],
                'button' => [
                    'bookNow' => sanitize_text_field($postData['button']['bookNow']),
                    'checkAvail' => sanitize_text_field($postData['button']['checkAvail']),
                ],
                'messages' => [
                    'notAvailable' => sanitize_text_field($postData['messages']['notAvailable']),
                    'invalidSelection' => sanitize_text_field($postData['messages']['invalidSelection']),
                    'availQuantityS' => sanitize_text_field($postData['messages']['availQuantityS']),
                    'availQuantityP' => sanitize_text_field($postData['messages']['availQuantityP']),
                    'resourceNotAvailable' => sanitize_text_field($postData['messages']['resourceNotAvailable']),
                    'minBookable' => sanitize_text_field($postData['messages']['minBookable']),
                    'maxBookable' => sanitize_text_field($postData['messages']['maxBookable'])
                ],
                'timePicker' => [
                    'format' => sanitize_text_field($postData['timePicker']['format'])
                ]

            ];

            update_option('mvvwb_config', $newConf);
        }

        return new WP_REST_Response(["status" => true], 200);

    }

    function saveResource($data)
    {
        $postData = $data->get_params();
        $postData = $postData['postData'];
        if (!isset($data['id'])) {
            return new WP_REST_Response([], 400);
        }
        $post_id = (int)$data['id'];
        $redirect = false;
        if ($post_id === 0) {
            // new post
            $term = wp_insert_term(
                $postData['title'],   // the term
                MVVWB_RESOURCE_TAX,
                ['description' => $postData['description']]
            );
            if ($term) {
                $post_id = $term['term_id'];
                $redirect = true;
                $this->updateResourceMeta($post_id, $postData);
            }

        } else {
            $update = wp_update_term($post_id, MVVWB_RESOURCE_TAX, array(
                'name' => $postData['title'],
                'description' => $postData['description']
            ));
            if ($update) {
                $this->updateResourceMeta($post_id, $postData);
            }

        }
        return new WP_REST_Response(["status" => true, 'item_id' => $post_id, 'redirect' => $redirect], 200);
    }

    public function updateResourceMeta($term_id, $data)
    {
        update_term_meta($term_id, 'mvvwb_price', floatval(sanitize_text_field($data['price'])));
        update_term_meta($term_id, 'mvvwb_optional', (isset($data['optional']) && $data['optional']) ? true : false);
        update_term_meta($term_id, 'mvvwb_multiplyByUnit', (isset($data['multiplyByUnit']) && $data['multiplyByUnit']) ? true : false);
        update_term_meta($term_id, 'mvvwb_multiplyByPerson', (isset($data['multiplyByPerson']) && $data['multiplyByPerson']) ? true : false);
        update_term_meta($term_id, 'mvvwb_enableQuantity', (isset($data['enableQuantity']) && $data['enableQuantity']) ? true : false);
        update_term_meta($term_id, 'mvvwb_maxQuantity', empty($data['maxQuantity']) ? '' : intval(sanitize_text_field($data['maxQuantity'])));
        update_term_meta($term_id, 'mvvwb_minQuantity', intval(sanitize_text_field($data['minQuantity'])));
        update_term_meta($term_id, 'mvvwb_hidden', (isset($data['hidden']) && $data['hidden']) ? true : false);
        update_term_meta($term_id, 'mvvwb_limitedResource', (isset($data['limitedResource']) && $data['limitedResource']) ? true : false);
        update_term_meta($term_id, 'mvvwb_availableNumber',  intval(sanitize_text_field($data['availableNumber'])));
        update_term_meta($term_id, 'mvvwb_maxCapacity',  intval(sanitize_text_field($data['maxCapacity'])));
        update_term_meta($term_id, 'mvvwb_sharable', (isset($data['sharable']) && $data['sharable']) ? true : false);

        update_term_meta($term_id, 'mvvwb_costPersonType', [
            'adult' => $data['costPersonType']['adult'] == '' ? $data['costPersonType']['adult'] : floatval(sanitize_text_field($data['costPersonType']['adult'])),
            'child' => $data['costPersonType']['child'] == '' ? $data['costPersonType']['child'] : floatval(sanitize_text_field($data['costPersonType']['child']))]);
    }

    function saveItem($data)
    {
        $postData = $data->get_params();
        $postData = $postData['postData'];
        if (!isset($data['id'])) {
            return new WP_REST_Response([], 400);
        }
        $redirect = false;
        $post_id = (int)$data['id'];
        $costExtra = [];
        $avaiRules = [];

        if (isset($postData['availability']['rules']) && is_array($postData['availability']['rules'])) {
            foreach ($postData['availability']['rules'] as $rule) {
                if ($rule['type'] == 'dateRange') {
                    $start = false;
                    $end = false;

                    if (isset($rule['start']) && isset($rule['start']['m'])) {
                        $start = new DateTime($rule['start']['m'] . '/' . $rule['start']['d'] . '/' . $rule['start']['y'], mvvwb_getTimeZone());
                    }
                    if (isset($rule['end']) && isset($rule['end']['m'])) {
                        $end = new DateTime($rule['end']['m'] . '/' . $rule['end']['d'] . '/' . $rule['end']['y'], mvvwb_getTimeZone());
                    }
                    $avaiRules[] = [
                        "type" => sanitize_text_field($rule['type']),
                        "start" => $start ? $start->getTimestamp() : 0,
                        "end" => $end ? $end->getTimestamp() : 0,
                        "bookable" => isset($rule['bookable']) ? sanitize_text_field($rule['bookable']) : '',
                        "priority" => sanitize_text_field($rule['priority']),
                    ];
                } else if ($rule['type'] == 'dateTimeRange') {

                    $start = false;
                    $end = false;


                    if (isset($rule['start']) && isset($rule['start']['m'])) {
                        $start = new DateTime($rule['start']['m'] . '/' . $rule['start']['d'] . '/' . $rule['start']['y'], mvvwb_getTimeZone());
                        if (isset($rule['start']['t']) && $rule['start']['t'] !== false) {
                            $start->setTime(0, $rule['start']['t'], 0);
                        }
                    }
                    if (isset($rule['end']) && isset($rule['end']['m'])) {
                        $end = new DateTime($rule['end']['m'] . '/' . $rule['end']['d'] . '/' . $rule['end']['y'], mvvwb_getTimeZone());
                        if (isset($rule['end']['t']) && $rule['end']['t'] !== false) {
                            $end->setTime(0, $rule['end']['t'], 0);
                        }
                    }
                    $avaiRules[] = [
                        "type" => sanitize_text_field($rule['type']),
                        "start" => $start ? $start->getTimestamp() : 0,
                        "end" => $end ? $end->getTimestamp() : 0,
                        "bookable" => isset($rule['bookable']) ? sanitize_text_field($rule['bookable']) : '',
                        "priority" => sanitize_text_field($rule['priority']),
                    ];


                } else if ($rule['type'] == 'weekDays') {
                    $avaiRules[] = [
                        "type" => sanitize_text_field($rule['type']),
                        "days" => isset($rule['days']) ? array_map(function ($d) {
                            return intval($d);
                        }, $rule['days']) : '',
                        "bookable" => isset($rule['bookable']) ? sanitize_text_field($rule['bookable']) : '',
                        "priority" => sanitize_text_field($rule['priority']),
                    ];
                } else if ($rule['type'] == 'timeWeekDays') {
                    $avaiRules[] = [
                        "type" => sanitize_text_field($rule['type']),
                        "days" => isset($rule['days']) ? array_map(function ($d) {
                            return intval($d);
                        }, $rule['days']) : '',
                        'timeRange' => [
                            'start' => intval(sanitize_text_field($rule['timeRange']['start'])),
                            'end' => sanitize_text_field($rule['timeRange']['end']),
                        ],
                        "bookable" => isset($rule['bookable']) ? sanitize_text_field($rule['bookable']) : '',
                        "priority" => sanitize_text_field($rule['priority']),
                    ];
                } else {
                    $avaiRules[] = [
                        "type" => sanitize_text_field($rule['type']),
                        "start" => isset($rule['start']) ? sanitize_text_field($rule['start']) : '',
                        "end" => isset($rule['end']) ? sanitize_text_field($rule['end']) : '',
                        "bookable" => isset($rule['bookable']) ? sanitize_text_field($rule['bookable']) : '',
                        "priority" => sanitize_text_field($rule['priority']),
                    ];
                }

            }
        }

        $costRules = [];
        if (isset($postData['cost']['rules']) && is_array($postData['cost']['rules'])) {
            foreach ($postData['cost']['rules'] as $rule) {
                $cost = [];
                if (isset($rule['cost'])) {
                    $cost['unit'] = [
                        'operation' => sanitize_text_field($rule['cost']['unit']['operation']),
                        'value' => floatval(sanitize_text_field($rule['cost']['unit']['value'])),
                    ];
                    $cost['fixed'] = [
                        'operation' => sanitize_text_field($rule['cost']['fixed']['operation']),
                        'value' => floatval(sanitize_text_field($rule['cost']['fixed']['value'])),
                    ];
                    $cost['perPerson'] = [
                        'operation' => sanitize_text_field($rule['cost']['perPerson']['operation']),
                        'value' => floatval(sanitize_text_field($rule['cost']['perPerson']['value'])),
                    ];
                    $personTypes = [];
                    if (isset($rule['cost']['personTypes']) && is_array($rule['cost']['personTypes'])) {
                        foreach ($rule['cost']['personTypes'] as $type => $va) {
                            $personTypes[] = [
                                'type' => sanitize_text_field($va['type']),
                                'operation' => sanitize_text_field($va['operation']),
                                'value' => floatval(sanitize_text_field($va['value'])),
                            ];
                        }
                    }

                    $cost['personTypes'] = $personTypes;
                }
                if ($rule['type'] == 'dateRange') {
                    $start = false;
                    $end = false;

                    if (isset($rule['start']) && isset($rule['start']['m'])) {
                        $start = new DateTime($rule['start']['m'] . '/' . $rule['start']['d'] . '/' . $rule['start']['y'], mvvwb_getTimeZone());

                    }
                    if (isset($rule['end']) && isset($rule['end']['m'])) {
                        $end = new DateTime($rule['end']['m'] . '/' . $rule['end']['d'] . '/' . $rule['end']['y'], mvvwb_getTimeZone());
                    }
                    $costRules[] = [
                        "type" => sanitize_text_field($rule['type']),
                        "start" => $start ? $start->getTimestamp() : 0,
                        "end" => $end ? $end->getTimestamp() : 0,
                        "cost" => $cost,
                        "priority" => sanitize_text_field($rule['priority']),
                    ];
                } else if ($rule['type'] == 'dateTimeRange') {
                    $start = false;
                    $end = false;

                    if (isset($rule['start']) && isset($rule['start']['m'])) {
                        $start = new DateTime($rule['start']['m'] . '/' . $rule['start']['d'] . '/' . $rule['start']['y'], mvvwb_getTimeZone());
                        if (isset($rule['start']['t']) && $rule['start']['t'] !== false) {
                            $start->setTime(0, $rule['start']['t'], 0);
                        }
                    }

                    if (isset($rule['end']) && isset($rule['end']['m'])) {
                        $end = new DateTime($rule['end']['m'] . '/' . $rule['end']['d'] . '/' . $rule['end']['y'], mvvwb_getTimeZone());
                        if (isset($rule['end']['t']) && $rule['end']['t'] !== false) {
                            $end->setTime(0, $rule['end']['t'], 0);
                        }
                    }
                    $costRules[] = [
                        "type" => sanitize_text_field($rule['type']),
                        "start" => $start ? $start->getTimestamp() : 0,
                        "end" => $end ? $end->getTimestamp() : 0,
                        "cost" => $cost,
                        "priority" => sanitize_text_field($rule['priority']),
                    ];
                } else if ($rule['type'] == 'weekDays') {
                    $costRules[] = [
                        "type" => sanitize_text_field($rule['type']),
                        "days" => isset($rule['days']) ? array_map(function ($d) {
                            return intval($d);
                        }, $rule['days']) : '',
                        "cost" => $cost,
                        "priority" => sanitize_text_field($rule['priority']),
                    ];
                } else {
                    $costRules[] = [
                        "type" => sanitize_text_field($rule['type']),
                        "start" => isset($rule['start']) ? sanitize_text_field($rule['start']) : '',
                        "end" => isset($rule['end']) ? sanitize_text_field($rule['end']) : '',
                        "cost" => $cost,
                        "priority" => sanitize_text_field($rule['priority']),
                    ];
                }

            }
        }
        $personTypes = false;
        if (isset($postData['cost']['personTypes']) && is_array($postData['cost']['personTypes'])) {
            $personTypes = [];
            foreach ($postData['cost']['personTypes'] as $type => $va) {
                $personTypes[] = ['enable' => true, 'type' => $va['type'], 'cost' => floatval($va['cost'])];
            }

        }
        $newConf = [
            'general' => [
                'durationUnit' => sanitize_text_field($postData['general']['durationUnit']), // minutes, hours, week
                'isFixed' => $postData['general']['isFixed'] ? true : false, //customer
                'blockDuration' => intval(sanitize_text_field($postData['general']['blockDuration'])),
                'requireConfirmation' => $postData['general']['requireConfirmation'] ? true : false, // false
                'allDayBooking' => $postData['general']['allDayBooking'] ? true : false, // false
                'canCancel' => $postData['general']['canCancel'] ? true : false, //false\
                'cancelBefore' => [
                    'value' => intval(sanitize_text_field($postData['general']['cancelBefore']['value'])),
                    'unit' => sanitize_text_field($postData['general']['cancelBefore']['unit']) == 'default' ?
                        sanitize_text_field($postData['general']['durationUnit']) : sanitize_text_field($postData['general']['cancelBefore']['unit']),
                ],
                'enablePerson' => $postData['general']['enablePerson'] ? true : false,// false
                'enablePersonType' => $postData['general']['enablePersonType'] ? true : false,// false
                'enableQuantity' => $postData['general']['enableQuantity'] ? true : false,// false
                'personsAsBooking' => $postData['general']['personsAsBooking'] ? true : false,// false
                'minBlocksBookable' => intval(sanitize_text_field($postData['general']['minBlocksBookable'])),
                'maxBlocksBookable' => intval(sanitize_text_field($postData['general']['maxBlocksBookable'])),
                'maxBookingPerBlock' => intval(sanitize_text_field($postData['general']['maxBookingPerBlock'])),
                'showDateRangePicker' => $postData['general']['showDateRangePicker'] &&
                    !$postData['general']['isFixed'] &&
                    $postData['general']['durationUnit'] == 'days' &&
                    $postData['general']['blockDuration'] == '1',// false
                'showInlineCalendar' => $postData['general']['showInlineCalendar'] ? true : false,// false
            ],
            'cost' => [
                'perUnit' => floatval(sanitize_text_field($postData['cost']['perUnit'])),
                'multiplyByPerson' => $postData['cost']['multiplyByPerson'] ? true : false,
                'fixedPrice' => [
                    'price' => floatval(sanitize_text_field($postData['cost']['fixedPrice']['price'])),
                    'multiplyByPerson' => $postData['cost']['fixedPrice']['multiplyByPerson'] ? true : false,
                ],
                'freePersonsCount' => intval(sanitize_text_field($postData['cost']['freePersonsCount'])),
                'perPerson' => floatval(sanitize_text_field($postData['cost']['perPerson'])),
                'customExtra' => [],
                'personTypes' => $personTypes,
                'rules' => $costRules
            ],
            'availability' => [
                'rules' => $avaiRules,
                'timeRange' => [
                    'start' => intval(sanitize_text_field($postData['availability']['timeRange']['start'])),
                    'end' => sanitize_text_field($postData['availability']['timeRange']['end']),
                ], 'minAdvanceBooking' => [
                    'value' => intval(sanitize_text_field($postData['availability']['minAdvanceBooking']['value'])),
                    'unit' => sanitize_text_field($postData['availability']['minAdvanceBooking']['unit']),
                ],
                'maxAdvanceBooking' => [
                    'value' => intval(sanitize_text_field($postData['availability']['maxAdvanceBooking']['value'])),
                    'unit' => sanitize_text_field($postData['availability']['maxAdvanceBooking']['unit']),
                ],
                'bufferPeriod' => [
                    'value' => intval(sanitize_text_field($postData['availability']['bufferPeriod']['value'])),
                    'unit' => sanitize_text_field($postData['availability']['bufferPeriod']['unit']) == 'default' ?
                        sanitize_text_field($postData['general']['durationUnit']) : sanitize_text_field($postData['availability']['bufferPeriod']['unit']),
                ],
                'timeIncrDuration' => [
                    'value' => floatval(sanitize_text_field($postData['availability']['timeIncrDuration']['value'])),
                    'unit' =>sanitize_text_field($postData['general']['durationUnit']) // keep it alwasys same as the duration unit
                    // sanitize_text_field($postData['availability']['timeIncrDuration']['unit']) == 'default' ?
                       // sanitize_text_field($postData['general']['durationUnit']) : sanitize_text_field($postData['availability']['timeIncrDuration']['unit']),
                ],

                'timeIncludeBuffer' => isset($postData['availability']['timeIncludeBuffer']) && $postData['availability']['timeIncludeBuffer'],// false


            ],
            'person' => [

            ],
//            'labels' => [
//                'dateStart' => sanitize_text_field($postData['labels']['dateStart']),
//                'dateEnd' => sanitize_text_field($postData['labels']['dateEnd']),
//                'timeStart' => sanitize_text_field($postData['labels']['timeStart']),
//                'duration' => sanitize_text_field($postData['labels']['duration']),
//                'persons' => sanitize_text_field($postData['labels']['persons']),
//            ]
        ];

        if ($post_id === 0) {
            // new post
            $my_post = array(
                'post_title' => $postData['general']['title'],
                'post_status' => 'publish',
                'post_author' => 1,
                'post_type' => MVVWB_ITEMS_PT
            );
            $post_id = wp_insert_post($my_post);
            update_post_meta($post_id, '_mvvwb_config', $newConf);
            $redirect = true;
        } else {
            wp_update_post(array(
                'ID' => $post_id,
                'post_title' => $postData['general']['title'],
                'post_status' => 'publish',
            ));
            update_post_meta($post_id, '_mvvwb_config', $newConf);
        }
        wp_set_post_terms($post_id, $postData['resources'], MVVWB_RESOURCE_TAX);
        $pro_ids = $this->_getItemProducts($post_id);
        if ($pro_ids) {
            foreach ($pro_ids as $pid) {
                MVVWB_Booking_Item::clearTrans($pid);
            }
        }

        return new WP_REST_Response(["status" => true, 'item_id' => $post_id, 'redirect' => $redirect], 200);
    }


    public function get_permission()
    {

        if (current_user_can('administrator') || current_user_can('manage_woocommerce')) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * Cloning is forbidden.
     *
     * @since 1.0.0
     */
    public function __clone()
    {
        _doing_it_wrong(__FUNCTION__, __('Cheating &#8217; huh?'), $this->_version);
    }

    /**
     * Unserializing instances of this class is forbidden.
     *
     * @since 1.0.0
     */
    public function __wakeup()
    {
        _doing_it_wrong(__FUNCTION__, __('Cheatin&#8217; huh?'), $this->_version);
    }

}
