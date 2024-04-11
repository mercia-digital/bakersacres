<?php

class BakersFMAPI {
    private $FMAPIBase;
    private $FMUser;
    private $FMPass;
    private $FMToken;

    public function __construct() {
        $this->FMAPIBase = "https://bakersacresdb.com/fmi/data/vLatest/databases/Baker's Database";
        $this->FMUser = 'API';
        $this->FMPass = 'taut-stucco-kurt';
    }

    private function GetFMSessionToken() {
        $this->FMToken = get_transient('fmtoken');
        if (!$this->FMToken) {
            $this->RequestFMSessionToken();
            $this->FMToken = get_transient('fmtoken');
        }
        return $this->FMToken;
    }

    private function RequestFMSessionToken() {
        $api_url = $this->FMAPIBase . '/sessions';
        $credentials = base64_encode($this->FMUser . ":" . $this->FMPass);
    
        $response = wp_remote_post($api_url, array(
            'headers' => array(
                'Authorization' => 'Basic ' . $credentials,
                'Content-Type' => 'application/json'
            )
        ));
    
        if (is_wp_error($response)) {
            error_log('Error fetching API token: ' . $response->get_error_message());
            return;
        }
    
        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body);
    
        if (!empty($data->response->token)) {
            set_transient('fmtoken', $data->response->token, 875);
            $this->FMToken = $data->response->token;
        }
    }

    private function RequestFMData($url) {
        $this->FMToken = $this->GetFMSessionToken();
        if (!$this->FMToken) {
            echo('Unable to retrieve API token.');
            return;
        }
    
        $response = wp_remote_get($url, array(
            'headers' => array('Authorization' => 'Bearer ' . $this->FMToken),
            'timeout' => 360
        ));
    
        if (is_wp_error($response)) {
            echo('Error making API request: ' . $response->get_error_message());
            return;
        }

        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body);

        //$this->WriteResponseToFile($data->response->scriptResult);
    
        return $data->response->scriptResult;
    }

    public function RetrieveProductionPlan($year) {
        $url = $this->FMAPIBase . "/layouts/Variety/script/RetrieveProductionPlan?script.param=".$year."";
        return $this->RequestFMData($url);
    }
    
    public function RetrieveVarietyInfoAll() {
        $url = $this->FMAPIBase . "/layouts/Variety/script/RetrieveVarietyInfoAll";
        return $this->RequestFMData($url);
    }

    public function RetrieveVarietyInfoByLastChange($date) {
        $url = $this->FMAPIBase . "/layouts/Variety/script/RetrieveVarietyInfoByLastChange?script.param='".$date."'";
        return $this->RequestFMData($url);
    }
    
    public function RetrieveVarietyInfoByID($id) {
        $url = $this->FMAPIBase . "/layouts/Variety/script/RetrieveVarietyInfoByID?script.param=".$id."";
        return $this->RequestFMData($url);
    }

    private function WriteResponseToFile($jsonData) {
        $tz = get_option('timezone_string');
        $timestamp = time();
        $dt = new DateTime("now", new DateTimeZone($tz)); //first argument "must" be a string
        $dt->setTimestamp($timestamp); //adjust the object to correct timestamp

        //$filePath = '/var/www/html/wp-content/plugins/bakers-fm-cloud/import-log/'.$dt->format('Ymd_His').'.json';

        $filePath = plugin_dir_path( __FILE__ ) . 'import-log/'.$dt->format('Ymd_His').'.json';

        $chunkSize = 1024 * 1024; // Size of each chunk (1MB)

        $fileHandle = fopen($filePath, 'w'); // Open the file for writing

        if ($fileHandle === false) {
            echo "Failed to open the file for writing.";
            exit;
        }

        $length = strlen($jsonData);
        for ($i = 0; $i < $length; $i += $chunkSize) {
            // Write a chunk
            $written = fwrite($fileHandle, substr($jsonData, $i, $chunkSize));
            if ($written === false) {
                echo "Failed to write to the file.";
                fclose($fileHandle);
                exit;
            }
        }

        fclose($fileHandle); // Close the file handle
    }    
}