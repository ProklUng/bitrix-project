<?php

namespace Local\Bundles\InstagramParserRapidApiBundle\Services;

use Exception;
use Local\Bundles\InstagramParserRapidApiBundle\Services\Interfaces\RetrieverInstagramDataInterface;
use Psr\Cache\InvalidArgumentException;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;

/**
 * Class RetrieverInstagramDataRapidApi
 * Парсер Инстаграма через RapidAPI.
 * @see https://rapidapi.com/restyler/api/instagram40
 *
 * @package Local\Bundles\InstagramParserRapidApiBundle\Services
 *
 * @since 21.02.2021
 */
class RetrieverInstagramDataRapidApi implements RetrieverInstagramDataInterface
{
    /**
     * @const string RAPID_API_URL URL RAPID API.
     */
    private const RAPID_API_URL = 'instagram40.p.rapidapi.com';

    /**
     * @var CacheInterface $cacher Кэшер.
     */
    private $cacher;

    /**
     * @var string $userId Instagram ID user. @see См. https://codeofaninja.com/tools/find-instagram-user-id/
     */
    private $userId;

    /**
     * @var string $queryId Параметр afterParams RapidAPI.
     */
    private $queryId;

    /**
     * @var integer $count Сколько картинок запрашивать.
     */
    private $count = 12;

    /**
     * @var string $rapidApiKey
     */
    private $rapidApiKey;

    /**
     * @var boolean $useMock Использовать мок? (для отладки)
     */
    private $useMock = false;

    /**
     * @var string $fixture Фикстура.
     */
    private $fixture = '';


    /**
     * RetrieverInstagramDataRapidApi constructor.
     *
     * @param CacheInterface $cacher      Кэшер.
     * @param string         $userId      Instagram ID user.
     * @param string         $rapidApiKey Ключ к https://rapidapi.com/restyler/api/instagram40.
     * @param string         $afterParam  Параметр after RapidAPI.
     */
    public function __construct(
        CacheInterface $cacher,
        string $userId,
        string $rapidApiKey,
        string $afterParam = ''
    ) {
        $this->cacher = $cacher;
        $this->userId = $userId;
        $this->queryId = $afterParam;
        $this->rapidApiKey = $rapidApiKey;
    }

    /**
     * @inheritDoc
     * @throws Exception
     * @throws InvalidArgumentException
     */
    public function query(): array
    {
        return $this->cacher->get('instagram-parser-fok', function (ItemInterface $item) {

            $response = $this->getCurlData($this->userId, $this->count);
            $json = json_decode($response, true);

            if (!$json) {
                throw new Exception('Get Request Error: answer not json!');
            }

            return $json;
        });
    }

    /**
     * Обращение к RapidAPI через CURL.
     *
     * @param string  $userId ID пользователя.
     * @param integer $count  Сколько картинок получить (максимум - 12).
     *
     * @return string
     * @throws Exception Ошибки транспорта.
     */
    private function getCurlData(
        string $userId,
        int $count = 12
    ): string {

        if ($this->useMock) {
            return $this->fixture;
        }

        // Опциональный параметр after.
        $queryString = 'https://' . self::RAPID_API_URL . '/account-medias?userid=' . $userId . '&first=' . $count;
        if ($this->queryId) {
            $queryString = $queryString . '&after=' . $this->queryId;
        }

        $curl = curl_init();

        curl_setopt_array($curl,
            [
                CURLOPT_URL => $queryString,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_ENCODING => '',
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 30,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => 'GET',
                CURLOPT_HTTPHEADER => [
                    'x-rapidapi-host: '.self::RAPID_API_URL,
                    'x-rapidapi-key: '.$this->rapidApiKey,
                ],
        ]);

        $response = curl_exec($curl);
        $err = curl_error($curl);

        curl_close($curl);

        if ($err) {
            throw new Exception('Get Request Error: '.$err);
        }

        return $response;
    }

    /**
     * @inheritDoc
     */
    public function setUserId(string $userId): RetrieverInstagramDataInterface
    {
        $this->userId = $userId;

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function setQueryId(string $queryId): RetrieverInstagramDataInterface
    {
        $this->queryId = $queryId;

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function setCount(int $count): RetrieverInstagramDataInterface
    {
        $this->count = $count;

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function setUseMock(bool $useMock, string $fixturePath = ''): self
    {
        $this->useMock = $useMock;
        if ($useMock & $fixturePath !== '') {
            $this->fixture = (string)file_get_contents(
              $_SERVER['DOCUMENT_ROOT'] . $fixturePath
            );
        }

        return $this;
    }
}
