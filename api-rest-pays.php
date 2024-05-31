<?php
/*
Plugin Name: API REST Pays
Description: API REST pour générer un menu de pays sous forme de boutons et afficher les destinations.
Version: 1.3
Author: James Ling
*/

// Register REST API endpoint
add_action('rest_api_init', function () {
    register_rest_route('voyage', '/pays', array(
        'methods' => 'GET',
        'callback' => 'get_pays_destinations',
    ));
});

// Callback function for REST API endpoint
function get_pays_destinations($request)
{
    $selected_pays = $request->get_param('pays');
    if (empty($selected_pays)) {
        $selected_pays = 'France';  // Default to France if no country is selected
    }

    $args = array(
        'post_type' => 'destination',
        'posts_per_page' => -1,
        'meta_query' => array(
            array(
                'key' => 'pays',
                'value' => $selected_pays,
                'compare' => '='
            ),
        ),
    );

    $destinations = new WP_Query($args);
    $response_data = array();

    if ($destinations->have_posts()) {
        while ($destinations->have_posts()) {
            $destinations->the_post();
            $destination_id = get_the_ID();
            $destination_title = get_the_title();
            $destination_image = get_the_post_thumbnail_url($destination_id, 'medium');
            if (empty($destination_image)) {
                $destination_image = 'https://via.placeholder.com/150';
            }

            $response_data[] = array(
                'id' => $destination_id,
                'title' => $destination_title,
                'image' => $destination_image,
            );
        }
    }

    wp_reset_postdata();
    return rest_ensure_response($response_data);
}

// Define the shortcode function
function pays_menu_shortcode()
{
    ob_start();
?>
    <div id="pays-menu">
        <button class="pays-button" data-pays="France">France</button>
        <button class="pays-button" data-pays="États-Unis">États-Unis</button>
        <button class="pays-button" data-pays="Canada">Canada</button>
        <button class="pays-button" data-pays="Argentine">Argentine</button>
        <button class="pays-button" data-pays="Chili">Chili</button>
        <button class="pays-button" data-pays="Belgique">Belgique</button>
        <button class="pays-button" data-pays="Maroc">Maroc</button>
        <button class="pays-button" data-pays="Mexique">Mexique</button>
        <button class="pays-button" data-pays="Japon">Japon</button>
        <button class="pays-button" data-pays="Italie">Italie</button>
        <button class="pays-button" data-pays="Islande">Islande</button>
        <button class="pays-button" data-pays="Chine">Chine</button>
        <button class="pays-button" data-pays="Grèce">Grèce</button>
        <button class="pays-button" data-pays="Suisse">Suisse</button>
        <div id="destinations-display"></div>
    </div>

    <script type="text/javascript">
        document.querySelectorAll('.pays-button').forEach(button => {
            button.addEventListener('click', function() {
                var pays = this.getAttribute('data-pays');
                fetch('<?php echo esc_url(rest_url('voyage/pays')); ?>?pays=' + pays)
                    .then(response => response.json())
                    .then(destinations => {
                        const display = document.getElementById('destinations-display');
                        display.innerHTML = '';
                        destinations.forEach(function(destination) {
                            display.innerHTML += `<div class="destination">
                                <h4>${destination.title}</h4>
                                <img src="${destination.image}" alt="Image de ${destination.title}">
                            </div>`;
                        });
                    });
            });
        });
    </script>
<?php
    return ob_get_clean();
}

add_shortcode('pays_menu', 'pays_menu_shortcode');
