<?php

namespace Richard87\ApiRoute\Controller;

use Richard87\ApiRoute\Service\OpenApiGenerator;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class IndexController extends AbstractController
{
    private const SWAGGER_HTML = <<<EOT
        <!DOCTYPE html>
        <html lang="us">
        <head>
            <meta charset="UTF-8">
            <title>API</title>
            <link rel="stylesheet" href="https://unpkg.com/swagger-ui-dist@3/swagger-ui.css"/>
            <script src="https://unpkg.com/swagger-ui-dist@3/swagger-ui-bundle.js" charset="UTF-8"></script>
        </head>
        <body>
        <div id="swagger-ui"></div>
        <script>
            SwaggerUIBundle({
                url: "%s",
                dom_id: '#swagger-ui',
                presets: [
                    SwaggerUIBundle.presets.apis,
                    SwaggerUIBundle.SwaggerUIStandalonePreset
                ],
            })
        </script>
        </body>
        </html>
        EOT;




    #[Route('/docs.json', name: 'api')]
    public function api(OpenApiGenerator $openApiGenerator): Response
    {
        return new JsonResponse($openApiGenerator->getDefinition());
    }

    #[Route('/', name: 'swagger')]
    public function swagger(): Response
    {
        $docsUrl = $this->generateUrl('api', [], UrlGeneratorInterface::ABSOLUTE_URL);
        return new Response(sprintf(self::SWAGGER_HTML,$docsUrl));
    }

}
