<?php
namespace Asil\VkMarket;

use Asil\VkMarket\Exception\VkException;

class VkConnect
{
    const API_VERSION = '5.131';
    const CONN_URL = 'https://api.vk.com/method/';

    private $accessToken;
    private $ownerId;
    private $groupId;

    /**
     * VkConnect constructor.
     */
    public function __construct($accessToken, $groupId, $ownerId)
    {
        $this->accessToken = $accessToken;
        $this->ownerId = $ownerId;
        $this->groupId = $groupId;
    }

    /**
     * @return mixed
     */
    public function getAccessToken()
    {
        return $this->accessToken;
    }

    /**
     * @return mixed
     */
    public function getOwnerId()
    {
        return $this->ownerId;
    }

    /**
     * @return mixed
     */
    public function getGroupId()
    {
        return $this->groupId;
    }


    public function getRequest($uploadUrl, array $params)
    {
        $step = 0;
        do {
            try {
                $tryAgain = false;
                $content =  $this->getCurlRequest($uploadUrl, $params);
            }
            catch(VkException $e) {
                sleep(5);
                if ($step <= 5)
                    $tryAgain = true;
                $step++;
            }
        } while($tryAgain);

        return $content;
    }

    public function getCurlRequest($methodName, array $params = [])
    {
        $getParams = '';

        if (sizeof($params)) {
            $getParams = '?' . http_build_query($params);
        }

        $conUrl = self::CONN_URL . $methodName . $getParams;

        $conn = curl_init();
        curl_setopt($conn, CURLOPT_URL, $conUrl);
        curl_setopt($conn, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($conn, CURLOPT_RETURNTRANSFER, true);

        $content = curl_exec($conn);
        $content = json_decode($content, true);
        curl_close($conn);

        VkHelper::checkError($content);

        return $content;
    }

    public function postRequest($uploadUrl, array $params)
    {
        $step = 0;
        do {
            try {
                $tryAgain = false;
                $content =  $this->postCurlRequest($uploadUrl, $params);
            }
            catch(VkException $e) {
                sleep(5);
                if ($step <= 5)
                    $tryAgain = true;
                $step++;
            }
        } while($tryAgain);

        return $content;
    }

    public function postCurlRequest($uploadUrl, array $params) {
        $conn = curl_init($uploadUrl);
        curl_setopt($conn, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($conn, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($conn, CURLOPT_POST, true);
        curl_setopt($conn, CURLOPT_POSTFIELDS, $params);

        $content = curl_exec($conn);
        $content = json_decode($content, true);
        curl_close($conn);

        VkHelper::checkError($content);

        return $content;
    }
}