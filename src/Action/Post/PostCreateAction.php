<?php

namespace App\Action\Post;

use App\Domain\Post\Service\PostCreator;
use App\Responder\Responder;
use Fig\Http\Message\StatusCodeInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Action.
 */
final class PostCreateAction
{
    private Responder $responder;

    private PostCreator $postCreator;
    /**
     * The constructor.
     *
     * @param Responder $responder The responder
     * @param PostCreator $postCreator The service
     */
    public function __construct(
        Responder $responder,
        PostCreator $postCreator
    ) {
        $this->responder = $responder;
        $this->postCreator = $postCreator;
    }

    /**
     * Action.
     *
     * @param ServerRequestInterface $request The request
     * @param ResponseInterface $response The response
     *
     * @return ResponseInterface The response
     */
    public function __invoke(
        ServerRequestInterface $request,
        ResponseInterface $response
    ): ResponseInterface {
        $data = (array) $request->getParsedBody();

        $data['created_user_id'] = $request->getAttribute('uId');
        $postId = $this->postCreator->createPost($data);

        return $this->responder
            ->withJson($response, ['post_id' => $postId])
            ->withStatus(StatusCodeInterface::STATUS_CREATED);
    }
}
