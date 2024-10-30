<?php
if (!defined('ABSPATH'))
    exit;

class MVVWB_Product_Meta
{

    private static $_instance = null;

    public function __construct()
    {

        add_action('woocommerce_process_product_meta', array($this, 'woocommerce_process_product_meta_fields_save'));

        add_action('woocommerce_product_options_general_product_data', array($this, 'options_general_product_data'));
    }

    public static function instance($file = '', $version = '1.0.0')
    {
        if (is_null(self::$_instance)) {
            self::$_instance = new self($file, $version);
        }
        return self::$_instance;
    }

    public function options_general_product_data()
    {
        global $product_object;

        $isBookable = get_post_meta($product_object->get_id(), '_mvvwb_bookable', 'true') == 'yes' ? '' : 'none';
        $meta_field = get_post_meta($product_object->get_id(), '_mvvwb_product_meta', true);


        $is_custom_price = get_post_meta($product_object->get_id(), '_mvvwb_is_custom_price', true);
        $custom_price = get_post_meta($product_object->get_id(), '_mvvwb_custom_price', true);


        $posts = get_posts(array('post_type' => MVVWB_ITEMS_PT, 'posts_per_page' => -1));
        $config = [
            'selected' => (int)$meta_field,
            'isCustomPrice' => ($is_custom_price=='yes'),
            'customPrice' => $custom_price,
            'items' => [[
                'title' => 'Select an Item', 'id' => ''
            ]]];
        foreach ($posts as $post) {
            $config['items'][] = [
                'title' => $post->post_title,
                'id' => $post->ID

            ];
        }

        echo '<div class="options_group show_if_bookable" data-conf=\'' . htmlspecialchars(wp_json_encode($config), ENT_QUOTES) . '\'  id="mvvwb_product_meta" style="display:' . $isBookable . '"> show_if_external show_if_variable</div>';
    }

    public function add_my_custom_product_data_tab($product_data_tabs)
    {
        $product_data_tabs['mvvwb_product-meta-tab'] = array(
            'label' => __('Booking Items', ''),
            'target' => 'mvvwb_product-meta-tab',
            'priority' => 90
        );
        return $product_data_tabs;
    }

    public function woocommerce_process_product_meta_fields_save($post_id)
    {

        if (isset($_POST['mvvwb_product_meta'])) {
            $item_id = (int)sanitize_text_field($_POST['mvvwb_product_meta']);
            update_post_meta($post_id, '_mvvwb_product_meta', $item_id);
        }
        if (isset($_POST['_mvvwb_bookable'])) {
            update_post_meta($post_id, '_mvvwb_bookable', 'yes');
        } else {
            update_post_meta($post_id, '_mvvwb_bookable', 'no');
        }
        if (isset($_POST['mvvwb_custom_price'])) {
            update_post_meta($post_id, '_mvvwb_is_custom_price', 'yes');
        } else {
            update_post_meta($post_id, '_mvvwb_is_custom_price', 'no');
        }



        if (isset($_POST['mvvwb_meta_custom_unit_price'])) {
            $customPrice = [];
            $price = $_POST['mvvwb_meta_custom_unit_price']!=''?(float)sanitize_text_field($_POST['mvvwb_meta_custom_unit_price']):'';
            $customPrice['perUnit'] = $price;
        }

        if (isset($_POST['mvvwb_meta_custom_person_price'])) {
            $price = $_POST['mvvwb_meta_custom_person_price']!=''?(float)sanitize_text_field($_POST['mvvwb_meta_custom_person_price']):'';
            $customPrice['perPerson'] = $price;
        }

        if (isset($_POST['mvvwb_meta_custom_personType_price'])) {

            $price = [];
            foreach ($_POST['mvvwb_meta_custom_personType_price'] as $type=> $p){
                    $price[$type] = $p!==''?(float)sanitize_text_field($p):'';
            }
            $customPrice['personType'] = $price;
        }



        if (isset($_POST['mvvwb_meta_custom_fixed_price'])) {
            $price = $_POST['mvvwb_meta_custom_fixed_price']!=''?(float)sanitize_text_field($_POST['mvvwb_meta_custom_fixed_price']):'';

            $customPrice['fixedPrice'] = $price;
            update_post_meta($post_id, '_mvvwb_custom_price', $customPrice);
        }
        MVVWB_Booking_Item::clearTrans($post_id);
    }

    public function add_my_custom_product_data_fields()
    {
        global $post;
        ?>
        <!-- id below must match target registered in above add_my_custom_product_data_tab function -->
        <div id="mvvwb_product - meta - tab" class="panel woocommerce_options_panel ">
            <h4> <?php _e('Select Item') ?></h4>
            <?php
            $meta_field = get_post_meta($post->ID, '_mvvwb_product_meta', true);

            $Items = get_posts(array('post_type' => MVVWB_ITEMS_PT, 'posts_per_page' => -1));

            foreach ($Items as $item) {
                $checked = '';
                if ($item->ID === (int)$meta_field) {
                    $checked = 'checked="checked"';
                }

                echo '<p><input type="radio" class="checkbox" ' . $checked . ' name="mvvwb_product_meta" id="mvvwb_product_meta_' . $item->ID . '"  value="' . $item->ID . '"" > '
                    . '<label for="mvvwb_product_meta_' . $item->ID . '" class="description" > ' . $item->post_title . '(' . $item->ID . ') </label > ';

                echo '</p > ';
            }
            ?>


        </div>
        <?php
    }

}
