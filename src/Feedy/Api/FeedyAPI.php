<?php

namespace Feedy\Api;

use Symfony\Component\HttpClient\HttpClient;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Component\Mime\Part\DataPart;
use Symfony\Component\Mime\Part\Multipart\FormDataPart;


class FeedyAPI {
    private HttpClientInterface $client;

    public function __construct(
        protected string $token
    ){
        $this->client = HttpClient::createForBaseUri("https://feedy.levkopo.ru/api");
    }

    /**
     * @throws TransportExceptionInterface
     */
    public function getUser(int $user_id = 0, string $fields = ""): array|false {
        $params = ["fields"=>$fields];
        if($user_id==0) $params['user_id'] = $user_id;

        return $this->request("user/get", $params);
    }

    /**
     * @throws TransportExceptionInterface
     */
    public function getUserPhotos(int $user_id = 0, int $start_from = 0, int $count = 20): array|false {
        $params = ["count"=>$count];
        if($start_from==0) $params['start_from'] = $start_from;

        return $this->request("user/photos", $params);
    }

    /**
     * @throws TransportExceptionInterface
     */
    public function subscribe(int $user_id): array|false {
        return $this->request("user/subscribe", [
            "user_id" => $user_id
        ], "POST");
    }

    /**
     * @throws TransportExceptionInterface
     */
    public function unsubscribe(int $user_id): array|false {
        return $this->request("user/unsubscribe", [
            "user_id" => $user_id
        ], "POST");
    }

    /**
     * @throws TransportExceptionInterface
     */
    public function updateAvatar(string $photo, string $hash): array|false {
        return $this->request("user/avatar", [
            "photo" => $photo,
            "hash" => $hash
        ], "PUT");
    }

    /**
     * @throws TransportExceptionInterface
     */
    public function updateStatus(string $status = ""): array|false {
        return $this->request("user/status", [
            "status" => $status,
        ], "PUT");
    }

    /**
     * @throws TransportExceptionInterface
     */
    public function createPost(string $text = "", string $attachment = "", string $thread = ""): array|false {
        return $this->request("feed/post", [
            "text" => $text,
            "attachment" => $attachment,
        ], "PUT");
    }

    /**
     * @throws TransportExceptionInterface
     */
    public function getSubscriptions(string $fields = ""): array|false {
        return $this->request("user/subscriptions", [
            "fields" => $fields
        ]);
    }

    /**
     * @throws TransportExceptionInterface
     */
    public function searchUser(string $query, string $fields = ""): array|false {
        if(strlen($query)<3) return false;
        return $this->request("user/subscriptions", [
            "query" => $query,
            "fields" => $fields
        ]);
    }

    /**
     * @throws TransportExceptionInterface
     */
    public function uploadPhoto(string $path, bool $private = true): array|false {
        if($server = $this->request("attachments/server", ["type"=>"photo"])){
            $uploadUrl = $server['server_url'];

            $formData = new FormDataPart([
                'photo' => DataPart::fromPath($path),
            ]);

            $response = $this->client->request('POST', $uploadUrl, [
                'headers' => $formData->getPreparedHeaders()->toArray(),
                'body' => $formData->bodyToIterable(),
            ]);

            if($response->getStatusCode()==200){
                try {
                    $data = json_decode($response->getContent(false), true);
                    return $this->request("attachments/save/photo", [
                        "photo" => $data["photo"],
                        "hash" => $data["hash"]
                    ]);
                } catch (ClientExceptionInterface | RedirectionExceptionInterface
                | ServerExceptionInterface | TransportExceptionInterface) {}
            }
        }

        return false;
    }

    /**
     * @param string $method Api method
     * @param string $requestMethod Request method
     * @throws TransportExceptionInterface
     */
    public function request(string $method, array $params = [],
                            string $requestMethod = "GET"): array|false {
        $params["access_token"] = $this->token;
        $response = $this->client->request($requestMethod, $method."?".http_build_query($params));
        if($response->getStatusCode()!=200) return false;

        try {
            return json_decode($response->getContent(false), true)['response'];
        } catch (ClientExceptionInterface | RedirectionExceptionInterface
                | ServerExceptionInterface | TransportExceptionInterface) {
            return false;
        }
    }
}