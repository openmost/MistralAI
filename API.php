<?php
/**
 * Matomo - free/libre analytics platform
 *
 * @link https://matomo.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 *
 */

namespace Piwik\Plugins\MistralAI;

use Piwik\API\Request;

/**
 * API for plugin MistralAI
 *
 * @method static \Piwik\Plugins\MistralAI\API getInstance()
 */
class API extends \Piwik\Plugin\API
{
    public function getResponse($idSite, $period, $date, $prompt)
    {
        return $this->fetchMistralAI($prompt);
    }

    public function getInsight($idSite, $period, $date, $reportId)
    {
        $request = new Request("method=$reportId&idSite=$idSite&date=$date&period=$period&format=json");
        $result = $request->process();

        $data = Request::processRequest($reportId, array(
            'idSite' => $idSite,
            'date' => $date,
            'period' => $period,
            'format' => 'json', // this is the important bit
        ));

        $prompt = "Give me insights from the dataset formatted in JSON provided just there : $data";
        return $this->fetchMistralAI($prompt);
    }

    function fetchMistralAI($prompt)
    {
        $settings = new \Piwik\Plugins\MistralAI\SystemSettings();
        $host = $settings->host->getValue();
        $api_key = $settings->apiKey->getValue();
        $model = $settings->model->getValue();

        if (!$host) {
            error_log('You must enter a valid host');
        }

        if (!$api_key) {
            error_log('You must enter a valid API Key');
        }

        if (!$model) {
            error_log('You must enter a valid model');
        }

        if (!$prompt) {
            error_log('You must enter a valid prompt');
        }

        $data = [
            "model" => $model[0],
            "messages" => [
                [
                    "role" => "user",
                    "content" => urldecode($prompt)
                ]
            ]
        ];

        $headers = [
            'Content-Type: application/json',
            'Accept: application/json',
            'Authorization: Bearer ' . $api_key,
        ];

        $ch = curl_init($host);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data)); // Send data as JSON
        $response = curl_exec($ch);

        if (!$response) {
            error_log('An error occurred with the request');
        }

        curl_close($ch);

        return json_decode($response, true);
    }
}
