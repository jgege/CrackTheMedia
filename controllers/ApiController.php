<?php

namespace app\controllers;

use Yii;
use yii\filters\AccessControl;
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

    public function behaviors()
    {
        return [
            'corsFilter' => [
                'class' => \yii\filters\Cors::className(),
                'cors' => [
                    'Origin' => ['*'],
                    'Access-Control-Request-Method' => ['GET'],
                    'Access-Control-Request-Headers' => ['X-Wsse'],
                    'Access-Control-Allow-Credentials' => true,
                    'Access-Control-Max-Age' => 3600,
                    'Access-Control-Expose-Headers' => ['X-Pagination-Current-Page'],
                ],

            ],
        ];
    }

    public function actionIndex($s)
    {
        $this->enableCsrfValidation = false;
        $apiKey = 'AIzaSyADwBff-ITox3I4QB0ZOcxguJu3vPGVB5g';

        $keyword = parse_url($s, PHP_URL_HOST);

        $baseUrl = 'https://kgsearch.googleapis.com/v1/entities:search';
        $params = [
          'query' => $keyword,
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
                'keyword' => $keyword,
                'isOrganization' => false,
            ];
        }

        return [
            'keyword' => $keyword,
            'name' => $name,
            'desc' => $desc,
            'image' => $imageUrl,
            'isOrganization' => true,
        ];
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
}
