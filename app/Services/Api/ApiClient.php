<?php

namespace App\Services\Api;

use Illuminate\Contracts\Auth\Factory as AuthFactory;
use Illuminate\Contracts\Http\Kernel as HttpKernel;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Facade;
use Illuminate\Support\Facades\Http;
use Symfony\Component\HttpFoundation\Response;

/**
 * Praat met de UpMove-API (config/upmove.php). Stuurt het token van de ingelogde
 * gebruiker mee en zet fouten om naar ApiException met een Nederlandse melding.
 *
 * Normaal via HTTP. In tests (UPMOVE_API_IN_PROCESS=true) wordt het verzoek binnen
 * hetzelfde proces afgehandeld, zodat een test het hele pad scherm → API → database raakt.
 */
class ApiClient
{
    public const CONNECTION_MESSAGE = 'Geen verbinding met de server. Controleer je internetverbinding en probeer het opnieuw.';

    public function __construct(private readonly ApiToken $token) {}

    /**
     * @param  array<string, mixed>  $query
     * @return array<string, mixed>
     */
    public function get(string $path, array $query = []): array
    {
        return $this->send('GET', $path, $query);
    }

    /**
     * @param  array<string, mixed>  $data
     * @param  array<string, array{path: string, name: string}>  $files  Bestanden als multipart (veld => pad + naam).
     * @return array<string, mixed>
     */
    public function post(string $path, array $data = [], array $files = []): array
    {
        return $this->send('POST', $path, $data, $files);
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public function put(string $path, array $data = []): array
    {
        return $this->send('PUT', $path, $data);
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public function delete(string $path, array $data = []): array
    {
        return $this->send('DELETE', $path, $data);
    }

    /**
     * @param  array<string, mixed>  $data
     * @param  array<string, array{path: string, name: string}>  $files
     * @return array<string, mixed>
     */
    private function send(string $method, string $path, array $data, array $files = []): array
    {
        [$status, $json] = config('upmove.api_in_process')
            ? $this->sendInProcess($method, $path, $data, $files)
            : $this->sendOverHttp($method, $path, $data, $files);

        if ($status >= 200 && $status < 300) {
            return $json;
        }

        $message = is_string($json['message'] ?? null) && $json['message'] !== '' ? $json['message'] : null;

        throw match (true) {
            $status === 401 => new ApiException('Je bent uitgelogd. Log opnieuw in.', 401),
            $status === 422 => new ApiException($message ?? 'Controleer de ingevulde gegevens.', 422, $this->validationErrors($json)),
            $status === 403 => new ApiException($message ?? 'Dit mag je niet doen.', 403),
            $status === 404 => new ApiException('Dit bestaat niet (meer).', 404),
            $status === 429 => new ApiException($message ?? 'Te veel verzoeken. Probeer het zo opnieuw.', 429),
            default => new ApiException('Er ging iets mis op de server. Probeer het later opnieuw.', $status),
        };
    }

    /**
     * @param  array<string, mixed>  $data
     * @param  array<string, array{path: string, name: string}>  $files
     * @return array{0: int, 1: array<string, mixed>}
     */
    private function sendOverHttp(string $method, string $path, array $data, array $files): array
    {
        $request = Http::baseUrl((string) config('upmove.api_url'))
            ->acceptJson()
            ->connectTimeout(5)
            ->timeout((int) config('upmove.api_timeout'));

        if (($token = $this->token->get()) !== null) {
            $request = $request->withToken($token);
        }

        try {
            if ($files !== []) {
                foreach ($files as $field => $file) {
                    $request = $request->attach($field, (string) file_get_contents($file['path']), $file['name']);
                }

                $response = $request->post(ltrim($path, '/'), $data);
            } else {
                $response = $request->send($method, ltrim($path, '/'), $method === 'GET' ? ['query' => $data] : ['json' => $data]);
            }
        } catch (ConnectionException) {
            throw new ApiException(self::CONNECTION_MESSAGE, 0);
        }

        $json = $response->json();

        return [$response->status(), is_array($json) ? $json : []];
    }

    /**
     * Handelt het API-verzoek af in dit proces (alleen voor tests). Daarna wordt de
     * toestand van het buitenste verzoek (request, standaard-guard) teruggezet.
     *
     * @param  array<string, mixed>  $data
     * @param  array<string, array{path: string, name: string}>  $files
     * @return array{0: int, 1: array<string, mixed>}
     */
    private function sendInProcess(string $method, string $path, array $data, array $files): array
    {
        $app = app();
        $outerRequest = $app->make('request');
        $auth = $app->make(AuthFactory::class);
        $defaultGuard = $auth->getDefaultDriver();

        $server = ['HTTP_ACCEPT' => 'application/json', 'REMOTE_ADDR' => $outerRequest->ip() ?? '127.0.0.1'];

        if (($token = $this->token->get()) !== null) {
            $server['HTTP_AUTHORIZATION'] = 'Bearer '.$token;
        }

        $uploads = array_map(fn (array $file): UploadedFile => new UploadedFile($file['path'], $file['name'], null, null, true), $files);
        $uri = rtrim((string) parse_url((string) config('upmove.api_url'), PHP_URL_PATH), '/').'/'.ltrim($path, '/');
        $request = Request::create($uri, $method, $data, [], $uploads, $server);

        // De sanctum-guard onthoudt de gebruiker van een vorig verzoek; elk verzoek begint schoon.
        $auth->guard('sanctum')->forgetUser();

        try {
            /** @var Response $response */
            $response = $app->make(HttpKernel::class)->handle($request);
        } finally {
            $app->instance('request', $outerRequest);
            Facade::clearResolvedInstance('request');
            $auth->shouldUse($defaultGuard);
            $auth->guard('sanctum')->forgetUser();
        }

        $json = json_decode((string) $response->getContent(), true);

        return [$response->getStatusCode(), is_array($json) ? $json : []];
    }

    /**
     * @param  array<string, mixed>  $json
     * @return array<string, list<string>>
     */
    private function validationErrors(array $json): array
    {
        $errors = [];

        foreach ((array) ($json['errors'] ?? []) as $field => $messages) {
            $errors[(string) $field] = array_values(array_map('strval', (array) $messages));
        }

        return $errors;
    }
}
