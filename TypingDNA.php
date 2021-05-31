<?php

namespace App\Services;

use Exception;

class TypingDNA
{
    private static $instance = null;
    private $apiKey = null;
    private $apiSecret = null;
    private $typingdna_url = null;


    private function __construct()
    {
        $this->init();
    }

    private function init()
    {
        if (!$this->apiKey = env('TYPINGDNA_API_KEY')) {
            throw new Exception("Provide your API Key", 1);
        }

        if (!$this->apiSecret = env('TYPINGDNA_API_SECRET')) {
            throw new Exception("Provide your secret key ", 1);
        }

        $this->typingdna_url = env('TYPINGDNA_BASE_URL', 'https://api.typingdna.com');
    }

    public static function getInstance(): TypingDNA
    {
        if (is_null(self::$instance)) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function check(string $uniqueKey)
    {
        $code = $this->generateCode($uniqueKey);
        $result = $this->checkUser($code);
        return  $result;
    }

    public function delete(string $uniqueKey)
    {
        $code = $this->generateCode($uniqueKey);
        $result = $this->deletePattern($code);
        return  $result;
    }

    public function auto(string $uniqueKey, $tp)
    {
        $code = $this->generateCode($uniqueKey);
        $response = $this->makePostRequest('auto', $tp, $code);

        $result = $response;
        if ($result['status'] === 429) {
            sleep(1);
            $result = $this->auto($uniqueKey, $tp);
        }
        return $result;
    }

    public function save(string $tp, string $uniqueKey)
    {
        $code = $this->generateCode($uniqueKey);
        return $this->savePattern($tp,  $code);
    }

    private function deletePattern(string $uniqueKey)
    {
        $response = $this->makeDeleteRequest('user', $uniqueKey);
        return $response;
    }

    private function savePattern(string $tp, string $code)
    {
        $response = $this->makePostRequest('save', $tp, $code);
        return $response;
    }

    private function checkUser(string $uniqueKey)
    {
        $response = $this->makeGetRequest('user', $uniqueKey);
        return $response;
    }

    private function generateCode(string $uniqueKey): string
    {
        if ($secret = env('TYPINGDNA_SECRET')) {
            return \md5($uniqueKey . $secret);
        }
        throw new Exception('Provide a secret key');
    }

    private function makePostRequest(string $type, string $tp, string $uniqueKey)
    {
        $base_url = $this->typingdna_url . '/%s/%s';
        $ch = curl_init(sprintf($base_url, $type, $uniqueKey));
        $data = array('tp' => $tp);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/x-www-form-urlencoded'));
        curl_setopt($ch, CURLOPT_HEADER, FALSE);
        curl_setopt($ch, CURLOPT_USERPWD, $this->apiKey . ":" . $this->apiSecret);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_POST, TRUE);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data) . "\n");

        $response = curl_exec($ch);
        curl_close($ch);

        if ($response === false) {
            throw new Exception("Error Processing Request", 1);
        }
        return $response;
    }

    private function makeGetRequest(string $type, string $uniqueKey)
    {
        $base_url = $this->typingdna_url . '/%s/%s';
        $ch = curl_init(sprintf($base_url, $type, $uniqueKey));
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/x-www-form-urlencoded'));
        curl_setopt($ch, CURLOPT_USERPWD, $this->apiKey . ":" . $this->apiSecret);
        curl_setopt($ch, CURLOPT_HEADER, FALSE);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
        $response = curl_exec($ch);
        curl_close($ch);
        if ($response === false) {
            throw new Exception("Error Processing Request", 1);
        }
        return $response;
    }

    private function makeDeleteRequest(string $type, string $uniqueKey, string $patternType = '', string $textId = '', string $device = 'all')
    {

        // work on custom fields

        $base_url = $this->typingdna_url . '/%s/%s?type=%s&textid=%s&device=%s';

        $ch = curl_init(sprintf($base_url, $type, $uniqueKey, $patternType, $textId, $device));
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/x-www-form-urlencoded'));
        curl_setopt($ch, CURLOPT_USERPWD, $this->apiKey . ":" . $this->apiSecret);
        curl_setopt($ch, CURLOPT_HEADER, FALSE);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "DELETE");
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);

        $response = curl_exec($ch);
        curl_close($ch);
        if ($response === false) {
            throw new Exception("Error Processing Request", 1);
        }
        return $response;
    }
}
