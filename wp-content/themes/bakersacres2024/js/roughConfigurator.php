<?php

function build_rough_js_configuration() {
    // Assuming 'configurator' is the field key for the repeater
    $configurations = get_field('rough_styles', 'option'); // Adjust if not an options page
    
    if (empty($configurations)) {
        return;
    }

    $jsContent = 'jQuery(document).ready(function($) {';
    foreach ($configurations as $configuration) {
        $className = $configuration['class_name'];
        $options = [];
        $type = 'rectangle';
        foreach ($configuration['options'] as $option) {
            if ($option['attribute'] != 'type') {
                $options[$option['attribute']] = $option['value'];
            } else {
                $type = $option['value'];
            }            
        }
        $jsonOptions = json_encode($options);

        switch($type) {
            case 'rectangle':
                $jsContent .= <<<JS
                    $('.$className').each(function() {
                        const el = $(this);
                        const rect = el.get(0).getBoundingClientRect();
                        const svg = document.createElementNS('http://www.w3.org/2000/svg', 'svg');
                        const rc = rough.svg(svg);
                        const node = rc.rectangle(0, 0, rect.width , rect.height , $jsonOptions);
                        svg.appendChild(node);
                        el.prepend(svg);
                    });
                JS;
                break;
            case 'line':
                $jsContent .= <<<JS
                    $('.$className').each(function() {
                        const el = $(this);
                        const rect = el.get(0).getBoundingClientRect();
                        const svg = document.createElementNS('http://www.w3.org/2000/svg', 'svg');
                        const rc = rough.svg(svg);
                        const node = rc.line(0, 3, rect.width - 6, 0, $jsonOptions);
                        svg.appendChild(node);
                        el.append(svg);
                    });
                JS;
                break;
        }
    }

    //button styles
    // --bka-pink: #f12f7d;
    // --bka-purple: #a02ff0;
    // --bka-lime: #7eef2f;
    // --bka-blue: #307def;

    $colors = [
        ['pink-bakers','#f12f7d'],
        ['purple-bakers','#a02ff0'],
        ['green-bakers','#7eef2f'],
        ['blue-bakers','#307def'],
    ];
    foreach ($colors as $color) {
        $className = "wp-block-button:has(.has-$color[0]-background-color)";
        $config = [
            ['roughness','2'],
            ['strokeWidth','3'],
            ['stroke',$color[1]],
            ['bowing','.25'],
            ['fill',$color[1]],
            ['hachureAngle','75'],
            ['fillWeight','3'],
            ['hachureGap','4'],
        ];
        foreach ($config as $option) {
            $options[$option[0]] = $option[1];          
        }
        
        $jsonOptions = json_encode($options);

        $jsContent .= <<<JS
            $('.$className').each(function() {
                const el = $(this);
                const rect = el.get(0).getBoundingClientRect();
                const svg = document.createElementNS('http://www.w3.org/2000/svg', 'svg');
                const rc = rough.svg(svg);
                const node = rc.rectangle(3, 3, rect.width - 6, rect.height - 6, $jsonOptions);
                svg.appendChild(node);
                el.prepend(svg);
            });
        JS;
    }
    $jsContent .= '});';

    return $jsContent;
}

function write_js_to_file() {
    $jsContent = build_rough_js_configuration();
    
    if (empty($jsContent)) {
        return;
    }
    
    // Define the path and filename for the JS file.
    // Ensure the directory is writable and exists. Adjust the path as necessary.
    $upload_dir = wp_upload_dir(); // Get WordPress uploads directory.
    $js_file_path = $upload_dir['basedir'] . '/custom-scripts/rough-configurator.js';
    
    // Check if the directory exists, if not create it.
    $js_dir = dirname($js_file_path);
    if (!file_exists($js_dir)) {
        wp_mkdir_p($js_dir);
    }
    
    // Write the JS content to the file.
    file_put_contents($js_file_path, $jsContent);
}

add_action('acf/save_post', 'write_js_to_file', 20); // Runs after ACF form submission

function enqueue_rough_js_configuration() {
    wp_enqueue_script('jquery');
    wp_enqueue_script('rough-config', '/wp-content/uploads/custom-scripts/rough-configurator.js', array('rough-js'), '1.0', true); // Adjust path as necessary
}
add_action('wp_enqueue_scripts', 'enqueue_rough_js_configuration');