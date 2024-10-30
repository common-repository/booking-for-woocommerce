<script>
    var mvvCartButtonSelector = '.mvvwb_bookable button.single_add_to_cart_button'
    if (document.querySelectorAll(mvvCartButtonSelector).length) {

        document.querySelectorAll(mvvCartButtonSelector).forEach((item) => {
            item.setAttribute('disabled', 'disabled')
        })

    }
    if (window.jQuery) {
        jQuery(document).ready(function ($) {
            $(".variations_form").on('show_variation', function (event, variation) {
                var mvvwo_event = new CustomEvent('mvvwb_show_variation',{detail:{variation:variation}});
                $(this).get(0).dispatchEvent(mvvwo_event);

            })
        });
    }

</script>
