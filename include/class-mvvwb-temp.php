<?php

if (!defined('ABSPATH'))
    exit;

class MVVWB_Temp
{


    public $_version;
    protected $store;
    protected $product_id;
    protected $bufferTime;

    function __construct($product_id = false, $variation_id = false)
    {
        if ($variation_id) {
            $this->product_id = $variation_id;

        } else if ($product_id) {
            $this->product_id = $product_id;
        }

        $this->bufferTime = mvvwb_getConfig('conf.cartBufferTime',600);

    }


    public function clear($session = false)
    {

        if ($session == false) {
//            $user_coockie = WC()->session->get_session_cookie();
            $session = MVVWB_Customer::get_session();
        }

        $trans = $this->getTransient();

        foreach ($trans as $tran) {
            if ($tran['resources'] && is_array($tran['resources'])) {
                foreach ($tran['resources'] as $res) {
                    if ($res['limitedResource']) {
                        $trans = $this->getResTransient($res['term_id']);

                        $trans = array_filter($trans, function ($e) use ($session) {
                            if (($e['session'] === $session[0] || $e['session'] === $session[1]) && $e['pId'] === $this->product_id) {
                                return false;
                            } else {
                                return true;
                            }
                        });

                        MVVWB_Transient::setTransient('mvvwb_temp_res_' . $res['term_id'], $trans, $res['term_id'], time() + $this->bufferTime, true);
                    }
                }

            }
        }
        $trans = array_filter($trans, function ($e) use ($session) {
            if ($e['session'] === $session[0] || $e['session'] === $session[1]) {
                return false;
            } else {
                return true;
            }
        });

        MVVWB_Transient::setTransient('mvvwb_temp_' . $this->product_id, $trans, $this->product_id, time() + $this->bufferTime);



    }

    public function getTransient()
    {
        $trans = get_transient('mvvwb_temp_' . $this->product_id);

        if ($trans === false) {
            return [];
        }


        return array_filter($trans, function ($e) {

            if ($e['time'] < (time() - $this->bufferTime)) {
                return false;
            } else {
                return true;
            }
        });
    }

    public function insert($data)
    {

        $trans = $this->getTransient();
        if($data['session'][0] && is_numeric($data['session'][0])){// check it is user ID, if it is user id we can use it as session id
            $session = $data['session'][0];
        }else{
            if($data['session'][1]===false){ // it checks the cookie session id false, if false we have to depend the normal session
                $session = $data['session'][0];
            }else{
                $session = $data['session'][1];
            }
        }
        $trans[] = [
            'time' => time(),
            'session' => $session,
            'start' => $data['start']->format('YmdHis'),
            'end' => $data['end']->format('YmdHis'),
            'count' => $data['count'],
            'resources' => $data['resources'],
        ];

        MVVWB_Transient::setTransient('mvvwb_temp_' . $this->product_id, $trans, $this->product_id, time() + $this->bufferTime);
        if ($data['resources'] && is_array($data['resources'])) {
            foreach ($data['resources'] as $res) {
                if ($res['limitedResource']) {
                    $trans = $this->getResTransient($res['term_id']);
                    $trans[] = [
                        'time' => time(),
                        'session' => $session,
                        'start' => $data['start']->format('YmdHis'),
                        'end' => $data['end']->format('YmdHis'),
                        'count' => $data['count'],
                        'pId'=>$this->product_id,
                        'resource' => $res,
                    ];
                    MVVWB_Transient::setTransient('mvvwb_temp_res_' . $res['term_id'], $trans, $res['term_id'], time() + $this->bufferTime, true);

                }
            }
        }

    }

    public function getResTransient($id)
    {
        $trans = get_transient('mvvwb_temp_res_' . $id);
        if ($trans === false) {
            return [];
        }

        return array_filter($trans, function ($e) {

            if ($e['time'] < (time() - $this->bufferTime)) {
                return false;
            } else {
                return true;
            }
        });
    }

    public function checkBooking($filter, $excludeSameSession = false)
    {
        $trans = $this->getTransient();
        $session = MVVWB_Customer::get_session();

        return array_filter($trans, function ($e) use ($filter, $excludeSameSession, $session) {
            if ($excludeSameSession && $session && in_array($e['session'], $session)) {
                return false;
            }
            if ($e['start'] <= $filter['end']->format('YmdHis') && $e['end'] >= $filter['start']->format('YmdHis')) {
                return true;
            } else {
                return false;
            }
        });
    }

    public function checkResource($id, $filter, $excludeSameSession = false)
    {
        $trans = $this->getResTransient($id);
        $session = MVVWB_Customer::get_session();

        return array_filter($trans, function ($e) use ($filter, $excludeSameSession, $session) {
            if ($excludeSameSession && $session && in_array($e['session'], $session)) {
                return false;
            }
            if ($e['start'] <= $filter['end']->format('YmdHis') && $e['end'] >= $filter['start']->format('YmdHis')) {
                return true;
            } else {
                return false;
            }
        });
    }
}
