<?php
/*
Plugin Name: API REST Pays
Description: API REST pour générer un menu de pays et afficher les destinations.
Version: 1.0
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
    // Get the selected pays from the request
    $selected_pays = $request->get_param('pays');

    // Default to France if no pays is selected
    if (empty($selected_pays)) {
        $selected_pays = 'France';
    }

    // Query destinations based on selected pays
    $args = array(
        'post_type' => 'destination',
        'posts_per_page' => -1, // Retrieve all destinations
        'meta_query' => array(
            array(
                'key' => 'pays',
                'value' => $selected_pays,
            ),
        ),
    );

    $destinations = new WP_Query($args);

    // Prepare the response data
    $response_data = array();

    if ($destinations->have_posts()) {
        while ($destinations->have_posts()) {
            $destinations->the_post();
            $destination_id = get_the_ID();
            $destination_title = get_the_title();
            $destination_image = get_the_post_thumbnail_url($destination_id, 'medium');

            // If no image is found, use a placeholder
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

    // Reset post data
    wp_reset_postdata();

    // Return JSON response
    return rest_ensure_response($response_data);
}

// Define the shortcode function
function pays_menu_shortcode()
{
    ob_start(); // Start output buffering
?>
    <div id="pays-menu">
        <select id="pays-select">
            <option value="France">France</option>
            <option value="États-Unis">États-Unis</option>
            <option value="Canada">Canada</option>
            <!-- Add more options here -->
        </select>
        <div id="destinations-display"></div>
    </div>

    <script type="text/javascript">
        document.getElementById('pays-select').addEventListener('change', function() {
            var pays = this.value;
            fetch('<?php echo esc_url(rest_url('voyage/pays')); ?>?pays=' + pays)
                .then(response => response.json())
                .then(destinations => {
                    const display = document.getElementById('destinations-display');
                    display.innerHTML = ''; // Clear previous content
                    destinations.forEach(function(destination) {
                        display.innerHTML += `<div class="destination">
                            <h4>${destination.title}</h4>
                            <img src="${destination.image}" alt="Image de ${destination.title}">
                        </div>`;
                    });
                });
        });
    </script>
<?php
    return ob_get_clean(); // Return the buffered content
}

// Register the shortcode with WordPress
add_shortcode('pays_menu', 'pays_menu_shortcode');
