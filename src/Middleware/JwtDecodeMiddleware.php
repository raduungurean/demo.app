<?php

namespace App\Middleware;

use Jose\Component\Core\JWK;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Jose\Component\Checker\ClaimCheckerManager;
use Jose\Component\Checker\InvalidClaimException;
use Jose\Component\Signature\JWSVerifier;
use Jose\Component\Signature\Serializer\CompactSerializer;
use Jose\Component\Signature\Serializer\JWSSerializerManager;
use Slim\Psr7\Factory\ResponseFactory;
use Symfony\Component\Cache\Adapter\FilesystemAdapter;
use Symfony\Contracts\Cache\ItemInterface;

// phpcs:disable
// TODO cache the response for one minute or so, see Auth0 php example
final class JwtDecodeMiddleware implements MiddlewareInterface
{
    /**
     * @var ContainerInterface|null
     */
    private $c;

    /**
     * @var string
     */
    private $jwkUrl;
    private $jwsVerifier;
    private $claimCheckerManager;
    private $refresh;
    private $cache;

    public function __construct(
        ?ContainerInterface $c,
        array $options = []
    ) {
        if ($options['refresh']) {
            $this->refresh = $options['refresh'];
        }
        $this->c = $c;
        $this->jwkUrl = $this->c->get('jwksUrl');
        $this->jwsVerifier = $c->get('JWSVerifier');
        $this->claimCheckerManager = $c->get('ClaimCheckerManager');
        $this->cache = $c->get(FilesystemAdapter::class);
    }

    /**
     * @param ServerRequestInterface $request
     * @param RequestHandlerInterface $handler
     * @return ResponseInterface
     */
    public function process(
        ServerRequestInterface $request,
        RequestHandlerInterface $handler
    ): ResponseInterface {
        $idToken = '';
        $authorizationHeaders = $request->getHeader('Authorization');

        foreach ($authorizationHeaders as $authorizationHeader) {
            if (substr($authorizationHeader, 0, 6) === "Bearer") {
                $idTokenArr = explode(" ", $authorizationHeader);
                if (isset($idTokenArr[1])) {
                    $idToken = $idTokenArr[1];
                    break;
                }
            }
        }

        if (empty($idToken)) {
            return (new ResponseFactory)
                ->createResponse(200);
        }

        $jwk = $this->getPublicKey();
        $jws = $this->unSerializeToken($idToken);

        $isVerified = $this->jwsVerifier->verifyWithKey($jws, $jwk, 0);

        if (!$isVerified) {
            return (new ResponseFactory)->createResponse(401);
        }

        $claims = $this->getPayloadClaims($jws);

        $updatedToken = null;

        try {
            $this->claimCheckerManager->check($claims);
        } catch (\Exception $exception) {
            if ($exception instanceof InvalidClaimException) {
                // try to refresh the token
                if (!empty($this->refresh) && $this->refresh === true) {
                    try {
                        $updatedTokenArr = $this->refresh($idToken);

                        $updatedToken = $updatedTokenArr['access_token'];

                        $jws = $this->unSerializeToken($updatedToken);

                        $isVerified = $this->jwsVerifier->verifyWithKey($jws, $jwk, 0);

                        if (!$isVerified) {
                            return (new ResponseFactory)->createResponse(401);
                        }

                        $claims = $this->getPayloadClaims($jws);

                        $this->claimCheckerManager->check($claims);
                    } catch (\Exception $exception) {
                        return (new ResponseFactory)->createResponse(403);
                    }
                }
            }
        }

        $request = $request->withAttribute('uId', $claims['id'])
            ->withAttribute('firstName', $claims['first_name'])
            ->withAttribute('lastName', $claims['last_name']);

        if ($updatedToken) {
            return $handler->handle($request)
                ->withHeader('newToken', $updatedToken);
        }

        return $handler->handle($request);
    }

    private function getPublicKey(): JWK
    {
        $data = $this->cache->get('jwks-cache', function (ItemInterface $item) {
            $item->expiresAfter(60 * 3);
            $content = file_get_contents($this->jwkUrl);
            return json_decode((string)$content);
        });
        return new JWK((array)$data->keys[0]);
    }

    private function getPayloadClaims($jws)
    {
        return json_decode((string)$jws->getPayload(), true);
    }

    private function unSerializeToken(string $idToken): \Jose\Component\Signature\JWS
    {
        $serializerManager = new JWSSerializerManager([
            new CompactSerializer(),
        ]);

        return $serializerManager->unserialize($idToken);
    }

    private function refresh(string $oldJwt)
    {
        $httpClient = new \GuzzleHttp\Client();
        $response = $httpClient->post(
            $this->c->get('authUrl') . 'api/auth/refresh',
            [
                'headers' =>
                    [
                        'Authorization' => "Bearer {$oldJwt}"
                    ]
            ]
        );
        return json_decode($response->getBody(), true);
    }
}
