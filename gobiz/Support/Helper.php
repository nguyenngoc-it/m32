<?php

namespace Gobiz\Support;

use Carbon\Carbon;
use DateInterval;
use DateTime;
use Exception;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Promise\Utils;
use Illuminate\Database\Eloquent\Builder;
use Psr\Http\Message\ResponseInterface;

class Helper
{
    /**
     * Xoa cac ky tu dac biet cua chuoi
     *
     * @param $string
     * @param string $replaceChar
     * @return null|string|string[]
     */
    public static function clean($string, $replaceChar = '_')
    {
        $string = str_replace(' ', $replaceChar, $string); // Replaces all spaces with hyphens.
        return preg_replace('/[^A-Za-z0-9\_\-]/', '', $string); // Removes special chars
    }

    /**
     * Tách đoạn dài thành từng đoạn nhỏ giới hạn độ dài
     *
     * @param $longString
     * @param int $maxLineLength
     * @param string $delimiter
     * @return array
     */
    public static function splitByChar($longString, int $maxLineLength = 30, $delimiter = ' ')
    {
        $words         = explode($delimiter, $longString);
        $currentLength = 0;
        $index         = 0;
        $output        = [];
        foreach ($words as $word) {
            $wordLength = strlen($word) + 1;
            if (($currentLength + $wordLength) <= $maxLineLength) {
                $output[$index] = !empty($output[$index]) ? $output[$index] . ' ' . $word : $word;
                $currentLength  += $wordLength;
            } else {
                $index          += 1;
                $currentLength  = $wordLength;
                $output[$index] = $word;
            }
        }

        return $output;
    }

    /**
     * @param integer $limit
     * @return bool|string
     */
    public static function unique_code(int $limit)
    {
        return substr(base_convert(sha1(uniqid(mt_rand())), 16, 36), 0, $limit);
    }

    /**
     * @param $array
     * @param $key
     * @return mixed|null
     */
    public static function issetNull($array, $key)
    {
        return isset($array[$key]) ? $array[$key] : null;
    }

    /**
     * @return string
     */
    public static function getFakeFullName()
    {
        $first            = ['Nguyễn', 'Vũ', 'Đặng', 'Lương', 'Phạm', 'Hoàng', 'Trịnh', 'Phan', 'Lê', 'Mạc', 'Hồ', 'Trương',
            'Lý', 'Tô', 'Mai', 'Tòng', 'Cung', 'Văn', 'Đinh', 'Vương', 'Đoàn', 'Hà'];
        $middleLast[0][0] = ['Hồng', 'Minh', 'Gia', 'Ngọc', 'Quang', 'Thanh', 'Ngân', 'Giang', 'Uyên', 'Trúc', 'Như', 'Thị', 'Y'];
        $middleLast[1][0] = ['Văn', 'Hồng', 'Minh', 'Gia', 'Ngọc', 'Quang', 'Thanh', 'Sơn', 'Huy', 'Ngân', 'Giang', 'Trúc', 'Như'];
        $middleLast[0][1] = [
            'Oanh', 'Trang', 'Tú Anh', 'Thùy Anh', 'Kim Chi', 'Mỹ Châu', 'Diệp Anh', 'Huệ', 'Phương', 'Quỳnh',
            'Như Lan', 'Ngọc Mai', 'Ái Nhi', 'Lê', 'Đào', 'Hồng', 'Trúc', 'Xu', 'Trâm', 'Châu', 'Phi', 'Vân', 'Tuyết', 'Ánh', 'Thu', 'Minh'
        ];
        $middleLast[1][1] = [
            'Hà', 'Bình', 'Tú', 'Tùng', 'Vũ', 'Thanh', 'Kim', 'Châu', 'Anh', 'Phương', 'Quỳnh',
            'Quân', 'Đức', 'Chiến', 'Sáng', 'Đông', 'Bảo', 'Hải', 'Việt', 'Duy', 'Tâm', 'Thái', 'Lâm', 'Bách', 'Khánh', 'Sơn', 'Kiên'
        ];
        $manOrWoman       = rand(0, 1);


        return $first[rand(0, count($first) - 1)] . ' ' . $middleLast[$manOrWoman][0][rand(0, count($middleLast[$manOrWoman][0]) - 1)] . ' ' .
            $middleLast[$manOrWoman][1][rand(0, count($middleLast[$manOrWoman][1]) - 1)];
    }

    /**
     * @param int $number
     * @param array $firsts
     * @param null $phoneTemp
     * @return string
     */
    public static function getFakeNumberPhone($number = 10, $firsts = [], $phoneTemp = null)
    {
        $first = ['016', '015', '018', '090', '091', '092', '093', '086', '097', '098'];
        if (!empty($firsts)) {
            $number++;
            $first = $firsts;
        }
        $characters = '0123456789';
        $last       = '';

        for ($i = 0; $i < ($number - 3); $i++) {
            if ($phoneTemp && !empty($phoneTemp[$i])) {
                $last .= $phoneTemp[$i];
            } else {
                $index = rand(0, strlen($characters) - 1);
                $last  .= $characters[$index];
            }
        }

        return $first[rand(0, count($first) - 1)] . $last;
    }

    /**
     * @param $dateTo
     * @param $dateFrom
     * @param bool $roundDay
     * @return int
     */
    public static function dateDiff(DateTime $dateTo, DateTime $dateFrom, $roundDay = true)
    {
        if (!$dateTo instanceof Carbon) {
            $dateTo = Carbon::parse($dateTo->format('Y-m-d H:i:s'));
        }
        if (!$dateFrom instanceof Carbon) {
            $dateFrom = Carbon::parse($dateFrom->format('Y-m-d H:i:s'));
        }

        $secondOnDayTo   = $dateTo->hour * 3600 + $dateTo->minute * 60 + $dateTo->second;
        $secondOnDayFrom = $dateFrom->hour * 3600 + $dateFrom->minute * 60 + $dateFrom->second;

        $dateDiff = date_diff($dateTo, $dateFrom);
        if ($dateDiff instanceof DateInterval) {
            if ($roundDay) {
                return $secondOnDayTo > $secondOnDayFrom ? $dateDiff->days : $dateDiff->days + 1;
            }

            return $dateDiff->days;
        }

        return 0;
    }

    /**
     * @param Builder $builder
     * @return string
     */
    public static function renderSql(Builder $builder)
    {
        $sql = str_replace(['?'], ['\'%s\''], $builder->toSql());
        return vsprintf($sql, $builder->getBindings());
    }

    /**
     * @param int $length
     * @param null $pool
     * @return bool|string
     */
    public static function quickRandom($length = 16, $pool = null)
    {
        if (empty($pool)) {
            $pool = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        }

        return substr(str_shuffle(str_repeat($pool, 5)), 0, $length);
    }

    /**
     * @param $baseUrl
     * @param $endPoint
     * @param string $method
     * @param array $headers
     * @param array $params
     * @return mixed|null|array
     * @throws GuzzleException
     */
    public static function quickCurl($baseUrl, $endPoint, string $method = 'get', array $headers = [], array $params = [], $decode = true)
    {
        $defaultHeaders['Accept']       = 'application/json';
        $defaultHeaders['Content-Type'] = 'application/json';
        if ($headers) {
            $defaultHeaders = $headers;
        }
        $curl = new Client([
            'base_uri' => $baseUrl,
            'timeout' => 60,
            'headers' => $defaultHeaders
        ]);
        /** @var ResponseInterface $result */
        switch ($method) {
            case 'get':
                $result = $curl->get($endPoint . '?' . http_build_query($params));
                break;
            case 'post':
                $result = $curl->post($endPoint, ['form_params' => $params]);
                break;
            default:
                $result = $curl->{$method}($endPoint, $params);
        }
        if ($result instanceof Exception) {
            return null;
        }
        return $decode ? json_decode($result->getBody()->getContents(), true) : $result;
    }

    /**
     * @param $baseUrl
     * @param array $endPoints
     * @param array $headers
     * @return array
     */
    public static function quickMultipleCurls($baseUrl, array $endPoints, $headers = [])
    {
        $defaultHeaders['Accept']       = 'application/json';
        $defaultHeaders['Content-Type'] = 'application/json';
        if ($headers) {
            $defaultHeaders = $headers;
        }
        $curl = new Client([
            'base_uri' => $baseUrl,
            'timeout' => 60,
            'headers' => $defaultHeaders
        ]);

        $promises = [];
        foreach ($endPoints as $key => $endPoint) {
            $promises[$key] = $curl->getAsync($endPoint);
        }

        $responses = Utils::settle($promises)->wait();
        $results   = [];
        foreach ($responses as $key => $response) {
            if ($response['state'] == 'fulfilled') {
                /** @var ResponseInterface $responseValue */
                $responseValue = $response['value'];
                $results[$key] = json_decode($responseValue->getBody()->getContents(), true);
            }
        }

        return $results;
    }

    /**
     * Convert camelCase to snake case
     *
     * @param $string
     * @return string
     */
    public static function decamelize($string)
    {
        return strtolower(preg_replace(['/([a-z\d])([A-Z])/', '/([^_])([A-Z][a-z])/'], '$1_$2', $string));
    }

    /**
     * Điều chỉnh đúng số điện thoại Thái Lan
     *
     * @param $mobilePhone
     * @return string
     */
    public static function correctThailandMobile($mobilePhone)
    {
        $mobilePhone = static::clean($mobilePhone);
        $mobilePhone = preg_replace('/[^0-9]/', '', $mobilePhone);
        $mobileLength = strlen($mobilePhone);
        if ($mobileLength == 10) {
            return $mobilePhone;
        }
        if ($mobileLength < 10) {
            return str_pad($mobilePhone, 10, '0');
        }
        if ($mobileLength > 10) {
            return '0' . substr($mobilePhone, ($mobileLength - 9), 9);
        }
        return $mobilePhone;
    }

}
