<?php
/*
 * Plugin Name:       Baker's Acres Filemaker Cloud Sync
 * Plugin URI:        https://mercia.digital
 * Description:       This plugin syncs data with the Baker's Cloud Filemaker Database and provides improved searching through Algolia.
 * Version:           1.0.0
 * Requires at least: 6.0
 * Requires PHP:      7.2
 * Author:            Mercia Digital LLC
 * Author URI:        https://mercia.digital
 * License:           GPL v2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Update URI:        https://example.com/my-plugin/
 * Text Domain:       bakers-fm-cloud
 */

require_once('BakersFMAPI.php');

add_action('BakersFMImport_cron_event', 'BakersFMCloudImport');

register_activation_hook( __FILE__, 'BakersFMImport_register_event' );

function BakersFMImport_register_event() {
   if(!wp_next_scheduled('BakersFMImport_cron_event')){
      wp_schedule_event(time(), 'twicedaily', 'BakersFMImport_cron_event');
   }
}

register_deactivation_hook( __FILE__, 'BakersFMImport_deregister_event' );

function BakersFMImport_deregister_event() {
   wp_clear_scheduled_hook( 'BakersFMImport_cron_event' );
}

//scripts
function bakers_fm_admin_script_enqueue() {
    wp_enqueue_script('my-custom-script', plugins_url('/admin-scripts.js', __FILE__), array('jquery'), null, true);
}
add_action('admin_enqueue_scripts', 'bakers_fm_admin_script_enqueue');


//ajax actions
add_action('wp_ajax_bakers_fm_manual_import', 'bakers_fm_manual_import');
function bakers_fm_manual_import() {
    BakersFMCloudImport();

    wp_send_json_success('Script executed successfully.');
}


function BakersGetTimeFormatted() {
    //format date for history
    $tz = get_option('timezone_string');
    $timestamp = time();
    $dt = new DateTime("now", new DateTimeZone($tz)); //first argument "must" be a string
    $dt->setTimestamp($timestamp); //adjust the object to correct timestamp

    return $dt->format('Y-m-d H:i:s');
}

function BakersFMCloudImport() {
    $startTime = microtime(true);
    $importStartTimeFormatted = BakersGetTimeFormatted();

    $BakersFMAPI = new BakersFMAPI();
    
    $data = $BakersFMAPI->RetrieveProductionPlan(intval(date("Y")));

    $items = json_decode($data);  

    BakersFMImport($items, $startTime, $importStartTimeFormatted);
}

function BakersFMLocalImport($localFile = null) {
    $startTime = microtime(true);
    $importStartTimeFormatted = BakersGetTimeFormatted();

    $filePath = '/var/www/html/wp-content/plugins/bakers-fm-cloud/import-log/20240214_161347.json';
    $data = file_get_contents($filePath);

    $items = json_decode($data);

    BakersFMImport($items, $startTime, $importStartTimeFormatted);
}

function BakersFMImport($items, $startTime, $importStartTimeFormatted) {
    $added = 0;
    $updated = 0;

    foreach ($items->Varieties as $item) {
        if ($item->Customer != "Baker's Acres" || $item->Variety == "" ) {
            continue;
        }

        // Separate data
        $variety_id = $item->VarietyID;
        $production_plan_id = $item->ProductionPlanID;

        // Extract Variety Data
        $variety_data = [
            'Variety' => $item->Variety,
            'VarietyID' => $variety_id,
            'Category' => $item->Category,
            'imageURL' => $item->imageURL,
            'TagDescription' => $item->TagDescription,
            'attracts' => $item->attracts,
            'flowerColor' => $item->flowerColor,
            'lightRequirements' => $item->lightRequirements,
            'matureSize' => $item->matureSize,
            'resists' => $item->resists
        ];

        // Extract Size Data
        $size_data = [
            'SizeName' => convert_size($item->SizeName),
            'ProductionPlanID' => $production_plan_id,
            'Flats' => $item->Flats,
            'Pots' => $item->Pots,
            'QtyProd' => $item->QtyProd,
            'Price' => $item->Price,
            'Online' => $item->Online == 'yes' ? true: false,
            'OnlineDate' => $item->OnlineDate,
            'OnlineInv' => $item->OnlineInv,
            'Avail' => $item->Avail == 'yes' ? true: false,
            'AvailDate' => $item->AvailDate,
            'SoldOut' => $item->SoldOut == 'yes' ? true: false,
            'SoldOutDate' => $item->SoldOutDate,
            'stock_flag' => -1
        ];

        //set stock_flag

        //stock statuses
        // -1 - out of stock -- SoldOut = yes
        // 0 - planned -- Flats/Pots > 0, QtyProd = 0
        // 1 - in production -- QtyProd > 0
        // 2 - in store -- Avail = yes
        $SoldOut = $size_data['SoldOut'];
        $Flats = $size_data['Flats'];
        $Pots = $size_data['Pots'];
        $QtyProd = $size_data['QtyProd'];
        $Avail = $size_data['Avail'];

        if (!$SoldOut && !$Avail && $Flats == 0 && $Pots == 0 && $QtyProd == 0) {
            //weird case
        } else {
            if ($SoldOut) {
                $size_data['stock_flag'] = -1;
            } elseif ($Avail) {
                $size_data['stock_flag'] = 2;
            } elseif ($QtyProd > 0) {
                $size_data['stock_flag'] = 1;
            } elseif (($Flats > 0 || $Pots > 0) && $QtyProd == 0) {
                $size_data['stock_flag'] = 0;
            }
        }        

        // $variety_item_hash = hash('sha256', serialize($variety_data));
        // $size_item_hash = hash('sha256', serialize($size_data));

        // $variety_data['variety_hash'] = $variety_item_hash;
        // $size_data['size_hash'] = $size_item_hash;

        //Check if a variety post with the given VarietyID exists
        $existing_variety_posts = get_posts([
            'post_type' => 'variety',
            'meta_key' => 'VarietyID',
            'meta_value' => $variety_id,
            'meta_compare' => '=',
        ]);

        $post_id = 0;
        if ($existing_variety_posts) {
            $post_id = $existing_variety_posts[0]->ID; // Get existing post ID, there should only be one
            
            // Update all variety fields
            foreach ($variety_data as $key => $value) {
                update_field($key, $value, $post_id);
            }

            $updated ++;
        } else {
            // Create a new variety post
            $post_id = wp_insert_post([
                'post_title' => wp_strip_all_tags($item->Variety),
                'post_type' => 'variety',
                'post_status' => 'publish'
            ]);

            // Update Variety fields
            foreach ($variety_data as $key => $value) {
                update_field($key, $value, $post_id);
            }

            $added ++;
        }

        $taxonomies = [
            ['attracts', $item->attracts],
            ['flower-color', $item->flowerColor],
            ['light-requirements', $item->lightRequirements],
            ['mature-size', $item->matureSize],
            ['resists', $item->resists],
            ['variety-category', $item->Category],
            ['initial-letter', strtoupper(mb_substr($item->Variety, 0, 1))]
        ];

        foreach ($taxonomies as $tax) {
            $key = $tax[0];
            $strTerms = $tax[1];

            $arrTerms = explode(",", $strTerms);
            $arrTerms = array_map('trim', $arrTerms);

            $arrTerms = str_replace('\'', '[ft]', $arrTerms);
            $arrTerms = str_replace('\"', '[in]', $arrTerms);

            if (count($arrTerms) > 0) {
                wp_set_post_terms($post_id, $arrTerms, $key);
            }            
        }   

        // Handle Size data in ACF Repeater
        $variety_stock_flag = -1;
        if (have_rows('sizes', $post_id)) {
            $row_added = false;
            while (have_rows('sizes', $post_id)) { the_row();
                $variety_stock_flag = max($variety_stock_flag, get_sub_field('stock_flag'));
                $existing_size_id = get_sub_field('ProductionPlanID');
                if ($existing_size_id == $item->ProductionPlanID) {                    
                    // Update all Size fields
                    foreach ($size_data as $key => $value) {
                        update_sub_field($key, $value);
                    }

                    $row_added = true;

                    break;
                }
            }
            if (!$row_added) { // If the size wasn't in the list to update, add it
                add_row('sizes', $size_data, $post_id);
            }
        } else {
            $variety_stock_flag = $size_data['stock_flag'];
            // Add new Size data to Repeater
            add_row('sizes', $size_data, $post_id);
        }

        //set/update variety stock flag
        update_field('stock_flag', $variety_stock_flag, $post_id);
    }

    $endTime = microtime(true);

    $importTime = $endTime - $startTime;

    // Convert to hours:minutes:seconds
    $iHours = floor($importTime / 3600);
    $iMinutes = floor(($importTime / 60) % 60);
    $iSeconds = $importTime % 60;

    $new_row = array(
        'sync_date' => $importStartTimeFormatted,
        'varieties_added' => $added,
        'varieties_updated' => $updated,
        'execution_time' => sprintf('%02d:%02d:%02d', $iHours, $iMinutes, $iSeconds)
    );

    add_row('FMSyncs', $new_row, 'options');
}

function convert_size($name) {
    $sizes = array(
        array(
            'SizeID' => 'C0086917-7B3D-4284-9F2B-42FEB14B0EC5',
            'SizeName' => '4.5',
            'SizeForWeb' => '4.5" Pot'
        ),
        array(
            'SizeID' => '36F4EF27-E94F-482F-B06E-2FAEC78AE09C',
            'SizeName' => '1203',
            'SizeForWeb' => '3 Pack'
        ),
        array(
            'SizeID' => 'D3A7062C-71C4-490C-AD61-F2611107A7AB',
            'SizeName' => '6.5',
            'SizeForWeb' => '6.5" Pot'
        ),
        array(
            'SizeID' => 'D049FD45-DF6A-45D0-B655-59244D22CA6B',
            'SizeName' => '1GAL',
            'SizeForWeb' => '1 Gallon Pot'
        ),
        array(
            'SizeID' => 'F947CF7F-F5D6-46E3-AFA1-1A596889B42E',
            'SizeName' => '8',
            'SizeForWeb' => '8" Pot'
        ),
        array(
            'SizeID' => 'CC58E1ED-36C0-4D14-822C-80E16C320E48',
            'SizeName' => '1QT',
            'SizeForWeb' => '1 Quart'
        ),
        array(
            'SizeID' => '9A89EBC4-9B06-43F4-9FA4-83A100AB432A',
            'SizeName' => '3C',
            'SizeForWeb' => '3" Compost Pot'
        ),
        array(
            'SizeID' => 'FBE45E64-F641-40E8-91C0-A7D3B04FD8A9',
            'SizeName' => '4C',
            'SizeForWeb' => '4" Compost Pot'
        ),
        array(
            'SizeID' => '2D36B613-5265-4C41-88BC-B4FED6FE21DC',
            'SizeName' => 'FL',
            'SizeForWeb' => '4 Pack'
        ),
        array(
            'SizeID' => '8D475DB3-98B5-4B7D-9A67-EA372DDAA170',
            'SizeName' => '3.5',
            'SizeForWeb' => '3.5" Pot'
        ),
        array(
            'SizeID' => '01D7CD07-44BE-4B80-99CD-215E9D2AE7AF',
            'SizeName' => 'A',
            'SizeForWeb' => '3.5" Alpine'
        ),
        array(
            'SizeID' => 'D82D09FE-5B25-47D5-99C7-9F8E6F01E374',
            'SizeName' => '10HB',
            'SizeForWeb' => '10" Basket'
        ),
        array(
            'SizeID' => 'B715F2AC-A806-4FB7-80AC-3B25FFF50133',
            'SizeName' => '12HB',
            'SizeForWeb' => ''
        ),
        array(
            'SizeID' => 'E7A7118B-49BC-4D12-99AB-502AE2887F27',
            'SizeName' => '8CP',
            'SizeForWeb' => '8" Pot'
        ),
        array(
            'SizeID' => '2F440960-D269-4FC5-80E7-1A20288EA7F8',
            'SizeName' => '10CP',
            'SizeForWeb' => '10" Pot'
        ),
        array(
            'SizeID' => '21EE694C-E44F-4C1D-9AC4-9BEC98EC38E0',
            'SizeName' => '12CP',
            'SizeForWeb' => ''
        ),
        array(
            'SizeID' => '991A61DD-E0C6-47F2-BDEB-7C875439C64E',
            'SizeName' => '306',
            'SizeForWeb' => '6 Pack'
        ),
        array(
            'SizeID' => '88D3382E-BC62-4D9D-999E-B68448836A28',
            'SizeName' => '3"HERB',
            'SizeForWeb' => '3" Herb Pot'
        ),
        array(
            'SizeID' => '8436AC0C-E864-4353-91C4-3ADFCC93C7F1',
            'SizeName' => 'T105',
            'SizeForWeb' => ''
        ),
        array(
            'SizeID' => '6BD29528-6252-49F4-B84D-3D520548C569',
            'SizeName' => 'T84',
            'SizeForWeb' => ''
        ),
        array(
            'SizeID' => '458CDE6A-DF8B-4E01-84BF-B0DD98EC2510',
            'SizeName' => 'T72',
            'SizeForWeb' => ''
        ),
        array(
            'SizeID' => '482672CA-33FF-46F5-A9B7-5B8C0CAAFF60',
            'SizeName' => 'T128',
            'SizeForWeb' => ''
        ),
        array(
            'SizeID' => '056AF5D2-A05C-46F3-A87A-0D34C761B547',
            'SizeName' => 'T288',
            'SizeForWeb' => ''
        ),
        array(
            'SizeID' => '0018B91F-D4A3-43C1-A7CC-410FAC9D42A8',
            'SizeName' => 'T512',
            'SizeForWeb' => ''
        ),
        array(
            'SizeID' => '038DC56E-B76F-4417-9AC0-98B455273A9D',
            'SizeName' => 'T50',
            'SizeForWeb' => ''
        ),
        array(
            'SizeID' => '27B0465B-73A6-414F-949C-21130042908B',
            'SizeName' => '12COMBO',
            'SizeForWeb' => ''
        ),
        array(
            'SizeID' => 'C85A3A64-0D94-416D-BBCD-DC1650E5D9F7',
            'SizeName' => '14COMBO',
            'SizeForWeb' => ''
        ),
        array(
            'SizeID' => '5807805A-117F-4CC0-B157-63D11BD597D8',
            'SizeName' => '16COMBO',
            'SizeForWeb' => ''
        ),
        array(
            'SizeID' => '792CDA17-7BA1-4DB0-9554-E8630B04E73C',
            'SizeName' => 'Moss',
            'SizeForWeb' => ''
        ),
        array(
            'SizeID' => '2B4E366A-A462-44AE-BC23-665B24DBC45B',
            'SizeName' => '1G/TR',
            'SizeForWeb' => '1 Gallon Pot'
        ),
        array(
            'SizeID' => '2D9B0FAB-7999-40B4-81F2-D79A0CF7EDE0',
            'SizeName' => 'SGAL',
            'SizeForWeb' => '1 Gallon Pot'
        ),
        array(
            'SizeID' => '9CABC70A-4243-492F-9081-D862E4243481',
            'SizeName' => '4"',
            'SizeForWeb' => '4" Pot'
        ),
        array(
            'SizeID' => '431E6385-58BE-4633-A1D9-E14EAEA26543',
            'SizeName' => '3GAL',
            'SizeForWeb' => '3 Gallon Pot'
        ),
        array(
            'SizeID' => 'CFADE944-0EC3-45A7-9E5C-9F1794CB80CD',
            'SizeName' => '2GAL',
            'SizeForWeb' => '2 Gallon Pot'
        ),
        array(
            'SizeID' => '0325C6A4-5B3E-43EB-81E3-9669F28B54EF',
            'SizeName' => '10CB',
            'SizeForWeb' => '10" Bowl'
        ),
        array(
            'SizeID' => '6B3F2579-09D3-4975-BDBD-29B55C573D7E',
            'SizeName' => '5"C',
            'SizeForWeb' => '5" Cow Pot'
        ),
        array(
            'SizeID' => 'C8C4069B-346C-4DE7-BAD3-6C3C2271A2F9',
            'SizeName' => '8HB',
            'SizeForWeb' => '8" Basket'
        ),
        array(
            'SizeID' => 'E07866E1-6A6A-4DA6-BC5E-FFEFBD992DD1',
            'SizeName' => '1 g',
            'SizeForWeb' => '1 Gallon'
        ),
        array(
            'SizeID' => '5ECA5FB9-4A01-4CAF-A7AC-FF8EDCDF93A1',
            'SizeName' => '2 g',
            'SizeForWeb' => '2 Gallon'
        ),
        array(
            'SizeID' => '1D01CF85-4384-4E1B-9543-FD402B524F51',
            'SizeName' => '3 g',
            'SizeForWeb' => '3 Gallon'
        ),
        array(
            'SizeID' => '9CB964D6-F435-444E-B430-2D3C8B1E42A0',
            'SizeName' => '4G',
            'SizeForWeb' => '4"'
        ),
        array(
            'SizeID' => '50D6A903-AD7E-43DB-9DCD-0229EDA8886D',
            'SizeName' => '5 g',
            'SizeForWeb' => '5 Gallon'
        ),
        array(
            'SizeID' => '3A05A32C-57F6-416A-8E46-C51623884E94',
            'SizeName' => '6 g',
            'SizeForWeb' => '6 Gallon'
        ),
        array(
            'SizeID' => '7203ED68-E55F-4084-8D2C-8C5F42DFD1D5',
            'SizeName' => '10 g',
            'SizeForWeb' => '10 Gallon'
        ),
        array(
            'SizeID' => 'A7F3F559-82E5-48E6-969D-DA6DE7EDE55F',
            'SizeName' => '15 g',
            'SizeForWeb' => '15 Gallon'
        ),
        array(
            'SizeID' => '2074A1DF-9155-4D53-A6B1-F2F893AD8740',
            'SizeName' => '10 g Patio Tree',
            'SizeForWeb' => '10 Gallon'
        ),
        array(
            'SizeID' => '617EF745-187D-44E5-8B0D-8F6CB2EA2A6F',
            'SizeName' => '2 g Patio Tree',
            'SizeForWeb' => '2 Gallon'
        ),
        array(
            'SizeID' => '98EF0CF6-A693-4AEF-9829-F19A8A51C70A',
            'SizeName' => '3 g Patio Tree',
            'SizeForWeb' => '3 Gallon'
        ),
        array(
            'SizeID' => '80168EC7-1D71-4A51-B229-A4044936B37A',
            'SizeName' => '5 g Patio Tree',
            'SizeForWeb' => '5 Gallon'
        ),
        array(
            'SizeID' => '30641F1A-1CD5-4A9B-BEC1-F7EF10A30653',
            'SizeName' => '7 g',
            'SizeForWeb' => '7 Gallon'
        ),
        array(
            'SizeID' => '01A75EEF-7DD0-45F2-80ED-35EBEBF5BD75',
            'SizeName' => '6SMR',
            'SizeForWeb' => ''
        ),
        array(
            'SizeID' => '3D95C6AD-FD54-4AEC-8FFE-7B87422CC693',
            'SizeName' => '306',
            'SizeForWeb' => '6 Pack'
        ),
        array(
            'SizeID' => '4C670017-C452-4F4C-9C14-8E953050C78F',
            'SizeName' => '8SMR',
            'SizeForWeb' => ''
        ),
        array(
            'SizeID' => 'D4308DA4-F375-4C22-AA6B-91122D9BFF0E',
            'SizeName' => '2C',
            'SizeForWeb' => '2" Compost Pot'
        ),
        array(
            'SizeID' => '490FA436-433C-4616-978D-01C614F6C0DF',
            'SizeName' => '9"MUM',
            'SizeForWeb' => '9"'
        ),
        array(
            'SizeID' => '6EAE8B8B-465B-45AB-99EA-E88CAF6502BF',
            'SizeName' => '12"MUM',
            'SizeForWeb' => '12"'
        ),
        array(
            'SizeID' => '1F6478CB-AD57-44EF-89EA-0B7C882F3C72',
            'SizeName' => 'IMP',
            'SizeForWeb' => '1 Gallon Pot'
        ),
        array(
            'SizeID' => '85D5CF0F-C29F-4EE0-B7A9-5ECDD7C66FB3',
            'SizeName' => '1QMC',
            'SizeForWeb' => '1 Quart'
        ),
        array(
            'SizeID' => '64025C81-6405-4048-97B3-7C88E909FC1B',
            'SizeName' => '1GPW',
            'SizeForWeb' => '1 Gallon Pot'
        ),
        array(
            'SizeID' => '949E9201-FB7A-414D-8E9B-61DAE377865B',
            'SizeName' => '3S',
            'SizeForWeb' => '3"'
        ),
        array(
            'SizeID' => '27088F03-4C1F-47C2-A4AD-0872A4783EA2',
            'SizeName' => '4.5B',
            'SizeForWeb' => '4.5" Pot'
        ),
        array(
            'SizeID' => '46B4A3E0-B8F3-4F52-A845-951BF19891DD',
            'SizeName' => '6HB',
            'SizeForWeb' => '6" Basket'
        ),
        array(
            'SizeID' => '0C2DE3ED-7467-40DC-AF7F-D147CE24B246',
            'SizeName' => '2"',
            'SizeForWeb' => '2" Pot'
        ),
        array(
            'SizeID' => 'C30DC090-4B19-46F5-AF8D-606823588303',
            'SizeName' => '1CF',
            'SizeForWeb' => '1CF'
        ),
        array(
            'SizeID' => '34AE93A2-718E-410A-8ABE-4437EF9073A9',
            'SizeName' => '2CF',
            'SizeForWeb' => '2CF'
        ),
        array(
            'SizeID' => '99278D79-B66A-43FB-8BAF-F3AD9F521973',
            'SizeName' => '3CF',
            'SizeForWeb' => '3CF'
        ),
        array(
            'SizeID' => '6917CD97-62BC-4C26-A1A6-20BF482B1B0E',
            'SizeName' => 'PCK',
            'SizeForWeb' => 'Pack'
        ),
        array(
            'SizeID' => '9AD3B2E9-D597-4FC6-8914-56433E5E076A',
            'SizeName' => '4.5S',
            'SizeForWeb' => '4.5" Pot'
        ),
        array(
            'SizeID' => '10B25E0C-6E03-4696-8F64-F2C81D793B57',
            'SizeName' => 'COMBO',
            'SizeForWeb' => ''
        ),
        array(
            'SizeID' => '1A0C9999-656D-4B80-8DA9-15DC6D1C3588',
            'SizeName' => '10SUM',
            'SizeForWeb' => ''
        ),
        array(
            'SizeID' => '80E28DAF-17CA-4B2E-BDB1-8E0FF03B8321',
            'SizeName' => '5"',
            'SizeForWeb' => '5" Pot'
        ),
        array(
            'SizeID' => '62D19A80-4E60-AB4D-9E2A-FEE4AFC5CB41',
            'SizeName' => '3QT',
            'SizeForWeb' => '3 Quart Pot'
        ),
        array(
            'SizeID' => 'C4C57803-A7DA-4D6A-B688-538EBE4D68D4',
            'SizeName' => '4"SO',
            'SizeForWeb' => 'Special Order'
        ),
        array(
            'SizeID' => 'F68460F6-95EF-4364-A855-0CCD38C464C4',
            'SizeName' => '6"SO',
            'SizeForWeb' => 'Special Order'
        ),
        array(
            'SizeID' => '76DBA995-F8BE-493E-8ECB-32DC9D919E4B',
            'SizeName' => '1gSO',
            'SizeForWeb' => 'Special Order'
        ),
        array(
            'SizeID' => '9B96A057-D1CD-4706-8686-F820BCAF85B9',
            'SizeName' => '3.5"SO',
            'SizeForWeb' => 'Special Order'
        ),
        array(
            'SizeID' => '2CE18F5C-F2D0-40B4-80BC-E38D9BDE19F6',
            'SizeName' => 'QtSO',
            'SizeForWeb' => 'Special Order'
        ),
        array(
            'SizeID' => '2AD4A8DB-4D2C-43F7-8078-AD41AC4E3F2E',
            'SizeName' => '1203SO',
            'SizeForWeb' => 'Special Order'
        ),
        array(
            'SizeID' => '7D144C11-18AF-4CA0-B41B-F26DE673065B',
            'SizeName' => 'pltSO',
            'SizeForWeb' => 'Special Order'
        ),
        array(
            'SizeID' => 'E0500AD1-D004-4572-86F8-ABFA11DF199A',
            'SizeName' => '306SO',
            'SizeForWeb' => 'Special Order'
        ),
        array(
            'SizeID' => 'D95B4BCE-EAD5-4C0A-847B-E6512E37DBC8',
            'SizeName' => '4.5*',
            'SizeForWeb' => '4.5" Pot'
        ),
        array(
            'SizeID' => 'EF2E21EF-8052-49A4-8F27-7458B9D4E1EC',
            'SizeName' => '1801',
            'SizeForWeb' => '1801 flats'
        ),
        array(
            'SizeID' => '1E601D46-DAD7-40A7-A813-FCDB0CE07FC7',
            'SizeName' => '3.5B',
            'SizeForWeb' => '3.5" Black Pot'
        ),
        array(
            'SizeID' => '17C5B5C6-7A3C-4B72-989B-9E189D39851E',
            'SizeName' => '804',
            'SizeForWeb' => '4 Pack'
        ),
        array(
            'SizeID' => '3D4F2578-BF95-40D6-B01F-B0A2C078E99E',
            'SizeName' => 'B&B',
            'SizeForWeb' => 'B&B'
        ),
        array(
            'SizeID' => '21DCC95E-D398-4C25-8873-D39A4FD47D43',
            'SizeName' => '1202',
            'SizeForWeb' => '1202'
        )
    );

    foreach($sizes as $size) {
        if ($size['SizeName'] == $name) {
            if (!empty($size['SizeForWeb'])) {
                return $size['SizeForWeb'];
            } else {
                return $name;
            }            
        }
    }
    return $name;
}