<?php

namespace Core;

class ApiClient
{
    private string $baseUrl;
    private ?string $token;
    private int $timeout = 30;

    public function __construct(?string $token = null)
    {
        $this->baseUrl = defined('API_BASE_URL') ? API_BASE_URL : 'https://api.learnrail.org/api';
        $this->token = $token;
    }

    public function get(string $endpoint, array $params = []): array
    {
        if (!empty($params)) {
            $endpoint .= '?' . http_build_query($params);
        }
        return $this->request('GET', $endpoint);
    }

    public function post(string $endpoint, array $data = []): array
    {
        return $this->request('POST', $endpoint, $data);
    }

    public function put(string $endpoint, array $data = []): array
    {
        return $this->request('PUT', $endpoint, $data);
    }

    public function delete(string $endpoint): array
    {
        return $this->request('DELETE', $endpoint);
    }

    public function upload(string $endpoint, array $files, array $data = []): array
    {
        return $this->request('POST', $endpoint, $data, $files);
    }

    private function request(string $method, string $endpoint, array $data = [], array $files = []): array
    {
        $url = $this->baseUrl . $endpoint;

        $ch = curl_init();

        $headers = [
            'Accept: application/json'
        ];

        if ($this->token) {
            $headers[] = 'Authorization: Bearer ' . $this->token;
        }

        $options = [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => $this->timeout,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_CUSTOMREQUEST => $method
        ];

        if (!empty($files)) {
            // Multipart form data for file uploads
            $postData = $data;
            foreach ($files as $key => $file) {
                if (isset($file['tmp_name']) && is_uploaded_file($file['tmp_name'])) {
                    $postData[$key] = new \CURLFile(
                        $file['tmp_name'],
                        $file['type'] ?? 'application/octet-stream',
                        $file['name'] ?? 'file'
                    );
                }
            }
            $options[CURLOPT_POSTFIELDS] = $postData;
        } elseif (in_array($method, ['POST', 'PUT', 'PATCH']) && !empty($data)) {
            $headers[] = 'Content-Type: application/json';
            $options[CURLOPT_POSTFIELDS] = json_encode($data);
        }

        $options[CURLOPT_HTTPHEADER] = $headers;

        curl_setopt_array($ch, $options);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);

        curl_close($ch);

        if ($error) {
            return [
                'success' => false,
                'status' => 0,
                'data' => ['message' => 'Connection error: ' . $error]
            ];
        }

        $decoded = json_decode($response, true);

        // Handle API response format
        if ($decoded === null) {
            return [
                'success' => false,
                'status' => $httpCode,
                'data' => ['message' => 'Invalid response from server']
            ];
        }

        // Check if response has success field
        if (isset($decoded['success'])) {
            return [
                'success' => $decoded['success'],
                'status' => $httpCode,
                'data' => $decoded['data'] ?? $decoded
            ];
        }

        // Assume success based on HTTP code
        return [
            'success' => $httpCode >= 200 && $httpCode < 300,
            'status' => $httpCode,
            'data' => $decoded
        ];
    }

    public function setToken(string $token): void
    {
        $this->token = $token;
    }

    public function setTimeout(int $seconds): void
    {
        $this->timeout = $seconds;
    }
}
