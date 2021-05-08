<?php

namespace Richard87\ApiRoute\Controller;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\RouterInterface;

class SwaggerController
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

    public function __construct(
        private string $docsEndpoint,
    ){}

    public function __invoke(): Response
    {
        return new Response(sprintf(self::SWAGGER_HTML,$this->docsEndpoint));
    }

}
