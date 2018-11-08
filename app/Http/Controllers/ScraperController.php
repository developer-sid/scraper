<?php

namespace App\Http\Controllers;

use Illuminate\Routing\Controller as BaseController;
use Illuminate\Support\Facades\Input;
use Purl\Url;
use GuzzleHttp\Exception\GuzzleException;
use Goutte\Client;
use Stillat\Numeral\Languages\LanguageManager;
use Stillat\Numeral\Numeral;


class ScraperController extends BaseController
{
    static public function run($args) {
    	// creating url parser object
    	$url = new Url($args['url']);
    	// its amazon product page
    	if ($args['store'] == 'amazon') {
    		// calling amazon scraper function
    		self::scrapeAmazon($url);
    	}    	
    }

    // method to scrape amazon product page
    static private function scrapeAmazon($url) {
    	// amazon url validation
    	if (!in_array(str_replace('www.', '', $url['host']), ['amazon.com', 'amazon.co.uk'])) {
    		// validation failed, throwing error
    		echo collect(['error' => 'not amazon url'])->toJson(), "\n";
            return false;
    	}

    	// Create the language manager instance.
		$languageManager = new LanguageManager;
		// Create the Numeral instance.
		$formatter = new Numeral;
		// Now we need to tell our formatter about the language manager.
		$formatter->setLanguageManager($languageManager);
		// do an http call
    	$crawler = self::httpCall($url->getUrl());

    	// amazon product page validation
    	if (!$crawler->filter('div#cerberus-data-metrics')->count()) {
    		echo collect(['error' => 'not valid amazon product page'])->toJson(), "\n";
            return false;
    	}

    	$data = [
        	'product_title' => trim($crawler->filter('div#titleSection > h1#title > span#productTitle')->first()->text()), // parse product title
        	'asin' => $crawler->filter('div#cerberus-data-metrics')->first()->attr('data-asin'), // parse the product number
        	'sale_price' => $formatter->unformat($crawler->filter('div#cerberus-data-metrics')->first()->attr('data-asin-price')),
        	'original_price' => $formatter->unformat($crawler->filter('div#price span.a-text-strike')->first()->text()), // get product sale price value
        	'product_description' => trim(strip_tags(preg_replace('~[\t\n]+~', '', $crawler->filter('div#featurebullets_feature_div')->first()->text()))) // get product original price value
        ];
        // get labels from specification data table
        $specification_labels = $crawler->filter('div#prodDetails > div.wrapper > div.col1 > div.techD div.pdTab > table tr > td.label')->each(function ($node) {
		    return $node->text();
		});
		// get values from specification data table
		$specification_values = $crawler->filter('div#prodDetails > div.wrapper > div.col1 > div.techD div.pdTab > table tr > td.value')->each(function ($node) {
		    return $node->text();		    
		});
		// create mapped array of specification 
		foreach ($specification_labels as $key => $label) {
			$specifications[] = ['label' => $label, 'value' => $specification_values[$key]];
		}
		if (isset($specifications)) $data['product_specifications'] = $specifications;

		// collect product images
		$data['product_images'] = $crawler->filter('div#altImages > ul.a-unordered-list > li.item span.a-button-thumbnail img')->each(function ($node) {
		    return str_replace('_SS40_', '_SL1500_', $node->attr('src'));		    
		});				
		echo json_encode($data, JSON_PRETTY_PRINT), "\n";
    }

    static private function httpCall($url) {
    	//create a http client object	
    	$client = new Client();
    	try {
            return $client->request('GET', $url);
        }
        catch (GuzzleHttp\Exception\ClientException $e) {
            $response = $e->getResponse();
            echo collect(['error' => $response->getBody()->getContents()])->toJson() , "\n";       
        }
        return false;
    }
}
