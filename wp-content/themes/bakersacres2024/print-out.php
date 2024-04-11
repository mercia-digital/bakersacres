<?php
$q_var = get_query_var('variety_category_print');
$term = get_term_by('slug', $q_var, 'variety-category'); ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title><?=$term->name?> Printout | <?=get_bloginfo('name')?></title>
    <style>
        /* Add your print-friendly styles here */
        body {
            font-family: Arial, sans-serif;
            font-size: 16px;
            padding: 10px;
        }
        h1 {
            margin: 0;
            margin-bottom: 10px;
        }
        h3 {
            margin: 0;
            margin-bottom: 25px;
        }
        table {
            border-collapse: collapse;
        }
        table, th, td {
            border: solid 1px #000;
        }
        td {
            padding: 5px;
        }
        button {
            margin-bottom: 25px;
            text-align: center;
        }
        
        @media screen {
            tr:nth-of-type(even) {
                background-color: #eee;
            }
        }

        @media print {
            table {
                width: 100%;
                font-size: 14px;
            }

            td {
                padding: 3px;
            }

            button {
                display: none;
            }

            input[type="checkbox"] {
                accent-color: #000;
            }

            ul, p {
                display: none;
            }
        }
    </style>
</head>
<body>
    <h1><?=$term->name?> Printout | <?=get_bloginfo('name')?></h1>
    <h3><?=date_i18n ('Y')?></h3>
    <button onClick="window.print()">Print this page</button>
    <p>You can check the size box here before you print, or by hand afterwards.</p>
    <p>Use "Find in Page" to quickly locate a variety:</p>
    <ul>
        <li>Windows/Mac: Press Ctrl+F (Windows) or Command+F (Mac)</li>
        <li>Android/iOS: Tap menu (⋮ or ⋯), select "Find in page"</li>
    </ul>
    <table>
        <thead>
            <tr>
                <th>Variety Name</th>
                <th>Sizes</th>
            </tr>
        </thead>
        <tbody><?php
            while ($posts->have_posts()) { $posts->the_post();
                $t = get_the_title();
                $s = "";
                $_s = [];
                $_sizes = get_field('sizes');
                foreach($_sizes as $size) {
                    if (!in_array($size['SizeName'], $_s)) {
                        $_s[] = $size['SizeName'];
                        $s .= '<div><input type="checkbox" />' . $size['SizeName'] . '</div>';
                    }
                }
                echo <<<HTML
                    <tr>
                        <td>$t</td>
                        <td>$s</td>
                    </tr>
                HTML;
            } ?>
        </tbody>
    </table>
</body>
</html>