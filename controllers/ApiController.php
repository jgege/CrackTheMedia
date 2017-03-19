<?php

namespace app\controllers;

use Yii;
use yii\filters\AccessControl;
use yii\helpers\Json;
use yii\web\Controller;
use yii\filters\VerbFilter;
use GuzzleHttp\Client;

class ApiController extends Controller
{
    /**
     * @inheritdoc
     */
    public function actions()
    {
        return [
            'error' => [
                'class' => 'yii\web\ErrorAction',
            ],
        ];
    }

    public function actionIndex($s)
    {
        $this->enableCsrfValidation = false;
        $apiKey = 'AIzaSyADwBff-ITox3I4QB0ZOcxguJu3vPGVB5g';

        $baseUrl = 'https://kgsearch.googleapis.com/v1/entities:search';
        $params = [
            'query' => $s,
            'limit' => 10,
            'indent' => TRUE,
            'key' => $apiKey];
        $url = $baseUrl . '?' . http_build_query($params);

        $httpClient = new Client;
        try {
            $request = $httpClient->get($url, ['allow_redirects' => false]);
            $content = ($request->getBody()->getContents());
        } catch (ClientException $e) {
            $msg = $e->getRequest();
            return null;
            //var_dump($msg);
        }

        $contentJson = \yii\helpers\Json::decode($content);

        if (!isset($contentJson['itemListElement'], $contentJson['itemListElement'][0], $contentJson['itemListElement'][0]['result'])) {
            return null;
        }

        $firstResult = $contentJson['itemListElement'][0]['result'];

        $name = $firstResult['name'];
        $desc = $firstResult['description'];
        $type = $firstResult['@type'];
        $imageUrl = $firstResult['image']['contentUrl'];
        $isOrganization = in_array('Organization', $type);

        Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
        if (!$isOrganization) {
            return [
                'keyword' => $s,
                'isOrganization' => false,
            ];
        }

        return [
            'keyword' => $s,
            'name' => $name,
            'desc' => $desc,
            'image' => $imageUrl,
            'isOrganization' => true,
        ];

        // $contentJson['itemListElement'][0]['result']['type'] // array
        // $contentJson['itemListElement'][0]['result']['name'] //
        // $contentJson['itemListElement'][0]['result']['description'] //
        exit();
    }

    public function actionImage()
    {
        $apiKey = 'AIzaSyADwBff-ITox3I4QB0ZOcxguJu3vPGVB5g';
        $cvurl = "https://vision.googleapis.com/v1/images:annotate?key=" . $apiKey;

        //$url = 'http://ichef-1.bbci.co.uk/news/624/cpsprodpb/2243/production/_95217780_60ce91f2-f679-4d92-95b9-9a0dab5430e3.jpg';
        $url = 'https://888-s3.s3.eu-central-1.amazonaws.com/thumbs/2017-03-18/dfe56fabd49f2264eb9c8664cd823cbd/web760.jpeg';

        $data = '{
  "requests":
  [
    {
      "image":
      {
        "source":
        {
          "imageUri": "' . $url . '"
        }
      },
      "features":
      [
        {
          "type": "WEB_DETECTION",
          "maxResults": 10
        }
      ]
    }
  ]
}';


        $httpClient = new Client;
        $response = $httpClient->post(
            $cvurl,
            [
                'headers' => [
                    'content-type' => 'application/json'
                ],
                'body' => $data
            ]
        );


        $content = \yii\helpers\Json::decode($response->getBody()->getContents());

        $imageList = $content['responses'][0]['webDetection']['fullMatchingImages'];
        $webpageList = $content['responses'][0]['webDetection']['pagesWithMatchingImages'];

        $originalImageResponse = $httpClient->get($url);
        $originalLastModDate = $originalImageResponse->getHeader('Last-Modified');
        $originalLastModDate = $originalLastModDate[0];
        $originalLastModDateTimestamp = strtotime($originalLastModDate);

        if (!$originalLastModDate) {
            return null;
        }

        $oldestImageUrl = $url;
        $oldestLastModDateTimestamp = $originalLastModDateTimestamp;
        $originalIsOldest = true;
        $originalWebsiteUrl = null;
        foreach ($imageList as $index => $imageData) {
            $data = $httpClient->get($imageData['url']);
            $lastMod = $data->getHeader('Last-Modified');

            if (isset($lastMod[0])) {
                $lastMod = $lastMod[0];
                $currentLastModTimestamp = strtotime($lastMod);
                if ($oldestLastModDateTimestamp > $currentLastModTimestamp) { // is newer
                    $oldestLastModDateTimestamp = $currentLastModTimestamp;
                    $oldestImageUrl = $imageData['url'];
                    $originalIsOldest = false;
                    $originalWebsiteUrl = $webpageList[$index];
                }
            }
        }

        Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
        return [
            'isOldest' => $originalIsOldest,
            'lastModifiedDate' => date('Y-m-d H:i:s', $oldestLastModDateTimestamp),
            'originalLastModifiedDate' => date('Y-m-d H:i:s', $originalLastModDateTimestamp),
            'lastModifiedDateDifference' => ($originalLastModDateTimestamp - $oldestLastModDateTimestamp),
            'imgUrl' => $oldestImageUrl,
            'originalWebsiteUrl' => $originalWebsiteUrl,
        ];
    }

    public function actionSimilarity($query) {

        $broken_down = array();
        $broken_down = preg_split("/ +/", $query);
        $query_formatted = preg_replace("/ +/", "+", $query);

        $url_base = "https://www.googleapis.com/customsearch/v1?q=";
        $url_custom_key = "&cx=003325682632509586946%3Aqs5_o_akoyc";
        $api_key = "&key=AIzaSyB7IyBHpgbElcGs4jeIRotuySpk2aewaTA";

        $full_call = $url_base . $query_formatted . $url_custom_key . $api_key;

        $http_client = new Client();
        $response = $http_client->get($full_call)->getBody()->getContents();
        $json_response = Json::decode($response);



        //$response_material = $json_response['items'][0]['pagemap']['newsarticle'];
        $response_items = array();
        $counter = 0;
        foreach ($json_response['items'] as $item) {

                foreach($item['pagemap']['newsarticle'] as $newsarticle) {
                    if(isset($newsarticle['headline'])) {
                        array_push($response_items, $newsarticle['headline']);
                    } else if (isset($newsarticle['name'])) {
                        array_push($response_items, $newsarticle['name']);
                    }
                    $counter+=1;
                    if($counter >= 10) break;
                }
            if($counter >= 10) break;
        }

        // var_dump($response_items);

        Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
        return [
            'foundSimilarities' => count($response_items)!=0,
            'similarArticles' => $response_items
        ];

    }

}
