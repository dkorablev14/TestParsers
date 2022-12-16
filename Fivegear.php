<?php
class Fivegear
{
    private $brandName = '';
    private $url = 'https://xn--80aaaoea1ebkq6dxec.xn--p1ai/manufacturers/';

    public function get($brandName)
    {
        $this->brandName = $brandName;
        return $this->content();
    }

    private function page()
    {
        $brandNameLink = $this->brandName;
        if (preg_match('/[А-яЁё]/iu', $brandNameLink)) {
            $brandNameLink = $this->translit($brandNameLink);
        }
        $url = $this->url . str_replace(' ', '-', strtolower($brandNameLink));
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_HEADER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 40);

        $html = curl_exec($ch);
        curl_close($ch);
        return $html;
    }

    private function content()
    {
        $page = $this->page();
        preg_match('#HTTP/2\s(\d+)#',$page,$match);
        $dom = new DOMDocument();
        libxml_use_internal_errors(true);
        $dom->loadHTML($page);
        $domXPath = new DOMXPath($dom);
        $content['status'] = (int)$match[1];
        if ($this->getHeader($domXPath) === 'Такого производителя не существует'){
            $content['error'] = 'Фирмы-производителя с таким идентификатором не существует. Вероятно, Вы перешли по ссылке, которая содержала ошибку.';
        }
        else{
            $content['brand'] = $this->brandName;
            $content['description'] = $this->getDescription($domXPath);
            $content['brand_logo'] = 'https://xn--80aaaoea1ebkq6dxec.xn--p1ai' . $this->getLogo($domXPath);
            $content['brand_sample'] = 'https://xn--80aaaoea1ebkq6dxec.xn--p1ai' . $this->getSample($domXPath);
            $content['info'] = $this->getInfo($domXPath);
        }
        return $this->response($content);
    }

    private function getHeader($xpath)
    {
        return trim($xpath->query('//h2')->item(0)->nodeValue);
    }

    private function getDescription($xpath)
    {
        return trim($xpath->query('//div[contains(@class,"manufacturer-info-description")]')->item(0)->nodeValue);
    }

    private function getLogo($xpath)
    {
        return trim($xpath->query('//img[contains(@class,"manufacturer-logo-img")]')->item(0)->getAttribute('src'));
    }

    private function getSample($xpath)
    {
        return trim($xpath->query('//img[contains(@class,"img-fluid")]')->item(0)->getAttribute('src'));
    }

    private function getInfo($domXPath)
    {
        $data = [];
        $nodeList = $domXPath->query('//div[contains(@class,"mfr-property-row row")]');
        for ($i = 0; $i < $nodeList->length; $i++) {
            $value = '';

            $xpathName = $domXPath->query('//div[contains(@class,"mfr-prop-name")]')->item($i);
            $name = str_replace(':', '', trim($xpathName->nodeValue));

            $xpathValue = $domXPath->query('//div[contains(@class,"mfr-prop-value")]')->item($i);
            if ($xpathValue->hasChildNodes()) {
                for ($j = 0; $j < $xpathValue->childNodes->count(); $j++) {
                    if ($xpathValue->childNodes->item($j)->nodeName === 'a') {
                        $value = trim($xpathValue->childNodes->item($j)->getAttribute('href'));
                    }
                }
            }
            if ($value == '') {
                $value = trim($xpathValue->nodeValue);
            }
            $data[$name] = $value;
        }
        return $data;
    }

    private function response($content){
        header("Access-Control-Allow-Origin: *");
        header('Access-Control-Allow-Methods: GET, POST');
        header("Access-Control-Allow-Headers: X-Requested-With");
        return json_encode($content,JSON_UNESCAPED_UNICODE);
    }

    private function translit($str)
    {
        $converter = array(
            'а' => 'a', 'б' => 'b', 'в' => 'v',
            'г' => 'g', 'д' => 'd', 'е' => 'e',
            'ё' => 'e', 'ж' => 'zh', 'з' => 'z',
            'и' => 'i', 'й' => 'y', 'к' => 'k',
            'л' => 'l', 'м' => 'm', 'н' => 'n',
            'о' => 'o', 'п' => 'p', 'р' => 'r',
            'с' => 's', 'т' => 't', 'у' => 'u',
            'ф' => 'f', 'х' => 'h', 'ц' => 'c',
            'ч' => 'ch', 'ш' => 'sh', 'щ' => 'sch',
            'ь' => '', 'ы' => 'y', 'ъ' => '',
            'э' => 'e', 'ю' => 'yu', 'я' => 'ja',

            'А' => 'A', 'Б' => 'B', 'В' => 'V',
            'Г' => 'G', 'Д' => 'D', 'Е' => 'E',
            'Ё' => 'E', 'Ж' => 'Zh', 'З' => 'Z',
            'И' => 'I', 'Й' => 'Y', 'К' => 'K',
            'Л' => 'L', 'М' => 'M', 'Н' => 'N',
            'О' => 'O', 'П' => 'P', 'Р' => 'R',
            'С' => 'S', 'Т' => 'T', 'У' => 'U',
            'Ф' => 'F', 'Х' => 'H', 'Ц' => 'C',
            'Ч' => 'Ch', 'Ш' => 'Sh', 'Щ' => 'Sch',
            'Ь' => '', 'Ы' => 'Y', 'Ъ' => '',
            'Э' => 'E', 'Ю' => 'Yu', 'Я' => 'ja',
        );
        return strtr($str, $converter);
    }
}

$parser = new Fivegear();
echo($parser->get('Аляsка'));